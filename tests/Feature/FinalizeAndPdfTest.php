<?php

namespace Tests\Feature;

use App\Enums\CvStatus;
use App\Models\Certification;
use App\Models\GeneratedCv;
use App\Models\Profile;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalizeAndPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_download_pdf_without_saving_final_cv_first(): void
    {
        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'status' => CvStatus::Review,
            'tailored_cv_raw' => [
                'tailored_cv' => [
                    'summary' => 'Summary mentah AI',
                ]
            ],
            'tailored_cv_final' => null,
        ]);

        $response = $this->get(route('tailoring.pdf', $generatedCv));

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('error', 'Selesaikan review & simpan dulu sebelum download PDF.');
    }

    public function test_can_finalize_tailored_cv_edits(): void
    {
        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'status' => CvStatus::Review,
            'tailored_cv_raw' => [
                'tailored_cv' => [
                    'summary' => 'Summary mentah AI',
                ]
            ],
            'tailored_cv_final' => null,
        ]);

        $payload = [
            'summary' => 'Ringkasan eksekutif disunting oleh user',
            'experiences' => [
                [
                    'source_id' => '1',
                    'position' => 'Senior Backend Engineer',
                    'company' => 'PT Solusi Teknologi',
                    'start_date' => '2022-01-01',
                    'end_date' => null,
                    'bullet_points' => [
                        [
                            'source_bullet_id' => '101',
                            'content' => 'Memimpin pengembangan microservice dengan Laravel & Redis.'
                        ]
                    ]
                ]
            ],
            'education' => [
                [
                    'source_id' => '1',
                    'institution' => 'Universitas Indonesia',
                    'degree' => 'Sarjana Komputer',
                    'start_date' => '2017-09-01',
                    'end_date' => '2021-07-01'
                ]
            ],
            'skills_highlighted' => [
                [
                    'category' => 'Backend',
                    'skills' => ['PHP', 'Laravel', 'PostgreSQL']
                ]
            ]
        ];

        $response = $this->post(route('tailoring.finalize', $generatedCv), $payload);

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('success');

        $generatedCv->refresh();
        $this->assertEquals(CvStatus::Review, $generatedCv->status);
        $this->assertNotNull($generatedCv->tailored_cv_final);
        $this->assertEquals('Ringkasan eksekutif disunting oleh user', $generatedCv->tailored_cv_final['tailored_cv']['summary']);
    }

    public function test_finalize_can_remove_a_project_from_the_cv(): void
    {
        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'status' => CvStatus::Review,
            'tailored_cv_raw' => ['tailored_cv' => ['summary' => 'Raw summary']],
        ]);

        $response = $this->post(route('tailoring.finalize', $generatedCv), [
            'summary' => 'Ringkasan final',
            'experiences' => [],
            'education' => [],
            'skills_highlighted' => [],
            'projects' => [
                [
                    'source_id' => '1',
                    'name' => 'Proyek Tidak Relevan',
                    'remove' => 1,
                    'bullet_points' => [
                        ['source_bullet_id' => '11', 'content' => 'Poin proyek yang dikeluarkan.'],
                    ],
                ],
                [
                    'source_id' => '2',
                    'name' => 'Proyek Dipertahankan',
                    'bullet_points' => [
                        ['source_bullet_id' => '21', 'content' => 'Poin proyek yang tetap dipakai.'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('success');

        $generatedCv->refresh();
        $projects = $generatedCv->tailored_cv_final['tailored_cv']['projects'];

        $this->assertCount(1, $projects);
        $this->assertSame('2', $projects[0]['source_id']);
        $this->assertArrayNotHasKey('remove', $projects[0]);
    }

    public function test_finalize_appends_reselected_master_profile_project(): void
    {
        config(['services.gemini.key' => 'mock']);

        $project = Project::create([
            'name' => 'SIMONA',
            'tech_stack' => ['Laravel', 'MySQL'],
        ]);

        $bullet = $project->bulletPoints()->create([
            'content' => 'Membangun dashboard monitoring aktivasi layanan.',
            'order' => 0,
        ]);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'status' => CvStatus::Review,
            'output_language' => 'en',
            'tailored_cv_raw' => ['tailored_cv' => ['summary' => 'Raw summary']],
        ]);

        $response = $this->post(route('tailoring.finalize', $generatedCv), [
            'summary' => 'Ringkasan final',
            'experiences' => [],
            'education' => [],
            'skills_highlighted' => [],
            'projects' => [],
            'add_project_ids' => [$project->id],
        ]);

        $response->assertRedirect(route('tailoring.review', $generatedCv));

        $generatedCv->refresh();
        $final = $generatedCv->tailored_cv_final['tailored_cv'];
        $projects = $final['projects'];

        $this->assertCount(1, $projects);
        $this->assertSame((string) $project->id, $projects[0]['source_id']);
        $this->assertSame('SIMONA', $projects[0]['name']);
        $this->assertSame(['Laravel', 'MySQL'], $projects[0]['tech_stack']);
        $this->assertSame((string) $bullet->id, $projects[0]['bullet_points'][0]['source_bullet_id']);

        // Added projects are re-tailored by the AI instead of copied verbatim from the Master Profile
        $this->assertStringStartsWith('[Tailored en]', $projects[0]['bullet_points'][0]['content']);
        $this->assertArrayNotHasKey('add_project_ids', $final);
    }

    public function test_finalize_tailors_new_projects_in_the_session_language(): void
    {
        config(['services.gemini.key' => 'mock']);

        $project = Project::create([
            'name' => 'SIMONA',
            'tech_stack' => ['Laravel'],
        ]);

        $project->bulletPoints()->create([
            'content' => 'Membangun dashboard monitoring aktivasi layanan.',
            'order' => 0,
        ]);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'We are hiring a Full Stack Developer with Laravel and MySQL.',
            'status' => CvStatus::Review,
            'output_language' => 'en',
            'tailored_cv_raw' => ['tailored_cv' => ['summary' => 'Raw summary']],
        ]);

        $response = $this->post(route('tailoring.finalize', $generatedCv), [
            'summary' => 'Final summary',
            'experiences' => [],
            'education' => [],
            'skills_highlighted' => [],
            'projects' => [],
            'add_project_ids' => [$project->id],
        ]);

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('success');

        $generatedCv->refresh();
        $project0 = $generatedCv->tailored_cv_final['tailored_cv']['projects'][0];

        $this->assertStringStartsWith('[Tailored en]', $project0['bullet_points'][0]['content']);
    }

    public function test_retailor_key_projects_rewrites_saved_projects(): void
    {
        config(['services.gemini.key' => 'mock']);

        $project = Project::create([
            'name' => 'SIMONA (Sistem Monitoring Aging Aktivasi)',
            'tech_stack' => ['Laravel'],
        ]);

        $bullet = $project->bulletPoints()->create([
            'content' => 'Membangun dashboard monitoring aktivasi layanan.',
            'order' => 0,
        ]);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'We are hiring a Full Stack Developer with Laravel and MySQL.',
            'status' => CvStatus::Review,
            'output_language' => 'en',
            'tailored_cv_raw' => ['tailored_cv' => ['summary' => 'Raw summary']],
            'tailored_cv_final' => [
                'tailored_cv' => [
                    'summary' => 'Final summary',
                    'experiences' => [],
                    'education' => [],
                    'skills_highlighted' => [],
                    'projects' => [
                        [
                            'source_id' => (string) $project->id,
                            'name' => 'SIMONA (Sistem Monitoring Aging Aktivasi)',
                            'tech_stack' => ['Laravel'],
                            'bullet_points' => [
                                ['source_bullet_id' => (string) $bullet->id, 'content' => 'Membangun dashboard monitoring aktivasi layanan.'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->post(route('tailoring.projects.retailor', $generatedCv));

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('success');

        $generatedCv->refresh();
        $retailored = $generatedCv->tailored_cv_final['tailored_cv']['projects'][0];

        $this->assertSame((string) $project->id, $retailored['source_id']);
        $this->assertSame((string) $bullet->id, $retailored['bullet_points'][0]['source_bullet_id']);
        $this->assertStringStartsWith('[Tailored en]', $retailored['bullet_points'][0]['content']);
    }

    public function test_retailor_key_projects_requires_existing_projects(): void
    {
        $generatedCv = GeneratedCv::create([
            'job_description' => 'We are hiring a Full Stack Developer with Laravel and MySQL.',
            'status' => CvStatus::Review,
            'output_language' => 'en',
            'tailored_cv_raw' => ['tailored_cv' => ['summary' => 'Raw summary']],
            'tailored_cv_final' => [
                'tailored_cv' => [
                    'summary' => 'Final summary',
                    'experiences' => [],
                    'education' => [],
                    'skills_highlighted' => [],
                    'projects' => [],
                ],
            ],
        ]);

        $response = $this->post(route('tailoring.projects.retailor', $generatedCv));

        $response->assertRedirect(route('tailoring.review', $generatedCv));
        $response->assertSessionHas('error');
    }

    public function test_finalize_does_not_duplicate_an_already_included_project(): void
    {
        $project = Project::create([
            'name' => 'SIMONA',
            'tech_stack' => ['Laravel'],
        ]);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'status' => CvStatus::Review,
            'tailored_cv_raw' => ['tailored_cv' => ['summary' => 'Raw summary']],
        ]);

        $this->post(route('tailoring.finalize', $generatedCv), [
            'summary' => 'Ringkasan final',
            'experiences' => [],
            'education' => [],
            'skills_highlighted' => [],
            'projects' => [
                [
                    'source_id' => (string) $project->id,
                    'name' => 'SIMONA',
                    'bullet_points' => [],
                ],
            ],
            'add_project_ids' => [$project->id],
        ]);

        $generatedCv->refresh();
        $projects = $generatedCv->tailored_cv_final['tailored_cv']['projects'];

        $this->assertCount(1, $projects);
        $this->assertSame((string) $project->id, $projects[0]['source_id']);
    }

    public function test_can_download_pdf_after_finalizing(): void
    {
        Profile::create([
            'name' => 'Hatta Siddiq',
            'email' => 'hatta@example.com',
            'phone' => '+628123456789',
            'location' => 'Jakarta, Indonesia',
            'summary' => 'Bio profil',
            'links' => ['https://linkedin.com/in/hatta'],
        ]);

        Certification::create([
            'name' => 'Laravel Certified Developer',
            'issuer' => 'Laravel LLC',
            'year' => '2025',
        ]);

        $generatedCv = GeneratedCv::create([
            'job_description' => 'Kami mencari Backend Engineer yang berpengalaman untuk merancang, membangun, dan memelihara aplikasi web berskala tinggi menggunakan Laravel.',
            'status' => CvStatus::Review,
            'tailored_cv_raw' => [
                'tailored_cv' => ['summary' => 'Raw summary']
            ],
            'tailored_cv_final' => [
                'tailored_cv' => [
                    'summary' => 'Ringkasan final Hatta Siddiq',
                    'experiences' => [],
                    'education' => [],
                    'skills_highlighted' => [],
                    'projects' => [],
                ]
            ],
        ]);

        $response = $this->get(route('tailoring.pdf', $generatedCv));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
