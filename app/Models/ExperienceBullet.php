<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExperienceBullet extends Model
{
    use HasFactory;

    protected $fillable = [
        'experience_id',
        'content',
        'order',
    ];

    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }
}
