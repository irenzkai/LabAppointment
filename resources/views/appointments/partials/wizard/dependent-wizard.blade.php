<!-- resources/views/appointments/partials/wizard/dependent-wizard.blade.php -->
<!-- DEPENDENT APPOINTMENT FLOW CONTAINER -->
<div id="dependent-wizard-container" class="d-none">
    <!-- ========================================================================= -->
    <!-- DEPENDENT STEP 2: DEPENDENT DETAILS (READONLY VERIFIED IDENTITY SNAPSHOT) -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="dep-step-2">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-2">
            <div>
                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Dependent Information</h3>
                <p class="text-secondary small mb-0">Review the verified clinical details for this family dependent.</p>
            </div>
            <a href="{{ route('profile.edit') }}#tab-dependents" target="_blank" id="dep_edit_profile_link" class="btn btn-sm btn-outline-accent py-2 px-3 fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                <i class="bi bi-person-gear"></i> EDIT DEPENDENT
            </a>
        </div>

        {{-- Guardian / Account Owner Info Accordion --}}
        <div id="guardian_info_card" class="col-12 mb-4">
            <div class="accordion" id="guardianAccordion">
                <div class="accordion-item border-secondary border-opacity-25 bg-card overflow-hidden rounded-3 shadow-sm" style="border-color: var(--border-color) !important;">
                    <h2 class="accordion-header" id="headingGuardian">
                        <button class="accordion-button collapsed bg-secondary bg-opacity-10 text-main py-2.5 px-3 fw-bold small uppercase shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGuardian" aria-expanded="false" aria-controls="collapseGuardian">
                            <i class="bi bi-shield-check text-accent me-2 fs-5"></i>
                            Guardian / Parent Information (Account Owner)
                        </button>
                    </h2>
                    <div id="collapseGuardian" class="accordion-collapse collapse" aria-labelledby="headingGuardian" data-bs-parent="#guardianAccordion">
                        <div class="accordion-body p-3 bg-card border-top border-secondary border-opacity-10 text-start" style="border-color: var(--border-color) !important;">
                            <div class="row g-2 text-start">
                                <div class="col-md-4">
                                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Guardian Name</small>
                                    <div class="text-main fw-bold small">{{ strtoupper(Auth::user()->name) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Contact Number</small>
                                    <div class="text-main fw-bold small">{{ Auth::user()->phone }}</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Email Address</small>
                                    <div class="text-main fw-bold small">{{ Auth::user()->email }}</div>
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Registered Home Address</small>
                                    <div class="text-main small">{{ Auth::user()->address }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Verified Dependent Identity Display Card --}}
        <div class="card p-4 border rounded-3 mb-4 bg-card" style="border-color: var(--border-color) !important;">
            <div class="d-flex align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-10">
                <span class="badge bg-secondary text-white px-2.5 py-1 small rounded uppercase fw-bold" style="font-size: 0.68rem;">
                    <i class="bi bi-people me-1"></i>VERIFIED FAMILY DEPENDENT
                </span>
            </div>

            <div class="row g-3">
                {{-- Dependent Full Name --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Dependent Full Name</label>
                    <div class="fw-bold text-main fs-6 text-truncate" id="dep_card_name">Please select a dependent in Step 1</div>
                </div>

                {{-- Age & Biological Classification --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Age & Classification</label>
                    <div class="fw-bold text-main fs-6" id="dep_card_age_sex">--</div>
                </div>

                {{-- Contact Phone --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Guardian Mobile Number</label>
                    <div class="fw-bold text-main fs-6" id="dep_card_phone">
                        <i class="bi bi-telephone text-accent me-1"></i>{{ Auth::user()->phone }}
                    </div>
                </div>

                {{-- Digital Results Email --}}
                <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Guardian Account Email</label>
                    <div class="fw-bold text-main fs-6 text-truncate">
                        <i class="bi bi-envelope text-accent me-1"></i>{{ Auth::user()->email }}
                    </div>
                </div>

                {{-- Registered Address --}}
                <div class="col-12 pt-1">
                    <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Registered Residential Address</label>
                    <div class="fw-bold text-main small" id="dep_card_address">
                        <i class="bi bi-geo-alt text-accent me-1"></i>--
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden Input Fields (Preserves backend compatibility & client-side scripts) --}}
        <input type="hidden" name="patient_first_name" id="dep_first_name">
        <input type="hidden" name="patient_middle_name" id="dep_middle_name">
        <input type="hidden" name="patient_last_name" id="dep_last_name">
        <input type="hidden" name="patient_suffix" id="dep_suffix">
        <input type="hidden" name="patient_sex" id="dep_sex">
        <input type="hidden" name="patient_birthdate" id="dep_bday">
        <input type="hidden" name="patient_phone" id="dep_phone_hidden">
        <input type="hidden" id="dep_phone_display">
        <input type="hidden" name="patient_street" id="dep_street">
        <input type="hidden" name="patient_barangay" id="dep_brgy">
        <input type="hidden" name="patient_city" id="dep_city">
        <input type="hidden" name="patient_province" id="dep_province">

        {{-- Doctor's Referral File Upload --}}
        <div class="col-12 mt-2">
            <label class="small text-secondary fw-bold mb-1 uppercase">Doctor's Referral / Physician Note (Optional)</label>
            <div id="dep_referral_wrapper">
                <input type="file" name="referral_note" id="dep_referral" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReferralUpload(this)">
            </div>
            <div id="dep_referral_preview_container" class="d-none mt-3 p-3 rounded" style="background-color: rgba(25, 211, 140, 0.03); border: 1px solid rgba(25, 211, 140, 0.15);">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-accent fw-semibold" id="dep_referral_file_label"><i class="bi bi-file-earmark-check-fill me-1"></i>Selected File</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="viewReferralFile('dep')">View</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold" onclick="removeUploadedReferral('dep')">Remove</button>
                    </div>
                </div>
            </div>
            <small class="text-muted smaller d-block mt-1">Attach a doctor's referral note or prescription image if available.</small>
        </div>

        <div class="d-flex gap-2 mt-5">
            <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(1)">
                <i class="bi bi-arrow-left me-2"></i> BACK
            </button>
            <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateDepStep2()">
                NEXT: SELECT TESTS & PACKAGES <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- DEPENDENT STEP 3: SELECT TESTS & PACKAGES (GENDER-FILTERED)               -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="dep-step-3">
        <div class="mb-4 border-bottom border-secondary border-opacity-10 pb-3">
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 3: Diagnostic Tests & Packages</h3>
            <p class="text-secondary small mb-3">Choose health packages or individual laboratory tests requested for your dependent.</p>
            
            {{-- Quick Search Input --}}
            <div class="input-group">
                <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
                <input type="text" id="depTestSearch" class="form-control shadow-none" placeholder="Search tests or packages (e.g. Pedia Package, CBC, Urinalysis)..." oninput="filterTestList('dep')">
            </div>
        </div>

        {{-- Sub-Tabs: Packages vs Individual Services --}}
        <ul class="nav nav-pills mb-3 gap-2 border-bottom border-secondary border-opacity-10 pb-2.5 flex-wrap" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold px-3 py-1.5 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#dep-pane-packages" type="button" role="tab">
                    <i class="bi bi-box-seam me-1"></i> Health Packages ({{ $packages->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold px-3 py-1.5 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#dep-pane-services" type="button" role="tab">
                    <i class="bi bi-flask me-1"></i> Individual Tests ({{ $services->count() }})
                </button>
            </li>
        </ul>

        <div class="tab-content" id="depTestTabsContent">
            {{-- 1. PACKAGES LIST --}}
            <div class="tab-pane fade show active" id="dep-pane-packages" role="tabpanel">
                <div class="test-list-container dep-test-list custom-scroll border border-secondary border-opacity-25 rounded bg-card" style="max-height: 480px; overflow-y: auto;">
                    @forelse($packages as $pkg)
                        @php $pkgGender = strtolower($pkg->gender_restriction ?? 'both'); @endphp
                        <div class="test-item border-bottom border-secondary border-opacity-10 transition-all p-3" data-name="{{ strtoupper($pkg->name) }}" data-gender="{{ $pkgGender }}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <input type="checkbox" name="package_ids[]" value="{{ $pkg->id }}" id="dep_pkg_{{ $pkg->id }}" class="pkg-checkbox dep-pkg-checkbox d-none" data-name="{{ $pkg->name }}" data-price="{{ $pkg->price }}" data-services="{{ json_encode($pkg->services->pluck('id')->toArray()) }}" onchange="handleTestSelectionChange('dep');">
                                    <label class="d-flex align-items-center cursor-pointer mb-0" for="dep_pkg_{{ $pkg->id }}">
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
                                                <span class="badge bg-info text-white smaller" style="font-size: 0.62rem;">
                                                    <i class="bi bi-check2-all me-1"></i>{{ $pkg->services->count() }} Tests
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-2 ps-2">
                                    <span class="badge bg-danger text-white border border-danger pkg-conflict-badge d-none smaller" style="font-size: 0.6rem;">Conflicting</span>
                                    <div class="text-accent fw-bold fs-6 text-nowrap">₱{{ number_format($pkg->price, 2) }}</div>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-secondary" onclick="toggleTestDetails('dep_pkg_details_{{ $pkg->id }}', this)" title="View Details">
                                        <i class="bi bi-chevron-down fs-5"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="dep_pkg_details_{{ $pkg->id }}" class="test-details-drawer d-none mt-3 p-3 rounded text-start animate-page" style="background-color: var(--bg-main);">
                                @if(!empty($pkg->description))
                                    <p class="mb-2 small text-muted lh-base" style="font-size: 0.8rem;">{{ $pkg->description }}</p>
                                @endif
                                
                                {{-- Included Constituent Services --}}
                                @if($pkg->services && $pkg->services->isNotEmpty())
                                    <div class="mb-2 pt-2 border-top border-secondary border-opacity-10">
                                        <small class="text-accent fw-800 d-block mb-1 fs-x-small uppercase"><i class="bi bi-check2-circle me-1"></i>Included Tests in this Package:</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($pkg->services as $svc)
                                                <span class="badge bg-secondary text-white border border-secondary px-2 py-0.5 rounded fw-semibold" style="font-size: 0.68rem;">
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
                </div>
            </div>

            {{-- 2. INDIVIDUAL SERVICES LIST --}}
            <div class="tab-pane fade" id="dep-pane-services" role="tabpanel">
                <div class="test-list-container dep-test-list custom-scroll border border-secondary border-opacity-25 rounded bg-card" style="max-height: 480px; overflow-y: auto;">
                    @forelse($services as $s)
                        @php $sGender = strtolower($s->gender_restriction ?? 'both'); @endphp
                        <div class="test-item border-bottom border-secondary border-opacity-10 transition-all p-3" id="service_wrapper_{{ $s->id }}" data-name="{{ strtoupper($s->name) }}" data-gender="{{ $sGender }}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="dep_test_{{ $s->id }}" class="service-checkbox test-checkbox dep-test-checkbox svc-chk-item d-none" data-name="{{ $s->name }}" data-price="{{ $s->price }}" onchange="handleTestSelectionChange('dep');">
                                    <label class="d-flex align-items-center cursor-pointer mb-0" for="dep_test_{{ $s->id }}">
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
                                                <span class="badge bg-warning text-dark border border-warning pkg-covered-badge d-none smaller" style="font-size: 0.62rem;">
                                                    <i class="bi bi-info-circle me-1"></i>Covered by Selected Package
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-3 ps-2">
                                    <div class="text-accent fw-bold fs-6 text-nowrap">₱{{ number_format($s->price, 2) }}</div>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-secondary" onclick="toggleTestDetails('dep_details_{{ $s->id }}', this)" title="View Details">
                                        <i class="bi bi-chevron-down fs-5"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="dep_details_{{ $s->id }}" class="test-details-drawer d-none mt-3 p-3 rounded text-start animate-page" style="background-color: var(--bg-main);">
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
    <!-- DEPENDENT STEP 4: SELECT SCHEDULE                                         -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="dep-step-4">
        <div class="mb-4">
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 4: Select Schedule</h3>
            <p class="text-secondary small">Choose your preferred date and time block for the laboratory visit.</p>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-md-5 border-end border-secondary border-opacity-25 pe-md-4 d-flex flex-column justify-content-between">
                <div>
                    <label class="small text-accent fw-bold mb-2 d-block uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">1. Pick a Date</label>
                    <input type="date" name="appointment_date" id="dep_wiz_date" class="form-control py-3 shadow-none fw-bold mb-4" min="{{ date('Y-m-d') }}" onchange="fetchTimeSlots('dep')">
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
                <div id="dep_slots_container" class="row g-2 overflow-auto custom-scroll" style="max-height: 420px; min-height: 250px;">
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
    <!-- DEPENDENT STEP 5: PAYMENT & FINALIZE                                      -->
    <!-- ========================================================================= -->
    <div class="wiz-section d-none text-start animate-page" id="dep-step-5">
        <div class="mb-4">
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 5: Payment & Finalize</h3>
            <p class="text-secondary small">Choose how you would like to settle your laboratory fees.</p>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem;">Select Payment Method</label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="radio" class="btn-check text-main" name="payment_method" id="dep_pay_cash" value="Cash" checked autocomplete="off" onchange="togglePaymentFields('dep')">
                        <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="dep_pay_cash">
                            <i class="bi bi-cash-stack fs-1 mb-2"></i>
                            <div class="fw-bold uppercase">Cash on Site</div>
                            <div class="smaller opacity-75 mt-1">Pay at reception desk upon arrival.</div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <input type="radio" class="btn-check text-main" name="payment_method" id="dep_pay_cashless" value="Cashless" autocomplete="off" onchange="togglePaymentFields('dep')">
                        <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="dep_pay_cashless">
                            <i class="bi bi-qr-code-scan fs-1 mb-2"></i>
                            <div class="fw-bold uppercase">Online / E-Wallet</div>
                            <div class="smaller opacity-75 mt-1">Scan and pay using digital wallets.</div>
                        </label>
                    </div>
                </div>
            </div>

            <div id="dep_provider_container" class="col-12 d-none mt-4 animate-fade-in">
                <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem;">Choose E-Wallet Provider</label>
                <div class="row g-3">
                    @foreach($paymentProviders as $provider)
                        <div class="col-md-4 col-6">
                            <input type="radio" class="btn-check provider-radio dep-prov-radio" name="payment_provider_id" id="dep_prov_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" autocomplete="off" onchange="handleProviderChange('dep', this)">
                            <label class="btn btn-outline-secondary w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="dep_prov_{{ $provider->id }}">
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
                <div class="invalid-feedback d-none mt-2" id="err_dep_provider"></div>
            </div>

            <div id="dep_qr_section" class="col-12 d-none animate-fade-in mt-4">
                <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                    <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem;">Scan to Pay (<span id="dep_selected_provider_name" class="text-accent">E-Wallet</span>)</h6>
                    <div class="d-flex justify-content-center">
                        <div id="dep_qr_zoom_wrapper" class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10 cursor-pointer" style="cursor: zoom-in;" onclick="window.zoomQR(document.getElementById('dep_selected_provider_qr').src)" title="Click to view full screen">
                            <img src="" id="dep_selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.7rem;"><i class="bi bi-zoom-in text-accent me-1"></i>Click the QR code image to zoom full-screen.</small>
                </div>
            </div>

            <div id="dep_receipt_container" class="col-12 d-none mt-4 animate-fade-in">
                <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>
                <div id="dep_receipt_input_wrapper">
                    <input type="file" name="payment_receipt" id="dep_in_receipt" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReceiptUpload(this)">
                </div>
                <div id="dep_receipt_preview_container" class="d-none mt-3 p-3 rounded" style="background-color: rgba(25, 211, 140, 0.03); border: 1px solid rgba(25, 211, 140, 0.15);">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-accent fw-semibold" id="dep_receipt_file_label"><i class="bi bi-file-earmark-check-fill me-1"></i>Selected File</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="viewReceiptFile('dep')">View</button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold" onclick="removeUploadedReceipt('dep')">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-secondary border-opacity-25 bg-card p-4">
                    <div class="form-check text-start">
                        <input class="form-check-input" type="checkbox" id="dep_agree_terms" required>
                        <label class="form-check-label text-main small" for="dep_agree_terms">
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
            <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="dep_submit_btn">
                CONFIRM & REGISTER <i class="bi bi-check2-circle ms-2"></i>
            </button>
        </div>
    </div>
</div>

<script>
// Dynamic filtering of tests based on dependent biological sex
function filterDependentTestsByGender(depSex) {
    const sex = (depSex || '').toLowerCase();
    document.querySelectorAll('#dependent-wizard-container .test-item').forEach(item => {
        const restriction = (item.dataset.gender || 'both').toLowerCase();
        let shouldHide = false;
        if (sex === 'male' && restriction === 'female') shouldHide = true;
        if (sex === 'female' && restriction === 'male') shouldHide = true;

        if (shouldHide) {
            item.classList.add('d-none', 'gender-hidden');
            const cb = item.querySelector('input[type="checkbox"]');
            if (cb && cb.checked) {
                cb.checked = false;
                if (typeof handleTestSelectionChange === 'function') handleTestSelectionChange('dep');
            }
        } else {
            item.classList.remove('gender-hidden');
            const searchInput = document.getElementById('depTestSearch');
            const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const name = (item.dataset.name || '').toLowerCase();
            if (!query || name.includes(query)) {
                item.classList.remove('d-none');
            }
        }
    });
}

// Live update card display for family dependent
function updateDepCardDisplay() {
    const fn = document.getElementById('dep_first_name')?.value || '';
    const mn = document.getElementById('dep_middle_name')?.value || '';
    const ln = document.getElementById('dep_last_name')?.value || '';
    const suffix = document.getElementById('dep_suffix')?.value || '';
    const sex = document.getElementById('dep_sex')?.value || '';
    const bday = document.getElementById('dep_bday')?.value || '';
    const phone = document.getElementById('dep_phone_hidden')?.value || user.phone || '';
    const street = document.getElementById('dep_street')?.value || '';
    const brgy = document.getElementById('dep_brgy')?.value || '';
    const city = document.getElementById('dep_city')?.value || '';
    const prov = document.getElementById('dep_province')?.value || '';

    const mid = (mn && mn !== 'N/A') ? ` ${mn}` : '';
    const suf = suffix ? ` ${suffix}` : '';
    const fullName = `${fn}${mid} ${ln}${suf}`.trim();

    let ageStr = 'AGE UNKNOWN';
    if (bday) {
        const dob = new Date(bday);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        ageStr = `${age} YRS OLD (${dob.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})})`;
    }

    const nameEl = document.getElementById('dep_card_name');
    if (nameEl) nameEl.innerText = fullName.toUpperCase() || 'NO DEPENDENT SELECTED';

    const ageSexEl = document.getElementById('dep_card_age_sex');
    if (ageSexEl) ageSexEl.innerText = `${ageStr} • ${sex ? sex.toUpperCase() : 'N/A'}`;

    const phoneEl = document.getElementById('dep_card_phone');
    if (phoneEl) phoneEl.innerHTML = `<i class="bi bi-telephone text-accent me-1.5"></i>${phone}`;

    const addrParts = [street, brgy ? `BRGY. ${brgy}` : '', city, prov].filter(Boolean);
    const addrEl = document.getElementById('dep_card_address');
    if (addrEl) addrEl.innerHTML = `<i class="bi bi-geo-alt text-accent me-1.5"></i>${addrParts.length > 0 ? addrParts.join(', ').toUpperCase() : 'No address on file'}`;

    const sel = document.getElementById('dependent_id');
    const editLink = document.getElementById('dep_edit_profile_link');
    if (editLink && sel && sel.value) {
        editLink.href = `/dependents/${sel.value}/edit`;
    }

    // Apply gender filtering to the tests and packages list
    filterDependentTestsByGender(sex);
}

// Automatically sync the dependent card whenever summary updates or dependent select changes
document.addEventListener('DOMContentLoaded', () => {
    const depSelect = document.getElementById('dependent_id');
    if (depSelect) {
        depSelect.addEventListener('change', () => {
            setTimeout(updateDepCardDisplay, 150);
        });
    }
    setTimeout(updateDepCardDisplay, 300);
});
</script>