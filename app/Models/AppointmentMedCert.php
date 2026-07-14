<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentMedCert extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_med_certs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_result_id',
        'cert_no',
        'date_of_issue',
        'findings',
        'remarks',
        'issued_to',
        'physician_name',
        'physician_license',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_issue' => 'date',
    ];

    /**
     * Relationship: Get the parent result record.
     */
    public function result()
    {
        return $this->belongsTo(AppointmentResult::class, 'appointment_result_id');
    }
}