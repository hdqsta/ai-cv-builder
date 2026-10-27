<?php

namespace Tests\Feature;

use App\Enums\CvStatus;
use App\Models\GeneratedCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TailoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_display_tailoring_create_page(): void
    {
        $response = $this->get(route('tailoring.create'));

        $response->assertStatus(200);
        $response->assertSee('Input Job Description');
    }

    public function test_can_store_job_description_successfully(): void
    {
        $jobDescriptionText = 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.';

        $response = $this->post(route('tailoring.store'), [
            'job_description' => $jobDescriptionText,
            'target_position' => 'Backend Developer',
            'output_language' => 'en',
            'ai_model' => 'gemini-2.5-flash',
        ]);

        $this->assertDatabaseHas('generated_cvs', [
            'job_description' => $jobDescriptionText,
            'target_position' => 'Backend Developer',
            'status' => CvStatus::Pending->value,
            'is_instant_check' => false,
            'output_language' => 'en',
            'ai_model' => 'gemini-2.5-flash',
        ]);

        $generatedCv = GeneratedCv::first();
        $this->assertNotNull($generatedCv);
        $response->assertRedirect(route('tailoring.review', $generatedCv));
    }

    public function test_fails_validation_when_job_description_is_too_short(): void
    {
        $response = $this->post(route('tailoring.store'), [
            'job_description' => 'Terlalu pendek',
            'target_position' => 'Backend Developer',
            'output_language' => 'id',
            'ai_model' => 'gemini-3.1-flash-lite',
        ]);

        $response->assertSessionHasErrors(['job_description']);
    }

    public function test_can_run_tailoring_engine_mock(): void
    {
        // Force the local mock engine so this test never depends on a live API call.
        config(['services.gemini.key' => 'mock']);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'target_position' => 'Backend Developer',
            'status' => CvStatus::Pending,
        ]);

        $response = $this->post(route('tailoring.generate', $generatedCv));

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        
        $generatedCv->refresh();
        $this->assertEquals(CvStatus::Review, $generatedCv->status);
        $this->assertNotNull($generatedCv->tailored_cv_raw);
        $this->assertArrayHasKey('tailored_cv', $generatedCv->tailored_cv_raw);
    }

    public function test_can_store_job_description_with_instant_check(): void
    {
        $jobDescriptionText = 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.';

        $response = $this->post(route('tailoring.store'), [
            'job_description' => $jobDescriptionText,
            'target_position' => 'Backend Developer',
            'action' => 'instant_check',
            'output_language' => 'id',
            'ai_model' => 'gemini-3.1-flash-lite',
        ]);

        $this->assertDatabaseHas('generated_cvs', [
            'job_description' => $jobDescriptionText,
            'target_position' => 'Backend Developer',
            'status' => CvStatus::Pending->value,
            'is_instant_check' => true,
            'output_language' => 'id',
            'ai_model' => 'gemini-3.1-flash-lite',
        ]);

        $generatedCv = GeneratedCv::first();
        $this->assertNotNull($generatedCv);
        $response->assertRedirect(route('tailoring.review', $generatedCv));
    }

    public function test_can_promote_instant_check_to_tailoring(): void
    {
        // Promote promotes the session and immediately re-runs tailoring: keep it on the local mock engine.
        config(['services.gemini.key' => 'mock']);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'target_position' => 'Backend Developer',
            'status' => CvStatus::Completed,
            'is_instant_check' => true,
        ]);

        $response = $this->post(route('tailoring.promote', $generatedCv));

        $response->assertRedirect(route('tailoring.review', $generatedCv));

        $generatedCv->refresh();
        $this->assertFalse($generatedCv->is_instant_check);
        $this->assertEquals(CvStatus::Review, $generatedCv->status);
    }

    public function test_can_upload_pdf_cv_and_extract_text_successfully(): void
    {
        Storage::fake('local');
        
        $jobDescriptionText = 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.';
        
        $this->mock(\App\Services\TextExtractor::class, function ($mock) {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn('Ini adalah teks CV hasil ekstraksi PDF yang berisi keahlian Laravel, PHP, dan Database.');
        });
        
        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');
        
        $response = $this->post(route('pdf-checker.store'), [
            'cv_file' => $file,
            'target_position' => 'Senior Developer',
            'job_description' => $jobDescriptionText,
            'output_language' => 'en',
            'ai_model' => 'gemini-2.5-flash',
        ]);
        
        $response->assertRedirect();
        
        $generatedCv = GeneratedCv::first();
        $this->assertNotNull($generatedCv);
        $this->assertTrue($generatedCv->is_instant_check);
        $this->assertEquals('en', $generatedCv->output_language);
        $this->assertEquals('Ini adalah teks CV hasil ekstraksi PDF yang berisi keahlian Laravel, PHP, dan Database.', $generatedCv->raw_cv_text);
        
        $response->assertRedirect(route('tailoring.review', $generatedCv));
    }

    public function test_can_display_master_check_create_page(): void
    {
        $response = $this->get(route('master-check.create'));

        $response->assertStatus(200);
        $response->assertSee('Cek ATS Master Profile');
    }

    public function test_legacy_record_with_null_ai_model_runs_without_errors(): void
    {
        // Force the local mock engine so this test never depends on a live API call.
        config(['services.gemini.key' => 'mock']);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'target_position' => 'Backend Developer',
            'status' => CvStatus::Pending,
            'ai_model' => null,
        ]);

        $response = $this->post(route('tailoring.generate', $generatedCv));

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        
        $generatedCv->refresh();
        $this->assertEquals(CvStatus::Review, $generatedCv->status);
        $this->assertNull($generatedCv->ai_model);
    }

    // ---------------------------------------------------------------------
    // Epic 6: Extra prompt ("prompt tambahan") → re-tailor the wording
    // ---------------------------------------------------------------------

    private function makeTailoredSession(array $overrides = []): GeneratedCv
    {
        return GeneratedCv::create(array_merge([
            'job_description' => 'Kami mencari Staf IT yang menguasai troubleshooting perangkat, jaringan dasar, dan dokumentasi teknis.',
            'target_position' => 'Staf IT / IT Generalist',
            'status' => CvStatus::Review,
            'tailored_cv_raw' => [
                'tailored_cv' => [
                    'summary' => 'Ringkasan versi lama.',
                    'experiences' => [],
                    'education' => [],
                    'skills_highlighted' => [],
                    'projects' => [],
                ],
            ],
        ], $overrides));
    }

    public function test_can_refine_tailoring_with_custom_instructions(): void
    {
        // Force the deterministic local mock engine instead of a real API call.
        config(['services.gemini.key' => 'mock']);

        $generatedCv = $this->makeTailoredSession([
            'tailored_cv_final' => ['tailored_cv' => ['summary' => 'Versi final lama.']],
            'ats_score' => ['score' => ['match_percentage' => 70]],
        ]);

        $instruction = 'Tambahkan keinginan kuat untuk mempelajari hal baru dan teknologi baru.';

        $response = $this->post(route('tailoring.refine', $generatedCv), [
            'custom_instructions' => $instruction,
        ]);

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('success');

        $generatedCv->refresh();
        $this->assertEquals($instruction, $generatedCv->custom_instructions);
        $this->assertEquals(CvStatus::Review, $generatedCv->status);

        // The stale final edit + ATS score must not survive a re-tailoring
        $this->assertNull($generatedCv->tailored_cv_final);
        $this->assertNull($generatedCv->ats_score);

        // Mock engine echoes the applied instruction into the wording so the flow is verifiable
        $this->assertStringContainsString(
            $instruction,
            $generatedCv->tailored_cv_raw['tailored_cv']['summary']
        );
        $this->assertEquals($instruction, $generatedCv->tailored_cv_raw['meta']['custom_instructions']);
    }

    public function test_can_remove_custom_instructions_and_retailor(): void
    {
        config(['services.gemini.key' => 'mock']);

        $generatedCv = $this->makeTailoredSession([
            'custom_instructions' => 'Tambahkan keinginan kuat mempelajari hal baru.',
        ]);

        $response = $this->post(route('tailoring.refine', $generatedCv), [
            'clear' => 1,
        ]);

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('success');

        $generatedCv->refresh();
        $this->assertNull($generatedCv->custom_instructions);
        $this->assertEquals(CvStatus::Review, $generatedCv->status);
        $this->assertNull($generatedCv->tailored_cv_raw['meta']['custom_instructions']);
    }

    public function test_refine_requires_custom_instructions(): void
    {
        $generatedCv = $this->makeTailoredSession();

        $response = $this->post(route('tailoring.refine', $generatedCv), [
            'custom_instructions' => '',
        ]);

        $response->assertSessionHasErrors(['custom_instructions']);

        $generatedCv->refresh();
        $this->assertNull($generatedCv->custom_instructions);
        $this->assertEquals(CvStatus::Review, $generatedCv->status);
    }

    public function test_refine_is_rejected_for_instant_pdf_check_sessions(): void
    {
        $generatedCv = $this->makeTailoredSession([
            'is_instant_check' => true,
            'raw_cv_text' => 'Teks CV hasil ekstraksi PDF.',
            'status' => CvStatus::Completed,
        ]);

        $response = $this->post(route('tailoring.refine', $generatedCv), [
            'custom_instructions' => 'Tambahkan keinginan mempelajari hal baru.',
        ]);

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('error');

        $generatedCv->refresh();
        $this->assertNull($generatedCv->custom_instructions);
        $this->assertEquals(CvStatus::Completed, $generatedCv->status);
    }

    public function test_refine_is_rejected_while_ai_is_still_processing(): void
    {
        $generatedCv = $this->makeTailoredSession(['status' => CvStatus::Tailoring]);

        $response = $this->post(route('tailoring.refine', $generatedCv), [
            'custom_instructions' => 'Tambahkan keinginan mempelajari hal baru.',
        ]);

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('error');

        $generatedCv->refresh();
        $this->assertNull($generatedCv->custom_instructions);
        $this->assertEquals(CvStatus::Tailoring, $generatedCv->status);
    }

    public function test_tailoring_prompt_enforces_summary_skills_and_bullet_budget_rules(): void
    {
        $engine = app(\App\Services\TailoringEngine::class);
        $method = new \ReflectionMethod($engine, 'buildSystemPrompt');

        $prompt = $method->invoke($engine, ['experiences' => [], 'education' => [], 'skills' => [], 'projects' => []], 'JD contoh', 'en');

        $this->assertStringContainsString('SUMMARY QUALITY', $prompt);
        $this->assertStringContainsString('STRICTLY FORBIDDEN generic filler', $prompt);
        $this->assertStringContainsString('high aptitude', $prompt);
        $this->assertStringContainsString('NEVER claim a technology', $prompt);
        $this->assertStringContainsString('SKILLS ORDERING', $prompt);
        $this->assertStringContainsString('BULLET BUDGET PER ROLE', $prompt);
        $this->assertStringContainsString('NEVER add a learning/training claim', $prompt);
        $this->assertStringContainsString('ONE LANGUAGE ONLY', $prompt);
        $this->assertStringContainsString('Data Reconciliation & Validation', $prompt);
    }

    public function test_faithfulness_check_flags_mixed_language_output_for_english_cvs(): void
    {
        $engine = app(\App\Services\TailoringEngine::class);
        $method = new \ReflectionMethod($engine, 'checkFaithfulness');

        $masterProfile = [
            'skills' => [['name' => 'Laravel/PHP']],
            'experiences' => [],
            'projects' => [],
            'education' => [],
        ];

        $indonesianOutput = [
            'tailored_cv' => [
                'summary' => 'Lulusan D3 yang membangun aplikasi web.',
                'experiences' => [],
                'projects' => [
                    [
                        'name' => 'SIMONA (Sistem Monitoring Aging Aktivasi)',
                        'bullet_points' => [['content' => 'Mengembangkan platform pembuat CV berbasis AI.']],
                    ],
                ],
                'skills_highlighted' => [
                    ['category' => 'Database', 'skills' => ['Rekonsiliasi & Validasi Data']],
                ],
            ],
        ];

        $warnings = implode(' | ', $method->invoke($engine, $masterProfile, $indonesianOutput, 'en'));
        $this->assertStringContainsString('Bahasa Indonesia', $warnings);

        $englishOutput = $indonesianOutput;
        $englishOutput['tailored_cv'] = [
            'summary' => 'Informatics graduate who builds full-stack web applications with Laravel.',
            'experiences' => [],
            'projects' => [
                [
                    'name' => 'SIMONA (Service Activation Aging Monitoring System)',
                    'bullet_points' => [['content' => 'Developed an AI-powered CV builder.']],
                ],
            ],
            'skills_highlighted' => [
                ['category' => 'Database', 'skills' => ['Data Reconciliation & Validation']],
            ],
        ];

        $cleanWarnings = implode(' | ', $method->invoke($engine, $masterProfile, $englishOutput, 'en'));
        $this->assertStringNotContainsString('Bahasa Indonesia', $cleanWarnings);
    }

    public function test_faithfulness_check_flags_generic_filler_and_unsupported_technology(): void
    {
        $engine = app(\App\Services\TailoringEngine::class);
        $method = new \ReflectionMethod($engine, 'checkFaithfulness');

        $masterProfile = [
            'skills' => [['name' => 'Laravel/PHP'], ['name' => 'JavaScript']],
            'experiences' => [],
            'projects' => [],
            'education' => [],
        ];

        $decoded = [
            'tailored_cv' => [
                'summary' => 'Highly motivated developer with a proven track record in modern JavaScript frameworks such as React and Next.js.',
                'experiences' => [],
                'projects' => [],
                'skills_highlighted' => [],
            ],
        ];

        $warnings = implode(' | ', $method->invoke($engine, $masterProfile, $decoded));

        $this->assertStringContainsString('highly motivated', $warnings);
        $this->assertStringContainsString('proven track record', $warnings);
        $this->assertStringContainsString("'React'", $warnings);
        $this->assertStringContainsString("'Next.js'", $warnings);

        // Supported technologies must not be flagged
        $supported = $decoded;
        $supported['tailored_cv']['summary'] = 'Built end-to-end web applications with Laravel PHP and JavaScript.';
        $supportedWarnings = implode(' | ', $method->invoke($engine, $masterProfile, $supported));

        $this->assertStringNotContainsString("'Laravel", $supportedWarnings);
        $this->assertStringNotContainsString("'JavaScript'", $supportedWarnings);
    }

    public function test_custom_instructions_are_injected_into_the_ai_prompt(): void
    {
        $engine = app(\App\Services\TailoringEngine::class);
        $method = new \ReflectionMethod($engine, 'buildSystemPrompt');
        $masterProfile = ['experiences' => [], 'education' => [], 'skills' => [], 'projects' => []];

        $prompt = $method->invoke($engine, $masterProfile, 'JD contoh', 'id', 'Tambahkan keinginan mempelajari hal baru.');

        $this->assertStringContainsString('USER ADDITIONAL INSTRUCTIONS', $prompt);
        $this->assertStringContainsString('Tambahkan keinginan mempelajari hal baru.', $prompt);

        $promptWithout = $method->invoke($engine, $masterProfile, 'JD contoh', 'id');

        $this->assertStringNotContainsString('Tambahkan keinginan mempelajari hal baru.', $promptWithout);
        $this->assertStringContainsString('did not add any extra instruction', $promptWithout);
    }

    public function test_review_page_shows_refine_prompt_panel_for_tailoring_sessions(): void
    {
        $generatedCv = $this->makeTailoredSession();

        $response = $this->get(route('tailoring.review', $generatedCv));

        $response->assertOk();
        $response->assertSee('Prompt Tambahan untuk AI');
        $response->assertSee('name="custom_instructions"', false);
        $response->assertSee(route('tailoring.refine', $generatedCv), false);
    }

    public function test_review_page_lists_master_projects_for_reselection(): void
    {
        \App\Models\Project::create([
            'name' => 'SIMONA',
            'tech_stack' => ['Laravel', 'MySQL'],
        ]);

        $generatedCv = $this->makeTailoredSession();

        $response = $this->get(route('tailoring.review', $generatedCv));

        $response->assertOk();
        $response->assertSee('Pilih Ulang Proyek dari Master Profile');
        $response->assertSee('SIMONA');
        $response->assertSee('name="add_project_ids[]"', false);
    }

    public function test_review_page_shows_remove_option_for_tailored_projects(): void
    {
        $generatedCv = $this->makeTailoredSession([
            'tailored_cv_raw' => [
                'tailored_cv' => [
                    'summary' => 'Ringkasan',
                    'experiences' => [],
                    'education' => [],
                    'skills_highlighted' => [],
                    'projects' => [
                        [
                            'source_id' => '1',
                            'name' => 'SIMONA',
                            'tech_stack' => ['Laravel'],
                            'bullet_points' => [['source_bullet_id' => '11', 'content' => 'Poin proyek.']],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->get(route('tailoring.review', $generatedCv));

        $response->assertOk();
        $response->assertSee('Hapus dari CV');
        $response->assertSee('name="projects[0][remove]"', false);
    }
}
