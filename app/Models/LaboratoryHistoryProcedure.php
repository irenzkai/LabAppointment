<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaboratoryHistoryProcedure extends Model
{
    protected $table = 'laboratory_history_procedures';

    protected $fillable = [
        'history_record_id',
        'procedure_name',
    ];

    public function record()
    {
        return $this->belongsTo(LaboratoryHistoryRecord::class, 'history_record_id');
    }
}