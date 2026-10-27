<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;

class MasterProfileFormatter
{
    /**
     * Format the complete Master Profile into the exact JSON payload contract for AI Tailoring.
     *
     * @return array
     */
    public function toTailoringPayload(): array
    {
        $profile = Profile::first();
        $experiences = Experience::with('bulletPoints')->get();
        $education = Education::all();
        $skills = Skill::all();
        $projects = Project::with('bulletPoints')->get();
        $certifications = Certification::all();

        return [
            'master_profile' => [
                'profile' => [
                    'name' => (string) ($profile->name ?? ''),
                    'email' => (string) ($profile->email ?? ''),
                    'phone' => (string) ($profile->phone ?? ''),
                    'location' => (string) ($profile->location ?? ''),
                    'links' => is_array($profile->links ?? null) ? array_values($profile->links) : [],
                ],
                'experiences' => $experiences->map(function (Experience $exp) {
                    return [
                        'id' => (string) $exp->id,
                        'company' => (string) $exp->company,
                        'position' => (string) $exp->position,
                        'start_date' => $exp->start_date ? $exp->start_date->format('Y-m-d') : '',
                        'end_date' => $exp->end_date ? $exp->end_date->format('Y-m-d') : null,
                        'bullet_points' => $exp->bulletPoints->map(function ($bullet) {
                            return [
                                'id' => (string) $bullet->id,
                                'content' => (string) $bullet->content,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
                'education' => $education->map(function (Education $edu) {
                    return [
                        'id' => (string) $edu->id,
                        'institution' => (string) $edu->institution,
                        'degree' => (string) $edu->degree,
                        'start_date' => $edu->start_date ? $edu->start_date->format('Y-m-d') : '',
                        'end_date' => $edu->end_date ? $edu->end_date->format('Y-m-d') : null,
                        'notes' => $edu->notes ? (string) $edu->notes : null,
                    ];
                })->values()->all(),
                'skills' => $skills->map(function (Skill $skill) {
                    return [
                        'id' => (string) $skill->id,
                        'name' => (string) $skill->name,
                        'category' => (string) $skill->category,
                    ];
                })->values()->all(),
                'projects' => $projects->map(function (Project $proj) {
                    return [
                        'id' => (string) $proj->id,
                        'name' => (string) $proj->name,
                        'bullet_points' => $proj->bulletPoints->map(function ($bullet) {
                            return [
                                'id' => (string) $bullet->id,
                                'content' => (string) $bullet->content,
                            ];
                        })->values()->all(),
                        'tech_stack' => is_array($proj->tech_stack) ? array_values($proj->tech_stack) : [],
                        'link' => $proj->link ? (string) $proj->link : null,
                    ];
                })->values()->all(),
                'certifications' => $certifications->map(function (Certification $cert) {
                    return [
                        'id' => (string) $cert->id,
                        'name' => (string) $cert->name,
                        'issuer' => (string) $cert->issuer,
                        'year' => (string) $cert->year,
                    ];
                })->values()->all(),
            ],
        ];
    }

    /**
     * Format a stored `tailored_cv_final` payload (Call 1 result, possibly user-edited)
     * into the compact `tailored_cv_data` shape required by Call 2 (ATS Scoring).
     *
     * Per JSON_Contract_Tailoring_Scoring.md:
     *   - summary: string
     *   - experiences: [{ position, company, bullet_points: [string] }]
     *   - skills: [string]  (flat list, derived from skills_highlighted)
     *   - projects: [{ name, bullet_points: [string] }]
     *
     * All source_id / source_bullet_id are STRIPPED — Call 2 only reads content,
     * it does not trace provenance.
     *
     * @param array $tailoredFinal  The `tailored_cv_final` column value
     *                              (must contain `tailored_cv` key).
     * @return array  The `tailored_cv_data` portion of Call 2 input.
     */
    public function toScoringPayload(array $tailoredFinal): array
    {
        $tailored = $tailoredFinal['tailored_cv'] ?? [];

        // Experiences: keep only position, company, bullet_points (as plain strings)
        $experiences = [];
        foreach (($tailored['experiences'] ?? []) as $exp) {
            $experiences[] = [
                'position' => (string) ($exp['position'] ?? ''),
                'company' => (string) ($exp['company'] ?? ''),
                'bullet_points' => array_values(array_map(
                    fn ($b) => (string) (is_array($b) ? ($b['content'] ?? '') : $b),
                    (array) ($exp['bullet_points'] ?? [])
                )),
            ];
        }

        // Skills: flatten skills_highlighted groups into a single flat string list
        $skills = [];
        foreach (($tailored['skills_highlighted'] ?? []) as $group) {
            foreach ((array) ($group['skills'] ?? []) as $skName) {
                $name = trim((string) $skName);
                if ($name !== '') {
                    $skills[] = $name;
                }
            }
        }

        // Projects: keep only name + bullet_points (plain strings). tech_stack dropped
        // because the Call 2 contract does not include it.
        $projects = [];
        foreach (($tailored['projects'] ?? []) as $proj) {
            $projects[] = [
                'name' => (string) ($proj['name'] ?? ''),
                'bullet_points' => array_values(array_map(
                    fn ($b) => (string) (is_array($b) ? ($b['content'] ?? '') : $b),
                    (array) ($proj['bullet_points'] ?? [])
                )),
            ];
        }

        return [
            'tailored_cv_data' => [
                'summary' => (string) ($tailored['summary'] ?? ''),
                'experiences' => $experiences,
                'skills' => $skills,
                'projects' => $projects,
            ],
        ];
    }

    /**
     * Format the current Master Profile (un-tailored) into the compact shape
     * required by Call 2 (ATS Scoring) for instant direct checks.
     *
     * @return array The `tailored_cv_data` portion of Call 2 input.
     */
    public function toScoringPayloadFromMaster(): array
    {
        $profile = Profile::first();
        $experiences = Experience::with('bulletPoints')->get();
        $skills = Skill::all();
        $projects = Project::with('bulletPoints')->get();

        $experiencesData = [];
        foreach ($experiences as $exp) {
            $experiencesData[] = [
                'position' => (string) $exp->position,
                'company' => (string) $exp->company,
                'bullet_points' => $exp->bulletPoints->pluck('content')->map('strval')->values()->all(),
            ];
        }

        $skillsData = $skills->pluck('name')->map('strval')->values()->all();

        $projectsData = [];
        foreach ($projects as $proj) {
            $projectsData[] = [
                'name' => (string) $proj->name,
                'bullet_points' => $proj->bulletPoints->pluck('content')->map('strval')->values()->all(),
            ];
        }

        return [
            'tailored_cv_data' => [
                'summary' => (string) ($profile->summary ?? ''),
                'experiences' => $experiencesData,
                'skills' => $skillsData,
                'projects' => $projectsData,
            ],
        ];
    }
}

