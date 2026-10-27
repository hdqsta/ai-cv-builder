<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveFinalCvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'experiences' => ['nullable', 'array'],
            'experiences.*.source_id' => ['required', 'string'],
            'experiences.*.company' => ['required', 'string'],
            'experiences.*.position' => ['required', 'string'],
            'experiences.*.start_date' => ['required', 'string'],
            'experiences.*.end_date' => ['nullable', 'string'],
            'experiences.*.bullet_points' => ['nullable', 'array'],
            'experiences.*.bullet_points.*.source_bullet_id' => ['required', 'string'],
            'experiences.*.bullet_points.*.content' => ['required', 'string'],
            'skills_highlighted' => ['nullable', 'array'],
            'skills_highlighted.*.category' => ['required', 'string'],
            'skills_highlighted.*.skills' => ['nullable', 'array'],
            'skills_highlighted.*.skills.*' => ['required', 'string'],
            'projects' => ['nullable', 'array'],
            'projects.*.source_id' => ['required', 'string'],
            'projects.*.name' => ['required', 'string'],
            'projects.*.remove' => ['nullable', 'boolean'],
            'projects.*.tech_stack' => ['nullable', 'array'],
            'projects.*.bullet_points' => ['nullable', 'array'],
            'projects.*.bullet_points.*.source_bullet_id' => ['required', 'string'],
            'projects.*.bullet_points.*.content' => ['required', 'string'],
            'add_project_ids' => ['nullable', 'array'],
            'add_project_ids.*' => ['nullable', 'integer'],
            'education' => ['nullable', 'array'],
            'education.*.source_id' => ['required', 'string'],
            'education.*.institution' => ['required', 'string'],
            'education.*.degree' => ['required', 'string'],
            'education.*.start_date' => ['required', 'string'],
            'education.*.end_date' => ['nullable', 'string'],
            'education.*.notes' => ['nullable', 'string'],
        ];
    }
}
