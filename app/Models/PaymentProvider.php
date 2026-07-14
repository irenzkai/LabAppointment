<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'qr_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}