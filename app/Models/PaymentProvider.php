<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Import SoftDeletes trait [99]

class PaymentProvider extends Model
{
    use SoftDeletes; // 2. Use SoftDeletes trait [99]

    protected $fillable = [
        'name',
        'logo',
        'qr_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime' // Cast soft delete timestamp [99]
    ];
}