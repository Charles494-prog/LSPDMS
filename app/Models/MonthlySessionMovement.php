<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySessionMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'record_id',
        'action',
        'full_name',
        'registry',
        'gender',
        'kapisanan',
        'date_of_baptism',
        'evangelist',
        'status',
        'monthly_code',
        'previous_purok',
        'previous_grupo',
        'destination_description',
        'previous_values',
        'next_values',
        'description',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_baptism' => 'date',
            'previous_values' => 'array',
            'next_values' => 'array',
            'changed_at' => 'datetime',
        ];
    }
}
