<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyMovementSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'active_key',
        'status',
        'period_month',
        'started_by',
        'started_at',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'period_month' => 'date',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MonthlySessionMovement::class, 'session_id');
    }
}
