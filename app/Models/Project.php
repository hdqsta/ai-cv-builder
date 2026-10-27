<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tech_stack',
        'link',
    ];

    protected $casts = [
        'tech_stack' => 'array',
    ];

    public function bulletPoints()
    {
        return $this->hasMany(ProjectBullet::class)->orderBy('order');
    }
}
