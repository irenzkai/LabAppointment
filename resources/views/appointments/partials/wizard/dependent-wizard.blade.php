<!-- DEPENDENT APPOINTMENT FLOW CONTAINER -->
<div id="dependent-wizard-container" class="d-none">
  <!-- DEPENDENT STEP 2: DEPENDENT DETAILS -->
  <div class="wiz-section d-none text-start animate-page" id="dep-step-2">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-2">
      <div>
        <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Dependent Information</h3>
        <p class="text-secondary small mb-0">Please verify or enter the details for this family dependent.</p>
      </div>
      <button type="button" class="btn btn-sm btn-outline-accent py-2 px-3 fw-bold" onclick="resetDependentDetails()">
        <i class="bi bi-arrow-counterclockwise me-1"></i> RESET INFO
      </button>
    </div>
    {{-- Guardian / Account Owner Info Accordion --}}
    <div id="guardian_info_card" class="col-12 mb-4">
      <div class="accordion" id="guardianAccordion">
        <div class="accordion-item border-secondary border-opacity-25 bg-card overflow-hidden rounded-3 shadow-sm">
          <h2 class="accordion-header" id="headingGuardian">
            <button class="accordion-button collapsed bg-secondary bg-opacity-10 text-main py-2.5 px-3 fw-bold small uppercase shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGuardian" aria-expanded="false" aria-controls="collapseGuardian">
              <i class="bi bi-shield-check text-accent me-2 fs-5"></i>
              Guardian / Parent Information (Account Owner)
            </button>
          </h2>
          <div id="collapseGuardian" class="accordion-collapse collapse" aria-labelledby="headingGuardian" data-bs-parent="#guardianAccordion">
            <div class="accordion-body p-3 bg-card border-top border-secondary border-opacity-10 text-start">
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
    <div class="row g-3">
      {{-- First Name --}}
      <div class="col-md-3">
        <div class="d-flex align-items-center mb-1" style="height: 22px;">
          <label class="small text-secondary fw-bold mb-0 uppercase">First Name</label>
        </div>
        <input type="text" name="patient_first_name" id="dep_first_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="First Name" oninput="validateNameInput(this, 'First Name', 'err_dep_first_name', true); updateSummary();" required>
        <div class="invalid-feedback d-none" id="err_dep_first_name"></div>
      </div>
      {{-- Middle Name --}}
      <div class="col-md-3">
        <div class="d-flex justify-content-between align-items-center mb-1" style="height: 22px;">
          <label class="small text-secondary fw-bold mb-0 uppercase">Middle Name</label>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="dep_no_mn" onclick="toggleDepMN(this)" style="margin-top: 0.15rem;">
            <label class="smaller text-muted" style="font-size: 0.65rem; line-height: 1;" for="dep_no_mn">None</label>
          </div>
        </div>
        <input type="text" name="patient_middle_name" id="dep_middle_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Middle Name" oninput="validateNameInput(this, 'Middle Name', 'err_dep_middle_name', false); updateSummary();">
        <div class="invalid-feedback d-none" id="err_dep_middle_name"></div>
      </div>
      {{-- Last Name --}}
      <div class="col-md-3">
        <div class="d-flex align-items-center mb-1" style="height: 22px;">
          <label class="small text-secondary fw-bold mb-0 uppercase">Last Name</label>
        </div>
        <input type="text" name="patient_last_name" id="dep_last_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Last Name" oninput="validateNameInput(this, 'Last Name', 'err_dep_last_name', true); updateSummary();" required>
        <div class="invalid-feedback d-none" id="err_dep_last_name"></div>
      </div>
      {{-- Suffix --}}
      <div class="col-md-3">
        <div class="d-flex align-items-center mb-1" style="height: 22px;">
          <label class="small text-secondary fw-bold mb-0 uppercase">Suffix (Opt.)</label>
        </div>
        <input type="text" name="patient_suffix" id="dep_suffix" list="suffix_options" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="e.g. JR" maxlength="10" oninput="this.value = this.value.replace(/[^a-zA-Z\s.]/g, ''); updateSummary(); clearInlineError(this)">
        <div class="invalid-feedback d-none" id="err_dep_suffix"></div>
      </div>
      {{-- Sex Selector --}}
      <div class="col-md-6">
        <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
        <select name="patient_sex" id="dep_sex" class="form-select py-3 shadow-none" onchange="clearInlineError(this); updateSummary();" required>
          <option value="">Select Sex</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
        <div class="invalid-feedback d-none" id="err_dep_sex"></div>
      </div>
      {{-- Birthdate Input --}}
      <div class="col-md-6">
        <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
        <input type="date" name="patient_birthdate" id="dep_bday" class="form-control py-3 shadow-none" onchange="validateBirthdateInput(); updateSummary();" required>
        <div class="invalid-feedback d-none" id="err_dep_birthdate"></div>
        <small class="text-muted smaller mt-1 d-block">Dependents must be minors under 18 years of age.</small>
      </div>
      {{-- Contact Phone --}}
      <div class="col-12 mt-2">
        <label class="small text-secondary fw-bold mb-1 uppercase">Contact Number</label>
        <div class="input-group">
          <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
          <input type="text" id="dep_phone_display" class="form-control py-3 shadow-none" placeholder="171234567" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncDepPhone(); clearInlineError(this);" required>
        </div>
        <input type="hidden" name="patient_phone" id="dep_phone_hidden" required>
        <div class="invalid-feedback d-none" id="err_dep_phone"></div>
        <div class="mt-1">
          <small class="text-muted smaller">
            <i class="bi bi-info-circle me-1"></i> For dependents, the guardian's contact number is used for notifications.
          </small>
        </div>
      </div>
      {{-- Doctor's Referral File Upload --}}
      <div class="col-12 mt-2">
        <label class="small text-secondary fw-bold mb-1 uppercase">Doctor's Referral / Note (Optional)</label>
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
      </div>
      {{-- Address Section --}}
      <div class="col-12 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 border-top border-secondary border-opacity-10 pt-3">
          <h6 class="text-accent mb-0 small fw-bold uppercase">Residential Address</h6>
          <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="copyParentAddressToDep()">
            <i class="bi bi-geo-alt-fill me-1"></i>Copy Parent's Address
          </button>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
            <select id="dep_province" name="patient_province" class="form-select py-3 shadow-none" onchange="fetchCities(this.value, 'dep'); clearInlineError(this)" required>
              <option value="">Select Province</option>
            </select>
            <div class="invalid-feedback d-none" id="err_dep_province"></div>
          </div>
          <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
            <select id="dep_city" name="patient_city" class="form-select py-3 shadow-none" onchange="fetchBarangays(this.value, 'dep'); clearInlineError(this)" disabled required>
              <option value="">Select Province First</option>
            </select>
            <div class="invalid-feedback d-none" id="err_dep_city"></div>
          </div>
          <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
            <select id="dep_brgy" name="patient_barangay" class="form-select py-3 shadow-none" onchange="updateCompiledAddress('dep'); clearInlineError(this)" disabled required>
              <option value="">Select City First</option>
            </select>
            <div class="invalid-feedback d-none" id="err_dep_barangay"></div>
          </div>
          <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
            <input type="text" id="dep_street" name="patient_street" class="form-control py-3 shadow-none uppercase" placeholder="House/Lot/Block/Street" oninput="updateCompiledAddress('dep'); clearInlineError(this)" required>
            <div class="invalid-feedback d-none" id="err_dep_street"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="d-flex gap-2 mt-5">
      <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(1)">
        <i class="bi bi-arrow-left me-2"></i> BACK
      </button>
      <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateDepStep2()">
        NEXT: SELECT TESTS <i class="bi bi-arrow-right ms-2"></i>
      </button>
    </div>
  </div>

  <!-- DEPENDENT STEP 3: SELECT TESTS -->
  <div class="wiz-section d-none text-start animate-page" id="dep-step-3">
    <div class="mb-4 border-bottom border-secondary border-opacity-10 pb-3">
      <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 3: Select Tests</h3>
      <p class="text-secondary small">Choose the laboratory examinations requested by your physician.</p>
      <div class="mt-3">
        <div class="input-group">
          <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
          <input type="text" id="depTestSearch" class="form-control shadow-none" placeholder="Type test name (e.g. CBC, Lipid Profile, X-Ray)..." oninput="filterTestList('dep')">
        </div>
      </div>
    </div>
    <div class="test-list-container dep-test-list custom-scroll border border-secondary border-opacity-25 rounded bg-card" style="max-height: 520px; overflow-y: auto;">
      @foreach($services as $s)
      <div class="test-item border-bottom border-secondary border-opacity-10 transition-all p-3" data-name="{{ strtoupper($s->name) }}">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center flex-grow-1">
            <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="dep_test_{{ $s->id }}" class="test-checkbox dep-test-checkbox d-none" data-name="{{ $s->name }}" data-price="{{ $s->price }}" onchange="updateSummary();">
            <label class="d-flex align-items-center cursor-pointer mb-0" for="dep_test_{{ $s->id }}">
              <div class="check-indicator rounded border border-secondary me-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                <i class="bi bi-check-lg text-dark d-none"></i>
              </div>
              <div>
                <div class="text-main fw-bold small uppercase mb-1">{{ $s->name }}</div>
                <div class="d-flex gap-2">
                  <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.6rem;">
                    <i class="bi bi-droplet-fill text-danger me-1"></i>{{ $s->sample_required ?? 'N/A' }}
                  </span>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.6rem;">
                    <i class="bi bi-clock me-1"></i>{{ $s->formatted_time }}
                  </span>
                </div>
              </div>
            </label>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="text-accent fw-bold" style="font-size: 0.95rem;">₱{{ number_format($s->price, 2) }}</div>
            <button type="button" class="btn btn-sm btn-link p-0 text-secondary" onclick="toggleTestDetails('dep_details_{{ $s->id }}', this)">
              <i class="bi bi-chevron-down fs-5"></i>
            </button>
          </div>
        </div>
        <div id="dep_details_{{ $s->id }}" class="test-details-drawer d-none mt-3 p-3 rounded text-start animate-page">
          <p class="mb-2 small text-muted lh-base" style="font-size:0.8rem;">{{ $s->description }}</p>
          @if($s->preparation)
          <div class="pt-2 border-top border-secondary border-opacity-25 mt-2">
            <small class="prep-badge d-block mb-1" style="font-size:0.7rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>PREPARATION REQUIRED:</small>
            <p class="mb-0 small text-muted" style="font-size:0.75rem;">{{ $s->preparation }}</p>
          </div>
          @endif
        </div>
      </div>
      @endforeach
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

  <!-- DEPENDENT STEP 4: SELECT SCHEDULE -->
  <div class="wiz-section d-none text-start animate-page" id="dep-step-4">
    <div class="mb-4">
      <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 4: Select Schedule</h3>
      <p class="text-secondary small">Choose your preferred date and time for the laboratory visit.</p>
    </div>
    <div class="row g-4 align-items-stretch">
      <div class="col-md-5 border-end border-secondary border-opacity-25 pe-md-4 d-flex flex-column justify-content-between">
        <div>
          <label class="small text-accent fw-bold mb-2 d-block uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">1. Pick a Date</label>
          <input type="date" name="appointment_date" id="dep_wiz_date" class="form-control py-3 shadow-none fw-bold mb-4" min="{{ date('Y-m-d') }}" onchange="fetchTimeSlots('dep')">
        </div>
        {{-- Fixed High-Contrast Operating Hours Card --}}
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

  <!-- DEPENDENT STEP 5: PAYMENT & FINALIZE -->
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
            <input type="radio" class="btn-check text-main" name="payment_method" id="dep_pay_cash" value="Cash" checked onchange="togglePaymentFields('dep')">
            <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="dep_pay_cash">
              <i class="bi bi-cash-stack fs-1 mb-2"></i>
              <div class="fw-bold uppercase">Cash on Site</div>
              <div class="smaller opacity-75 mt-1">Pay at reception desk upon arrival.</div>
            </label>
          </div>
          <div class="col-md-6">
            <input type="radio" class="btn-check text-main" name="payment_method" id="dep_pay_cashless" value="Cashless" onchange="togglePaymentFields('dep')">
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
            <input type="radio" class="btn-check provider-radio dep-prov-radio" name="payment_provider_id" id="dep_prov_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" onchange="handleProviderChange('dep', this)">
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
            <div id="dep_qr_zoom_wrapper" class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" onclick="window.zoomQR(document.getElementById('dep_selected_provider_qr').src)" title="Click to view full screen">
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