<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 'registry', 'purok', 'grupo', 'kapisanan',
        'date_of_baptism', 'evangelist', 'date_of_entry',
        'r2_10_eligibility', 'kalagayan', 'r2_10_endorsement', 'notes',
    ];

    protected $casts = [
        'date_of_baptism' => 'date',
        'date_of_entry' => 'date',
        'r2_10_eligibility' => 'boolean',
        'r2_10_endorsement' => 'boolean',
    ];
}