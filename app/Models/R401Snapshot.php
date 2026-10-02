<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class R401Snapshot extends Model
{
    protected $table = 'r4_01_snapshots';

    protected $fillable = [
        'period_month',
        'report_type',
        'kalagayan',
        'purok_selection',
        'format',
        'file_path',
        'file_name',
        'generated_by',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
        ];
    }
}
