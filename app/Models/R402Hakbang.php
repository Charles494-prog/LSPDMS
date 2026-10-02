<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class R402Hakbang extends Model
{
    protected $table = 'r4_02_hakbangs';

    protected $fillable = [
        'status',
        'code',
        'description',
    ];
}
