<?php

namespace Tests\Feature;

use App\Enums\CvStatus;
use App\Models\Certification;
use App\Models\GeneratedCv;
use App\Models\Profile;
use App\Services\DocxGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvDocumentLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeProfile(): Profile
    {
        return Profile::create([
            'name' => 'M. Hatta Siddiq',
            'email' => 'hatta@example.com',
            'phone' => '+62 811-0000-0000',
            'location' => 'Palembang, Indonesia',
            'summary' => 'Web Developer',
            'links' => [
                'https://www.linkedin.com/in/example-candidate',
                'https://github.com/example-user',
                'https://www.instagram.com/example_user/',
            ],
        ]);
    }

    private function tailoredCv(): array
    {
        return [
            'summary' => 'Informatics graduate (D3, GPA 3.63) with hands-on experience building end-to-end web applications.',
            'experiences' => [
                [
                    'source_id' => '1',
                    'position' => 'Staf Magang – Divisi Pemasaran dan Penjualan',
                    'company' => 'PT PLN Icon Plus',
                    'start_date' => '2025-07-01',
                    'end_date' => '2025-12-01',
                    'bullet_points' => [
                        ['source_bullet_id' => '2', 'content' => 'Audited and verified project delivery documents.'],
                    ],
                ],
            ],
            'education' => [],
            'skills_highlighted' => [
                ['category' => 'Backend & API', 'skills' => ['REST API Integration', 'MySQL', 'Git', 'Docker']],
            ],
            'projects' => [
                [
                    'source_id' => '2',
                    'name' => 'SIMONA',
                    'tech_stack' => ['Laravel', 'MySQL'],
                    'bullet_points' => [
                        ['source_bullet_id' => '5', 'content' => 'Built the service activation monitoring system.'],
                    ],
                ],
            ],
        ];
    }

    public function test_pdf_template_renders_key_projects_before_work_experience(): void
    {
        $html = view('pdf.cv-template', [
            'profile' => $this->makeProfile(),
            'certifications' => Certification::all(),
            'tailoredCv' => $this->tailoredCv(),
            'lang' => 'en',
            'targetPosition' => 'Full Stack Developer',
            'generatedCv' => new GeneratedCv(['target_position' => 'Full Stack Developer']),
        ])->render();

        $summaryPos = strpos($html, 'PROFESSIONAL SUMMARY');
        $projectsPos = strpos($html, 'KEY PROJECTS');
        $experiencePos = strpos($html, 'WORK EXPERIENCE');

        $this->assertNotFalse($summaryPos);
        $this->assertNotFalse($projectsPos);
        $this->assertNotFalse($experiencePos);

        $this->assertLessThan($projectsPos, $summaryPos, 'Summary must come first.');
        $this->assertLessThan($experiencePos, $projectsPos, 'Key Projects must be printed above Work Experience.');
    }

    public function test_pdf_header_shows_professional_links_but_hides_instagram(): void
    {
        $html = view('pdf.cv-template', [
            'profile' => $this->makeProfile(),
            'certifications' => Certification::all(),
            'tailoredCv' => $this->tailoredCv(),
            'lang' => 'en',
            'targetPosition' => 'Full Stack Developer',
            'generatedCv' => new GeneratedCv(['target_position' => 'Full Stack Developer']),
        ])->render();

        $this->assertStringContainsString('github.com/example-user', $html);
        $this->assertStringContainsString('linkedin.com/in/example-candidate', $html);
        $this->assertStringNotContainsString('instagram', $html);
    }

    public function test_docx_renders_key_projects_before_work_experience(): void
    {
        $profile = $this->makeProfile();

        $path = app(DocxGeneratorService::class)->generate(
            $profile,
            Certification::all(),
            $this->tailoredCv(),
            'en',
            'Full Stack Developer'
        );

        $this->assertFileExists($path);

        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $text = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($xml)));

        $summaryPos = strpos($text, 'PROFESSIONAL SUMMARY');
        $projectsPos = strpos($text, 'KEY PROJECTS');
        $experiencePos = strpos($text, 'WORK EXPERIENCE');

        $this->assertNotFalse($projectsPos, 'KEY PROJECTS heading must exist.');
        $this->assertNotFalse($experiencePos, 'WORK EXPERIENCE heading must exist.');

        $this->assertLessThan($projectsPos, $summaryPos, 'Summary must come first.');
        $this->assertLessThan($experiencePos, $projectsPos, 'Key Projects must be printed above Work Experience.');
        $this->assertStringContainsString('github.com/example-user', $text);
    }

    public function test_review_page_lists_projects_section_before_experience_section(): void
    {
        $generatedCv = GeneratedCv::create([
            'job_description' => 'We are hiring a Full Stack Developer with Laravel and MySQL.',
            'target_position' => 'Full Stack Developer',
            'status' => CvStatus::Review,
            'output_language' => 'en',
            'tailored_cv_raw' => ['tailored_cv' => $this->tailoredCv()],
        ]);

        $html = $this->get(route('tailoring.review', $generatedCv))->assertOk()->getContent();

        $projectsPos = strpos($html, '2. Key Projects');
        $experiencePos = strpos($html, '3. Pengalaman Kerja');

        $this->assertNotFalse($projectsPos);
        $this->assertNotFalse($experiencePos);
        $this->assertLessThan($experiencePos, $projectsPos);
    }
}
