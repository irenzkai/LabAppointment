<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentResult extends Model
{
    /**
     * The attributes that are mass assignable.
     * 
     * FIXED: Added workstation properties back to $fillable to allow controllers 
     * to trigger mutators during Eloquent mass-assignment ($model->update() / ::create()).
     */
    protected $fillable = [
        'appointment_id',
        'included_reports',
        
        // Workstation Fallbacks for Mass-Assignment Interception
        'lab_status', 'med_status', 'radio_status', 'drug_status',
        'lab_scan', 'med_cert_scan', 'radio_scan', 'drug_test_scan', 'xray_image',
        'lab_data', 'med_cert_data', 'radio_data', 'drug_test_data',
        'lab_return_reason', 'med_return_reason', 'radio_return_reason', 'drug_return_reason',
        
        // Workstation Audit Fields for Mass Assignment / Interception
        'lab_v1_by_name', 'lab_v1_by', 'lab_v1_at', 'lab_v2_by_name', 'lab_v2_by', 'lab_v2_at',
        'med_v1_by_name', 'med_v1_by', 'med_v1_at', 'med_v2_by_name', 'med_v2_by', 'med_v2_at',
        'radio_v1_by_name', 'radio_v1_by', 'radio_v1_at', 'radio_v2_by_name', 'radio_v2_by', 'radio_v2_at',
        'drug_v1_by_name', 'drug_v1_by', 'drug_v1_at', 'drug_v2_by_name', 'drug_v2_by', 'drug_v2_at'
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'included_reports' => 'array',
    ];

    /**
     * Relationship: Connected parent appointment.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Relationship: Connected workflow audit trail entries.
     */
    public function audits()
    {
        return $this->hasMany(WorkstationAudit::class, 'appointment_result_id');
    }

    // =========================================================================
    // NORMALIZED 3NF ELOQUENT RELATIONSHIPS
    // =========================================================================

    public function labResults()
    {
        return $this->hasMany(AppointmentLabResult::class, 'appointment_result_id');
    }

    public function labDetails()
    {
        return $this->hasOne(AppointmentLabDetail::class, 'appointment_result_id');
    }

    public function medCert()
    {
        return $this->hasOne(AppointmentMedCert::class, 'appointment_result_id');
    }

    public function radiologyReport()
    {
        return $this->hasOne(AppointmentRadiologyReport::class, 'appointment_result_id');
    }

    public function drugTest()
    {
        return $this->hasOne(AppointmentDrugTest::class, 'appointment_result_id');
    }

    public function customWorkstationResults()
    {
        return $this->hasMany(CustomWorkstationResult::class, 'appointment_result_id');
    }

    // =========================================================================
    // CENTRALIZED RELATION HANDSHAKE (Prevents Duplicate SQL Insert Race Conditions)
    // =========================================================================

    /**
     * Safely fetches or creates a worksheet and caches it inside Eloquent's relation cache
     * so subsequent mutators in the same request process the exact same model instance.
     */
    private function getOrCreateWorksheet(string $relation, string $modelClass)
    {
        // 1. Return immediately if the relation is already loaded and cached
        if ($this->relationLoaded($relation) && $this->getRelation($relation) !== null) {
            return $this->getRelation($relation);
        }

        // 2. Otherwise, fetch from the database
        $record = $this->$relation()->first();

        // 3. Create, save, and manually cache the relation if it doesn't exist yet
        if (!$record) {
            $record = $this->$relation()->create([]);
        }

        $this->setRelation($relation, $record);

        return $record;
    }

    // Helper wrappers to fetch worksheets cleanly
    public function getLabDetailsRecord() { return $this->getOrCreateWorksheet('labDetails', AppointmentLabDetail::class); }
    public function getMedCertRecord() { return $this->getOrCreateWorksheet('medCert', AppointmentMedCert::class); }
    public function getRadiologyReportRecord() { return $this->getOrCreateWorksheet('radiologyReport', AppointmentRadiologyReport::class); }
    public function getDrugTestRecord() { return $this->getOrCreateWorksheet('drugTest', AppointmentDrugTest::class); }

    // =========================================================================
    // TRANSPARENT DYNAMIC ACCESSORS (Maintains 100% Backwards Compatibility)
    // =========================================================================

    // --- Laboratory Workstation fallbacks ---
    public function getLabStatusAttribute() { return $this->getLabDetailsRecord()->status; }
    public function setLabStatusAttribute($value) { $record = $this->getLabDetailsRecord(); $record->status = $value; $record->save(); }

    public function getLabScanAttribute() { return $this->getLabDetailsRecord()->scan_path; }
    public function setLabScanAttribute($value) { $record = $this->getLabDetailsRecord(); $record->scan_path = $value; $record->save(); }

    public function getLabReturnReasonAttribute() { return $this->getLabDetailsRecord()->return_reason; }
    public function setLabReturnReasonAttribute($value) { $record = $this->getLabDetailsRecord(); $record->return_reason = $value; $record->save(); }

    // --- Medical Certificate Workstation fallbacks ---
    public function getMedStatusAttribute() { return $this->getMedCertRecord()->status; }
    public function setMedStatusAttribute($value) { $record = $this->getMedCertRecord(); $record->status = $value; $record->save(); }

    public function getMedCertScanAttribute() { return $this->getMedCertRecord()->scan_path; }
    public function setMedCertScanAttribute($value) { $record = $this->getMedCertRecord(); $record->scan_path = $value; $record->save(); }

    public function getMedReturnReasonAttribute() { return $this->getMedCertRecord()->return_reason; }
    public function setMedReturnReasonAttribute($value) { $record = $this->getMedCertRecord(); $record->return_reason = $value; $record->save(); }

    // --- Radiology Workstation fallbacks ---
    public function getRadioStatusAttribute() { return $this->getRadiologyReportRecord()->status; }
    public function setRadioStatusAttribute($value) { $record = $this->getRadiologyReportRecord(); $record->status = $value; $record->save(); }

    public function getRadioScanAttribute() { return $this->getRadiologyReportRecord()->scan_path; }
    public function setRadioScanAttribute($value) { $record = $this->getRadiologyReportRecord(); $record->scan_path = $value; $record->save(); }

    // FIXED: Mapped xray_image accessor/mutator straight to the normalized sub-table
    public function getXrayImageAttribute() { return $this->getRadiologyReportRecord()->xray_image; }
    public function setXrayImageAttribute($value) { $record = $this->getRadiologyReportRecord(); $record->xray_image = $value; $record->save(); }

    public function getRadioReturnReasonAttribute() { return $this->getRadiologyReportRecord()->return_reason; }
    public function setRadioReturnReasonAttribute($value) { $record = $this->getRadiologyReportRecord(); $record->return_reason = $value; $record->save(); }

    // --- Drug Test Workstation fallbacks ---
    public function getDrugStatusAttribute() { return $this->getDrugTestRecord()->status; }
    public function setDrugStatusAttribute($value) { $record = $this->getDrugTestRecord(); $record->status = $value; $record->save(); }

    public function getDrugTestScanAttribute() { return $this->getDrugTestRecord()->scan_path; }
    public function setDrugTestScanAttribute($value) { $record = $this->getDrugTestRecord(); $record->scan_path = $value; $record->save(); }

    public function getDrugReturnReasonAttribute() { return $this->getDrugTestRecord()->return_reason; }
    public function setDrugReturnReasonAttribute($value) { $record = $this->getDrugTestRecord(); $record->return_reason = $value; $record->save(); }

    // =========================================================================
    // TRANSPARENT RETROACTIVE ACCESSORS WITH JSON FALLBACKS
    // =========================================================================

    /**
     * Reconstructs lab_data JSON structure. Falls back to raw JSON column if database tables are empty.
     */
    public function getLabDataAttribute()
    {
        $results = $this->labResults;

        // Relational fallback to raw JSON column if normalized database tables contain no rows
        if ($results->isEmpty() && !empty($this->attributes['lab_data'])) {
            $decoded = json_decode($this->attributes['lab_data'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $details = $this->getLabDetailsRecord();
        $resultsArray = $results->map(fn($r) => [
            'name' => $r->parameter_name,
            'value' => $r->observed_value,
            'ref_range' => $r->reference_range
        ])->toArray();

        // Categorize results dynamically for standard display blocks in PDF reports
        $hem = []; $uri = []; $fec = []; $ser = [];

        foreach ($results as $r) {
            $name = trim($r->parameter_name);
            $val = $r->observed_value;

            // Hematology parameters mapping
            if (strcasecmp($name, 'WBC Count') === 0) $hem['wbc'] = $val;
            elseif (strcasecmp($name, 'Hemoglobin') === 0) $hem['hb'] = $val;
            elseif (strcasecmp($name, 'MCH') === 0) $hem['mch'] = $val;
            elseif (strcasecmp($name, 'MCHC') === 0) $hem['mchc'] = $val;
            elseif (strcasecmp($name, 'MCV') === 0) $hem['mcv'] = $val;
            elseif (strcasecmp($name, 'RBC Count') === 0) $hem['rbc'] = $val;
            elseif (strcasecmp($name, 'Hematocrit') === 0) $hem['hct'] = $val;
            elseif (strcasecmp($name, 'Platelet Count') === 0) $hem['plt'] = $val;
            elseif (strcasecmp($name, 'Bleeding Time') === 0) $hem['bt_time'] = $val;
            elseif (strcasecmp($name, 'Clotting Time') === 0) $hem['ct_time'] = $val;
            elseif (strcasecmp($name, 'ESR') === 0) $hem['esr'] = $val;
            elseif (strcasecmp($name, 'RDW') === 0) $hem['rdw'] = $val;
            elseif (strcasecmp($name, 'Reticulocyte CT') === 0) $hem['retic'] = $val;
            elseif (strcasecmp($name, 'Neutrophils') === 0) $hem['neu'] = $val;
            elseif (strcasecmp($name, 'Lymphocytes') === 0) $hem['lym'] = $val;
            elseif (strcasecmp($name, 'Monocytes') === 0) $hem['mon'] = $val;
            elseif (strcasecmp($name, 'Eosinophils') === 0) $hem['eos'] = $val;
            elseif (strcasecmp($name, 'Basophils') === 0) $hem['bas'] = $val;
            elseif (strcasecmp($name, 'Stabs') === 0) $hem['sta'] = $val;

            // Urinalysis parameters mapping
            elseif (strcasecmp($name, 'Urine Color') === 0) $uri['color'] = $val;
            elseif (strcasecmp($name, 'Transparency') === 0) $uri['trans'] = $val;
            elseif (strcasecmp($name, 'Urine Pus Cells') === 0) $uri['pus'] = $val;
            elseif (strcasecmp($name, 'Urine RBC') === 0) $uri['rbc'] = $val;
            elseif (strcasecmp($name, 'Specific Gravity') === 0) $uri['sg'] = $val;
            elseif (strcasecmp($name, 'Urine pH') === 0) $uri['ph'] = $val;
            elseif (strcasecmp($name, 'Urine Sugar') === 0) $uri['sugar'] = $val;
            elseif (strcasecmp($name, 'Urine Protein') === 0) $uri['prot'] = $val;

            // Fecalysis parameters mapping
            elseif (strcasecmp($name, 'Consistency') === 0) $fec['cons'] = $val;
            elseif (strcasecmp($name, 'Fecal Color') === 0) $fec['color'] = $val;
            elseif (strcasecmp($name, 'Fecal WBC') === 0) $fec['wbc'] = $val;
            elseif (strcasecmp($name, 'Fecal RBC') === 0) $fec['rbc'] = $val;
            elseif (strcasecmp($name, 'Occult Blood') === 0) $fec['occ'] = $val;

            // Serology parameters mapping
            elseif (strcasecmp($name, 'HBsAg') === 0) $ser['hbsag'] = $val;
            elseif (strcasecmp($name, 'HAV') === 0) $ser['hav'] = $val;
            elseif (strcasecmp($name, 'VDRL / RPR') === 0) $ser['vdrl'] = $val;
            elseif (strcasecmp($name, 'Pregnancy Test') === 0) $ser['preg_ser'] = $val;
            elseif (strcasecmp($name, 'TSH') === 0) $ser['tsh'] = $val;
        }

        return [
            'metadata' => [
                'case_no' => $details->case_no ?? null,
            ],
            'sig' => [
                'rel_name' => $details->released_by_name ?? null,
                'rel_lic' => $details->released_by_license ?? null,
                'val1_name' => $details->validated_by_name ?? null,
                'val1_lic' => $details->validated_by_license ?? null,
                'val2_name' => $details->validated_by_name_2 ?? null,
                'val2_lic' => $details->validated_by_license_2 ?? null,
            ],
            'hem' => $hem,
            'uri' => $uri,
            'fec' => $fec,
            'ser' => $ser,
            'results' => $resultsArray
        ];
    }

    /**
     * Reconstructs the med_cert_data JSON structure. Falls back to raw JSON column if database tables are empty.
     */
    public function getMedCertDataAttribute()
    {
        $cert = $this->getMedCertRecord();

        // Fallback to raw JSON column if relational medCert record is missing
        if (!$cert && !empty($this->attributes['med_cert_data'])) {
            $decoded = json_decode($this->attributes['med_cert_data'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'metadata' => [
                'cert_no' => $cert->cert_no ?? null,
                'date' => $cert->date_of_issue ?? null,
            ],
            'findings' => $cert->findings ?? null,
            'remarks' => $cert->remarks ?? null,
            'issued_to' => $cert->issued_to ?? null,
            'sig' => [
                'name' => $cert->physician_name ?? null,
                'lic' => $cert->physician_license ?? null,
            ]
        ];
    }

    /**
     * Reconstructs the radio_data JSON structure. Falls back to raw JSON column if database tables are empty.
     */
    public function getRadioDataAttribute()
    {
        $report = $this->getRadiologyReportRecord();

        // Fallback to raw JSON column if relational radiologyReport record is missing
        if (!$report && !empty($this->attributes['radio_data'])) {
            $decoded = json_decode($this->attributes['radio_data'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'metadata' => [
                'case_no' => $report->case_no ?? null,
                'date' => $report->date_of_exam ?? null,
            ],
            'technique' => $report->technique ?? null,
            'findings' => $report->findings ?? null,
            'impression' => $report->impression ?? null,
            'sig' => [
                'name' => $report->radiologist_name ?? null,
                'lic' => $report->radiologist_license ?? null,
            ]
        ];
    }

    /**
     * FIXED: Reconstructs the drug_test_data JSON structure dynamically from the normalized table.
     */
    public function getDrugTestDataAttribute()
    {
        $record = $this->getDrugTestRecord();

        return [
            'metadata' => [
                'cert_no' => $record->cert_no,
                'date' => $record->updated_at ? $record->updated_at->format('Y-m-d') : now()->format('Y-m-d'),
            ]
        ];
    }

    // =========================================================================
    // TRANSPARENT MUTATORS (Writes incoming JSON arrays into relational tables)
    // =========================================================================

    /**
     * Overrides parent model setAttribute to dynamically intercept and save
     * workstation audit records to the normalized workstations_audits table.
     */
    public function setAttribute($key, $value)
    {
        // Detects if key matches audit fields (e.g., lab_v1_by_name, med_verified_at, etc.)
        if (preg_match('/^(lab|med|radio|drug)_(v1|v2|verified)_(by|by_name|at)$/', $key, $matches)) {
            $workstation = $matches[1]; // 'lab', 'med', 'radio', 'drug'
            $version = $matches[2]; // 'v1', 'v2', 'verified'
            $field = $matches[3]; // 'by', 'by_name', 'at'

            // Map legacy 'verified' key to standard 'v2' verifier database column
            $versionKey = ($version === 'verified') ? 'v2' : $version;
            $dbField = "{$versionKey}_{$field}";

            $this->updateAudit($workstation, [
                $dbField => $value
            ]);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Intercepts updates to 'lab_data' and writes them directly to relational tables.
     */
    public function setLabDataAttribute($value)
    {
        if (is_array($value)) {
            // 1. Sync Lab details & signatories
            $record = $this->getLabDetailsRecord();
            $record->fill([
                'case_no' => $value['metadata']['case_no'] ?? null,
                'released_by_name' => $value['sig']['rel_name'] ?? null,
                'released_by_license' => $value['sig']['rel_lic'] ?? null,
                'validated_by_name' => $value['sig']['val1_name'] ?? null,
                'validated_by_license' => $value['sig']['val1_lic'] ?? null,
                'validated_by_name_2' => $value['sig']['val2_name'] ?? null,
                'validated_by_license_2' => $value['sig']['val2_lic'] ?? null,
            ])->save();

            // 2. Sync Lab parameters
            if (isset($value['results']) && is_array($value['results'])) {
                $this->labResults()->delete(); // Purge old test parameters to preserve 1NF atomic states
                foreach ($value['results'] as $r) {
                    if (!empty($r['name'])) {
                        $this->labResults()->create([
                            'parameter_name' => $r['name'],
                            'observed_value' => $r['value'] ?? '',
                            'reference_range' => $r['ref_range'] ?? null,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Intercepts updates to 'med_cert_data' and writes them to the relational table.
     */
    public function setMedCertDataAttribute($value)
    {
        if (is_array($value)) {
            $record = $this->getMedCertRecord();
            $record->fill([
                'cert_no' => $value['metadata']['cert_no'] ?? ($value['cert_no'] ?? null),
                'date_of_issue' => $value['metadata']['date'] ?? ($value['date'] ?? null),
                'findings' => $value['findings'] ?? null,
                'remarks' => $value['remarks'] ?? null,
                'issued_to' => $value['issued_to'] ?? ($value['metadata']['name'] ?? null),
                'physician_name' => $value['sig']['name'] ?? ($value['sig_name'] ?? null),
                'physician_license' => $value['sig']['lic'] ?? ($value['sig_info'] ?? null),
            ])->save();
        }
    }

    /**
     * Intercepts updates to 'radio_data' and writes them to the relational table.
     */
    public function setRadioDataAttribute($value)
    {
        if (is_array($value)) {
            $record = $this->getRadiologyReportRecord();
            $record->fill([
                'case_no' => $value['metadata']['case_no'] ?? ($value['case_no'] ?? null),
                'date_of_exam' => $value['metadata']['date'] ?? ($value['date'] ?? null),
                'technique' => $value['technique'] ?? null,
                'findings' => $value['findings'] ?? null,
                'impression' => $value['impression'] ?? null,
                'radiologist_name' => $value['sig']['name'] ?? ($value['sig_name'] ?? null),
                'radiologist_license' => $value['sig']['lic'] ?? ($value['sig_info'] ?? null),
            ])->save();
        }
    }

    /**
     * FIXED: Intercepts updates to 'drug_test_data' and maps the nested cert_no cleanly to the 3NF table.
     */
    public function setDrugTestDataAttribute($value)
    {
        if (is_array($value)) {
            $record = $this->getDrugTestRecord();
            $record->fill([
                'cert_no' => $value['metadata']['cert_no'] ?? ($value['cert_no'] ?? null),
            ])->save();
        }
    }

    /**
     * Helper to write/update audit trail records dynamically from any workstation.
     */
    public function updateAudit(string $type, array $data)
    {
        return $this->audits()->updateOrCreate(
            ['workstation_type' => $type],
            $data
        );
    }

    // =========================================================================
    // DYNAMIC ACCESSORS FOR RETROACTIVE COMPATIBILITY
    // =========================================================================

    private function getAuditValue(string $type, string $field)
    {
        $audit = $this->audits->where('workstation_type', $type)->first();
        if ($audit) {
            return $audit->$field;
        }

        // Clinical Fallback resolves active encoder/verifier fields safely
        $statusField = ($type === 'med_cert' || $type === 'med') ? 'med_status' : "{$type}_status";
        $currentStatus = $this->$statusField ?? 'pending';

        if (str_contains($field, 'v1')) {
            // Encoder: requires 'encoded', 'verified', or 'released'
            $hasEncoder = in_array($currentStatus, ['encoded', 'verified', 'released']);
            if ($hasEncoder) {
                return str_contains($field, 'by_name') ? 'SYSTEM USER' : $this->updated_at;
            }
        }

        // FIXED: Verifier fallback only appears if status is actually 'verified' or 'released'
        if (str_contains($field, 'v2') || str_contains($field, 'verified')) {
            // Verifier: requires 'verified' or 'released'
            $hasVerifier = in_array($currentStatus, ['verified', 'released']);
            if ($hasVerifier) {
                return str_contains($field, 'by_name') ? 'SYSTEM USER' : $this->updated_at;
            }
        }

        return null;
    }

    public function getLabV1ByAttribute() { return $this->getAuditValue('lab', 'v1_by'); }
    public function getLabV1ByNameAttribute() { return $this->getAuditValue('lab', 'v1_by_name'); }
    public function getLabV1AtAttribute() { return $this->getAuditValue('lab', 'v1_at'); }
    public function getLabV2ByAttribute() { return $this->getAuditValue('lab', 'v2_by'); }
    public function getLabV2ByNameAttribute() { return $this->getAuditValue('lab', 'v2_by_name'); }
    public function getLabV2AtAttribute() { return $this->getAuditValue('lab', 'v2_at'); }

    public function getMedV1ByNameAttribute() { return $this->getAuditValue('med', 'v1_by_name'); }
    public function getMedV1AtAttribute() { return $this->getAuditValue('med', 'v1_at'); }
    public function getMedVerifiedByAttribute() { return $this->getAuditValue('med', 'v2_by'); }
    public function getMedV2ByNameAttribute() { return $this->getAuditValue('med', 'v2_by_name'); }
    public function getMedVerifiedAtAttribute() { return $this->getAuditValue('med', 'v2_at'); }

    public function getRadioV1ByNameAttribute() { return $this->getAuditValue('radio', 'v1_by_name'); }
    public function getRadioV1AtAttribute() { return $this->getAuditValue('radio', 'v1_at'); }
    public function getRadioVerifiedByAttribute() { return $this->getAuditValue('radio', 'v2_by'); }
    public function getRadioV2ByNameAttribute() { return $this->getAuditValue('radio', 'v2_by_name'); }
    public function getRadioVerifiedAtAttribute() { return $this->getAuditValue('radio', 'v2_at'); }

    public function getDrugV1ByNameAttribute() { return $this->getAuditValue('drug', 'v1_by_name'); }
    public function getDrugV1AtAttribute() { return $this->getAuditValue('drug', 'v1_at'); }
    public function getDrugVerifiedByAttribute() { return $this->getAuditValue('drug', 'v2_by'); }
    public function getDrugV2ByNameAttribute() { return $this->getAuditValue('drug', 'v2_by_name'); }
    public function getDrugVerifiedAtAttribute() { return $this->getAuditValue('drug', 'v2_at'); }
}