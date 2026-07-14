<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaboratoryHistoryScan extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laboratory_history_scans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'history_record_id',
        'label',
        'file_path',
        'certificate_no', 
    ];

    /**
     * Relationship: Get the parent digitized historical record.
     */
    public function record()
    {
        return $this->belongsTo(LaboratoryHistoryRecord::class, 'history_record_id');
    }
}