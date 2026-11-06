<!-- resources/views/appointments/partials/wizard/self-wizard.blade.php -->
@php
    $userSex = strtolower(Auth::user()->sex ?? 'both');
@endphp

<!-- SELF APPOINTMENT FLOW CONTAINER -->
<div id="self-wizard-container">
    <!-- ========================================================================= -->
    <!-- SELF STEP 2: PATIENT DETAILS (READONLY VERIFIED IDENTITY SNAPSHOT)        -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="self-step-2">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-2">
            <div>
                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Patient Information</h3>
                <p class="text-secondary small mb-0">Review your verified personal medical profile for this booking.</p>
            </div>
            <a href="{{ route('profile.edit') }}" target="_blank" class="btn btn-sm btn-outline-accent py-2 px-3 fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                <i class="bi bi-person-gear"></i> EDIT PROFILE
            </a>
        </div>

        {{-- Readonly Demographic Information Display Card --}}
        <div class="card p-4 border rounded-3 mb-4 bg-card" style="border-color: var(--border-color) !important;">
            <div class="row g-3">
                {{-- Full Name --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Patient Full Name</label>
                    <div class="fw-bold text-main fs-6 text-truncate">{{ strtoupper(Auth::user()->name) }}</div>
                </div>

                {{-- Sex & Age / Birthdate --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Age & Biological Classification</label>
                    <div class="fw-bold text-main fs-6">
                        {{ Auth::user()->birthdate ? \Carbon\Carbon::parse(Auth::user()->birthdate)->age . ' YRS OLD' : 'AGE UNKNOWN' }}
                        <span class="text-muted mx-1">&bull;</span>
                        {{ strtoupper(Auth::user()->sex) }}
                        <small class="text-muted fw-normal ms-1">({{ Auth::user()->birthdate ? \Carbon\Carbon::parse(Auth::user()->birthdate)->format('M d, Y') : 'N/A' }})</small>
                    </div>
                </div>

                {{-- Contact Number --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Mobile Contact Number</label>
                    <div class="fw-bold text-main fs-6">
                        <i class="bi bi-telephone text-accent me-1"></i>{{ Auth::user()->phone ?: 'No phone registered' }}
                    </div>
                </div>

                {{-- Email Address --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Account Email</label>
                    <div class="fw-bold text-main fs-6 text-truncate">
                        <i class="bi bi-envelope text-accent me-1"></i>{{ Auth::user()->email }}
                    </div>
                </div>

                {{-- Registered Residential Address --}}
                <div class="col-12 pt-1">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Registered Clinical Address</label>
                    <div class="fw-bold text-main small">
                        <i class="bi bi-geo-alt text-accent me-1"></i>{{ strtoupper(Auth::user()->address ?: 'No address registered on file') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden Form Fields (Preserves backend compatibility & client-side scripts) --}}
        <input type="hidden" name="patient_first_name" id="self_first_name" value="{{ Auth::user()->first_name }}">
        <input type="hidden" name="patient_middle_name" id="self_middle_name" value="{{ Auth::user()->middle_name ?: 'N/A' }}">
        <input type="hidden" name="patient_last_name" id="self_last_name" value="{{ Auth::user()->last_name }}">
        <input type="hidden" name="patient_suffix" id="self_suffix" value="{{ Auth::user()->suffix }}">
        <input type="hidden" name="patient_sex" id="self_sex" value="{{ Auth::user()->sex }}">
        <input type="hidden" name="patient_birthdate" id="self_bday" value="{{ Auth::user()->birthdate ? Auth::user()->birthdate->format('Y-m-d') : '' }}">
        <input type="hidden" name="patient_phone" id="self_phone_hidden" value="{{ Auth::user()->phone }}">
        <input type="hidden" id="self_phone_display">
        <input type="hidden" name="patient_street" id="self_street" value="{{ Auth::user()->street }}">
        <input type="hidden" name="patient_barangay" id="self_brgy" value="{{ Auth::user()->barangay }}">
        <input type="hidden" name="patient_city" id="self_city" value="{{ Auth::user()->city }}">
        <input type="hidden" name="patient_province" id="self_province" value="{{ Auth::user()->province }}">

        {{-- Doctor's Referral File Upload --}}
        <div class="col-12 mt-2">
            <label class="small text-secondary fw-bold mb-1 uppercase">Doctor's Referral / Physician Note (Optional)</label>
            <div id="self_referral_wrapper">
                <input type="file" name="referral_note" id="self_referral" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReferralUpload(this)">
            </div>
            <div id="self_referral_preview_container" class="d-none mt-3 p-3 rounded" style="background-color: rgba(25, 211, 140, 0.03); border: 1px solid rgba(25, 211, 140, 0.15);">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-accent fw-semibold" id="self_referral_file_label"><i class="bi bi-file-earmark-check-fill me-1"></i>Selected File</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="viewReferralFile('self')">View</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold" onclick="removeUploadedReferral('self')">Remove</button>
                    </div>
                </div>
            </div>
            <small class="text-muted smaller d-block mt-1">Attach a doctor's referral note or prescription image if available.</small>
        </div>

        <div class="d-flex gap-2 mt-5">
            <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(1)">
                <i class="bi bi-arrow-left me-2"></i> BACK
            </button>
            <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateSelfStep2()">
                NEXT: SELECT TESTS & PACKAGES <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SELF STEP 3: SELECT TESTS & PACKAGES (GENDER-FILTERED)                    -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="self-step-3">
        <div class="mb-4 border-bottom border-secondary border-opacity-10 pb-3">
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 3: Diagnostic Tests & Packages</h3>
            <p class="text-secondary small mb-3">Choose health packages or standalone laboratory examinations requested by your physician.</p>
            
            {{-- Quick Search Input --}}
            <div class="input-group">
                <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
                <input type="text" id="selfTestSearch" class="form-control shadow-none" placeholder="Search tests or packages (e.g. CBC, Chem 8, Lipid Profile, X-Ray)..." oninput="filterTestList('self')">
            </div>
        </div>

        {{-- Sub-Tabs: Packages vs Individual Services --}}
        <ul class="nav nav-pills mb-3 gap-2 border-bottom border-secondary border-opacity-10 pb-2.5 flex-wrap" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold px-3 py-1.5 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#self-pane-packages" type="button" role="tab">
                    <i class="bi bi-box-seam me-1"></i> Health Packages ({{ $packages->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold px-3 py-1.5 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#self-pane-services" type="button" role="tab">
                    <i class="bi bi-flask me-1"></i> Individual Tests ({{ $services->count() }})
                </button>
            </li>
        </ul>

        <div class="tab-content" id="selfTestTabsContent">
            {{-- 1. PACKAGES LIST --}}
            <div class="tab-pane fade show active" id="self-pane-packages" role="tabpanel">
                <div class="test-list-container self-test-list custom-scroll border border-secondary border-opacity-25 rounded bg-card" style="max-height: 480px; overflow-y: auto;">
                    @php $pkgCount = 0; @endphp
                    @forelse($packages as $pkg)
                        @php $pkgGender = strtolower($pkg->gender_restriction ?? 'both'); @endphp
                        @if($pkgGender !== 'both' && $pkgGender !== $userSex)
                            @continue
                        @endif
                        @php $pkgCount++; @endphp
                        <div class="test-item border-bottom border-secondary border-opacity-10 transition-all p-3" data-name="{{ strtoupper($pkg->name) }}" data-gender="{{ $pkgGender }}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <input type="checkbox" name="package_ids[]" value="{{ $pkg->id }}" id="self_pkg_{{ $pkg->id }}" class="pkg-checkbox self-pkg-checkbox d-none" data-name="{{ $pkg->name }}" data-price="{{ $pkg->price }}" data-services="{{ json_encode($pkg->services->pluck('id')->toArray()) }}" onchange="handleTestSelectionChange('self');">
                                    <label class="d-flex align-items-center cursor-pointer mb-0" for="self_pkg_{{ $pkg->id }}">
                                        <div class="check-indicator rounded border border-secondary me-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-check-lg text-dark d-none"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="text-main fw-bold small uppercase">{{ $pkg->name }}</span>
                                            </div>
                                            <div class="d-flex gap-2 flex-wrap">
                                                @if($pkg->sample_required && $pkg->sample_required !== 'N/A')
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.62rem;">
                                                        <i class="bi bi-droplet-half text-danger me-1"></i>{{ $pkg->sample_required }}
                                                    </span>
                                                @endif
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.62rem;">
                                                    <i class="bi bi-clock me-1"></i>{{ $pkg->formatted_time }}
                                                </span>
                                                <span class="badge bg-info bg-opacity-10 text-info smaller" style="font-size: 0.62rem;">
                                                    <i class="bi bi-check2-all me-1"></i>{{ $pkg->services->count() }} Tests
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-2 ps-2">
                                    <span class="badge bg-danger bg-opacity-15 text-white border border-danger border-opacity-30 pkg-conflict-badge d-none smaller" style="font-size: 0.6rem;">Conflicting</span>
                                    <div class="text-accent fw-bold fs-6 text-nowrap">₱{{ number_format($pkg->price, 2) }}</div>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-secondary" onclick="toggleTestDetails('self_pkg_details_{{ $pkg->id }}', this)" title="View Details">
                                        <i class="bi bi-chevron-down fs-5"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="self_pkg_details_{{ $pkg->id }}" class="test-details-drawer d-none mt-3 p-3 rounded text-start animate-page" style="background-color: var(--bg-main);">
                                @if(!empty($pkg->description))
                                    <p class="mb-2 small text-muted lh-base" style="font-size: 0.8rem;">{{ $pkg->description }}</p>
                                @endif
                                
                                {{-- Constituent Services List --}}
                                @if($pkg->services && $pkg->services->isNotEmpty())
                                    <div class="mb-2 pt-2 border-top border-secondary border-opacity-10">
                                        <small class="text-accent fw-800 d-block mb-1 fs-x-small uppercase"><i class="bi bi-check2-circle me-1"></i>Included Tests in this Package:</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($pkg->services as $svc)
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0.5 rounded fw-semibold" style="font-size: 0.68rem;">
                                                    {{ strtoupper($svc->name) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($pkg->preparation))
                                    <div class="pt-2 border-top border-secondary border-opacity-10 mt-2">
                                        <small class="text-warning fw-800 d-block mb-0.5 fs-x-small uppercase"><i class="bi bi-exclamation-triangle-fill me-1"></i>PREPARATION REQUIRED:</small>
                                        <p class="mb-0 small text-muted" style="font-size: 0.75rem;">{{ $pkg->preparation }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-5 text-center text-muted small">No diagnostic packages available.</div>
                    @endforelse

                    @if($pkgCount === 0)
                        <div class="py-5 text-center text-muted small">No health packages available for your demographic profile.</div>
                    @endif
                </div>
            </div>

            {{-- 2. INDIVIDUAL SERVICES LIST --}}
            <div class="tab-pane fade" id="self-pane-services" role="tabpanel">
                <div class="test-list-container self-test-list custom-scroll border border-secondary border-opacity-25 rounded bg-card" style="max-height: 480px; overflow-y: auto;">
                    @php $svcCount = 0; @endphp
                    @forelse($services as $s)
                        @php $sGender = strtolower($s->gender_restriction ?? 'both'); @endphp
                        @if($sGender !== 'both' && $sGender !== $userSex)
                            @continue
                        @endif
                        @php $svcCount++; @endphp
                        <div class="test-item border-bottom border-secondary border-opacity-10 transition-all p-3" id="service_wrapper_{{ $s->id }}" data-name="{{ strtoupper($s->name) }}" data-gender="{{ $sGender }}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="self_test_{{ $s->id }}" class="service-checkbox test-checkbox self-test-checkbox svc-chk-item d-none" data-name="{{ $s->name }}" data-price="{{ $s->price }}" onchange="handleTestSelectionChange('self');">
                                    <label class="d-flex align-items-center cursor-pointer mb-0" for="self_test_{{ $s->id }}">
                                        <div class="check-indicator rounded border border-secondary me-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-check-lg text-dark d-none"></i>
                                        </div>
                                        <div>
                                            <div class="text-main fw-bold small uppercase mb-1">{{ $s->name }}</div>
                                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                                @if($s->sample_required && $s->sample_required !== 'N/A')
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.62rem;">
                                                        <i class="bi bi-droplet-half text-danger me-1"></i>{{ $s->sample_required }}
                                                    </span>
                                                @endif
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.62rem;">
                                                    <i class="bi bi-clock me-1"></i>{{ $s->formatted_time }}
                                                </span>
                                                <span class="badge bg-warning bg-opacity-15 text-secondary border border-warning border-opacity-30 pkg-covered-badge d-none smaller" style="font-size: 0.62rem;">
                                                    <i class="bi bi-info-circle me-1"></i>Covered by Selected Package
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-3 ps-2">
                                    <div class="text-accent fw-bold fs-6 text-nowrap">₱{{ number_format($s->price, 2) }}</div>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-secondary" onclick="toggleTestDetails('self_details_{{ $s->id }}', this)" title="View Details">
                                        <i class="bi bi-chevron-down fs-5"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="self_details_{{ $s->id }}" class="test-details-drawer d-none mt-3 p-3 rounded text-start animate-page" style="background-color: var(--bg-main);">
                                <p class="mb-2 small text-muted lh-base" style="font-size: 0.8rem;">{{ $s->description }}</p>
                                @if($s->preparation)
                                    <div class="pt-2 border-top border-secondary border-opacity-10 mt-2">
                                        <small class="text-warning fw-800 d-block mb-0.5 fs-x-small uppercase"><i class="bi bi-exclamation-triangle-fill me-1"></i>PREPARATION REQUIRED:</small>
                                        <p class="mb-0 small text-muted" style="font-size: 0.75rem;">{{ $s->preparation }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-5 text-center text-muted small">No individual tests available.</div>
                    @endforelse

                    @if($svcCount === 0)
                        <div class="py-5 text-center text-muted small">No individual tests available for your demographic profile.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-5">
            <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(2)">
                <i class="bi bi-arrow-left me-2"></i> BACK
            </button>
            <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateStep3()">
                NEXT: CHOOSE SCHEDULE <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SELF STEP 4: SELECT SCHEDULE (SUPPORTS SAME-DAY & ADVANCE SLOTS)           -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="self-step-4">
        <div class="mb-4">
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 4: Select Schedule</h3>
            <p class="text-secondary small">Choose your preferred date and time block for the laboratory visit.</p>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-md-5 border-end border-secondary border-opacity-25 pe-md-4 d-flex flex-column justify-content-between">
                <div>
                    <label class="small text-accent fw-bold mb-2 d-block uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">1. Pick a Date</label>
                    <input type="date" name="appointment_date" id="self_wiz_date" class="form-control py-3 shadow-none fw-bold mb-4" min="{{ date('Y-m-d') }}" onchange="fetchTimeSlots('self')">
                </div>

                {{-- High-Contrast Operating Hours Card --}}
                <div class="p-3.5 border rounded-3 mt-3 text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color) !important;">
                    <h6 class="text-main smaller fw-bold uppercase mb-2.5" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <i class="bi bi-clock-history me-1.5 text-accent"></i>Operating Hours
                    </h6>
                    <ul class="list-unstyled smaller mb-0" style="font-size: 0.75rem; line-height: 1.8;">
                        <li class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-10 pb-1.5 mb-1.5">
                            <span style="color: var(--text-muted);">Mon - Sat:</span>
                            <span class="fw-bold" style="color: var(--text-main);">08:00 AM - 05:00 PM</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-10 pb-1.5 mb-1.5">
                            <span style="color: var(--text-muted);">Lunch Break:</span>
                            <span class="text-warning italic fw-bold">12:00 PM - 01:00 PM</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span style="color: var(--text-muted);">Sunday:</span>
                            <span class="text-danger uppercase fw-bold">Closed</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-7 ps-md-4">
                <label class="small text-accent fw-bold mb-2 d-block uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">2. Choose Time Block</label>
                <div id="self_slots_container" class="row g-2 overflow-auto custom-scroll" style="max-height: 420px; min-height: 250px;">
                    <div class="col-12 py-5 text-center text-secondary border border-secondary border-opacity-25 border-dashed rounded" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                        <i class="bi bi-calendar-event fs-1 d-block mb-2 opacity-25"></i>
                        <p class="mb-0">Please select a preferred date first<br>to view available time slots.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-5">
            <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(3)">
                <i class="bi bi-arrow-left me-2"></i> BACK
            </button>
            <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateStep4()">
                NEXT: FINAL CHECKOUT <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SELF STEP 5: PAYMENT & FINALIZE                                           -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="self-step-5">
        <div class="mb-4">
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 5: Payment & Finalize</h3>
            <p class="text-secondary small">Choose how you would like to settle your laboratory fees.</p>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem;">Select Payment Method</label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="radio" class="btn-check text-main" name="payment_method" id="self_pay_cash" value="Cash" checked autocomplete="off" onchange="togglePaymentFields('self')">
                        <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="self_pay_cash">
                            <i class="bi bi-cash-stack fs-1 mb-2"></i>
                            <div class="fw-bold uppercase">Cash on Site</div>
                            <div class="smaller opacity-75 mt-1">Pay at reception desk upon arrival.</div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <input type="radio" class="btn-check text-main" name="payment_method" id="self_pay_cashless" value="Cashless" autocomplete="off" onchange="togglePaymentFields('self')">
                        <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="self_pay_cashless">
                            <i class="bi bi-qr-code-scan fs-1 mb-2"></i>
                            <div class="fw-bold uppercase">Online / E-Wallet</div>
                            <div class="smaller opacity-75 mt-1">Scan and pay using digital wallets.</div>
                        </label>
                    </div>
                </div>
            </div>

            <div id="self_provider_container" class="col-12 d-none mt-4 animate-fade-in">
                <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem;">Choose E-Wallet Provider</label>
                <div class="row g-3">
                    @foreach($paymentProviders as $provider)
                        <div class="col-md-4 col-6">
                            <input type="radio" class="btn-check provider-radio self-prov-radio" name="payment_provider_id" id="self_prov_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" autocomplete="off" onchange="handleProviderChange('self', this)">
                            <label class="btn btn-outline-secondary w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="self_prov_{{ $provider->id }}">
                                @if($provider->logo)
                                    <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" class="mb-2" style="height: 32px; object-fit: contain;">
                                @else
                                    <i class="bi bi-wallet2 fs-3 mb-2 text-secondary"></i>
                                @endif
                                <div class="small fw-bold uppercase text-main">{{ $provider->name }}</div>
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="invalid-feedback d-none mt-2" id="err_self_provider"></div>
            </div>

            <div id="self_qr_section" class="col-12 d-none animate-fade-in mt-4">
                <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                    <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem;">
                        Scan to Pay (<span id="self_selected_provider_name" class="text-accent">E-Wallet</span>)
                    </h6>
                    <div class="d-flex justify-content-center">
                        <div id="self_qr_zoom_wrapper" class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10 cursor-pointer" style="cursor: zoom-in;" onclick="window.zoomQR(document.getElementById('self_selected_provider_qr').src)" title="Click to view full screen">
                            <img src="" id="self_selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.7rem;"><i class="bi bi-zoom-in text-accent me-1"></i>Click the QR code image to zoom full-screen.</small>
                </div>
            </div>

            <div id="self_receipt_container" class="col-12 d-none mt-4 animate-fade-in">
                <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>
                <div id="self_receipt_input_wrapper">
                    <input type="file" name="payment_receipt" id="self_in_receipt" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReceiptUpload(this)">
                </div>
                <div id="self_receipt_preview_container" class="d-none mt-3 p-3 rounded" style="background-color: rgba(25, 211, 140, 0.03); border: 1px solid rgba(25, 211, 140, 0.15);">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-accent fw-semibold" id="self_receipt_file_label"><i class="bi bi-file-earmark-check-fill me-1"></i>Selected File</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="viewReceiptFile('self')">View</button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold" onclick="removeUploadedReceipt('self')">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-secondary border-opacity-25 bg-card p-4">
                    <div class="form-check text-start">
                        <input class="form-check-input" type="checkbox" id="self_agree_terms" required>
                        <label class="form-check-label text-main small" for="self_agree_terms">
                            I confirm that all information provided is accurate and I agree to the <a href="{{ route('legal.privacy') }}" target="_blank" class="text-accent fw-bold text-decoration-none">Clinical Privacy Policy</a>.
                        </label>
                    </div>
                    <div class="mt-3 p-3 rounded border border-secondary border-opacity-10 text-start" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                        <h6 class="text-warning fw-bold mb-1 smaller uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;"><i class="bi bi-info-circle-fill me-1.5"></i> Cancellation & Refund Policy:</h6>
                        <p class="text-muted smaller mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                            Cancellations made <strong>more than 24 hours</strong> prior to your scheduled visit qualify for a <strong>100% full refund</strong>. Cancellations requested <strong>within 24 hours</strong> of your scheduled time are subject to a <strong>50% administrative cancellation fee</strong> (50% refund).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-5">
            <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(4)">
                <i class="bi bi-arrow-left me-2"></i> BACK
            </button>
            <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="self_submit_btn">
                CONFIRM & REGISTER <i class="bi bi-check2-circle ms-2"></i>
            </button>
        </div>
    </div>
</div>