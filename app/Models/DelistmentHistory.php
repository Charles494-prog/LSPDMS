<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelistmentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'record_id',
        'delisted_by_user_id',
        'delisted_by',
        'reason',
        'delisted_at',
    ];

    protected function casts(): array
    {
        return ['delisted_at' => 'datetime'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function delistedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delisted_by_user_id');
    }
}
