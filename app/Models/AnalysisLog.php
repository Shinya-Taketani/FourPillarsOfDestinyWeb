<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisLog extends Model
{
    protected $fillable = [
        'target_id',
        'chart_data',
    ];

    protected function casts(): array
    {
        return [
            'chart_data' => 'array',
        ];
    }
}
