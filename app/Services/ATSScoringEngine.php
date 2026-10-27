<?php

namespace App\Services;

use App\Enums\CvStatus;
use App\Models\GeneratedCv;
use Exception;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class ATSScoringEngine
{
    public function __construct(
        protected MasterProfileFormatter $formatter
    ) {}

    /**
     * Run ATS scoring (Call 2 — separate API call from Tailoring) for the
     * user-edited final CV and persist the result on the model.
     *
     * Per JSON_Contract_Tailoring_Scoring.md:
     *  - Input  : tailored_cv_data (compact, no IDs) + job_description
     *  - Output : { score: {match_percentage, matched_keywords, missing_keywords},
     *              feedback: [{type, message}...], meta: {...} }
     *
     * On any failure (HTTP error, non-JSON response, type-validation failure)
     * the record is marked Failed and a debug payload is written into
     * `ats_score` so the user can see WHY and retry — we never throw 500.
     *
     * @param GeneratedCv $generatedCv
     * @return void
     *
     * @throws InvalidArgumentException When called on a record with no
     *         tailored_cv_final (defensive — controller should guard first).
     */
    public function score(GeneratedCv $generatedCv): void
    {
        set_time_limit(180);
        ini_set('max_execution_time', '180');

        // ---- 1. Defensive guard ----
        if (!$generatedCv->is_instant_check && (empty($generatedCv->tailored_cv_final) || empty($generatedCv->tailored_cv_final['tailored_cv']))) {
            throw new InvalidArgumentException(
                'ATSScoringEngine dipanggil tanpa tailored_cv_final. ' .
                'Simpan versi final CV dulu sebelum menjalankan ATS scoring.'
            );
        }

        // ---- 2. Mark as Scoring before the network call ----
        $generatedCv->status = CvStatus::Scoring;
        $generatedCv->save();

        $isRawText = !empty($generatedCv->raw_cv_text);
        if ($isRawText) {
            $tailoredCvData = $generatedCv->raw_cv_text;
        } else {
            if ($generatedCv->is_instant_check) {
                $payload = $this->formatter->toScoringPayloadFromMaster();
            } else {
                $payload = $this->formatter->toScoringPayload($generatedCv->tailored_cv_final);
            }
            $tailoredCvData = $payload['tailored_cv_data'];
        }
        $jobDescription = (string) $generatedCv->job_description;

        $apiKey = config('services.gemini.key');
        $model = $generatedCv->ai_model
            ?? config('services.gemini.scoring_model')
            ?? config('services.gemini.model', 'gemini-3.1-flash-lite');

        // Local/test mock mode
        if (empty($apiKey) || $apiKey === 'mock') {
            $this->handleMockScoring($generatedCv, $model);
            return;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $outputLanguage = $generatedCv->output_language ?? 'id';
        $prompt = $this->buildSystemPrompt($tailoredCvData, $jobDescription, (string) $generatedCv->target_position, $isRawText, $outputLanguage);

        $maxAttempts = 3;
        $attempt = 0;
        $delay = 1;
        $textResult = null;

        // ---- 3. HTTP call with same retry pattern as TailoringEngine ----
        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = Http::timeout(120)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json'
                        ]
                    ]);

                if ($response->status() === 429) {
                    if ($attempt >= $maxAttempts) {
                        $this->markAsFailed($generatedCv, "Rate Limit Exceeded (HTTP 429) saat menghubungi AI Gemini.", [
                            'stage' => 'scoring',
                        ]);
                        return;
                    }
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }

                if ($response->failed()) {
                    $this->markAsFailed($generatedCv, "Gemini API error: HTTP " . $response->status() . " - " . $response->body(), [
                        'stage' => 'scoring',
                    ]);
                    return;
                }

                $responseData = $response->json();
                $textResult = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                break;

            } catch (Exception $e) {
                if ($attempt >= $maxAttempts) {
                    $this->markAsFailed($generatedCv, "Koneksi ke AI Gemini gagal atau timeout (60s): " . $e->getMessage(), [
                        'stage' => 'scoring',
                    ]);
                    return;
                }
                sleep($delay);
                $delay *= 2;
            }
        }

        if (empty($textResult)) {
            $this->markAsFailed($generatedCv, "AI Gemini mengembalikan respon kosong.", ['stage' => 'scoring']);
            return;
        }

        // ---- 4. Response handling & markdown fence stripping ----
        $textResult = trim($textResult);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $textResult, $matches)) {
            $textResult = trim($matches[1]);
        }

        $decoded = json_decode($textResult, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->markAsFailed($generatedCv, "Respon AI Gemini bukan JSON yang valid: " . json_last_error_msg(), [
                'stage' => 'scoring',
                'raw_response' => substr($textResult, 0, 2000),
            ]);
            return;
        }

        // ---- 5. Strict type validation on the decoded output ----
        $validation = $this->validateOutput($decoded);
        if (!$validation['valid']) {
            $this->markAsFailed($generatedCv, "Output AI tidak lolos validasi tipe: " . implode('; ', $validation['errors']), [
                'stage' => 'scoring',
                'ai_output' => $decoded,
            ]);
            return;
        }

        // ---- 6. Inject / preserve meta ----
        if (!isset($decoded['meta']) || !is_array($decoded['meta'])) {
            $decoded['meta'] = [];
        }
        $decoded['meta']['model_used'] = $model;
        $decoded['meta']['generated_at'] = now()->toIso8601String();

        // ---- 7. Persist success ----
        $generatedCv->ats_score = $decoded;
        $generatedCv->status = CvStatus::Completed;
        $generatedCv->save();
    }

    /**
     * Build system prompt for AI ATS Scoring.
     */
    protected function buildSystemPrompt(mixed $cvData, string $jobDescription, string $appliedPosition = null, bool $isRawText = false, string $outputLanguage = 'id'): string
    {
        if ($isRawText) {
            $cvContent = "RAW UNSTRUCTURED CV TEXT:\n" . (string) $cvData;
        } else {
            $cvContent = "CV DATA (JSON format):\n" . json_encode($cvData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $positionText = $appliedPosition ? "- TARGET APPLIED POSITION: {$appliedPosition}" : '';

        if ($outputLanguage === 'en') {
            $suitabilityPrompt = 'Evaluate the qualitative suitability of the candidate\'s CV specifically for the Applied Position (if provided). Rate the suitability as "Kuat" (strong), "Cukup" (average), or "Kurang" (poor). Explain the reason in English and provide specific improvement suggestions.';
            $langInstruction = 'Output language for all free-text fields (`feedback.message`, `suitability.reason`, `suitability.suggestions`) MUST be in English.';
        } else {
            $suitabilityPrompt = 'Evaluate the qualitative suitability of the candidate\'s CV specifically for the Applied Position (if provided). Rate the suitability as "Kuat" (strong), "Cukup" (average), or "Kurang" (poor). Explain the reason in Bahasa Indonesia and provide specific improvement suggestions.';
            $langInstruction = 'Output language for all free-text fields (`feedback.message`, `suitability.reason`, `suitability.suggestions`) MUST be in Bahasa Indonesia.';
        }

        return <<<PROMPT
You are an expert ATS (Applicant Tracking System) Scoring Engine. Your job is to evaluate how well a candidate's CV matches a target Job Description and Applied Position, producing an honest, actionable assessment.

INPUTS:
- TARGET JOB DESCRIPTION: a free-text posting the candidate applied to.
{$positionText}
- CV DATA: the candidate's CV content, which may be raw text or structured JSON.

YOUR TASK:
1. Extract the most important hard-skill keywords, tools, and domain terms from the Job Description.
2. Compare them against the CV content to calculate matching keywords and percentage.
3. {$suitabilityPrompt}
4. Produce a JSON assessment following the EXACT schema below.

STRICT OUTPUT RULES:
- Output ONLY a single valid JSON object. DO NOT include markdown code fences (no ```json), DO NOT include any commentary, greeting, or trailing text.
- All "type" values in feedback MUST be one of: "missing_keyword", "weak_bullet", or "format_suggestion".
- `score.match_percentage` MUST be an integer between 0 and 100 (no decimals, no out-of-range values).
- Be honest: if the CV is missing many required keywords, give a low score. Do not inflate.
- {$langInstruction} (Note: `matched_keywords` and `missing_keywords` MUST still reflect the exact spelling and casing used in the Job Description, as they represent literal keyword matches).

TARGET JOB DESCRIPTION:
{$jobDescription}

CV CONTENT:
{$cvContent}

REQUIRED OUTPUT JSON SCHEMA:
{
  "score": {
    "match_percentage": 0,
    "matched_keywords": ["string"],
    "missing_keywords": ["string"]
  },
  "suitability": {
    "rating": "Kuat | Cukup | Kurang",
    "reason": "Penjelasan detail kesesuaian kandidat dengan posisi yang dilamar dalam Bahasa Indonesia.",
    "suggestions": ["Saran 1...", "Saran 2..."]
  },
  "feedback": [
    {
      "type": "missing_keyword",
      "message": "Penjelasan singkat dalam Bahasa Indonesia"
    }
  ],
  "meta": {
    "model_used": "gemini-model-name",
    "generated_at": "ISO8601 timestamp"
  }
}
PROMPT;
    }

    /**
     * Strictly validate the type shape of the AI's output.
     *
     * @return array{valid: bool, errors: string[]}
     */
    protected function validateOutput(mixed $decoded): array
    {
        $errors = [];

        if (!is_array($decoded)) {
            return ['valid' => false, 'errors' => ['Root output is not a JSON object.']];
        }

        // score.match_percentage
        $mp = $decoded['score']['match_percentage'] ?? null;
        if (!is_int($mp) && !(is_float($mp) && floor($mp) == $mp)) {
            $errors[] = 'score.match_percentage must be an integer 0-100';
        } elseif ($mp < 0 || $mp > 100) {
            $errors[] = 'score.match_percentage out of range (0-100)';
        }

        // score.matched_keywords & missing_keywords
        if (!isset($decoded['score']['matched_keywords']) || !is_array($decoded['score']['matched_keywords'])) {
            $errors[] = 'score.matched_keywords must be an array';
        } else {
            foreach ($decoded['score']['matched_keywords'] as $k) {
                if (!is_string($k)) {
                    $errors[] = 'score.matched_keywords must contain only strings';
                    break;
                }
            }
        }

        if (!isset($decoded['score']['missing_keywords']) || !is_array($decoded['score']['missing_keywords'])) {
            $errors[] = 'score.missing_keywords must be an array';
        } else {
            foreach ($decoded['score']['missing_keywords'] as $k) {
                if (!is_string($k)) {
                    $errors[] = 'score.missing_keywords must contain only strings';
                    break;
                }
            }
        }

        // feedback
        if (!isset($decoded['feedback']) || !is_array($decoded['feedback'])) {
            $errors[] = 'feedback must be an array';
        } else {
            $allowedTypes = ['missing_keyword', 'weak_bullet', 'format_suggestion'];
            foreach ($decoded['feedback'] as $i => $f) {
                if (!is_array($f) || !isset($f['type'], $f['message'])) {
                    $errors[] = "feedback[$i] must be an object with 'type' and 'message'";
                    continue;
                }
                if (!is_string($f['type']) || !in_array($f['type'], $allowedTypes, true)) {
                    $errors[] = "feedback[$i].type must be one of: " . implode(', ', $allowedTypes);
                }
                if (!is_string($f['message'])) {
                    $errors[] = "feedback[$i].message must be a string";
                }
            }
        }

        // suitability (optional, but validated if present)
        if (isset($decoded['suitability'])) {
            $suit = $decoded['suitability'];
            if (!is_array($suit)) {
                $errors[] = 'suitability must be an object';
            } else {
                if (!isset($suit['rating']) || !is_string($suit['rating']) || !in_array($suit['rating'], ['Kuat', 'Cukup', 'Kurang'], true)) {
                    $errors[] = 'suitability.rating must be one of: Kuat, Cukup, Kurang';
                }
                if (!isset($suit['reason']) || !is_string($suit['reason'])) {
                    $errors[] = 'suitability.reason must be a string';
                }
                if (isset($suit['suggestions']) && !is_array($suit['suggestions'])) {
                    $errors[] = 'suitability.suggestions must be an array';
                } elseif (isset($suit['suggestions']) && is_array($suit['suggestions'])) {
                    foreach ($suit['suggestions'] as $sug) {
                        if (!is_string($sug)) {
                            $errors[] = 'suitability.suggestions must contain only strings';
                            break;
                        }
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Fallback mock scoring for local environment without API keys.
     */
    protected function handleMockScoring(GeneratedCv $generatedCv, string $model): void
    {
        sleep(1);

        $mockOutput = [
            'score' => [
                'match_percentage' => 72,
                'matched_keywords' => ['Laravel', 'PHP', 'PostgreSQL', 'Backend Development'],
                'missing_keywords' => ['Kubernetes', 'GraphQL', 'AWS Lambda'],
            ],
            'suitability' => [
                'rating' => 'Cukup',
                'reason' => '[Mock] Kandidat memiliki fondasi kuat di PHP dan Laravel, namun terdapat ketidaksesuaian kompetensi cloud native (Docker/Kubernetes) yang diminta untuk posisi ' . ($generatedCv->target_position ?? 'target') . '.',
                'suggestions' => [
                    '[Mock] Tambahkan detail penggunaan cloud providers di salah satu proyek.',
                    '[Mock] Sebutkan pengalaman integrasi pihak ketiga/API di bullet points Anda.'
                ]
            ],
            'feedback' => [
                [
                    'type' => 'missing_keyword',
                    'message' => '[Mock] Tambahkan pengalaman atau proyek yang menyebut Kubernetes / container orchestration untuk memenuhi kata kunci JD.',
                ],
                [
                    'type' => 'weak_bullet',
                    'message' => '[Mock] Bullet "Memimpin pengembangan microservice" terlalu umum — tambahkan metrik dampak (pengguna, latency, QPS).',
                ],
                [
                    'type' => 'format_suggestion',
                    'message' => '[Mock] Pertimbangkan memecah section Skills ke dalam subkategori (Backend / Database / DevOps) agar ATS lebih mudah mem-parsing.',
                ],
            ],
            'meta' => [
                'model_used' => 'mock-local-engine (' . $model . ')',
                'generated_at' => now()->toIso8601String(),
            ],
        ];

        $generatedCv->ats_score = $mockOutput;
        $generatedCv->status = CvStatus::Completed;
        $generatedCv->save();
    }

    /**
     * Mark GeneratedCv record as Failed and write a debug payload into
     * `ats_score` (overwriting any prior value) so the user can see WHY.
     * Never throws 500.
     */
    protected function markAsFailed(GeneratedCv $generatedCv, string $errorMessage, array $extraContext = []): void
    {
        $generatedCv->status = CvStatus::Failed;
        $generatedCv->ats_score = array_merge([
            'error' => $errorMessage,
            'failed_at' => now()->toIso8601String(),
        ], $extraContext);
        $generatedCv->save();
    }
}
