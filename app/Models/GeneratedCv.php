<?php

namespace App\Models;

use App\Enums\CvStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedCv extends Model
{
    use HasFactory;

    protected $table = 'generated_cvs';

    public const OUTPUT_LANG_ID = 'id';
    public const OUTPUT_LANG_EN = 'en';

    public const AI_MODELS = [
        'gemini-2.5-flash' => [
            'label'       => 'Gemini 2.5 Flash',
            'description' => 'Lebih cerdas & konteks lebih dalam. Cocok untuk CV dengan pengalaman kompleks atau JD yang detail. Sedikit lebih lambat.',
        ],
        'gemini-3.1-flash-lite' => [
            'label'       => 'Gemini 3.1 Flash Lite',
            'description' => 'Lebih cepat & ringan. Cocok untuk draft cepat atau JD yang straightforward. Default sistem.',
        ],
    ];

    protected $fillable = [
        'job_description',
        'target_position',
        'raw_cv_text',
        'tailored_cv_raw',
        'tailored_cv_final',
        'ats_score',
        'status',
        'is_instant_check',
        'output_language',
        'ai_model',
        'custom_instructions',
    ];

    protected $casts = [
        'tailored_cv_raw' => 'array',
        'tailored_cv_final' => 'array',
        'ats_score' => 'array',
        'status' => CvStatus::class,
        'is_instant_check' => 'boolean',
    ];
}
