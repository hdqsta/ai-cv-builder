<?php

namespace App\Services;

use App\Enums\CvStatus;
use App\Models\GeneratedCv;
use Exception;
use Illuminate\Support\Facades\Http;

class TailoringEngine
{
    public function __construct(
        protected MasterProfileFormatter $formatter
    ) {}

    /**
     * Generate a tailored CV using Gemini AI and validate hard constraints.
     *
     * @param GeneratedCv $generatedCv
     * @return void
     */
    public function generate(GeneratedCv $generatedCv): void
    {
        set_time_limit(180);
        ini_set('max_execution_time', '180');

        // 1. Mark status as Tailoring before making API request
        $generatedCv->status = CvStatus::Tailoring;
        $generatedCv->save();

        $payload = $this->formatter->toTailoringPayload();
        $masterProfile = $payload['master_profile'];
        $jobDescription = $generatedCv->job_description;

        $apiKey = config('services.gemini.key');
        $model = $generatedCv->ai_model
            ?? config('services.gemini.tailoring_model')
            ?? config('services.gemini.model', 'gemini-3.1-flash-lite');

        // Handle local/test mock mode when credentials are missing or mock
        if (empty($apiKey) || $apiKey === 'mock') {
            $this->handleMockGeneration($generatedCv, $masterProfile);
            return;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $outputLanguage = $generatedCv->output_language ?? 'id';
        $customInstructions = trim((string) ($generatedCv->custom_instructions ?? ''));
        $prompt = $this->buildSystemPrompt($masterProfile, $jobDescription, $outputLanguage, $customInstructions);

        $maxAttempts = 3;
        $attempt = 0;
        $delay = 1;
        $textResult = null;

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
                        $this->markAsFailed($generatedCv, "Rate Limit Exceeded (HTTP 429) saat menghubungi AI Gemini.");
                        return;
                    }
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }

                if ($response->failed()) {
                    $this->markAsFailed($generatedCv, "Gemini API error: HTTP " . $response->status() . " - " . $response->body());
                    return;
                }

                $responseData = $response->json();
                $textResult = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                break;

            } catch (Exception $e) {
                if ($attempt >= $maxAttempts) {
                    $this->markAsFailed($generatedCv, "Koneksi ke AI Gemini gagal atau timeout (60s): " . $e->getMessage());
                    return;
                }
                sleep($delay);
                $delay *= 2;
            }
        }

        if (empty($textResult)) {
            $this->markAsFailed($generatedCv, "AI Gemini mengembalikan respon kosong.");
            return;
        }

        // 2. Response Handling & Markdown Code Block Stripping
        $textResult = trim($textResult);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $textResult, $matches)) {
            $textResult = trim($matches[1]);
        }

        $decoded = json_decode($textResult, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->markAsFailed($generatedCv, "Respon AI Gemini bukan JSON yang valid: " . json_last_error_msg(), [
                'raw_response' => substr($textResult, 0, 2000),
            ]);
            return;
        }

        // 3. Hard Constraint ID Validation
        $validationResult = $this->validateHardConstraints($masterProfile, $decoded);
        if (!$validationResult['valid']) {
            $this->markAsFailed($generatedCv, "Terdeteksi ID Fabrikasi/Halusinasi dari AI: " . implode(', ', $validationResult['errors']), [
                'ai_output' => $decoded,
                'invalid_ids' => $validationResult['invalid_ids'],
            ]);
            return;
        }

        // 3.5. Deterministic Faithfulness Validation (checks claims, ungrounded numbers, unlisted skills)
        $warnings = $this->checkFaithfulness($masterProfile, $decoded, $outputLanguage);
        $decoded['warnings'] = $warnings;

        // Extract headline if provided and update target_position if currently blank or generic
        if (!empty($decoded['headline']) && (empty($generatedCv->target_position) || $generatedCv->target_position === 'IT Generalist')) {
            $generatedCv->target_position = $decoded['headline'];
        }

        // Add metadata
        if (!isset($decoded['meta'])) {
            $decoded['meta'] = [
                'model_used' => $model,
                'generated_at' => now()->toIso8601String(),
            ];
        }
        $decoded['meta']['custom_instructions'] = $customInstructions !== '' ? $customInstructions : null;

        // 4. Save successful tailored CV raw output
        $generatedCv->tailored_cv_raw = $decoded;
        $generatedCv->status = CvStatus::Review;
        $generatedCv->save();
    }

    /**
     * Tailor (translate + reframe) specific Master Profile projects for one session.
     *
     * Used when the user reselects projects in the review form or asks to re-tailor the
     * existing ones, so added projects follow the CV output language and target JD
     * instead of being pasted verbatim from the Master Profile.
     *
     * @param  array<int, array<string, mixed>>  $masterProjects  Master profile project payloads.
     * @return array<int, array<string, mixed>>  Tailored entries; empty array when the AI call fails.
     */
    public function tailorProjects(GeneratedCv $generatedCv, array $masterProjects): array
    {
        $masterProjects = array_values(array_filter($masterProjects));

        if ($masterProjects === []) {
            return [];
        }

        $outputLanguage = $generatedCv->output_language ?? 'id';
        $apiKey = config('services.gemini.key');

        if (empty($apiKey) || $apiKey === 'mock') {
            return $this->mockTailoredProjects($masterProjects, $outputLanguage);
        }

        $model = $generatedCv->ai_model
            ?? config('services.gemini.tailoring_model')
            ?? config('services.gemini.model', 'gemini-3.1-flash-lite');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $prompt = $this->buildProjectTailoringPrompt($masterProjects, (string) $generatedCv->job_description, $outputLanguage);

        try {
            $response = Http::timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->failed()) {
                return [];
            }

            $text = trim((string) ($response->json()['candidates'][0]['content']['parts'][0]['text'] ?? ''));

            if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $text, $matches)) {
                $text = trim($matches[1]);
            }

            $decoded = json_decode($text, true);

            if (!is_array($decoded['projects'] ?? null)) {
                return [];
            }

            return $this->normalizeTailoredProjects($masterProjects, $decoded['projects']);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Re-tailor every Key Project of a session from the Master Profile (in-place).
     *
     * Returns false when there is nothing to do or the AI call fails, so callers can
     * surface an actionable message instead of silently keeping the old wording.
     */
    public function retailorSessionProjects(GeneratedCv $generatedCv): bool
    {
        $tailored = $generatedCv->tailored_cv_final['tailored_cv']
            ?? $generatedCv->tailored_cv_raw['tailored_cv']
            ?? [];

        $projects = $tailored['projects'] ?? [];

        if ($projects === []) {
            return false;
        }

        $sourceIds = array_values(array_unique(array_filter(array_map(
            fn ($project) => (string) ($project['source_id'] ?? ''),
            $projects
        ))));

        if ($sourceIds === []) {
            return false;
        }

        $masterProjects = collect($this->formatter->toTailoringPayload()['master_profile']['projects'] ?? [])
            ->filter(fn ($project) => in_array((string) ($project['id'] ?? ''), $sourceIds, true))
            ->values()
            ->all();

        if ($masterProjects === []) {
            return false;
        }

        $retailored = $this->tailorProjects($generatedCv, $masterProjects);

        if ($retailored === []) {
            return false;
        }

        $bySourceId = collect($retailored)->keyBy(fn ($project) => (string) $project['source_id']);

        $merged = [];
        foreach ($projects as $project) {
            $merged[] = $bySourceId->get((string) ($project['source_id'] ?? ''), $project);
        }

        $mergedIds = array_map(fn ($project) => (string) ($project['source_id'] ?? ''), $merged);
        foreach ($retailored as $project) {
            if (!in_array((string) $project['source_id'], $mergedIds, true)) {
                $merged[] = $project;
            }
        }

        $final = $generatedCv->tailored_cv_final;

        if (!empty($final['tailored_cv'])) {
            $final['tailored_cv']['projects'] = $merged;
        } else {
            $final = ['tailored_cv' => array_merge($tailored, ['projects' => $merged])];
        }

        $generatedCv->tailored_cv_final = $final;
        $generatedCv->save();

        $this->refreshWarnings($generatedCv);

        return true;
    }

    /**
     * Recompute the deterministic QA warnings for a session from its current final content.
     *
     * @return array<int, string>
     */
    public function refreshWarnings(GeneratedCv $generatedCv): array
    {
        $tailored = $generatedCv->tailored_cv_final['tailored_cv']
            ?? $generatedCv->tailored_cv_raw['tailored_cv']
            ?? [];

        $raw = $generatedCv->tailored_cv_raw ?? [];

        if ($raw === [] || $tailored === []) {
            return [];
        }

        $masterProfile = $this->formatter->toTailoringPayload()['master_profile'];
        $raw['warnings'] = $this->checkFaithfulness(
            $masterProfile,
            ['tailored_cv' => $tailored],
            $generatedCv->output_language ?? 'id'
        );

        $generatedCv->tailored_cv_raw = $raw;
        $generatedCv->save();

        return $raw['warnings'];
    }

    /**
     * Prompt used to re-tailor a subset of projects (keep ids, translate, align to the JD).
     */
    protected function buildProjectTailoringPrompt(array $masterProjects, string $jobDescription, string $outputLanguage = 'id'): string
    {
        $projectsJson = json_encode($masterProjects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $languageInstruction = $outputLanguage === 'en'
            ? 'English. EVERY human-readable value (project name wording, bullet content) MUST be in English.'
            : 'Bahasa Indonesia. Setiap kalimat pada bullet harus berbahasa Indonesia (istilah teknis standar boleh tetap Inggris).';

        return <<<PROMPT
You are an expert ATS CV Tailoring Engine. Re-write the Master Profile projects below so they fit the target Job Description and the required output language.

OUTPUT LANGUAGE: {$languageInstruction}

STRICT RULES:
1. `source_id` and every `source_bullet_id` MUST be copied EXACTLY from the input. Never invent ids or new entities.
2. Stay 100% faithful: do NOT invent numbers, metrics, tools, or achievements that are not present in the source project.
3. Translate/reframe every human-readable string into the output language. A project name containing Indonesian descriptive words MUST be translated (e.g. "SIMONA (Sistem Monitoring Aging Aktivasi)" -> "SIMONA (Service Activation Aging Monitoring System)"). Brand/product names (SIMONA, Firefly III, Telegram, Laravel, MySQL, Gemini) stay as-is.
4. NO generic filler ("proven track record", "highly motivated", "strong passion", "eager to contribute", "rapidly mastering"). Start each bullet with an action verb and keep it to one line.
5. Keep at most 4 bullets per project (drop the weakest, keep the most technical/verifiable ones).
6. `tech_stack` may only contain technologies already listed for that project's `tech_stack` in the input.
7. Output ONLY valid JSON, no markdown fences, no commentary.

TARGET JOB DESCRIPTION:
{$jobDescription}

PROJECTS TO TAILOR (JSON):
{$projectsJson}

OUTPUT JSON FORMAT:
{"projects":[{"source_id":"...","name":"...","tech_stack":["..."],"bullet_points":[{"source_bullet_id":"...","content":"..."}]}]}
PROMPT;
    }

    /**
     * Keep only AI entries whose ids exist in the submitted projects, and never lose bullets.
     */
    protected function normalizeTailoredProjects(array $masterProjects, array $tailoredProjects): array
    {
        $masterById = [];
        foreach ($masterProjects as $project) {
            $masterById[(string) ($project['id'] ?? '')] = $project;
        }

        $normalized = [];

        foreach ($tailoredProjects as $project) {
            if (!is_array($project)) {
                continue;
            }

            $sourceId = (string) ($project['source_id'] ?? '');

            if (!isset($masterById[$sourceId])) {
                continue;
            }

            $master = $masterById[$sourceId];
            $validBulletIds = array_map('strval', array_column($master['bullet_points'] ?? [], 'id'));

            $bullets = [];
            foreach ($project['bullet_points'] ?? [] as $bullet) {
                $bulletId = (string) ($bullet['source_bullet_id'] ?? '');
                $content = trim((string) ($bullet['content'] ?? ''));

                if ($content === '' || !in_array($bulletId, $validBulletIds, true)) {
                    continue;
                }

                $bullets[] = [
                    'source_bullet_id' => $bulletId,
                    'content' => $content,
                ];
            }

            if ($bullets === []) {
                $bullets = $this->verbatimProjectBullets($master);
            }

            $normalized[$sourceId] = [
                'source_id' => $sourceId,
                'name' => trim((string) ($project['name'] ?? '')) ?: (string) ($master['name'] ?? ''),
                'tech_stack' => array_values(array_filter(array_map(
                    'strval',
                    (array) ($project['tech_stack'] ?? ($master['tech_stack'] ?? []))
                ))),
                'bullet_points' => array_slice($bullets, 0, 4),
            ];
        }

        return array_values($normalized);
    }

    /**
     * Deterministic stand-in for local/test mode (no API key).
     */
    protected function mockTailoredProjects(array $masterProjects, string $outputLanguage): array
    {
        return array_map(function (array $project) use ($outputLanguage) {
            return [
                'source_id' => (string) ($project['id'] ?? ''),
                'name' => (string) ($project['name'] ?? ''),
                'tech_stack' => array_values((array) ($project['tech_stack'] ?? [])),
                'bullet_points' => array_map(fn (array $bullet) => [
                    'source_bullet_id' => (string) ($bullet['id'] ?? ''),
                    'content' => '[Tailored ' . $outputLanguage . '] ' . ($bullet['content'] ?? ''),
                ], $project['bullet_points'] ?? []),
            ];
        }, $masterProjects);
    }

    /**
     * Map master project payloads to the tailored shape without AI (last-resort fallback).
     */
    protected function verbatimProjectBullets(array $masterProject): array
    {
        return array_map(fn (array $bullet) => [
            'source_bullet_id' => (string) ($bullet['id'] ?? ''),
            'content' => (string) ($bullet['content'] ?? ''),
        ], $masterProject['bullet_points'] ?? []);
    }

    /**
     * Build system prompt for AI Tailoring.
     *
     * @param array  $masterProfile       Master profile payload (with source ids).
     * @param string $jobDescription      Raw target Job Description text.
     * @param string $outputLanguage      'id' or 'en'.
     * @param string $customInstructions  Optional extra instruction from the user ("prompt tambahan").
     */
    protected function buildSystemPrompt(
        array $masterProfile,
        string $jobDescription,
        string $outputLanguage = 'id',
        string $customInstructions = ''
    ): string {
        $masterProfileJson = json_encode($masterProfile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($outputLanguage === 'en') {
            $langInstruction = "All human-readable text fields in the output (summary, bullet_points content) MUST be written entirely in English, regardless of the language used in the Job Description or Master Profile. Any Indonesian terms/jargon MUST be translated to appropriate professional English terms.";
        } else {
            $langInstruction = "All human-readable text fields in the output (summary, bullet_points content) MUST be written in Bahasa Indonesia, regardless of the language used in the Job Description title, headings, or any English terms/jargon found within it. Job titles or terms that are standard industry English terms MAY be kept in English if commonly used as-is in Indonesian professional context, but all sentences, descriptions, and summaries MUST be in Bahasa Indonesia.";
        }

        $todayDate = now()->toDateString();
        $firstEdu = $masterProfile['education'][0] ?? null;
        $eduNotes = !empty($firstEdu['notes']) ? $firstEdu['notes'] : '';
        $degree = !empty($firstEdu['degree']) ? $firstEdu['degree'] : 'D3 Manajemen Informatika';
        $eduStatus = "Lulusan {$degree}" . ($eduNotes ? " ({$eduNotes})" : " (Sudah Lulus / Berijazah Resmi)");

        $customInstructions = trim($customInstructions);
        if ($customInstructions === '') {
            $customInstructionsSection = "(none — the user did not add any extra instruction for this round).";
        } else {
            $customInstructionsSection = <<<INSTRUCTION
The user explicitly asked for this extra adjustment in this tailoring round (treat it as a wording/style request):
"""
{$customInstructions}
"""

HOW TO APPLY IT:
- Apply it by REWRITING the wording of `summary`, `headline` (only if genuinely relevant), the affected `bullet_points`, and/or `suggestions`, in the output language defined above.
- Integrate it naturally and professionally. NEVER paste the raw instruction text verbatim as a sentence inside the CV.
- Requests about attitude, motivation, or willingness (e.g. eagerness to learn new things, adaptability, growth mindset, curiosity) are ALLOWED: express them as an honest self-statement in `summary` (optionally echoed in `suggestions`). This is the candidate's own statement, not a fabricated credential.
- Requests that would require a hard skill, tool, certification, job title, company, or number that DOES NOT exist in the Master Profile are FORBIDDEN to fabricate. In that case: state the honest aspiration instead (e.g. "tertarik mempelajari X") in `summary`, keep it OUT of `experiences` / `education` / `projects` / `skills_highlighted`, and add a note in `suggestions`.
- Every `source_id` / `source_bullet_id` must stay exactly as in the Master Profile, and job titles must stay unchanged.
INSTRUCTION;
        }

        return <<<PROMPT
You are an expert ATS CV Tailoring Engine. Your job is to highlight and optimize a candidate's Master Profile to align with a target Job Description WITHOUT ANY FABRICATION.

CONTEXT:
- Today's date: {$todayDate}
- Candidate Education Status: {$eduStatus} (Candidate has officially graduated and already holds the official diploma/ijazah. Refer to them as "Lulusan {$degree}" or "Fresh Graduate". DO NOT use "mahasiswa", "semester akhir", or "SKL dalam proses").

STRICT HARD CONSTRAINTS (CRITICAL - DO NOT VIOLATE):
1. NO JOB TITLE FABRICATION (SACRED FIELD):
   - In `experiences.position`, you MUST EXACTLY PRESERVE the candidate's actual official position title and division from `master_profile.experiences`.
   - STRICTLY FORBIDDEN: NEVER change, alter, reframe, or rename job titles or division names! (e.g. if Master Profile says "Staf Magang – Divisi Pemasaran dan Penjualan", you MUST output "Staf Magang – Divisi Pemasaran dan Penjualan". NEVER rename it to "Staf IT", "IT Support", or anything else).
2. NO HALLUCINATION / NO FABRICATED CONTEXT IN WORK EXPERIENCES:
   - In `experiences.bullet_points`, you MUST remain 100% faithful to the real tasks performed.
   - STRICTLY FORBIDDEN: DO NOT inject fake IT duties, fake software systems, or fake technical support responsibilities into non-IT experiences! (e.g. if the task was verifying physical/administrative BAST/BAP documents or sales travel expenses in Divisi Pemasaran dan Penjualan, DO NOT claim they worked on "sistem informasi perusahaan", "troubleshooting software", or "IT support").
   - HOW TO TAILOR HONESTLY: Highlight genuine, transferable operational strengths such as: data accuracy, audit compliance, Excel data verification, operational cost reconciliation, and cross-team communication.
3. UNMATCHED JD REQUIREMENTS MUST GO TO "gaps":
   - If the Job Description demands requirements, skills, or responsibilities with NO direct evidence in the Master Profile (e.g. hardware troubleshooting, network support, system maintenance), DO NOT invent them or force them into work experiences or skills!
   - Put them explicitly into the top-level `"gaps"` array as clear, constructive gap notes (e.g. "IT support hardware & troubleshooting: Tidak ada bukti/riwayat di Master Profile").
4. NEVER STRENGTHEN CLAIMS / NO OVERCLAIMING:
   - DO NOT turn "mengurangi" (reduce) into "mengeliminasi" (eliminate). Keep claims realistic, honest, and defensible in job interviews.
   - DO NOT add level words like "tingkat lanjut", "advanced", "expert", "ahli" unless explicitly present in the Master Profile for that skill.
5. NO INVENTED NUMBERS OR METRICS:
   - DO NOT invent numbers, percentages, user counts, or durations. If a bullet point has no metric, keep it qualitative and add a suggestion to `"suggestions"` (e.g. "Tambahkan jumlah user atau baris data pada proyek SIMONA jika ada").
6. TECHNICAL/IT SKILLS BELONG IN PROJECTS & SKILLS:
   - The candidate's technical IT, programming, and system development strengths must be showcased in the `projects` section (e.g. SIMONA) and `skills_highlighted`, as well as connecting them truthfully in the executive `summary`.
   - For SIMONA project: You may explicitly note that it was built for operational needs at PT PLN Icon Plus (SBU Regional Sumbagsel) to monitor service activation and reduce data discrepancies.
7. TARGET HEADLINE:
   - In `"headline"`, provide a clean, professional target job title matching the Job Description (e.g. "Staf IT / IT Generalist") without any company name.
   - Only list skills in `skills_highlighted` that exist in `master_profile.skills`.
8. SOURCE ID MANDATE:
   - For every entry in `experiences`, `source_id` MUST EXACTLY MATCH a valid `id` from `master_profile.experiences`.
   - For every bullet point in `experiences.bullet_points`, `source_bullet_id` MUST EXACTLY MATCH a valid `id` from `master_profile.experiences.bullet_points`.
   - For every entry in `projects`, `source_id` MUST EXACTLY MATCH a valid `id` from `master_profile.projects`.
   - For every bullet point in `projects.bullet_points`, `source_bullet_id` MUST EXACTLY MATCH a valid `id` from `master_profile.projects.bullet_points`.
   - For every entry in `education`, `source_id` MUST EXACTLY MATCH a valid `id` from `master_profile.education`.
9. OUTPUT FORMAT: Output ONLY valid JSON matching the target JSON schema below. DO NOT include markdown code block formatting (like ```json), commentary, or extra text.
10. OUTPUT LANGUAGE: {$langInstruction}
11. SUMMARY QUALITY (RECRUITER SKIM TEST — CRITICAL):
   - The `summary` MUST open with the candidate's real education and concrete stack/evidence, e.g. "Informatics graduate (D3, GPA 3.63) with hands-on experience building end-to-end web applications using Laravel, MySQL, and REST API integration, including payment gateway webhooks and LLM integration (Gemini API). Experienced in system design and documentation (UML, ERD), with a strong focus on data validation and accuracy."
   - STRICTLY FORBIDDEN generic filler (no evidence = no sentence): "highly motivated", "strong passion" / "passionate", "proven track record", "proven ability", "high aptitude", "rapidly mastering", "adapting to complex", "dynamic team", "hard-working", "quick learner", "fast learner", "self-motivated", "team player", "goal-getter", "dedicated professional", "detail-oriented" as a standalone claim, "excellent communication skills", "strong problem-solving skills", "cutting-edge", "seeking opportunities", "eager to contribute". Every sentence in `summary` MUST carry a verifiable fact from the Master Profile.
   - NEVER claim a technology, framework, or platform that is not present in `master_profile.skills` / `master_profile.projects`. Do NOT write "modern JavaScript frameworks", "React", "Next.js", "Vue", "Node.js", "TypeScript", "Kubernetes", "AWS", etc. unless that exact capability is listed in the Master Profile.
   - Keep `summary` to 3-4 sentences maximum.
   - Attitude/motivation sentences are allowed ONLY when the user's additional instruction explicitly asks for them (e.g. desire to learn new things). Even then: one short concrete clause, never the opening sentence, and never as a substitute for evidence.
   - NEVER add a learning/training claim (e.g. "currently learning Python and PostgreSQL") unless it is explicitly recorded in the Master Profile.
12. SKILLS ORDERING:
   - Order `skills_highlighted` GROUPS and the items inside each group by relevance to the target JD — the most job-critical first (for a development role: REST API, MySQL, Git, Docker, Laravel/PHP; frontend afterwards; general administration/soft skills last or omitted).
   - Only list skills that exist in `master_profile.skills`. NEVER rename, upgrade, or add a skill just because the JD mentions it.
13. BULLET BUDGET PER ROLE:
   - Projects are the candidate's strongest signal for technical roles: keep their bullets specific and technical, max 4 each.
   - A non-target-role experience (e.g. an internship in sales/administration): keep ONLY the 2-3 most relevant, evidence-rich bullets. NEVER pad with generic lines such as "demonstrated adaptability" or "supported cross-functional requirements".
   - Prefer grounded numbers that already exist in the Master Profile (e.g. "dozens of documents per day", "hundreds of BAA closing documents") over adjectives like "rigorous" or "precise".
14. ONE LANGUAGE ONLY (ALL FIELDS):
   - When the output language is English, EVERY human-readable value MUST be written in English — including `skills_highlighted` skill names AND category names, each project `name`, and the education `degree`. Never leave a single Indonesian phrase in an English CV.
   - Translate these exactly: "Rekonsiliasi & Validasi Data" -> "Data Reconciliation & Validation"; "Database Design (ERD & Normalisasi)" -> "Database Design (ERD & Normalization)"; "Koordinasi Lintas Tim" -> "Cross-Team Coordination"; "Ketelitian & Akurasi Data" -> "Data Accuracy & Attention to Detail"; "Adaptif dalam Dynamic Project Environment" -> "Adaptability in Dynamic Project Environments"; "Sistem Monitoring Aging Aktivasi" -> "Service Activation Aging Monitoring System"; "D3 Manajemen Informatika" -> "D3 Informatics Management"; "IPK" -> "GPA".
   - Keep proper nouns unchanged (SIMONA, PT PLN Icon Plus, Politeknik Negeri Sriwijaya, Firefly III, Laravel, MySQL, etc.).
   - In Bahasa Indonesia mode apply the same rule in reverse: no English sentences, but keep standard technical terms (REST API, webhook, Laravel) as-is.

USER ADDITIONAL INSTRUCTIONS (apply after the hard constraints above — never at the cost of them):
{$customInstructionsSection}

TARGET JOB DESCRIPTION:
{$jobDescription}

MASTER PROFILE DATA:
{$masterProfileJson}

TARGET OUTPUT JSON SCHEMA:
{
  "headline": "Target Job Title from JD (e.g. Staf IT / IT Generalist)",
  "gaps": [
    "List of requirements from JD with no direct evidence in Master Profile (e.g. IT support hardware troubleshooting)"
  ],
  "suggestions": [
    "Constructive suggestions for candidate (e.g. add impact metrics to SIMONA if available)"
  ],
  "tailored_cv": {
    "summary": "Professional executive summary connecting candidate education, work experience, and technical projects to the target JD without inventing fake credentials",
    "experiences": [
      {
        "source_id": "exact experience id from master_profile",
        "company": "Company Name from master_profile",
        "position": "EXACT position title from master_profile.experiences (DO NOT ALTER)",
        "start_date": "YYYY-MM-DD",
        "end_date": "YYYY-MM-DD or null",
        "bullet_points": [
          {
            "source_bullet_id": "exact bullet id from master_profile",
            "content": "Professional, truthful action-oriented bullet point emphasizing transferable skills (accuracy, reconciliation, audit) without inventing fake IT tasks"
          }
        ]
      }
    ],
    "education": [
      {
        "source_id": "exact education id from master_profile",
        "institution": "University/Institution Name",
        "degree": "Degree",
        "start_date": "YYYY-MM-DD",
        "end_date": "YYYY-MM-DD or null",
        "notes": "Academic notes / GPA from master_profile (e.g. IPK 3.63 / 4.00)"
      }
    ],
    "skills_highlighted": [
      {
        "category": "Skill Category Name",
        "skills": ["array of skill names matching master profile skills"]
      }
    ],
    "projects": [
      {
        "source_id": "exact project id from master_profile",
        "name": "Project Name",
        "bullet_points": [
          {
            "source_bullet_id": "exact bullet id from master_profile",
            "content": "Reframed project bullet point highlighting technical achievements"
          }
        ],
        "tech_stack": ["array of technology names"]
      }
    ]
  },
  "meta": {
    "model_used": "gemini-model",
    "generated_at": "ISO8601 timestamp"
  }
}
PROMPT;
    }

    /**
     * Validate that all source_id and source_bullet_id in AI output exist in Master Profile.
     */
    protected function validateHardConstraints(array $masterProfile, array $decoded): array
    {
        $validExpIds = array_column($masterProfile['experiences'] ?? [], 'id');
        $validExpBulletIds = [];
        foreach ($masterProfile['experiences'] ?? [] as $exp) {
            foreach ($exp['bullet_points'] ?? [] as $b) {
                if (isset($b['id'])) {
                    $validExpBulletIds[] = (string) $b['id'];
                }
            }
        }

        $validProjIds = array_column($masterProfile['projects'] ?? [], 'id');
        $validProjBulletIds = [];
        foreach ($masterProfile['projects'] ?? [] as $proj) {
            foreach ($proj['bullet_points'] ?? [] as $b) {
                if (isset($b['id'])) {
                    $validProjBulletIds[] = (string) $b['id'];
                }
            }
        }

        $validEduIds = array_column($masterProfile['education'] ?? [], 'id');

        $errors = [];
        $invalidIds = [];

        $tailored = $decoded['tailored_cv'] ?? [];

        // 1. Validate Experience IDs
        if (is_array($tailored['experiences'] ?? null)) {
            foreach ($tailored['experiences'] as $exp) {
                $sourceId = (string) ($exp['source_id'] ?? '');
                if (!in_array($sourceId, array_map('strval', $validExpIds), true)) {
                    $errors[] = "Invalid experience source_id: '{$sourceId}'";
                    $invalidIds[] = $sourceId;
                }

                if (is_array($exp['bullet_points'] ?? null)) {
                    foreach ($exp['bullet_points'] as $b) {
                        $bulletId = (string) ($b['source_bullet_id'] ?? '');
                        if (!in_array($bulletId, array_map('strval', $validExpBulletIds), true)) {
                            $errors[] = "Invalid experience bullet source_bullet_id: '{$bulletId}'";
                            $invalidIds[] = $bulletId;
                        }
                    }
                }
            }
        }

        // 2. Validate Project IDs
        if (is_array($tailored['projects'] ?? null)) {
            foreach ($tailored['projects'] as $proj) {
                $sourceId = (string) ($proj['source_id'] ?? '');
                if (!in_array($sourceId, array_map('strval', $validProjIds), true)) {
                    $errors[] = "Invalid project source_id: '{$sourceId}'";
                    $invalidIds[] = $sourceId;
                }

                if (is_array($proj['bullet_points'] ?? null)) {
                    foreach ($proj['bullet_points'] as $b) {
                        $bulletId = (string) ($b['source_bullet_id'] ?? '');
                        if (!in_array($bulletId, array_map('strval', $validProjBulletIds), true)) {
                            $errors[] = "Invalid project bullet source_bullet_id: '{$bulletId}'";
                            $invalidIds[] = $bulletId;
                        }
                    }
                }
            }
        }

        // 3. Validate Education IDs
        if (is_array($tailored['education'] ?? null)) {
            foreach ($tailored['education'] as $edu) {
                $sourceId = (string) ($edu['source_id'] ?? '');
                if (!in_array($sourceId, array_map('strval', $validEduIds), true)) {
                    $errors[] = "Invalid education source_id: '{$sourceId}'";
                    $invalidIds[] = $sourceId;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'invalid_ids' => array_unique($invalidIds),
        ];
    }

    /**
     * Fallback mock generation for local environment without API keys.
     */
    protected function handleMockGeneration(GeneratedCv $generatedCv, array $masterProfile): void
    {
        sleep(1);

        $experiences = [];
        foreach ($masterProfile['experiences'] ?? [] as $exp) {
            $bullets = [];
            foreach ($exp['bullet_points'] ?? [] as $b) {
                $bullets[] = [
                    'source_bullet_id' => (string) $b['id'],
                    'content' => '[Reframed for JD] ' . $b['content'],
                ];
            }
            $experiences[] = [
                'source_id' => (string) $exp['id'],
                'company' => $exp['company'],
                'position' => $exp['position'] . ' (Tailored)',
                'start_date' => $exp['start_date'],
                'end_date' => $exp['end_date'],
                'bullet_points' => $bullets,
            ];
        }

        $projects = [];
        foreach ($masterProfile['projects'] ?? [] as $proj) {
            $bullets = [];
            foreach ($proj['bullet_points'] ?? [] as $b) {
                $bullets[] = [
                    'source_bullet_id' => (string) $b['id'],
                    'content' => '[Tailored Contribution] ' . $b['content'],
                ];
            }
            $projects[] = [
                'source_id' => (string) $proj['id'],
                'name' => $proj['name'],
                'bullet_points' => $bullets,
                'tech_stack' => $proj['tech_stack'],
            ];
        }

        $education = [];
        foreach ($masterProfile['education'] ?? [] as $edu) {
            $education[] = [
                'source_id' => (string) $edu['id'],
                'institution' => $edu['institution'],
                'degree' => $edu['degree'],
                'start_date' => $edu['start_date'],
                'end_date' => $edu['end_date'],
                'notes' => $edu['notes'] ?? null,
            ];
        }

        $skillsHighlighted = [];
        $skillsByCategory = [];
        foreach ($masterProfile['skills'] ?? [] as $sk) {
            $skillsByCategory[$sk['category']][] = $sk['name'];
        }
        foreach ($skillsByCategory as $cat => $skNames) {
            $skillsHighlighted[] = [
                'category' => $cat,
                'skills' => $skNames,
            ];
        }

        $customInstructions = trim((string) ($generatedCv->custom_instructions ?? ''));

        $mockSummary = 'Hasil reframing otomatis AI Gemini berdasarkan Job Description target: ' . substr($generatedCv->job_description, 0, 150) . '...';
        $mockSuggestions = [
            'Tambahkan estimasi metrik (jumlah pengguna/baris data) pada proyek SIMONA jika tersedia.',
        ];

        if ($customInstructions !== '') {
            // Local mock: make the extra instruction visible so the flow can be verified without API access.
            $mockSummary .= ' ' . $customInstructions;
            $mockSuggestions[] = 'Prompt tambahan diterapkan pada wording CV: ' . $customInstructions;
        }

        $mockOutput = [
            'headline' => $generatedCv->target_position ?: 'Staf IT / IT Generalist',
            'gaps' => [
                'IT Support & Troubleshooting (Perangkat Keras/Jaringan): Belum ada riwayat langsung di Master Profile.',
            ],
            'suggestions' => $mockSuggestions,
            'warnings' => [],
            'tailored_cv' => [
                'summary' => $mockSummary,
                'experiences' => $experiences,
                'education' => $education,
                'skills_highlighted' => $skillsHighlighted,
                'projects' => $projects,
            ],
            'meta' => [
                'model_used' => 'mock-local-engine',
                'generated_at' => now()->toIso8601String(),
                'custom_instructions' => $customInstructions !== '' ? $customInstructions : null,
            ]
        ];

        $generatedCv->tailored_cv_raw = $mockOutput;
        $generatedCv->status = CvStatus::Review;
        $generatedCv->save();
    }

    /**
     * Deterministic faithfulness validator to detect overclaims, ungrounded numbers, or unlisted skills.
     */
    protected function checkFaithfulness(array $masterProfile, array $decoded, ?string $outputLanguage = null): array
    {
        $warnings = [];
        $masterText = mb_strtolower(json_encode($masterProfile, JSON_UNESCAPED_UNICODE));

        $tailored = $decoded['tailored_cv'] ?? [];
        $bullets = collect($tailored['experiences'] ?? [])
            ->merge($tailored['projects'] ?? [])
            ->flatMap(fn($x) => $x['bullet_points'] ?? [])
            ->map(fn($b) => is_array($b) ? ($b['content'] ?? '') : (string) $b)
            ->push($tailored['summary'] ?? '')
            ->implode(' ');

        // 1. Skill check: skills in output should exist in master profile
        $masterSkills = collect($masterProfile['skills'] ?? [])->pluck('name')->map(fn($v) => mb_strtolower((string) $v));
        foreach ($tailored['skills_highlighted'] ?? [] as $group) {
            foreach ($group['skills'] ?? [] as $sk) {
                $skLower = mb_strtolower(trim($sk));
                if (!$masterSkills->contains($skLower)) {
                    $found = false;
                    foreach ($masterSkills as $mSk) {
                        if (str_contains($skLower, $mSk) || str_contains($mSk, $skLower)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $warnings[] = "Keahlian '{$sk}' tidak terdaftar langsung di Master Profile.";
                    }
                }
            }
        }

        // 2. Escalation claims
        $escalationWords = [
            'mengeliminasi' => 'Sebaiknya gunakan kata "mengurangi" alih-alih klaim absolut "mengeliminasi".',
            'tingkat lanjut' => 'Klaim kata "tingkat lanjut" terdeteksi, pastikan Anda siap diuji pada tingkat advanced.',
            'ahli' => 'Klaim kata "ahli" terdeteksi tanpa catatan khusus di Master Profile.',
            'expert' => 'Klaim kata "expert" terdeteksi.',
            'eliminate' => 'Kata "eliminate" terdeteksi, lebih aman menggunakan "reduce".'
        ];
        foreach ($escalationWords as $word => $msg) {
            if (str_contains(mb_strtolower($bullets), $word) && !str_contains($masterText, $word)) {
                $warnings[] = $msg;
            }
        }

        // 3. Numbers / Metrics check (e.g. 50%, 100 orang)
        preg_match_all('/\b\d+(?:[.,]\d+)?%?\b/', $bullets, $matches);
        if (!empty($matches[0])) {
            foreach (array_unique($matches[0]) as $num) {
                if (in_array($num, ['2023', '2024', '2025', '2026', '1', '2', '3', '8', '11', '12'])) {
                    continue;
                }
                if (!str_contains($masterText, mb_strtolower($num))) {
                    $warnings[] = "Angka/metrik '{$num}' tidak ditemukan di Master Profile. Pastikan data ini riil jika ditanya saat wawancara.";
                }
            }
        }

        // 4. Generic filler / buzzword claims (unverifiable noise for recruiters)
        $genericPhrases = [
            'highly motivated' => 'Frasa generik "highly motivated" tidak memberi bukti. Ganti dengan fakta konkret dari Master Profile.',
            'high aptitude' => 'Klaim "high aptitude" tidak terverifikasi. Tunjukkan lewat bukti nyata, bukan kata sifat.',
            'rapidly mastering' => 'Klaim "rapidly mastering" adalah filler; ganti dengan pencapaian konkret.',
            'proven ability' => 'Klaim "proven ability" tanpa bukti sebaiknya diganti pencapaian nyata.',
            'proven track record' => 'Klaim "proven track record" perlu didukung pencapaian yang bisa diverifikasi.',
            'strong passion' => 'Frasa generik "strong passion" sebaiknya diganti bukti nyata, bukan klaim.',
            'passionate' => 'Klaim "passionate" tanpa bukti sebaiknya dihapus atau diganti pencapaian konkret.',
            'dynamic team' => 'Frasa "dynamic team" tidak spesifik; ganti dengan kontribusi nyata Anda.',
            'hard-working' => 'Klaim "hard-working" tanpa bukti sebaiknya dihapus.',
            'quick learner' => 'Klaim "quick learner" tanpa bukti sebaiknya diganti contoh nyata.',
            'fast learner' => 'Klaim "fast learner" tanpa bukti sebaiknya diganti contoh nyata.',
            'self-motivated' => 'Klaim "self-motivated" adalah filler; sebutkan inisiatif konkret.',
            'team player' => 'Klaim "team player" tanpa bukti sebaiknya dihapus.',
            'adapting to complex' => 'Frasa "adapting to complex requirements" adalah filler; sebutkan bukti.',
            'strong problem-solving' => 'Klaim "strong problem-solving skills" tanpa bukti sebaiknya dihapus.',
            'eager to contribute' => 'Klaim "eager to contribute" tidak memberi informasi; ganti dengan bukti.',
            'cutting-edge' => 'Kata "cutting-edge" adalah klaim kosong; sebutkan teknologi yang benar-benar Anda pakai.',
            'seeking opportunities' => 'Frasa "seeking opportunities" tidak perlu ada di CV.',
            'modern javascript frameworks' => 'Klaim "modern JavaScript frameworks" tidak didukung Master Profile (JavaScript/Alpine.js saja).',
        ];
        foreach ($genericPhrases as $phrase => $message) {
            if (str_contains(mb_strtolower($bullets), $phrase)) {
                $warnings[] = $message;
            }
        }

        // 5. Technologies claimed but absent from the Master Profile
        $unsupportedTech = [
            'React', 'Next.js', 'Vue', 'Angular', 'Node.js', 'TypeScript', 'Spring Boot',
            '.NET', 'Kubernetes', 'AWS', 'GCP', 'Azure', 'MongoDB', 'Redis', 'Django',
            'GraphQL', 'Livewire', 'Flutter',
        ];
        $outputLower = mb_strtolower($bullets);
        $masterLower = mb_strtolower(json_encode($masterProfile, JSON_UNESCAPED_UNICODE));
        foreach ($unsupportedTech as $tech) {
            $pattern = '/(?<![a-z0-9])' . preg_quote(mb_strtolower($tech), '/') . '(?![a-z0-9])/i';
            if (preg_match($pattern, $outputLower) && !preg_match($pattern, $masterLower)) {
                $warnings[] = "Teknologi '{$tech}' disebut di CV tetapi tidak ada di Master Profile — hapus, atau pastikan Anda benar-benar menguasainya sebelum ditanya di interview.";
            }
        }

        // 6. Language purity: an English CV must not contain Indonesian phrases / skill labels
        if (strtolower((string) $outputLanguage) === 'en') {
            $languageFields = collect($tailored['experiences'] ?? [])
                ->flatMap(fn ($x) => $x['bullet_points'] ?? [])
                ->map(fn ($b) => is_array($b) ? ($b['content'] ?? '') : (string) $b)
                ->push($tailored['summary'] ?? '');

            foreach ($tailored['projects'] ?? [] as $project) {
                $languageFields->push((string) ($project['name'] ?? ''));
                foreach ($project['bullet_points'] ?? [] as $b) {
                    $languageFields->push(is_array($b) ? ($b['content'] ?? '') : (string) $b);
                }
            }

            foreach ($tailored['skills_highlighted'] ?? [] as $group) {
                $languageFields->push((string) ($group['category'] ?? ''));
                foreach ($group['skills'] ?? [] as $skill) {
                    $languageFields->push((string) $skill);
                }
            }

            $indonesianMarkers = [
                'mengembangkan', 'membangun', 'mengintegrasikan', 'menerapkan', 'merancang',
                'normalisasi', 'rekonsiliasi', 'validasi', 'pemantauan', 'layanan', 'berbasis',
                'kecocokan', 'keahlian', 'penilaian', 'serta', 'koordinasi', 'ketelitian',
            ];

            $haystack = mb_strtolower($languageFields->implode(' '));
            $foundMarkers = [];
            foreach ($indonesianMarkers as $marker) {
                if (preg_match('/(?<![a-z])' . preg_quote($marker, '/') . '/u', $haystack)) {
                    $foundMarkers[] = $marker;
                }
            }

            if (!empty($foundMarkers)) {
                $warnings[] = 'Teks CV masih Bahasa Indonesia padahal output language = English (terdeteksi: '
                    . implode(', ', array_slice($foundMarkers, 0, 4))
                    . '). Terjemahkan semua bullet, nama skill, dan nama proyek agar konsisten.';
            }
        }

        return array_values(array_unique($warnings));
    }

    /**
     * Mark GeneratedCv record as Failed without throwing exception.
     */
    protected function markAsFailed(GeneratedCv $generatedCv, string $errorMessage, array $extraContext = []): void
    {
        $generatedCv->status = CvStatus::Failed;
        $generatedCv->tailored_cv_raw = array_merge([
            'error' => $errorMessage,
            'failed_at' => now()->toIso8601String(),
        ], $extraContext);
        $generatedCv->save();
    }
}
