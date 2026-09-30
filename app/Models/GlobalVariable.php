<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalVariable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'locale',
        'lcode',
        'district',
        'dcode',
        's_number',
        'pastor_destinado',
        'ksp_pd',
        'kalihim_ms',
        'kalihim_uwp',
        'baptism_requirement',
        'entry_years_requirement',
        'dateofbaptism_requirement_boolean',
        'dateofentryrequirement_boolean',
        'require_otp',
        'allow_forgot_password',
    ];

    protected function casts(): array
    {
        return [
            'baptism_requirement' => 'date',
            'entry_years_requirement' => 'integer',
            'dateofbaptism_requirement_boolean' => 'boolean',
            'dateofentryrequirement_boolean' => 'boolean',
            'require_otp' => 'boolean',
            'allow_forgot_password' => 'boolean',
        ];
    }
}
