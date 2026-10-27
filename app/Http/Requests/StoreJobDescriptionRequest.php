<?php

namespace App\Http\Requests;

use App\Models\GeneratedCv;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobDescriptionRequest extends FormRequest
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
            'job_description' => ['required', 'string', 'min:100'],
            'target_position' => ['required', 'string', 'max:255'],
            'action' => ['sometimes', 'string', 'in:tailor,instant_check'],
            'output_language' => ['required', 'string', 'in:id,en,auto'],
            'ai_model' => ['required', 'string', 'in:' . implode(',', array_keys(GeneratedCv::AI_MODELS))],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'job_description.required' => 'Teks Job Description wajib diisi.',
            'job_description.string' => 'Job Description harus berupa teks.',
            'job_description.min' => 'Teks Job Description terlalu pendek. Minimal 100 karakter agar AI dapat melakukan tailoring secara akurat.',
            'target_position.required' => 'Posisi yang dilamar wajib diisi.',
            'target_position.string' => 'Posisi yang dilamar harus berupa teks.',
            'target_position.max' => 'Nama posisi terlalu panjang (maksimal 255 karakter).',
            'action.in' => 'Aksi tidak valid.',
            'output_language.required' => 'Bahasa output wajib dipilih.',
            'output_language.in' => 'Bahasa output yang dipilih tidak valid.',
            'ai_model.required' => 'Model AI wajib dipilih.',
            'ai_model.in' => 'Model AI yang dipilih tidak valid.',
        ];
    }
}
