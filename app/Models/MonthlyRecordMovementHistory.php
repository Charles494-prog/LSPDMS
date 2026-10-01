<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRecordMovementHistory extends Model
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
        'previous_purok',
        'previous_grupo',
        'status',
        'monthly_code',
        'destination_status',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_baptism' => 'date',
            'changed_at' => 'datetime',
        ];
    }

}
