<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purok extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purok_number',
        'purok_name',
        'secretary',
        'manggagawa',
        'ministerial_id',
        'grupo_start',
        'grupo_end',
        'full_purok',
    ];

    protected $casts = [
        'full_purok' => 'boolean',
    ];

    public function secretaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary');
    }

    // Shows the range as "1 to 12"
    public function getSakopNaGrupoAttribute(): string
    {
        return "{$this->grupo_start} to {$this->grupo_end}";
    }
}
