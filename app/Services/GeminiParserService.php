<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeminiParserService
{
    public const SKILL_CATEGORIES = [
        'Data Management',
        'Project Administration',
        'Design',
        'Web Development',
        'Marketing & Content',
        'Tools & Productivity',
        'Soft Skills',
        'Languages',
        'Other',
    ];

    /**
     * Parse raw CV text using Google Gemini API.
     *
     * @param string $rawText
     * @return array
     * @throws Exception
     */
    public function parse(string $rawText): array
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-3.1-flash-lite');

        if (empty($apiKey) || $apiKey === 'mock') {
            // Mock fallback for local/testing without credentials
            sleep(1);
            return [
                'profile' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane.doe@example.com',
                    'phone' => '+1 555-0100',
                    'location' => 'San Francisco, CA',
                    'summary' => 'Experienced backend engineer specializing in PHP and Laravel.',
                    'links' => ['https://github.com/janedoe', 'https://linkedin.com/in/janedoe'],
                ],
                'experiences' => [
                    [
                        'company' => 'Tech Innovators',
                        'position' => 'Backend Lead',
                        'start_date' => '2021-06-01',
                        'end_date' => null, // Present
                        'bullet_points' => [
                            'Architected scalable API services using Laravel and PostgreSQL.',
                            'Led a team of 4 engineers and improved release speed by 25%.',
                        ]
                    ]
                ],
                'education' => [
                    [
                        'institution' => 'UC Berkeley',
                        'degree' => 'B.S. in Computer Science',
                        'start_date' => '2017-09-01',
                        'end_date' => '2021-05-15',
                        'notes' => 'GPA: 3.9/4.0',
                    ]
                ],
                'skills' => [
                    ['name' => 'PHP', 'category' => 'Backend'],
                    ['name' => 'Laravel', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Database'],
                    ['name' => 'Docker', 'category' => 'Tools'],
                ],
                'projects' => [
                    [
                        'name' => 'E-commerce API',
                        'link' => 'https://github.com/janedoe/shop',
                        'tech_stack' => ['Laravel', 'PostgreSQL', 'Redis'],
                        'bullet_points' => [
                            'Built a high-performance RESTful shopping cart API.',
                            'Optimized queries reducing checkout latency by 40%.',
                        ]
                    ]
                ],
                'certifications' => [
                    [
                        'name' => 'AWS Certified Developer Associate',
                        'issuer' => 'Amazon Web Services',
                        'year' => '2023',
                    ]
                ],
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $prompt = $this->buildPrompt($rawText);

        $maxAttempts = 3;
        $attempt = 0;
        $delay = 1; // start with 1 second delay

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($url, [
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
                        throw new Exception("Layanan AI sedang sibuk, coba lagi beberapa saat (Rate Limit Exceeded).");
                    }
                    sleep($delay);
                    $delay *= 2; // exponential backoff
                    continue;
                }

                if ($response->failed()) {
                    throw new Exception("Gemini API returned error: " . $response->status() . " - " . $response->body());
                }

                $data = $response->json();
                $textResult = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                if (empty($textResult)) {
                    throw new Exception("Failed to retrieve text content from Gemini response.");
                }

                $textResult = trim($textResult);
                if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $textResult, $matches)) {
                    $textResult = trim($matches[1]);
                }

                $decoded = json_decode($textResult, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $snippet = substr($textResult, 0, 500);
                    throw new Exception("Gemini did not return valid JSON: " . json_last_error_msg() . ". Response: " . $snippet);
                }

                return $this->sanitizeParsedData($decoded);

            } catch (Exception $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                sleep($delay);
                $delay *= 2;
            }
        }

        throw new Exception("Failed to call Gemini API after multiple retries.");
    }

    /**
     * Build the parsing instructions prompt.
     *
     * @param string $rawText
     * @return string
     */
    protected function buildPrompt(string $rawText): string
    {
        return <<<PROMPT
You are a CV parser. Extract all candidate information from the following raw CV text and format it strictly into a JSON object matching the JSON schema below. 

Guidelines:
1. Extract profile summary, links (LinkedIn, GitHub, Portfolios), experiences, projects, education, skills, and certifications.
2. For dates (like start_date and end_date), convert the month and year from the CV into "YYYY-MM-01" format (the day must always be "01"). Explicitly forbid guessing or fabricating any day numbers that are not written. If a date is not present, use null. Use "Present" if they are currently working or studying there.
3. Ensure bullet_points is always a flat array of strings representing individual bullet points. Do not group them into single long paragraphs.
4. For each extracted skill, DO NOT simply copy the literal category label from the source CV (such as generic "Hard Skills" or "Soft Skills"). Instead, perform a functional categorization based on the context of how the skill was used in the respective experiences or projects. Categorize each skill into EXACTLY ONE of the following 8 reference categories (case-sensitive):
   - Data Management (for data processing, analysis, reconciliation, spreadsheets for data, SQL)
   - Project Administration (for documentation, archiving, project coordination, contract management)
   - Design (for visual design software/skills, UI/UX. Use this for Figma, Canva, Adobe Photoshop, Illustrator)
   - Web Development (for programming languages, frameworks, development tools)
   - Marketing & Content (for social media management, content creation, copywriting, branding)
   - Tools & Productivity (for general non-data productivity software, e.g. Notion, Google Calendar, Microsoft Office Suite)
   - Soft Skills (for interpersonal/managerial skills, e.g. Leadership, Communication, Teamwork)
   - Languages (for languages spoken/mastered)
5. LANGUAGE PRESERVATION: DO NOT translate any extracted text. All text fields (summary, bullet_points, degree, notes, skill names, etc.) MUST remain in the exact same language as they appear in the original raw CV text. If the raw CV is written in Bahasa Indonesia, keep the output in Bahasa Indonesia. If the raw CV is written in English, keep the output in English. Your only job is extraction and structuring — NOT translation.

   If a skill does not fit any of the above 8 categories, use "Other". Under no circumstances should you create a new category name outside these 9 choices (8 categories + Other). The category must help in matching skills to a job description later.

   Few-shot examples for consistency:
   * Skill "Microsoft Excel (Pivot Table, VLOOKUP)" in context of "preparing data reports" -> category: Data Management
   * Skill "Project Documentation & Archiving" in context of B2B admin -> category: Project Administration
   * Skill "Figma", "Canva", "Adobe Photoshop" -> category: Design
   * Skill "Leadership", "Teamwork", "Communication" -> category: Soft Skills
   * Skill "Bahasa Indonesia", "English" -> category: Languages

Target JSON Schema:
{
  "profile": {
    "name": "Full Name or null",
    "email": "Email Address or null",
    "phone": "Phone Number or null",
    "location": "City, Country or null",
    "summary": "Professional Summary or null",
    "links": ["array of URL strings"]
  },
  "experiences": [
    {
      "company": "Company Name",
      "position": "Job Title",
      "start_date": "Start Date text or null",
      "end_date": "End Date text or null (use 'Present' if current job)",
      "bullet_points": ["array of achievement strings"]
    }
  ],
  "education": [
    {
      "institution": "University/School Name",
      "degree": "Degree details",
      "start_date": "Start Date text or null",
      "end_date": "End Date text or null",
      "notes": "GPA, honors, or null"
    }
  ],
  "skills": [
    {
      "name": "Skill name",
      "category": "Skill category"
    }
  ],
  "projects": [
    {
      "name": "Project Name",
      "link": "Project URL or null",
      "tech_stack": ["array of technology names"],
      "bullet_points": ["array of accomplishments"]
    }
  ],
  "certifications": [
    {
      "name": "Certification Name",
      "issuer": "Issuing Organization",
      "year": "YYYY text or null"
    }
  ]
}

Raw CV Text:
{$rawText}
PROMPT;
    }

    /**
     * Sanitize and normalize the parsed JSON structure.
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeParsedData(array $data): array
    {
        // 1. Profile
        $profile = $data['profile'] ?? [];
        $sanitizedProfile = [
            'name' => trim($profile['name'] ?? ''),
            'email' => trim($profile['email'] ?? ''),
            'phone' => trim($profile['phone'] ?? ''),
            'location' => trim($profile['location'] ?? ''),
            'summary' => trim($profile['summary'] ?? ''),
            'links' => is_array($profile['links'] ?? null) ? array_values(array_filter(array_map('trim', $profile['links']))) : [],
        ];

        // 2. Experiences
        $experiences = [];
        if (is_array($data['experiences'] ?? null)) {
            foreach ($data['experiences'] as $exp) {
                $bullets = [];
                if (is_array($exp['bullet_points'] ?? null)) {
                    $bullets = array_values(array_filter(array_map('trim', $exp['bullet_points'])));
                } elseif (is_string($exp['bullet_points'] ?? null)) {
                    $rawBullets = preg_split('/\r\n|\r|\n/', $exp['bullet_points']);
                    foreach ($rawBullets as $rb) {
                        $rb = trim($rb, " \t\n\r\0\x0B-*•");
                        if (!empty($rb)) {
                            $bullets[] = $rb;
                        }
                    }
                }

                $experiences[] = [
                    'company' => trim($exp['company'] ?? ''),
                    'position' => trim($exp['position'] ?? ''),
                    'start_date' => $this->sanitizeDate($exp['start_date'] ?? ''),
                    'end_date' => $this->sanitizeDate($exp['end_date'] ?? ''),
                    'bullet_points' => $bullets,
                ];
            }
        }

        // 3. Education
        $education = [];
        if (is_array($data['education'] ?? null)) {
            foreach ($data['education'] as $edu) {
                $education[] = [
                    'institution' => trim($edu['institution'] ?? ''),
                    'degree' => trim($edu['degree'] ?? ''),
                    'start_date' => $this->sanitizeDate($edu['start_date'] ?? ''),
                    'end_date' => $this->sanitizeDate($edu['end_date'] ?? ''),
                    'notes' => trim($edu['notes'] ?? ''),
                ];
            }
        }

        // 4. Skills
        $skills = [];
        if (is_array($data['skills'] ?? null)) {
            foreach ($data['skills'] as $skill) {
                if (is_string($skill)) {
                    $skills[] = [
                        'name' => trim($skill),
                        'category' => 'Other',
                    ];
                } else {
                    $name = trim($skill['name'] ?? '');
                    if (!empty($name)) {
                        $category = trim($skill['category'] ?? 'Other');
                        if (!in_array($category, self::SKILL_CATEGORIES, true)) {
                            $category = 'Other';
                        }
                        $skills[] = [
                            'name' => $name,
                            'category' => $category,
                        ];
                    }
                }
            }
        }

        // 5. Projects
        $projects = [];
        if (is_array($data['projects'] ?? null)) {
            foreach ($data['projects'] as $proj) {
                $techStack = [];
                if (is_array($proj['tech_stack'] ?? null)) {
                    $techStack = array_values(array_filter(array_map('trim', $proj['tech_stack'])));
                } elseif (is_string($proj['tech_stack'] ?? null)) {
                    $techStack = array_values(array_filter(array_map('trim', explode(',', $proj['tech_stack']))));
                }

                $bullets = [];
                if (is_array($proj['bullet_points'] ?? null)) {
                    $bullets = array_values(array_filter(array_map('trim', $proj['bullet_points'])));
                } elseif (is_string($proj['bullet_points'] ?? null)) {
                    $rawBullets = preg_split('/\r\n|\r|\n/', $proj['bullet_points']);
                    foreach ($rawBullets as $rb) {
                        $rb = trim($rb, " \t\n\r\0\x0B-*•");
                        if (!empty($rb)) {
                            $bullets[] = $rb;
                        }
                    }
                }

                $projects[] = [
                    'name' => trim($proj['name'] ?? ''),
                    'link' => trim($proj['link'] ?? ''),
                    'tech_stack' => $techStack,
                    'bullet_points' => $bullets,
                ];
            }
        }

        // 6. Certifications
        $certifications = [];
        if (is_array($data['certifications'] ?? null)) {
            foreach ($data['certifications'] as $cert) {
                $year = trim($cert['year'] ?? '');
                if (preg_match('/\b\d{4}\b/', $year, $matches)) {
                    $year = $matches[0];
                } else {
                    $year = date('Y');
                }

                $certifications[] = [
                    'name' => trim($cert['name'] ?? ''),
                    'issuer' => trim($cert['issuer'] ?? ''),
                    'year' => $year,
                ];
            }
        }

        return [
            'profile' => $sanitizedProfile,
            'experiences' => $experiences,
            'education' => $education,
            'skills' => $skills,
            'projects' => $projects,
            'certifications' => $certifications,
        ];
    }

    /**
     * Parse arbitrary date string into YYYY-MM-DD.
     *
     * @param mixed $dateStr
     * @return string|null
     */
    protected function sanitizeDate($dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);
        $lowerStr = strtolower($dateStr);

        if ($lowerStr === 'present' || $lowerStr === 'now' || $lowerStr === 'current') {
            return null;
        }

        // Format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Format YYYY-MM
        if (preg_match('/^\d{4}-\d{2}$/', $dateStr)) {
            return $dateStr . '-01';
        }

        // Try standard PHP strtotime parsing
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        // Format YYYY (year only)
        if (preg_match('/^\b\d{4}\b$/', $dateStr)) {
            return $dateStr . '-01-01';
        }

        return null;
    }
}
