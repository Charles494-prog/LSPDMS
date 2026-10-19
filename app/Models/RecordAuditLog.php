<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'record_id',
        'record_name',
        'actor_email',
        'action',
        'summary',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
