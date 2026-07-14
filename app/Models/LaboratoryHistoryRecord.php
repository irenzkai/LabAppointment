<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaboratoryHistoryRecord extends Model
{
    protected $table = 'laboratory_history_records';

    protected $fillable = [
        'laboratory_history_id',
        'date_of_record',
        'requested_by',
        'patient_first_name',
        'patient_middle_name',
        'patient_last_name',
        'patient_name',
        'age',
        'sex',
        'patient_street',
        'patient_barangay',
        'patient_city',
        'patient_province',
        'patient_address',
    ];

    protected $casts = [
        'date_of_record' => 'date',
    ];

    public function laboratoryHistory()
    {
        return $this->belongsTo(LaboratoryHistory::class, 'laboratory_history_id');
    }

    public function scans()
    {
        return $this->hasMany(LaboratoryHistoryScan::class, 'history_record_id');
    }

    public function procedures()
    {
        return $this->hasMany(LaboratoryHistoryProcedure::class, 'history_record_id');
    }
}