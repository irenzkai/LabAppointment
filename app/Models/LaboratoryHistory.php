<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaboratoryHistory extends Model
{
    protected $fillable = ['user_id', 'permission_status', 'dynamic_data'];
    protected $casts = ['dynamic_data' => 'array'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
