<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecretaryTodo extends Model
{
    protected $fillable = [
        'user_id',
        'scope',
        'title',
        'deadline',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
