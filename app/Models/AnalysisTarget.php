<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisTarget extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'gender',
        'birthday',
        'birth_place',
        'longitude',
        'memo',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'datetime',
            'longitude' => 'decimal:2',
        ];
    }
}
