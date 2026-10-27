<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CvAssistantAgentService
{
    public function __construct(
        protected DocEmbeddingService $embeddingService
    ) {}

    /**
     * Run Agent loop with Gemini function calling / tool-calling.
     *
     * @param string $userQuestion
     * @return array Array containing 'answer', 'traces', and 'iterations'
     */
    public function ask(string $userQuestion): array
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-3.1-flash-lite');

        if (empty($apiKey) || $apiKey === 'mock') {
            return $this->handleMockAgent($userQuestion);
        }

        $systemPrompt = <<<PROMPT
Anda adalah CV Assistant Agent cerdas untuk aplikasi AI CV Builder.
Anda memiliki akses ke 2 tools berikut:
1. query_docs(question): Gunakan ini untuk mencari dokumentasi teknis project (PRD, ERD, Task Breakdown, arsitektur).
2. get_master_profile_summary(): Gunakan ini untuk mengambil ringkasan data aktual & kelengkapan Master Profile user dari database.

Petunjuk Penting:
- Jika pertanyaan user membutuhkan informasi dokumentasi DAN data aktual user (misal: membandingkan skema docs dengan profil user saat ini), Anda WAJIB memanggil KEDUA tools tersebut sebelum menyusun jawaban akhir.
- Jawablah secara ramah, terstruktur dalam Markdown, dan berikan evaluasi yang jelas berdasarkan hasil pemanggilan tools.
PROMPT;

        $toolsDeclaration = [
            [
                'functionDeclarations' => [
                    [
                        'name'        => 'query_docs',
                        'description' => 'Cari jawaban dari dokumentasi teknis project CV Builder (PRD, ERD, Task Breakdown, arsitektur fitur). Gunakan tool ini untuk pertanyaan seputar desain, skema, atau cara kerja sistem yang terdokumentasi.',
                        'parameters'  => [
                            'type'       => 'OBJECT',
                            'properties' => [
                                'question' => [
                                    'type'        => 'STRING',
                                    'description' => 'Pertanyaan spesifik seputar dokumentasi project untuk dicari di index RAG',
                                ],
                            ],
                            'required'   => ['question'],
                        ],
                    ],
                    [
                        'name'        => 'get_master_profile_summary',
                        'description' => 'Ambil ringkasan data Master Profile user saat ini: jumlah experience bullets, jumlah skill, jumlah sertifikasi, dan section mana saja yang sudah/belum terisi. Gunakan tool ini untuk pertanyaan soal kelengkapan atau isi data profile user sebenarnya.',
                        'parameters'  => [
                            'type'       => 'OBJECT',
                            'properties' => (object)[],
                        ],
                    ],
                ],
            ],
        ];

        $contents = [
            [
                'role'  => 'user',
                'parts' => [
                    ['text' => "System Instruction:\n{$systemPrompt}\n\nUser Question:\n{$userQuestion}"],
                ],
            ],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $maxIterations = 5;
        $traces = [];
        $finalAnswer = null;

        for ($iteration = 1; $iteration <= $maxIterations; $iteration++) {
            try {
                $response = Http::timeout(45)->post($url, [
                    'contents' => $contents,
                    'tools'    => $toolsDeclaration,
                ]);

                if ($response->failed()) {
                    Log::error('Gemini Agent API Error', ['status' => $response->status(), 'body' => $response->body()]);
                    return $this->handleMockAgent($userQuestion);
                }

                $data = $response->json();
                $candidate = $data['candidates'][0]['content'] ?? null;
                $parts = $candidate['parts'] ?? [];

                if (empty($parts)) {
                    throw new Exception('Empty parts returned from Gemini Agent payload.');
                }

                // Check if candidate parts contain a functionCall
                $functionCall = null;
                $textAnswer = null;

                foreach ($parts as $part) {
                    if (isset($part['functionCall'])) {
                        $functionCall = $part['functionCall'];
                        break;
                    }
                    if (isset($part['text'])) {
                        $textAnswer = $part['text'];
                    }
                }

                if ($functionCall) {
                    $toolName = $functionCall['name'] ?? '';
                    $toolArgs = $functionCall['args'] ?? [];

                    // Execute tool
                    $toolResultText = $this->executeTool($toolName, $toolArgs);

                    // Add trace log
                    $traces[] = [
                        'iteration' => $iteration,
                        'tool'      => $toolName,
                        'args'      => $toolArgs,
                        'result'    => mb_strimwidth($toolResultText, 0, 300, '...'),
                    ];

                    // Sanitize parts so functionCall.args is serialized as JSON object {} not array []
                    $sanitizedParts = [];
                    foreach ($parts as $p) {
                        if (isset($p['functionCall'])) {
                            $p['functionCall']['args'] = (object)($p['functionCall']['args'] ?? []);
                        }
                        $sanitizedParts[] = $p;
                    }

                    // Append assistant content with sanitized candidate parts
                    $contents[] = [
                        'role'  => 'model',
                        'parts' => $sanitizedParts,
                    ];

                    // Append user content with functionResponse
                    $contents[] = [
                        'role'  => 'user',
                        'parts' => [
                            [
                                'functionResponse' => [
                                    'name'     => $toolName,
                                    'response' => (object)[
                                        'result' => $toolResultText,
                                    ],
                                ],
                            ],
                        ],
                    ];
                } elseif (!empty($textAnswer)) {
                    $finalAnswer = trim($textAnswer);
                    return [
                        'answer'     => $finalAnswer,
                        'traces'     => $traces,
                        'iterations' => $iteration,
                    ];
                } else {
                    throw new Exception('Unexpected response format from Gemini API');
                }
            } catch (Exception $e) {
                Log::warning('Gemini Agent Exception: ' . $e->getMessage());
                return $this->handleMockAgent($userQuestion);
            }
        }

        return [
            'answer'     => $finalAnswer ?? "Agent telah mencapai batas maksimum iterasi tool calls ($maxIterations).",
            'traces'     => $traces,
            'iterations' => $maxIterations,
        ];
    }

    /**
     * Execute PHP logic corresponding to the requested tool.
     *
     * @param string $toolName
     * @param array $args
     * @return string
     */
    public function executeTool(string $toolName, array $args): string
    {
        if ($toolName === 'query_docs') {
            $question = $args['question'] ?? 'dokumentasi project';
            $chunks = $this->embeddingService->searchRelevantChunks($question, 4);

            if (empty($chunks)) {
                return "Tidak ditemukan dokumentasi relevan untuk query: {$question}";
            }

            $output = "Hasil pencarian dokumentasi RAG (Top Chunks):\n\n";
            foreach ($chunks as $idx => $c) {
                $num = $idx + 1;
                $output .= "[Chunk #{$num}] File: {$c['source']} | Section: {$c['heading']} (Similarity: " . round($c['score'], 4) . ")\n";
                $output .= "Content:\n{$c['content']}\n\n";
            }

            return $output;
        }

        if ($toolName === 'get_master_profile_summary') {
            $profile = Profile::first();
            $expCount = Experience::count();
            $eduCount = Education::count();
            $skillCount = Skill::count();
            $projectCount = Project::count();
            $certCount = Certification::count();

            $totalExpBullets = 0;
            $experiences = Experience::with('bulletPoints')->get();
            foreach ($experiences as $exp) {
                $totalExpBullets += $exp->bulletPoints->count();
            }

            $categories = Skill::pluck('category')->unique()->filter()->values()->toArray();

            $filledSections = [];
            $missingSections = [];

            if ($profile && !empty($profile->name)) {
                $filledSections[] = 'Bio Profile (Name, Email, Phone, Location, Summary)';
            } else {
                $missingSections[] = 'Bio Profile';
            }

            if ($expCount > 0) {
                $filledSections[] = "Work Experiences ({$expCount} posisi, {$totalExpBullets} bullet points)";
            } else {
                $missingSections[] = 'Work Experiences';
            }

            if ($eduCount > 0) {
                $filledSections[] = "Education ({$eduCount} data pendidikan)";
            } else {
                $missingSections[] = 'Education';
            }

            if ($skillCount > 0) {
                $filledSections[] = "Skills ({$skillCount} keahlian dalam " . count($categories) . " kategori)";
            } else {
                $missingSections[] = 'Skills';
            }

            if ($projectCount > 0) {
                $filledSections[] = "Projects ({$projectCount} projek terdaftar)";
            } else {
                $missingSections[] = 'Projects';
            }

            if ($certCount > 0) {
                $filledSections[] = "Certifications ({$certCount} sertifikasi)";
            } else {
                $missingSections[] = 'Certifications';
            }

            $summary = "Ringkasan Data Master Profile User Terbaru:\n";
            $summary .= "- Status Bio Profile: " . ($profile ? "Terisi ({$profile->name})" : "Belum terisi") . "\n";
            $summary .= "- Total Pengalaman Kerja: {$expCount} posisi ({$totalExpBullets} detail bullet points)\n";
            $summary .= "- Total Data Pendidikan: {$eduCount}\n";
            $summary .= "- Total Skills: {$skillCount} keahlian\n";
            $summary .= "- Total Projects: {$projectCount}\n";
            $summary .= "- Total Certifications: {$certCount}\n\n";

            $summary .= "Section Yang Sudah Terisi:\n" . (empty($filledSections) ? "- (Tidak ada)\n" : "- " . implode("\n- ", $filledSections) . "\n") . "\n";
            $summary .= "Section Yang Masih Kosong / Perlu Dilengkapi:\n" . (empty($missingSections) ? "- Semua section utama sudah terisi!\n" : "- " . implode("\n- ", $missingSections) . "\n");

            return $summary;
        }

        return "Unknown tool: {$toolName}";
    }

    /**
     * Fallback mock agent response for offline / test mode.
     */
    private function handleMockAgent(string $userQuestion): array
    {
        $traces = [
            [
                'iteration' => 1,
                'tool'      => 'query_docs',
                'args'      => ['question' => 'skema database Master Profile'],
                'result'    => '[Chunk #1] File: docs/ERD_AI_CV_Builder.md | Section: Master Profile Schema... (Similarity: 0.4755)',
            ],
            [
                'iteration' => 2,
                'tool'      => 'get_master_profile_summary',
                'args'      => [],
                'result'    => 'Ringkasan Data Master Profile: Bio Profile Terisi, 2 Experiences (5 bullets), 1 Education, 8 Skills, 0 Projects, 1 Certification.',
            ],
        ];

        $summary = $this->executeTool('get_master_profile_summary', []);

        $answer = "*(Mode Demo/Offline Agent)*\n\n" .
            "### 1. Skema Database Master Profile (Berdasarkan Dokumentasi)\n" .
            "Berdasarkan dokumentasi **`docs/ERD_AI_CV_Builder.md`**, skema Master Profile terdiri dari 6 entitas utama:\n" .
            "- **`profiles`**: Menampung biodata dasar (nama, email, phone, location, summary, links).\n" .
            "- **`experiences` & `experience_bullets`**: Menampung posisi kerja dan detail pencapaian.\n" .
            "- **`education`**: Menampung riwayat akademik.\n" .
            "- **`skills`**: Menampung daftar keahlian berdasarkan kategori.\n" .
            "- **`projects`**: Menampung daftar projek portofolio.\n" .
            "- **`certifications`**: Menampung sertifikat profesional.\n\n" .
            "### 2. Evaluasi Kelengkapan Data Anda Saat Ini\n\n" .
            "{$summary}\n\n" .
            "*(Catatan: Untuk eksekusi function calling Gemini secara realtime, pastikan GEMINI_API_KEY valid terpasang di file .env)*";

        return [
            'answer'     => $answer,
            'traces'     => $traces,
            'iterations' => 2,
        ];
    }
}
