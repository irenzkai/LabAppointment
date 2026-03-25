<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dependent extends Model
{
    protected $fillable = ['user_id', 'name', 'birthdate', 'sex', 'phone', 'relationship', 'address'];
    protected $casts = ['birthdate' => 'date'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
