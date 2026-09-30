<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'registry',
        'gender',
        'purok',
        'grupo',
        'kapisanan',
        'date_of_baptism',
        'evangelist',
        'date_of_entry',
        'r2_10_eligibility',
        'status',
        'tarheta_status',
        'monthly_code',
        'date_of_birth',
        'filing_status',
        'r2_10_endorsement',
        'reason_of_delistment',
        'log_section',
    ];

    protected function casts(): array
    {
        return [
            'date_of_baptism' => 'date',
            'date_of_entry' => 'date',
            'date_of_birth' => 'date',
        ];
    }
}
