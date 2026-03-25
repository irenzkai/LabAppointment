@extends('layouts.app')

@section('content')
<div class="row justify-content-center text-start">
    <div class="col-md-11">

        {{-- HEADER SECTION --}}
        <div class="d-flex justify-content-between align-items-start mb-4 border-bottom border-secondary pb-4">
            <div>
                <h2 class="text-neon fw-bold mb-1 uppercase" style="letter-spacing: 2px;">RESULT ENCODING</h2>
                <p class="text-white fw-bold mb-1">Patient: {{ strtoupper($appointment->patient_name) }}</p>
                
                {{-- TESTS REQUESTED LIST --}}
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <small class="text-white fw-bold uppercase pt-1" style="font-size: 1rem;">Tests Requested:</small>
                    @foreach($appointment->services as $service)
                        <span class="badge border border-neon text-neon px-2" style="font-size: 1rem;">
                            {{ strtoupper($service->name) }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- TOP ACTION BUTTONS --}}
            <div class="d-flex gap-2">
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-neon border-secondary text-secondary px-3 py-2 small">
                    CANCEL
                </a>
                {{-- MOVED: RELEASE BUTTON TO TOP --}}
                <button type="button" onclick="confirmRelease()" class="btn-custom btn-neon px-4 py-2 shadow">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> RELEASE FINAL RESULTS
                </button>
            </div>
        </div>

        {{-- MAIN FORM START --}}
        <form id="mainEncodeForm" action="{{ route('appointments.release', $appointment->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            {{-- STEP 1: REPORT SELECTION --}}
            <div class="card p-4 border-secondary bg-black shadow-lg mb-4">
                <h6 class="text-neon fw-bold mb-3 small uppercase" style="letter-spacing: 1px;">SELECT REPORTS TO ISSUE</h6>
                <div class="d-flex flex-wrap gap-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input report-toggle" type="checkbox" name="included_reports[]" value="lab" id="chk-lab">
                        <label class="form-check-label text-white small fw-bold" for="chk-lab">LABORATORY RESULT</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input report-toggle" type="checkbox" name="included_reports[]" value="med_cert" id="chk-med">
                        <label class="form-check-label text-white small fw-bold" for="chk-med">MEDICAL CERTIFICATE</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input report-toggle" type="checkbox" name="included_reports[]" value="drug" id="chk-drug">
                        <label class="form-check-label text-white small fw-bold" for="chk-drug">DRUG TEST REPORT</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input report-toggle" type="checkbox" name="included_reports[]" value="radio" id="chk-radio">
                        <label class="form-check-label text-white small fw-bold" for="chk-radio">RADIOLOGIC REPORT</label>
                    </div>
                </div>
            </div>

            {{-- STEP 2: DYNAMIC ENCODING SECTIONS --}}
            <div id="encoding-section" style="display: block;">
                <h6 class="text-neon fw-bold mb-3 small uppercase" style="letter-spacing: 1px;">ENCODE DATA / UPLOAD SCANS</h6>
                <ul class="nav nav-pills mb-4 border-bottom border-secondary pb-3 gap-2" id="encodeTabs">
                    <li class="nav-item tab-item-lab" style="display:none;">
                        <button class="nav-link small fw-bold uppercase text-white" type="button" data-bs-toggle="pill" data-bs-target="#tab-lab">LABORATORY</button>
                    </li>
                    <li class="nav-item tab-item-med_cert" style="display:none;">
                        <button class="nav-link small fw-bold uppercase text-white" type="button" data-bs-toggle="pill" data-bs-target="#tab-med">MEDICAL CERT</button>
                    </li>
                    <li class="nav-item tab-item-drug" style="display:none;">
                        <button class="nav-link small fw-bold uppercase text-white" type="button" data-bs-toggle="pill" data-bs-target="#tab-drug">DRUG TEST</button>
                    </li>
                    <li class="nav-item tab-item-radio" style="display:none;">
                        <button class="nav-link small fw-bold uppercase text-white" type="button" data-bs-toggle="pill" data-bs-target="#tab-radio">RADIOLOGY</button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- PLACEHOLDER: Shown when no tab is selected --}}
                    <div id="tab-placeholder" class="text-center py-5 border border-secondary border-dashed rounded bg-dark bg-opacity-25 mt-2">
                        <i class="bi bi-arrow-up text-secondary fs-1 mb-3 d-block"></i>
                        <p class="text-secondary smaller mb-0 px-4">Toggle a report category above to automatically open its encoding form.</p>
                    </div>

                    {{-- TAB: LAB --}}
                    <div class="tab-pane fade" id="tab-lab">
                        {{-- FIRST SECTION: FILE ATTACHMENT --}}
                        <div class="mt-4 p-3 border border-secondary border-dashed rounded bg-dark mb-4">
                            <label class="text-neon small fw-bold mb-2 uppercase d-block">
                                <i class="bi bi-paperclip me-1"></i> Attach Official Physical Scan (Optional)
                            </label>
                            <input type="file" name="lab_scan" class="form-control bg-black border-secondary text-white shadow-none">
                            <small class="text-white mt-2 d-block italic">If uploaded, the patient will see this scan instead of the digital version.</small>
                        </div> 

                        {{-- 1. CLINIC HEADER (Visual match to physical form) --}}
                        <div class="text-center mb-4 border-bottom border-neon pb-3">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo mb-2" style="height: 60px; width: 60px; border-radius: 50%;">
                            <h3 class="text-neon fw-bold mb-0">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
                            <p class="text-white small mb-0 uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                                BANISIL STREET (FORMERLY ATIS STREET), BRGY. DADIANGAS WEST, GENERAL SANTOS CITY
                            </p>
                            <p class="text-secondary small mb-0" style="font-size: 0.6rem;">
                                DOH ACCREDITED | TEL NO: (083) 823 8754 | EMAIL: medscreen.lab@gmail.com
                            </p>
                        </div>

                        {{-- 2. PATIENT METADATA --}}
                        <div class="card p-4 border-secondary bg-black mb-4 shadow-lg">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="text-white smaller fw-bold uppercase">CASE #</label>
                                    <input type="text" name="lab_data[metadata][case_no]" class="form-control border-secondary bg-dark text-white" placeholder="Required" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-white smaller fw-bold uppercase">Name</label>
                                    <input type="text" name="patient_name" class="form-control border-secondary bg-dark text-white" value="{{ strtoupper($appointment->patient_name) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="text-white smaller fw-bold uppercase">Date</label>
                                    <input type="date" name="appointment_date" class="form-control border-secondary bg-dark text-white" value="{{ $appointment->appointment_date->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="text-white smaller fw-bold uppercase">Address</label>
                                    <input type="text" name="patient_address" class="form-control border-secondary bg-dark text-white" value="{{ $appointment->patient_address }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="text-white smaller fw-bold uppercase">Age / Sex</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control border-secondary bg-dark text-white w-50" value="{{ $appointment->patient_age }}">
                                        <input type="text" name="patient_sex" class="form-control border-secondary bg-dark text-white w-50" value="{{ $appointment->patient_sex }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-white smaller fw-bold uppercase">Requested By</label>
                                    <input type="text" name="organization_name" class="form-control border-secondary bg-dark text-white" value="{{ $appointment->organization_name ?? 'INDIVIDUAL' }}">
                                </div>
                            </div>
                        </div>

                        <h4 class="text-center text-white fw-bold border-bottom border-secondary pb-2 mb-4">LABORATORY RESULT(S)</h4>

                        {{-- 3. HEMATOLOGY SECTION --}}
                        <div class="card p-4 border-secondary bg-black mb-4">
                            <h6 class="text-neon fw-bold mb-4 uppercase text-center" style="letter-spacing: 2px; font-size: 1.25rem;">HEMATOLOGY</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="text-white smaller fw-bold">WBC COUNT</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="lab_data[hem][wbc]" class="form-control bg-dark border-secondary text-white">
                                        <span class="input-group-text bg-black border-secondary text-white" style="font-size: 0.6rem;">5-10 x 10⁹/L</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-white smaller fw-bold">HEMOGLOBIN</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="lab_data[hem][hb]" class="form-control bg-dark border-secondary text-white">
                                        <span class="input-group-text bg-black border-secondary text-white" style="font-size: 0.55rem;">(M) 140-170 / (F) 120-150 G/L</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-white smaller fw-bold">PLATELET COUNT</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="lab_data[hem][plt]" class="form-control bg-dark border-secondary text-white">
                                        <span class="input-group-text bg-black border-secondary text-white" style="font-size: 0.6rem;">150-400 x 10⁹/L</span>
                                    </div>
                                </div>

                                <div class="col-md-3"><label class="text-white smaller fw-bold">MCH</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][mch]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">25.0-35.0 pg</span></div></div>
                                <div class="col-md-3"><label class="text-white smaller fw-bold">MCHC</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][mchc]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">310-380 g/dl</span></div></div>
                                <div class="col-md-3"><label class="text-white smaller fw-bold">MCV</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][mcv]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">75.0-100.0 fl</span></div></div>
                                <div class="col-md-3"><label class="text-white smaller fw-bold">RBC</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][rbc]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">(M)4.5-6.5 / (F)4.3-5.5</span></div></div>

                                <div class="col-md-4"><label class="text-white smaller fw-bold">HEMATOCRIT</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][hct]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">(M)0.40-0.50 / (F)0.36-0.48</span></div></div>
                                <div class="col-md-4"><label class="text-white smaller fw-bold">BLEEDING TIME</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][bt_time]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">2-6 MINUTES</span></div></div>
                                <div class="col-md-4"><label class="text-white smaller fw-bold">CLOTTING TIME</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][ct_time]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">2-8 MINUTES</span></div></div>

                                <div class="col-md-4"><label class="text-white smaller fw-bold">ESR</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][esr]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">(M)0-10 / (F)0-20 mm/hr</span></div></div>
                                <div class="col-md-4"><label class="text-white smaller fw-bold">RDW</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][rdw]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">11.0-16.0%</span></div></div>
                                <div class="col-md-4"><label class="text-white smaller fw-bold">RETICULOCYTE CT</label><div class="input-group input-group-sm"><input type="text" name="lab_data[hem][retic]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text text-white bg-black" style="font-size:0.7rem">0.5-1.5%</span></div></div>

                                {{-- DIFFERENTIAL COUNT SUB-GRID --}}
                                <div class="col-12 mt-4 pt-2 border-top border-secondary border-opacity-25">
                                    <p class="text-white fw-bold smaller uppercase mb-3">Differential Count (%)</p>
                                    <div class="row g-2">
                                        <div class="col"><label class="text-white smaller fw-bold">NEUTROPHILS</label><input type="text" name="lab_data[hem][neu]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0.40-0.65</span></div>
                                        <div class="col"><label class="text-white smaller fw-bold">LYMPHOCYTES</label><input type="text" name="lab_data[hem][lym]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0.20-0.40</span></div>
                                        <div class="col"><label class="text-white smaller fw-bold">MONOCYTES</label><input type="text" name="lab_data[hem][mon]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0.02-0.06</span></div>
                                        <div class="col"><label class="text-white smaller fw-bold">EOSINOPHILS</label><input type="text" name="lab_data[hem][eos]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0.01-0.03</span></div>
                                        <div class="col"><label class="text-white smaller fw-bold">BASOPHILS</label><input type="text" name="lab_data[hem][bas]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0.00-0.01</span></div>
                                        <div class="col"><label class="text-white smaller fw-bold">STABS</label><input type="text" name="lab_data[hem][sta]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0.01-0.04</span></div>
                                    </div>
                                </div>

                                <div class="col-md-6 mt-3"><label class="text-white fw-bold smaller">BLOOD TYPE</label><input type="text" name="lab_data[hem][bt]" class="form-control form-control-sm bg-dark border-secondary text-white" placeholder="e.g. O"></div>
                                <div class="col-md-6 mt-3"><label class="text-white fw-bold smaller">RH TYPING</label><input type="text" name="lab_data[hem][rh]" class="form-control form-control-sm bg-dark border-secondary text-white" placeholder="e.g. POSITIVE"></div>
                            </div>
                        </div>

                        {{-- 4. URINALYSIS SECTION --}}
                        <div class="card p-4 border-secondary bg-black mb-4">
                            <h6 class="text-neon fw-bold mb-4 uppercase text-center" style="letter-spacing: 2px; font-size: 1.25rem;">URINALYSIS</h6>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <p class="text-white fw-bold smaller uppercase border-bottom border-secondary border-opacity-50 pb-1 mb-3">MICROSCOPIC EXAMINATION</p>
                                    <div class="row g-2">
                                        <div class="col-6"><label class="text-white smaller fw-bold">COLOR</label><input type="text" name="lab_data[uri][color]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-6"><label class="text-white smaller fw-bold">TRANSPARENCY</label><input type="text" name="lab_data[uri][trans]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-6"><label class="text-white smaller fw-bold">PUS CELLS</label><input type="text" name="lab_data[uri][pus]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0-2 / (0-5)</span></div>
                                        <div class="col-6"><label class="text-white smaller fw-bold">RBC</label><input type="text" name="lab_data[uri][rbc]" class="form-control form-control-sm bg-dark border-secondary text-white"><span class="text-white" style="font-size:0.7rem">0-2 / (0-2)</span></div>
                                        <div class="col-6"><label class="text-white smaller fw-bold">EPITHELIAL</label><input type="text" name="lab_data[uri][epi]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-6"><label class="text-white smaller fw-bold">MUCUS THREADS</label><input type="text" name="lab_data[uri][mucus]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-white fw-bold smaller uppercase border-bottom border-secondary border-opacity-50 pb-1 mb-3">CHEMICAL EXAMINATION</p>
                                    <div class="row g-2">
                                        <div class="col-6"><label class="text-white smaller fw-bold">URINE pH</label><input type="text" name="lab_data[uri][ph]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-6"><label class="text-white smaller fw-bold">SPECIFIC GRAVITY</label><input type="text" name="lab_data[uri][sg]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-4"><label class="text-white smaller fw-bold">SUGAR</label><input type="text" name="lab_data[uri][sugar]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-4"><label class="text-white smaller fw-bold">PROTEIN</label><input type="text" name="lab_data[uri][prot]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-4"><label class="text-white smaller fw-bold">KETONE</label><input type="text" name="lab_data[uri][ket]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-4"><label class="text-white smaller fw-bold">BLOOD</label><input type="text" name="lab_data[uri][blood]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-4"><label class="text-white smaller fw-bold">NITRITE</label><input type="text" name="lab_data[uri][nit]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                        <div class="col-4"><label class="text-white smaller fw-bold">UROBILINOGEN</label><input type="text" name="lab_data[uri][uro]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                    </div>
                                </div>
                                <div class="row g-4 text-start">
                                    {{-- CASTS COLUMN --}}
                                    <div class="col-md-6 border-end border-secondary border-opacity-25">
                                        <p class="text-white fw-bold uppercase border-bottom border-secondary border-opacity-50 pb-1 mb-3" style="font-size: 1rem; letter-spacing: 1px;">CASTS</p>
                                        <div class="row g-2">
                                            @foreach(['FINE GRANULAR','COARSE GRANULAR','HYALINE','PUS CELL','WAXY'] as $cast)
                                            <div class="col-6 mb-1">
                                                <div class="row g-0 align-items-center">
                                                    <div class="col-7">
                                                        <small class="text-white fw-bold uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">{{ $cast }}</small>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text" name="lab_data[uri][cast][{{ $cast }}]" class="form-control form-control-sm bg-dark border-secondary text-white shadow-none py-1">
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- CRYSTALS / OTHERS COLUMN --}}
                                    <div class="col-md-6">
                                        <p class="text-white fw-bold uppercase border-bottom border-secondary border-opacity-50 pb-1 mb-3" style="font-size: 1rem; letter-spacing: 1px;">CRYSTALS / OTHERS</p>
                                        <div class="row g-2">
                                            @foreach(['CALCIUM OXALATE','AMORPHOUS URATE','AMORPHOUS PHOSPHATE','PREGNANCY TEST (OTHERS)'] as $cry)
                                            <div class="col-6 mb-1">
                                                <div class="row g-0 align-items-center">
                                                    <div class="col-7">
                                                        <small class="text-white fw-bold uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">{{ $cry }}</small>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text" name="lab_data[uri][cry][{{ $cry }}]" class="form-control form-control-sm bg-dark border-secondary text-white shadow-none py-1">
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 5. FECALYSIS SECTION --}}
                        <div class="card p-4 border-secondary bg-black mb-4">
                            <h6 class="text-neon fw-bold mb-4 uppercase text-center" style="letter-spacing: 2px; font-size: 1.25rem;">FECALYSIS</h6>
                            <div class="row g-3">
                                <div class="col-md-3"><label class="text-white smaller fw-bold">COLOR</label><input type="text" name="lab_data[fec][color]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-3"><label class="text-white smaller fw-bold">CONSISTENCY</label><input type="text" name="lab_data[fec][cons]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-3"><label class="text-white smaller fw-bold">WBC / HPF</label><input type="text" name="lab_data[fec][wbc]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-3"><label class="text-white smaller fw-bold">RBC / HPF</label><input type="text" name="lab_data[fec][rbc]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-4"><label class="text-white smaller fw-bold">FAT GLOBULE</label><input type="text" name="lab_data[fec][fat]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-4"><label class="text-white smaller fw-bold">OVA / PARASITES</label><input type="text" name="lab_data[fec][ova]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-4"><label class="text-white smaller fw-bold">OCCULT BLOOD (OTHERS)</label><input type="text" name="lab_data[fec][occ]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                            </div>
                        </div>

                        {{-- 6. SEROLOGY --}}
                        <div class="card p-4 border-secondary bg-black mb-4">
                            <h6 class="text-neon fw-bold mb-4 uppercase text-center" style="letter-spacing: 2px; font-size: 1.25rem;">SEROLOGY</h6>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="text-white smaller fw-bold">HBsAg (HEPATITIS B)</label><input type="text" name="lab_data[ser][hbsag]" class="form-control form-control-sm bg-dark border-secondary text-white" placeholder="NON-REACTIVE"></div>
                                <div class="col-md-6"><label class="text-white smaller fw-bold">HAV (HEPATITIS A)</label><input type="text" name="lab_data[ser][hav]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-6"><label class="text-white smaller fw-bold">VRDL / RPR (SYPHILIS)</label><input type="text" name="lab_data[ser][vdrl]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-6"><label class="text-white smaller fw-bold">PREGNANCY TEST (SERUM)</label><input type="text" name="lab_data[ser][preg_ser]" class="form-control form-control-sm bg-dark border-secondary text-white"></div>
                                <div class="col-md-12">
                                    <label class="text-white smaller fw-bold">TSH</label>
                                    <div class="input-group input-group-sm w-50">
                                        <input type="text" name="lab_data[ser][tsh]" class="form-control bg-dark border-secondary text-white"><span class="input-group-text bg-black border-secondary text-white" style="font-size:0.7rem">0.4-5.5 uIU/mL</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 7. SIGNATORIES SECTION --}}
                        <div class="card p-4 border-secondary bg-black mb-4 shadow-sm">
                            <div class="row g-4 text-start">
                                
                                {{-- LEFT SIDE: RELEASED BY --}}
                                <div class="col-md-6 border-end border-secondary border-opacity-25">
                                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary pb-1">
                                        <h6 class="text-neon small fw-bold mb-0 uppercase" style="letter-spacing: 1px;">RELEASED BY</h6>
                                    </div>

                                    <div class="bg-dark p-0 rounded border-start border-secondary shadow-sm">
                                        <input type="text" name="lab_data[sig][rel_name]" class="form-control form-control-sm mb-1 bg-dark border-secondary text-white shadow-none" placeholder="FULL NAME">
                                        <input type="text" name="lab_data[sig][rel_lic]" class="form-control form-control-sm bg-dark border-secondary text-white shadow-none" placeholder="POSITION / LICENSE NO.">
                                    </div>
                                </div>

                                {{-- RIGHT SIDE: VALIDATED BY --}}
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary pb-1">
                                        <h6 class="text-neon small fw-bold mb-0 uppercase" style="letter-spacing: 1px;">VALIDATED BY</h6>
                                        <button type="button" class="btn btn-outline-info btn-sm py-0 border-0 fw-bold" style="font-size: 0.65rem;" onclick="addValidator()">
                                            <i class="bi bi-plus-circle-fill me-1"></i> ADD
                                        </button>
                                    </div>
                                    
                                    <div id="validator-container">
                                        <div class="validator-row mb-2 bg-dark p-0 rounded border-start border-neon shadow-sm">
                                            <input type="text" name="lab_data[sig][val][0][name]" class="form-control form-control-sm mb-1 bg-dark border-secondary text-white shadow-none" placeholder="FULL NAME">
                                            <input type="text" name="lab_data[sig][val][0][lic]" class="form-control form-control-sm bg-dark border-secondary text-white shadow-none" placeholder="POSITION / LICENSE NO.">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- FOOTER NOTICES --}}
                        <div class="card p-3 bg-dark border-secondary border-opacity-50 small text-white-50 italic">
                            <p class="mb-1"><i class="bi bi-info-circle me-2"></i><strong>Reminder:</strong> Tests left blank or without recorded results are considered not performed or not requested by patient.</p>
                            <p class="mb-0"><i class="bi bi-info-circle me-2"></i><strong>Important Notice:</strong> This laboratory report is designed for interpretation by a qualified medical doctor.</p>
                        </div>
                    </div>            

                    {{-- TAB: MED CERT --}}
                    <div class="tab-pane fade" id="tab-med">
                        {{-- FIRST SECTION: FILE ATTACHMENT --}}
                        <div class="mt-4 p-3 border border-secondary border-dashed rounded bg-dark mb-4">
                            <label class="text-neon small fw-bold mb-2 uppercase d-block">
                                <i class="bi bi-paperclip me-1"></i> Attach Official Physical Scan (Optional)
                            </label>
                            <input type="file" name="med_cert_scan" class="form-control bg-black border-secondary text-white shadow-none">
                            <small class="text-white mt-2 d-block italic">If uploaded, the patient will see this scan instead of the digital version.</small>
                        </div> 

                        {{-- CLINIC HEADER --}}
                        <div class="text-center mb-4 border-bottom border-neon pb-3">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo mb-2" style="height: 60px; width: 60px; border-radius: 50%;">
                            <h3 class="text-neon fw-bold mb-0">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
                            <p class="text-white small mb-0 uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                                BANISIL STREET (FORMERLY ATIS STREET), BRGY. DADIANGAS WEST, GENERAL SANTOS CITY
                            </p>
                            <p class="text-secondary small mb-0" style="font-size: 0.6rem;">
                                DOH ACCREDITED | TEL NO: (083) 823 8754 | EMAIL: medscreen.lab@gmail.com
                            </p>
                        </div>

                        <div class="card p-5 border-secondary bg-black shadow-lg">
                            {{-- CERTIFICATE HEADER ROW --}}
                            <div class="row mb-5 align-items-end">
                                <div class="col-md-6 text-start">
                                    <label class="text-white smaller fw-bold uppercase">Cert. No.:</label>
                                    <input type="text" name="med_cert_data[cert_no]" class="form-control border-secondary bg-dark text-white fw-bold shadow-none" style="max-width: 250px;" required>
                                </div>
                                <div class="col-md-6 text-end">
                                    <label class="text-white smaller fw-bold uppercase">Date:</label>
                                    <input type="date" name="med_cert_data[date]" class="form-control border-secondary bg-dark text-white shadow-none ms-auto" style="max-width: 180px;" value="{{ date('Y-m-d') }}">
                                </div>
                            </div>

                            <h2 class="text-center text-white fw-bold mb-5" style="letter-spacing: 5px; border-bottom: 2px solid #ffffff; display: inline-block; margin: 0 auto; width: fit-content; padding-bottom: 10px;">MEDICAL CERTIFICATE</h2>

                            <div class="text-start mt-4">
                                <h6 class="text-white fw-bold mb-4">TO WHOM IT MAY CONCERN:</h6>

                                <p class="text-white mb-4" style="line-height: 2.2; font-size: 0.95rem;">
                                    This is to certify that 
                                    <input type="text" name="med_cert_data[name]" class="border-0 border-bottom border-secondary bg-transparent text-white fw-bold text-center px-2" style="min-width: 250px;" value="{{ strtoupper($appointment->patient_name) }}">, 
                                    <input type="text" name="med_cert_data[age]" class="border-0 border-bottom border-secondary bg-transparent text-white fw-bold text-center px-2" style="width: 50px;" value="{{ $appointment->patient_age }}"> years old,
                                    <input type="text" name="med_cert_data[sex]" class="border-0 border-bottom border-secondary bg-transparent text-white fw-bold text-center px-2" style="width: 80px;" value="{{ strtoupper($appointment->patient_sex) }}"> residing at 
                                    <input type="text" name="med_cert_data[address]" class="border-0 border-bottom border-secondary bg-transparent text-white fw-bold text-center px-2" value="{{ $appointment->patient_address }}">
                                    has been examined on 
                                    <input type="date" name="med_cert_data[exam_date]" class="border-0 border-bottom border-secondary bg-transparent text-white fw-bold px-2" value="{{ $appointment->tested_at ? $appointment->tested_at->format('Y-m-d') : date('Y-m-d') }}"> 
                                    with the following findings and/or diagnosis:
                                </p>

                                {{-- FINDINGS BOX --}}
                                <div class="mb-4">
                                    <textarea name="med_cert_data[findings]" class="form-control bg-dark border-secondary text-white p-3 shadow-none" rows="4" placeholder="Enter findings and diagnosis here..." style="font-size: 0.9rem;"></textarea>
                                </div>

                                <div class="mb-5 d-flex align-items-start">
                                    <label class="text-white fw-bold me-2 mt-1">REMARKS:</label>
                                    <textarea name="med_cert_data[remarks]" class="form-control bg-dark border-secondary text-white shadow-none" rows="2" style="font-size: 0.9rem;"></textarea>
                                </div>

                                <p class="text-white mb-5" style="line-height: 2;">
                                    This certification is being issued to 
                                    <input type="text" name="med_cert_data[issued_to]" class="border-0 border-bottom border-secondary bg-transparent text-white fw-bold px-2" style="min-width: 200px;" value="{{ strtoupper($appointment->patient_name) }}">
                                    for whatever legal purposes it may serve him/her best. Not for medico-legal or court purposes.
                                </p>
                            </div>

                            {{-- SIGNATORY BOX (Bottom Right) --}}
                            <div class="row mt-5">
                                <div class="col-md-6 offset-md-6 text-center">
                                    <div class="p-2 border-start border-neon bg-dark rounded shadow-sm">
                                        <input type="text" name="med_cert_data[sig_name]" class="form-control form-control-sm bg-transparent border-0 border-bottom border-secondary text-white text-center fw-bold mb-1" style="font-size: 1rem;" placeholder="NAME OF PHYSICIAN">
                                        <input type="text" name="med_cert_data[sig_info]" class="form-control form-control-sm bg-transparent border-0 text-white text-center" style="font-size: 1rem;" placeholder="POSITION / LICENSE NO.">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB: DRUG TEST --}}
                    <div class="tab-pane fade" id="tab-drug">
                        {{-- FIRST SECTION: FILE ATTACHMENT --}}
                        <div class="mt-4 p-3 border border-secondary border-dashed rounded bg-dark mb-4">
                            <label class="text-neon small fw-bold mb-2 uppercase d-block">
                                <i class="bi bi-paperclip me-1"></i> Attach Official Physical Scan
                            </label>
                            <input type="file" name="drug_test_scan" class="form-control bg-black border-secondary text-white shadow-none">
                            <small class="text-white mt-2 d-block italic">If uploaded, the patient will see this scan.</small>
                        </div> 
                    </div>

                    {{-- TAB: RADIOLOGY --}}
                    <div class="tab-pane fade" id="tab-radio">
                        {{-- FIRST SECTION: FILE ATTACHMENT --}}
                        <div class="mt-4 p-3 border border-secondary border-dashed rounded bg-dark mb-4">
                            <label class="text-neon small fw-bold mb-2 uppercase d-block">
                                <i class="bi bi-paperclip me-1"></i> Attach Official Physical Scan (Optional)
                            </label>
                            <input type="file" name="radio_scan" class="form-control bg-black border-secondary text-white shadow-none">
                            <small class="text-white mt-2 d-block italic">If uploaded, the patient will see this scan instead of the digital version.</small>
                        </div> 
                        {{-- X-RAY IMAGE ATTACHMENT --}}
                        <div class="mt-4 p-3 border border-secondary border-dashed rounded bg-dark mb-4">
                            <label class="text-neon small fw-bold mb-2 uppercase d-block">
                                <i class="bi bi-image me-1"></i> Import X-Ray Scan (Image)
                            </label>
                            <input type="file" name="xray_image" class="form-control bg-black border-secondary text-white shadow-none">
                            <small class="text-white mt-2 d-block italic">Upload the digital capture of the X-Ray film.</small>
                        </div> 

                        {{-- CLINIC HEADER --}}
                        <div class="text-center mb-4 border-bottom border-neon pb-3">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo mb-2" style="height: 60px; width: 60px; border-radius: 50%;">
                            <h3 class="text-neon fw-bold mb-0">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
                            <p class="text-white small mb-0 uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                                BANISIL STREET (FORMERLY ATIS STREET), BRGY. DADIANGAS WEST, GENERAL SANTOS CITY
                            </p>
                            <p class="text-secondary small mb-0" style="font-size: 0.6rem;">
                                DOH ACCREDITED | TEL NO: (083) 823 8754 | EMAIL: medscreen.lab@gmail.com
                            </p>
                        </div>

                        <div class="card p-5 border-secondary bg-black shadow-lg">
                            <h4 class="text-center text-white fw-bold mb-5" style="letter-spacing: 3px;">RADIOLOGIC REPORT</h4>

                            {{-- PATIENT METADATA --}}
                            <div class="row g-3 mb-5 text-start">
                                <div class="col-md-8">
                                    <label class="text-white smaller fw-bold uppercase">NAME</label>
                                    <input type="text" name="radio_data[patient_name]" class="form-control border-0 border-bottom border-secondary bg-transparent text-white fw-bold p-0 rounded-0" value="{{ strtoupper($appointment->patient_name) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="text-white smaller fw-bold uppercase">DATE</label>
                                    <input type="date" name="radio_data[date]" class="form-control border-0 border-bottom border-secondary bg-transparent text-white fw-bold p-0 rounded-0" value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="text-white smaller fw-bold uppercase">ADDRESS</label>
                                    <input type="text" name="radio_data[address]" class="form-control border-0 border-bottom border-secondary bg-transparent text-white fw-bold p-0 rounded-0" value="{{ $appointment->patient_address }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="text-white smaller fw-bold uppercase">AGE/SEX</label>
                                    <input type="text" name="radio_data[age_sex]" class="form-control border-0 border-bottom border-secondary bg-transparent text-white fw-bold p-0 rounded-0" value="{{ $appointment->patient_age }} / {{ substr($appointment->patient_sex, 0, 1) }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="text-white smaller fw-bold uppercase">CASE #</label>
                                    <input type="text" name="radio_data[case_no]" class="form-control border-0 border-bottom border-neon bg-transparent text-white fw-bold p-0 rounded-0" placeholder="REQUIRED">
                                </div>
                            </div>

                            {{-- REPORT CONTENT --}}
                            <div class="text-start">
                                {{-- TECHNIQUE (Defaulted but editable) --}}
                                <div class="mb-4">
                                    <label class="text-white fw-bold uppercase mb-2" style="font-size: 0.85rem; border-left: 3px solid #ffffff; padding-left: 10px;">Technique:</label>
                                    <textarea name="radio_data[technique]" class="form-control bg-transparent border-0 text-white fw-bold p-0 fs-5" rows="5">CHEST PA</textarea>
                                </div>

                                {{-- FINDINGS (Manual Textarea) --}}
                                <div class="mb-4">
                                    <label class="text-white fw-bold uppercase mb-2" style="font-size: 0.85rem; border-left: 3px solid #ffffff; padding-left: 10px;">Findings:</label>
                                    <textarea name="radio_data[findings]" class="form-control bg-dark border-secondary text-white p-3 shadow-none" rows="8" placeholder="Enter radiologic findings here (Supports multiple lines)..." style="font-size: 0.95rem; line-height: 1.6; border-style: dashed;"></textarea>
                                </div>

                                {{-- IMPRESSION --}}
                                <div class="mb-5">
                                    <label class="text-white fw-bold uppercase mb-2" style="font-size: 0.85rem; border-left: 3px solid #ffffff; padding-left: 10px;">Impression:</label>
                                    <textarea name="radio_data[impression]" class="form-control bg-dark border-secondary text-white fw-bold shadow-none p-3" rows="3" placeholder="Enter final clinical impression..."></textarea>
                                </div>
                            </div>

                            {{-- SIGNATORY BOX (Bottom Right) --}}
                            <div class="row mt-5">
                                <div class="col-md-6 offset-md-6 text-center">
                                    <div class="p-2 border-start border-neon bg-dark rounded shadow-sm">
                                        <input type="text" name="radio_data[sig_name]" class="form-control form-control-sm bg-transparent border-0 border-bottom border-secondary text-white text-center fw-bold mb-1" style="font-size: 1rem;" placeholder="FULL NAME">
                                        <input type="text" name="radio_data[sig_info]" class="form-control form-control-sm bg-transparent border-0 text-white text-center uppercase" style="font-size: 1rem;" placeholder="RADIOLOGIST / LICENSE NO.">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FINAL RELEASE BUTTON: ONLY THIS SUBMITS --}}
                <div class="mt-5 border-top border-secondary pt-4 text-center">
                    <button type="submit" class="btn-custom btn-neon px-5 py-3 fs-5 shadow-lg">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> RELEASE FINAL RESULTS
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="releaseConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-neon bg-black shadow-lg p-4 text-center">
            <i class="bi bi-question-circle text-neon display-4 mb-3"></i>
            <h5 class="text-white fw-bold uppercase small">Ready to Release?</h5>
            <p class="text-secondary smaller">Ensure all checked reports are correctly encoded before publishing.</p>
            <div class="d-grid gap-2">
                <button type="button" onclick="document.getElementById('mainEncodeForm').submit();" class="btn-custom btn-neon py-3">YES, RELEASE NOW</button>
                <button type="button" class="btn-custom btn-outline-neon border-0 text-white" data-bs-dismiss="modal">NOT YET</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let vCount = 1;
    function addValidator() {
        const html = `
            <div class="validator-row mb-2 bg-dark p-0 rounded border-start border-neon shadow-sm" id="v_row_${vCount}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <input type="text" name="lab_data[sig][val][${vCount}][name]" class="form-control form-control-sm bg-dark border-secondary text-white shadow-none" placeholder="NAME">
                    <button type="button" class="btn btn-link text-danger" onclick="document.getElementById('v_row_${vCount}').remove()">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
                <input type="text" name="lab_data[sig][val][${vCount}][lic]" class="form-control form-control-sm bg-dark border-secondary text-white shadow-none" placeholder="POSITION / LICENSE NO.">
            </div>`;
        document.getElementById('validator-container').insertAdjacentHTML('beforeend', html);
        vCount++;
    }

    function confirmRelease() {
        const anyChecked = document.querySelectorAll('.report-toggle:checked').length > 0;
        if (!anyChecked) {
            showAlert("Please select at least one report type to issue.");
            return;
        }
        new bootstrap.Modal(document.getElementById('releaseConfirmModal')).show();
    }

    document.querySelectorAll('#encodeTabs button').forEach(button => {
        button.addEventListener('shown.bs.tab', function () {
            document.getElementById('tab-placeholder').style.display = 'none';
        });
    });

    document.querySelectorAll('.report-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const reportType = this.value;
            const tabItem = document.querySelector(`.tab-item-${reportType}`);
            const section = document.getElementById('encoding-section');
            const placeholder = document.getElementById('tab-placeholder');
            const targetBtn = tabItem.querySelector('button');
            const targetPane = document.querySelector(targetBtn.getAttribute('data-bs-target'));

            // Get all currently checked switches
            const allChecked = document.querySelectorAll('.report-toggle:checked');

            if (this.checked) {
                // --- ACTION: TOGGLE ON ---
                tabItem.style.display = 'block';
                section.style.display = 'block';
                placeholder.style.display = 'none';

                // Auto-select the tab that was just turned on
                const tabTrigger = new bootstrap.Tab(targetBtn);
                tabTrigger.show();
                
            } else {
                // --- ACTION: TOGGLE OFF ---
                tabItem.style.display = 'none';
                
                // Force remove active state from the hidden tab/pane
                targetBtn.classList.remove('active');
                targetPane.classList.remove('show', 'active');

                if (allChecked.length > 0) {
                    // If there are still other reports toggled on, jump to the first one available
                    const nextReportType = allChecked[0].value;
                    const nextTabBtn = document.querySelector(`.tab-item-${nextReportType} button`);
                    const nextTabTrigger = new bootstrap.Tab(nextTabBtn);
                    nextTabTrigger.show();
                    placeholder.style.display = 'none';
                } else {
                    // If NO reports are left toggled on, show the placeholder
                    placeholder.style.display = 'block';
                    // Optional: hide the tabs container since it's empty
                    // section.style.display = 'none'; 
                }
            }
        });
    });
</script>
@endpush
@endsection