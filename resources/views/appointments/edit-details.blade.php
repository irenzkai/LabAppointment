<!-- resources/views/appointments/edit-details.blade.php -->
@extends('layouts.app')
@section('title', 'Edit Demographics & Clinical Requests - Ref #' . $appointment->id)

@section('content')
@php
    $from = request('from', 'queue');
    $customId = request('custom_id');
    
    // Resolve clean return URL based on where the staff member navigated from
    $backUrl = match($from) {
        'hub' => route('appointments.encode', $appointment->id),
        'radio', 'radiology' => route('workstation.radiology', $appointment->id),
        'lab' => route('workstation.lab', $appointment->id),
        'med_cert', 'medical' => route('workstation.med_cert', $appointment->id),
        'drug' => route('workstation.drug', $appointment->id),
        'custom' => ($customId ? route('workstation.custom', [$appointment->id, $customId]) : route('appointments.encode', $appointment->id)),
        default => route('appointments.index', ['view' => 'queue', 'id' => $appointment->id]),
    };

    $activePackageIds = old('package_ids', $appointment->packages->pluck('id')->toArray());
    $activeServiceIds = old('service_ids', $appointment->services->pluck('id')->toArray());
    $patientSex = strtolower(old('patient_sex', $appointment->patient_sex ?? 'both'));
    $currentAmount = old('payment_amount', $appointment->payment_amount ?: $appointment->totalPrice());

    $initialStreet = old('patient_street', $appointment->patient_street);
    $initialProv = old('patient_province', $appointment->patient_province);
    $initialCity = old('patient_city', $appointment->patient_city);
    $initialBrgy = old('patient_barangay', $appointment->patient_barangay);

    // Fetch and merge any organization-specific packages
    $bookedPackages = $appointment->packages()->with(['services', 'organization'])->get();
    $orgPackages = collect();

    if (!empty($appointment->organization_id)) {
        $orgPackages = \App\Models\Package::with(['services', 'organization'])
            ->where('organization_id', $appointment->organization_id)
            ->where('is_available', true)
            ->get();
    } elseif ($appointment->user && method_exists($appointment->user, 'organizations')) {
        $orgIds = $appointment->user->organizations()->pluck('id')->toArray();
        if (!empty($orgIds)) {
            $orgPackages = \App\Models\Package::with(['services', 'organization'])
                ->whereIn('organization_id', $orgIds)
                ->where('is_available', true)
                ->get();
        }
    }

    // Comprehensive catalog of all available packages (Global + Appointment's Packages + Org Packages)
    $allDisplayPackages = $packages->concat($bookedPackages)->concat($orgPackages)->unique('id')->sortBy('name');
    $allDisplayPackages->loadMissing(['services', 'organization']);
@endphp

<div class="container text-start animate-page py-3 px-3 px-sm-4" id="edit-details-root">
    {{-- Header Action Bar --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-1 uppercase tracking-tight fs-3 fs-md-2">
                <i class="bi bi-pencil-square me-2"></i>Edit Patient Demographics & Tests
            </h2>
            <p class="text-secondary small mb-0">
                Updating record for <strong class="text-main">{{ strtoupper($appointment->patient_name) }}</strong> &bull; Ref #{{ $appointment->id }}
                @if($appointment->organization_name)
                    &bull; <span class="badge bg-secondary bg-opacity-25 text-main uppercase smaller">{{ $appointment->organization_name }}</span>
                @endif
            </p>
        </div>
        <div class="flex-shrink-0">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2 text-decoration-none d-inline-flex align-items-center gap-1" style="border-radius: 8px;">
                <i class="bi bi-arrow-left"></i>
                <span>Back to {{ strtoupper($from) }}</span>
            </a>
        </div>
    </div>

    {{-- Validation Error Alerts --}}
    @if($errors->any())
        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <span>Please correct the following errors before saving:</span>
            </div>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Editing Form --}}
    <form action="{{ route('internal.appointment-details.update', $appointment->id) }}" method="POST" id="editDetailsForm" novalidate>
        @csrf
        @method('PUT')
        
        {{-- Navigation tracking flags --}}
        <input type="hidden" name="from" value="{{ $from }}">
        <input type="hidden" name="custom_id" value="{{ $customId }}">

        <div class="row g-4 align-items-stretch">
            {{-- LEFT COLUMN: PATIENT IDENTITY, PSGC ADDRESS & AUDIT REASON --}}
            <div class="col-lg-6 d-flex flex-column gap-4">
                {{-- 1. Personal Identity --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100" style="border-color: var(--border-color) !important; border-radius: 14px;">
                    <h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-person-vcard me-2"></i>1. Personal Identity
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="patient_first_name" id="patient_first_name" class="form-control fw-bold uppercase @error('patient_first_name') is-invalid @enderror" value="{{ old('patient_first_name', $appointment->patient_first_name) }}" required>
                            @error('patient_first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Middle Name</label>
                            <input type="text" name="patient_middle_name" id="patient_middle_name" class="form-control uppercase" value="{{ old('patient_middle_name', $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name) }}" placeholder="Leave blank if N/A">
                            @error('patient_middle_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-8">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="patient_last_name" id="patient_last_name" class="form-control fw-bold uppercase @error('patient_last_name') is-invalid @enderror" value="{{ old('patient_last_name', $appointment->patient_last_name) }}" required>
                            @error('patient_last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Suffix</label>
                            <input type="text" name="patient_suffix" id="patient_suffix" class="form-control uppercase" value="{{ old('patient_suffix', $appointment->patient_suffix) }}" placeholder="e.g. JR, SR, III" maxlength="10">
                            @error('patient_suffix') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Biological Sex <span class="text-danger">*</span></label>
                            <select name="patient_sex" id="patient_sex" class="form-select fw-semibold @error('patient_sex') is-invalid @enderror" required onchange="handleSexChange(this.value)">
                                <option value="Male" {{ $patientSex === 'male' ? 'selected' : '' }}>MALE</option>
                                <option value="Female" {{ $patientSex === 'female' ? 'selected' : '' }}>FEMALE</option>
                            </select>
                            @error('patient_sex') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" name="patient_birthdate" id="patient_birthdate" class="form-control fw-semibold @error('patient_birthdate') is-invalid @enderror" value="{{ old('patient_birthdate', $appointment->patient_birthdate ? \Carbon\Carbon::parse($appointment->patient_birthdate)->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}" required>
                            @error('patient_birthdate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="patient_phone" id="patient_phone" class="form-control fw-semibold @error('patient_phone') is-invalid @enderror" value="{{ old('patient_phone', $appointment->patient_phone) }}" placeholder="09XXXXXXXXX" maxlength="11" required>
                            @error('patient_phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- 2. Residential Address with PSGC API Dropdowns --}}
                <div class="card p-4 border-secondary bg-card shadow-sm" style="border-color: var(--border-color) !important; border-radius: 14px;">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <h5 class="text-accent fw-bold uppercase mb-0 small">
                            <i class="bi bi-geo-alt me-2"></i>2. Clinical Residential Address (PSGC Standard)
                        </h5>
                        <span id="psgcStatusBadge" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-x-small">
                            <i class="bi bi-cloud-check me-1"></i>PSGC Live
                        </span>
                    </div>

                    <div class="row g-3">
                        {{-- Province Dropdown --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Province <span class="text-danger">*</span></label>
                            <select name="patient_province" id="patient_province" class="form-select fw-semibold @error('patient_province') is-invalid @enderror" onchange="fetchCities(this.value)" required>
                                <option value="">Loading Provinces...</option>
                            </select>
                            @error('patient_province') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- City / Municipality Dropdown --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality <span class="text-danger">*</span></label>
                            <select name="patient_city" id="patient_city" class="form-select fw-semibold @error('patient_city') is-invalid @enderror" onchange="fetchBarangays(this.value)" disabled required>
                                <option value="">Select Province First</option>
                            </select>
                            @error('patient_city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- Barangay Dropdown --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Barangay <span class="text-danger">*</span></label>
                            <select name="patient_barangay" id="patient_barangay" class="form-select fw-semibold @error('patient_barangay') is-invalid @enderror" disabled required>
                                <option value="">Select City First</option>
                            </select>
                            @error('patient_barangay') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- Street / House No. --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No. <span class="text-danger">*</span></label>
                            <input type="text" name="patient_street" id="patient_street" class="form-control uppercase fw-semibold @error('patient_street') is-invalid @enderror" value="{{ $initialStreet }}" placeholder="House No., Building, Street Name" required>
                            @error('patient_street') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- 3. Medical Audit Trail Justification --}}
                <div class="card p-4 border-warning bg-card shadow-sm" style="border-color: rgba(255, 193, 7, 0.4) !important; border-radius: 14px;">
                    <h5 class="text-warning fw-bold uppercase mb-2 small d-flex align-items-center gap-2">
                        <i class="bi bi-shield-exclamation fs-5"></i>
                        <span>3. Reason for Demographic Revision (Required)</span>
                    </h5>
                    <p class="text-muted smaller mb-3">
                        Republic Act No. 10173 mandates an auditable clinical justification whenever patient records are revised by laboratory staff.
                    </p>

                    <div class="mb-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Preset Justification</label>
                        <select id="reason_preset_select" class="form-select @error('reason') is-invalid @enderror" onchange="handleReasonPresetChange(this)">
                            <option value="" disabled selected>-- Select an Audit Justification --</option>
                            <option value="Mismatched patient identification documents on arrival">Mismatched patient identification documents on arrival</option>
                            <option value="Correction of typographical error in demographic details">Correction of typographical error in demographic details</option>
                            <option value="Patient requested revision of contact details during check-in">Patient requested revision of contact details during check-in</option>
                            <option value="Physician recommended diagnostic test adjustment">Physician recommended diagnostic test adjustment</option>
                            <option value="Billing adjustments requested by clinic administrator">Billing adjustments requested by clinic administrator</option>
                            <option value="Others">Others (Specify below)</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Official Audit Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="audit_reason_input" class="form-control @error('reason') is-invalid @enderror" rows="3" placeholder="Enter clinical reason (minimum 5 characters)..." required>{{ old('reason') }}</textarea>
                        @error('reason') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: DIAGNOSTIC TESTS, PACKAGES & BILLING --}}
            <div class="col-lg-6 d-flex flex-column gap-4">
                <div class="card p-4 border-secondary bg-card shadow-sm h-100 d-flex flex-column justify-content-between" style="border-color: var(--border-color) !important; border-radius: 14px;">
                    <div>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3" style="border-color: var(--border-color) !important;">
                            <h5 class="text-accent fw-bold uppercase mb-0 small">
                                <i class="bi bi-flask me-2"></i>4. Diagnostic Tests & Packages
                            </h5>
                            <span class="badge bg-secondary bg-opacity-25 text-main small" id="testsSelectedCounter">0 Selected</span>
                        </div>

                        {{-- Search Filter --}}
                        <div class="mb-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-card border-secondary border-opacity-25 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="catalogSearchInput" class="form-control shadow-none" placeholder="Search tests or packages..." onkeyup="filterCatalogItems(this.value)">
                            </div>
                        </div>

                        {{-- Sub-Tabs: Packages vs Individual Tests --}}
                        <ul class="nav nav-pills mb-3 gap-2 border-bottom border-secondary border-opacity-10 pb-2" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-bold px-3 py-1.5 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#tab-packages" type="button" role="tab" style="font-size: 0.72rem;">
                                    <i class="bi bi-box-seam me-1"></i> Health Packages ({{ $allDisplayPackages->count() }})
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold px-3 py-1.5 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#tab-services" type="button" role="tab" style="font-size: 0.72rem;">
                                    <i class="bi bi-flask me-1"></i> Individual Tests ({{ $services->count() }})
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mb-3" id="catalogTabContent">
                            {{-- Packages List (Global & Organization Packages) --}}
                            <div class="tab-pane fade show active" id="tab-packages" role="tabpanel">
                                <div class="p-2 border rounded custom-scroll" style="max-height: 280px; overflow-y: auto; background-color: var(--bg-main) !important; border-color: var(--border-color) !important;">
                                    @forelse($allDisplayPackages as $pkg)
                                        @php
                                            $pkgGender = strtolower($pkg->gender_restriction ?? 'both');
                                            $isPkgChecked = in_array($pkg->id, $activePackageIds);
                                            $isOrgPkg = $pkg->isCustomOrgPackage() || !empty($pkg->organization_id);
                                        @endphp
                                        <div class="form-check p-2 mb-1.5 border rounded bg-card d-flex align-items-center justify-content-between gap-2 catalog-item pkg-item-wrap" id="pkg_wrap_{{ $pkg->id }}" data-name="{{ strtoupper($pkg->name) }}" data-gender="{{ $pkgGender }}">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                <input class="form-check-input ms-0 me-1 edit-pkg-cb" type="checkbox" name="package_ids[]" value="{{ $pkg->id }}" id="pkg_cb_{{ $pkg->id }}" data-price="{{ $pkg->price }}" data-services="{{ json_encode($pkg->services->pluck('id')->toArray()) }}" {{ $isPkgChecked ? 'checked' : '' }} onchange="onCatalogSelectionChange()">
                                                <label class="form-check-label text-main small cursor-pointer text-truncate fw-bold mb-0" for="pkg_cb_{{ $pkg->id }}">
                                                    @if($isOrgPkg)
                                                        <span class="badge bg-primary text-white py-0.5 px-1 me-1" style="font-size: 0.58rem; letter-spacing: 0.5px;">ORG PKG</span>
                                                    @else
                                                        <span class="badge bg-success bg-opacity-15 text-accent py-0.5 px-1 me-1" style="font-size: 0.58rem;">PKG</span>
                                                    @endif
                                                    {{ strtoupper($pkg->name) }}
                                                    @if($isOrgPkg && $pkg->organization)
                                                        <span class="text-secondary smaller fw-normal ms-1">({{ $pkg->organization->name }})</span>
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                {{-- HIGH-CONTRAST CONFLICT BADGE: Solid Red with Bold White Text --}}
                                                <span class="badge bg-danger text-white border border-danger px-2 py-0.5 fw-bold pkg-conflict-badge d-none smaller shadow-sm" style="font-size: 0.62rem; letter-spacing: 0.5px;">
                                                    <i class="bi bi-slash-circle me-1"></i>Conflicting
                                                </span>
                                                <span class="text-accent smaller fw-bold text-nowrap">₱{{ number_format($pkg->price, 2) }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-3 text-center text-muted small italic">No packages available.</div>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Individual Services List --}}
                            <div class="tab-pane fade" id="tab-services" role="tabpanel">
                                <div class="p-2 border rounded custom-scroll" style="max-height: 280px; overflow-y: auto; background-color: var(--bg-main) !important; border-color: var(--border-color) !important;">
                                    @foreach($services as $svc)
                                        @php
                                            $svcGender = strtolower($svc->gender_restriction ?? 'both');
                                            $isSvcChecked = in_array($svc->id, $activeServiceIds);
                                        @endphp
                                        <div class="form-check p-2 mb-1.5 border rounded bg-card d-flex align-items-center justify-content-between gap-2 catalog-item svc-item-wrap" id="svc_wrap_{{ $svc->id }}" data-name="{{ strtoupper($svc->name) }}" data-gender="{{ $svcGender }}">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                <input class="form-check-input ms-0 me-1 edit-svc-cb" type="checkbox" name="service_ids[]" value="{{ $svc->id }}" id="svc_cb_{{ $svc->id }}" data-price="{{ $svc->price }}" {{ $isSvcChecked ? 'checked' : '' }} onchange="onCatalogSelectionChange()">
                                                <label class="form-check-label text-main small cursor-pointer text-truncate mb-0" for="svc_cb_{{ $svc->id }}">
                                                    {{ strtoupper($svc->name) }}
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                {{-- HIGH-CONTRAST COVERED BADGE: Solid Yellow with Bold Dark Text --}}
                                                <span class="badge bg-warning text-dark border border-warning px-2 py-0.5 fw-bold svc-covered-badge d-none smaller shadow-sm" style="font-size: 0.62rem; letter-spacing: 0.5px;">
                                                    <i class="bi bi-box-seam me-1"></i>In Package
                                                </span>
                                                <span class="text-muted smaller fw-bold text-nowrap">₱{{ number_format($svc->price, 2) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- High-Contrast Conflict Error Alert Box --}}
                        <div id="catalog_conflict_msg" class="alert alert-danger text-danger border-danger p-2.5 rounded-3 mb-3 d-none smaller fw-bold shadow-sm" style="background-color: rgba(220, 53, 69, 0.12) !important; border: 1.5px solid #dc3545 !important;">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i> <span id="catalog_conflict_text"></span>
                        </div>
                    </div>

                    {{-- Billing & Actions Footer --}}
                    <div>
                        <div class="card p-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-secondary uppercase">Calculated Catalog Total:</span>
                                <span class="text-accent fw-bold fs-5" id="calculatedTotalDisplay">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10">
                                <div>
                                    <label class="small text-secondary fw-bold uppercase d-block mb-0">Confirmed Payment Amount (PHP) <span class="text-danger">*</span></label>
                                    <small class="text-muted smaller">Amount collected or charged for this booking.</small>
                                </div>
                                <div class="d-flex align-items-center gap-2" style="max-width: 170px;">
                                    <input type="number" step="0.01" min="0" name="payment_amount" id="payment_amount_input" class="form-control form-control-sm text-end fw-bold text-accent" value="{{ $currentAmount }}" required>
                                    <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2" title="Sync with Calculated Total" onclick="syncPriceToCalculated()">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Submission Buttons --}}
                        <div class="d-flex gap-2">
                            <a href="{{ $backUrl }}" class="btn btn-outline-secondary w-50 py-2.5 fw-bold uppercase text-center text-decoration-none" style="border-radius: 8px;">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-accent w-50 py-2.5 fw-bold uppercase shadow-sm" style="border-radius: 8px;" id="btnSubmitRevisions">
                                <i class="bi bi-check2-circle me-1"></i> Save Revisions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
/* Guaranteed High-Contrast Overrides */
.pkg-conflict-badge {
    background-color: #dc3545 !important;
    color: #ffffff !important;
    border: 1px solid #b02a37 !important;
}

.svc-covered-badge {
    background-color: #ffc107 !important;
    color: #1c232d !important;
    border: 1px solid #d39e00 !important;
}

#catalog_conflict_msg {
    color: #dc3545 !important;
}
</style>
@endpush

@push('scripts')
<script>
// Includes all available packages + booked custom org packages for conflict checking
const packagesMap = @json($allDisplayPackages->keyBy('id'));
const servicesMap = @json($services->keyBy('id'));
const apiBase = "https://psgc.gitlab.io/api";

const initialStreet = @json($initialStreet);
const initialProv = @json($initialProv);
const initialCity = @json($initialCity);
const initialBrgy = @json($initialBrgy);

// =========================================================================
// 1. PSGC ADDRESS ENGINE
// =========================================================================
function findOptionFlexibly(selectEl, searchVal) {
    if (!selectEl || !searchVal) return null;
    const target = searchVal.toString().trim().toUpperCase();
    return Array.from(selectEl.options).find(opt => {
        if (!opt.value && !opt.text) return false;
        const optVal = opt.value.toString().trim().toUpperCase();
        const optText = opt.text.toString().trim().toUpperCase();
        if (optVal === target || optText === target) return true;
        const normOpt = optText.replace(/\b(CITY|PROVINCE|MUNICIPALITY) OF\b/g, '').replace(/[^A-Z0-9]/g, '');
        const normTarget = target.replace(/\b(CITY|PROVINCE|MUNICIPALITY) OF\b/g, '').replace(/[^A-Z0-9]/g, '');
        return normOpt && normOpt === normTarget;
    });
}

async function fetchProvinces(targetProv = null, targetCity = null, targetBrgy = null) {
    const provSel = document.getElementById('patient_province');
    if (!provSel) return;
    try {
        const res = await fetch(`${apiBase}/provinces.json`);
        const data = await res.json();
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.dataset.code = p.code;
            opt.textContent = p.name;
            provSel.appendChild(opt);
        });

        if (targetProv) {
            let matched = findOptionFlexibly(provSel, targetProv);
            if (!matched) {
                matched = document.createElement('option');
                matched.value = targetProv;
                matched.textContent = targetProv;
                provSel.appendChild(matched);
            }
            provSel.value = matched.value;
            await fetchCities(matched.value, targetCity, targetBrgy);
        }
    } catch (e) {
        console.error("Provinces fetch error:", e);
        if (targetProv) {
            provSel.innerHTML = `<option value="${targetProv}" selected>${targetProv}</option>`;
        }
    }
}

async function fetchCities(provName, targetCity = null, targetBrgy = null) {
    const provSel = document.getElementById('patient_province');
    const citySel = document.getElementById('patient_city');
    const brgySel = document.getElementById('patient_barangay');
    if (!citySel || !brgySel) return;
    
    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';
    brgySel.innerHTML = '<option value="">Select City First</option>';

    if (!provName) {
        citySel.innerHTML = '<option value="">Select Province First</option>';
        return;
    }

    const matchedProv = findOptionFlexibly(provSel, provName);
    const provCode = matchedProv ? matchedProv.dataset.code : null;

    if (!provCode) {
        citySel.disabled = false;
        if (targetCity) {
            citySel.innerHTML = `<option value="${targetCity}" selected>${targetCity}</option>`;
            brgySel.disabled = false;
            if (targetBrgy) {
                brgySel.innerHTML = `<option value="${targetBrgy}" selected>${targetBrgy}</option>`;
            }
        } else {
            citySel.innerHTML = '<option value="">Select City</option>';
        }
        return;
    }

    try {
        const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities.json`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.dataset.code = c.code;
            opt.textContent = c.name;
            citySel.appendChild(opt);
        });
        citySel.disabled = false;

        if (targetCity) {
            let matchedCity = findOptionFlexibly(citySel, targetCity);
            if (!matchedCity) {
                matchedCity = document.createElement('option');
                matchedCity.value = targetCity;
                matchedCity.textContent = targetCity;
                citySel.appendChild(matchedCity);
            }
            citySel.value = matchedCity.value;
            await fetchBarangays(matchedCity.value, targetBrgy);
        }
    } catch (e) {
        console.error("Cities fetch error:", e);
        citySel.disabled = false;
        if (targetCity) {
            citySel.innerHTML = `<option value="${targetCity}" selected>${targetCity}</option>`;
        }
    }
}

async function fetchBarangays(cityName, targetBrgy = null) {
    const citySel = document.getElementById('patient_city');
    const brgySel = document.getElementById('patient_barangay');
    if (!brgySel) return;
    
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    if (!cityName) {
        brgySel.innerHTML = '<option value="">Select City First</option>';
        return;
    }

    const matchedCity = findOptionFlexibly(citySel, cityName);
    const cityCode = matchedCity ? matchedCity.dataset.code : null;

    if (!cityCode) {
        brgySel.disabled = false;
        if (targetBrgy) {
            brgySel.innerHTML = `<option value="${targetBrgy}" selected>${targetBrgy}</option>`;
        } else {
            brgySel.innerHTML = '<option value="">Select Barangay</option>';
        }
        return;
    }

    try {
        const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays.json`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.name;
            opt.dataset.code = b.code;
            opt.textContent = b.name;
            brgySel.appendChild(opt);
        });
        brgySel.disabled = false;

        if (targetBrgy) {
            let matchedBrgy = findOptionFlexibly(brgySel, targetBrgy);
            if (!matchedBrgy) {
                matchedBrgy = document.createElement('option');
                matchedBrgy.value = targetBrgy;
                matchedBrgy.textContent = targetBrgy;
                brgySel.appendChild(matchedBrgy);
            }
            brgySel.value = matchedBrgy.value;
        }
    } catch (e) {
        console.error("Barangays fetch error:", e);
        brgySel.disabled = false;
        if (targetBrgy) {
            brgySel.innerHTML = `<option value="${targetBrgy}" selected>${targetBrgy}</option>`;
        }
    }
}

// =========================================================================
// 2. DIAGNOSTIC CATALOG SELECTION & CONFLICT ENGINE
// =========================================================================
function filterCatalogItems(query) {
    const q = (query || '').toUpperCase().trim();
    document.querySelectorAll('.catalog-item').forEach(item => {
        const name = item.dataset.name || '';
        item.classList.toggle('d-none', q !== '' && !name.includes(q));
    });
}

function handleSexChange(sex) {
    const s = (sex || '').toLowerCase();
    document.querySelectorAll('.catalog-item').forEach(item => {
        const restriction = (item.dataset.gender || 'both').toLowerCase();
        let hide = false;
        if (s === 'male' && restriction === 'female') hide = true;
        if (s === 'female' && restriction === 'male') hide = true;
        
        if (hide) {
            item.classList.add('d-none');
            const cb = item.querySelector('input[type="checkbox"]');
            if (cb) cb.checked = false;
        } else {
            item.classList.remove('d-none');
        }
    });
    onCatalogSelectionChange();
}

function onCatalogSelectionChange() {
    recalculateDependencies();
    updateTotals();
}

function recalculateDependencies() {
    const activePackages = Array.from(document.querySelectorAll('.edit-pkg-cb:checked'));
    const coveredServices = new Set();
    
    // 1. Gather all services inside checked packages
    activePackages.forEach(pkgCb => {
        const pkgId = pkgCb.value;
        const pkg = packagesMap[pkgId];
        if (pkg && pkg.services) {
            pkg.services.forEach(svc => coveredServices.add(svc.id.toString()));
        } else if (pkgCb.dataset.services) {
            try {
                const sArr = JSON.parse(pkgCb.dataset.services);
                sArr.forEach(id => coveredServices.add(id.toString()));
            } catch(e) {}
        }
    });

    const checkedIndividualServices = new Set();
    document.querySelectorAll('.edit-svc-cb:checked').forEach(svcCb => {
        checkedIndividualServices.add(svcCb.value.toString());
    });

    // 2. Package conflict check (Package vs Package & Package vs Individual)
    document.querySelectorAll('.edit-pkg-cb').forEach(pkgCb => {
        const pkgId = pkgCb.value;
        const pkg = packagesMap[pkgId];
        let pkgServiceIds = [];
        if (pkg && pkg.services) {
            pkgServiceIds = pkg.services.map(s => s.id.toString());
        } else if (pkgCb.dataset.services) {
            try {
                pkgServiceIds = JSON.parse(pkgCb.dataset.services).map(String);
            } catch(e) {}
        }

        const wrap = document.getElementById(`pkg_wrap_${pkgId}`);
        const badge = wrap ? wrap.querySelector('.pkg-conflict-badge') : null;

        if (pkgCb.checked) {
            pkgCb.disabled = false;
            if (wrap) {
                wrap.classList.remove('opacity-50');
                wrap.removeAttribute('title');
            }
            if (badge) badge.classList.add('d-none');
            return;
        }

        const conflictsWithPackage = pkgServiceIds.some(id => coveredServices.has(id));
        const conflictsWithIndividual = pkgServiceIds.some(id => checkedIndividualServices.has(id));
        const isConflicting = conflictsWithPackage || conflictsWithIndividual;

        if (isConflicting) {
            pkgCb.disabled = true;
            if (wrap) {
                wrap.classList.add('opacity-50');
                wrap.style.cursor = 'not-allowed';
                wrap.setAttribute('title', 'Conflicting package shares tests with already selected items.');
            }
            if (badge) badge.classList.remove('d-none');
        } else {
            pkgCb.disabled = false;
            if (wrap) {
                wrap.classList.remove('opacity-50');
                wrap.style.cursor = 'default';
                wrap.removeAttribute('title');
            }
            if (badge) badge.classList.add('d-none');
        }
    });

    // 3. Individual services conflict check (Package vs Service)
    document.querySelectorAll('.edit-svc-cb').forEach(svcCb => {
        const isCovered = coveredServices.has(svcCb.value.toString());
        const wrap = document.getElementById(`svc_wrap_${svcCb.value}`);
        const badge = wrap ? wrap.querySelector('.svc-covered-badge') : null;

        if (isCovered) {
            svcCb.checked = false;
            svcCb.disabled = true;
            if (wrap) {
                wrap.classList.add('opacity-50');
                wrap.style.cursor = 'not-allowed';
                wrap.setAttribute('title', 'Already included in a selected package.');
            }
            if (badge) badge.classList.remove('d-none');
        } else {
            svcCb.disabled = false;
            if (wrap) {
                wrap.classList.remove('opacity-50');
                wrap.style.cursor = 'default';
                wrap.removeAttribute('title');
            }
            if (badge) badge.classList.add('d-none');
        }
    });

    // Update Counter
    const totalCount = document.querySelectorAll('.edit-pkg-cb:checked').length + document.querySelectorAll('.edit-svc-cb:checked').length;
    const counterEl = document.getElementById('testsSelectedCounter');
    if (counterEl) {
        counterEl.innerText = `${totalCount} Selected`;
    }
}

function updateTotals() {
    let sum = 0;
    document.querySelectorAll('.edit-pkg-cb:checked').forEach(cb => {
        sum += parseFloat(cb.dataset.price || 0);
    });
    document.querySelectorAll('.edit-svc-cb:checked').forEach(cb => {
        sum += parseFloat(cb.dataset.price || 0);
    });

    const displayEl = document.getElementById('calculatedTotalDisplay');
    if (displayEl) {
        displayEl.innerText = '₱' + sum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function syncPriceToCalculated() {
    let sum = 0;
    document.querySelectorAll('.edit-pkg-cb:checked').forEach(cb => {
        sum += parseFloat(cb.dataset.price || 0);
    });
    document.querySelectorAll('.edit-svc-cb:checked').forEach(cb => {
        sum += parseFloat(cb.dataset.price || 0);
    });
    const input = document.getElementById('payment_amount_input');
    if (input) {
        input.value = sum.toFixed(2);
    }
}

function handleReasonPresetChange(select) {
    const input = document.getElementById('audit_reason_input');
    if (!input || !select.value) return;
    if (select.value === 'Others') {
        input.value = '';
        input.focus();
    } else {
        input.value = select.value;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize PSGC Cascading Address dropdowns with patient's registered address
    fetchProvinces(initialProv, initialCity, initialBrgy);

    // 2. Initialize catalog gender and conflict restrictions
    handleSexChange(document.getElementById('patient_sex')?.value || 'Male');
    onCatalogSelectionChange();
});
</script>
@endpush
@endsection