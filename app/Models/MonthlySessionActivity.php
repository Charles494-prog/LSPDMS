<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySessionActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'session_movement_id',
        'record_id',
        'action',
        'direction',
        'full_name',
        'registry',
        'gender',
        'kapisanan',
        'date_of_baptism',
        'evangelist',
        'status',
        'monthly_code',
        'purok',
        'grupo',
        'description',
        'changed_by',
        'changed_at',
        'previous_values',
        'next_values',
    ];

    protected function casts(): array
    {
        return [
            'date_of_baptism' => 'date',
            'changed_at' => 'datetime',
            'previous_values' => 'array',
            'next_values' => 'array',
        ];
    }
}
