<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRecordCodeHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'record_id',
        'full_name',
        'registry',
        'gender',
        'kapisanan',
        'date_of_baptism',
        'evangelist',
        'purok',
        'grupo',
        'status',
        'monthly_code',
        'deliberated_at',
        'changed_by',
        'movement_history_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_baptism' => 'date',
            'deliberated_at' => 'date',
        ];
    }
}
