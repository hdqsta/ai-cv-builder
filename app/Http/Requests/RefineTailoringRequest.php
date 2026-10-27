<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RefineTailoringRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise the extra prompt before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'custom_instructions' => is_string($this->input('custom_instructions'))
                ? trim($this->input('custom_instructions'))
                : '',
            'clear' => $this->boolean('clear'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'custom_instructions' => ['nullable', 'string', 'max:2000'],
            'clear' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Require an actual instruction unless the user is removing the extra prompt.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('clear')) {
                    return;
                }

                $instructions = (string) $this->input('custom_instructions', '');

                if ($instructions === '') {
                    $validator->errors()->add(
                        'custom_instructions',
                        'Tulis dulu prompt tambahan yang ingin Anda sampaikan ke AI, atau hapus prompt tambahan.'
                    );

                    return;
                }

                if (mb_strlen($instructions) < 5) {
                    $validator->errors()->add(
                        'custom_instructions',
                        'Prompt tambahan terlalu pendek. Jelaskan minimal 5 karakter agar AI paham maksudnya.'
                    );
                }
            },
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
            'custom_instructions.string' => 'Prompt tambahan harus berupa teks.',
            'custom_instructions.max' => 'Prompt tambahan terlalu panjang (maksimal 2000 karakter).',
        ];
    }
}
