<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRecordSnapshot extends Model
{
    protected $fillable = [
        'month',
        'purok_number',
        'ms_count',
        'uwp_count',
        'mk_count',
        'movement_counts',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'movement_counts' => 'array',
        ];
    }
}
