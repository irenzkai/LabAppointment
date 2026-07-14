<div class="modal fade resubmit-modal" id="resubmitModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="{{ route('appointments.update', $app->id) }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg" onsubmit="compileResAddress('{{$app->id}}')">
            @csrf
            @method('PUT')

            <input type="hidden" name="patient_province" id="res_province_hidden_{{$app->id}}" value="{{ $app->patient_province }}">
            <input type="hidden" name="patient_city" id="res_city_hidden_{{$app->id}}" value="{{ $app->patient_city }}">
            <input type="hidden" name="patient_barangay" id="res_barangay_hidden_{{$app->id}}" value="{{ $app->patient_barangay }}">

            {{-- Modal Header --}}
            <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0">
                    Resubmit Appointment #{{ $app->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start text-main" style="max-height: 70vh; overflow-y: auto; background-color: var(--bg-card);">

                {{-- Expiration Resubmit Warnings --}}
                @if($app->isExpired())
                    <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-warning p-3 rounded mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <strong>Rescheduling Notice:</strong> This expired appointment is currently inactive. Resubmitting with a new schedule will reactivate this record on your dashboard.
                    </div>
                @endif

                {{-- SECTION 1: IDENTITY CORRECTION (Step 2) --}}
                <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                    <h6 class="text-accent fw-bold mb-3 small uppercase">1. Correct Patient Demographics</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">First Name</label>
                            <input type="text" name="patient_first_name" class="form-control" value="{{ old('patient_first_name', $app->patient_first_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Middle Name</label>
                            <input type="text" name="patient_middle_name" class="form-control" value="{{ old('patient_middle_name', $app->patient_middle_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Last Name</label>
                            <input type="text" name="patient_last_name" class="form-control" value="{{ old('patient_last_name', $app->patient_last_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Contact Number</label>
                            <input type="text" name="patient_phone" class="form-control" value="{{ old('patient_phone', $app->patient_phone) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                            <select name="patient_sex" class="form-select" required>
                                <option value="Male" {{ old('patient_sex', $app->patient_sex) == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('patient_sex', $app->patient_sex) == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                            <input type="date" name="patient_birthdate" class="form-control" value="{{ old('patient_birthdate', $app->patient_birthdate ? $app->patient_birthdate->format('Y-m-d') : '') }}" required max="{{ date('Y-m-d') }}">
                        </div>

                        {{-- Optional Referral Note Upload --}}
                        @if(!$app->batch_id)
                            <div class="col-12 mt-2">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Doctor's Referral / Note (Optional)</label>
                                <input type="file" name="referral_note" class="form-control" accept="image/*, application/pdf">
                                @if($app->referral_note)
                                    <div class="text-accent small mt-1"><i class="bi bi-file-earmark-check"></i> Existing Referral File Attached on Server</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SECTION 2: ADRESS CORRECTION (Step 2 Part 2) --}}
                <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                    <h6 class="text-accent fw-bold mb-3 small uppercase">2. Correct Residential Address</h6>
                    <div class="row g-3">
                        @if($app->batch_id)
                            {{-- Unified single street input for bulk individual --}}
                            <div class="col-12">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Home Address</label>
                                <input type="text" name="patient_street" class="form-control uppercase" value="{{ old('patient_street', $app->patient_street) }}" required placeholder="Enter complete address...">
                            </div>
                        @else
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Province</label>
                                <select id="res_province_{{$app->id}}" class="form-select" onchange="fetchResCities('{{$app->id}}', this.value)" required>
                                    <option value="">Loading Provinces...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                                <select id="res_city_{{$app->id}}" class="form-select" onchange="fetchResBarangays('{{$app->id}}', this.value)" disabled required>
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Barangay</label>
                                <select id="res_brgy_{{$app->id}}" class="form-select" disabled required>
                                    <option value="">Select City First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                                <input type="text" id="res_street_{{$app->id}}" name="patient_street" class="form-control uppercase" value="{{ old('patient_street', $app->patient_street) }}" required>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SECTION 3: TEST SELECTIONS WITH SEARCH FILTER (Step 3) --}}
                <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                    <h6 class="text-accent fw-bold mb-1 small uppercase">3. Correct Laboratory Tests Selections</h6>
                    <div class="mb-3">
                        <input type="text" id="res_test_search_{{ $app->id }}" class="form-control form-control-sm" placeholder="Search test name..." onkeyup="filterResTests('{{ $app->id }}')">
                    </div>
                    <div class="row g-2 overflow-auto" id="res_test_list_{{ $app->id }}" style="max-height: 250px;">
                        @php $linkedTests = $app->services->pluck('id')->toArray(); @endphp
                        @foreach($services as $s)
                            <div class="col-md-6 col-12 res-test-item-{{ $app->id }}" data-name="{{ strtoupper($s->name) }}">
                                <div class="form-check p-2 border border-secondary border-opacity-10 rounded bg-secondary bg-opacity-5">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="res_test_{{$app->id}}_{{ $s->id }}" {{ in_array($s->id, $linkedTests) ? 'checked' : '' }}>
                                    <label class="form-check-label text-white small" for="res_test_{{$app->id}}_{{ $s->id }}">
                                        {{ strtoupper($s->name) }} ({{ number_format($s->price) }})
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- SECTION 4: RESCHEDULE VISIT WITH LEAD TIME SAFEGUARDS (Step 4) --}}
                <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                    <h6 class="text-accent fw-bold mb-3 small uppercase">4. Correct Schedule Visit</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Preferred Date</label>
                            <input type="date" name="appointment_date" id="res_date_{{$app->id}}" class="form-control" value="{{ old('appointment_date', $app->appointment_date ? $app->appointment_date->format('Y-m-d') : '') }}" required min="{{ date('Y-m-d') }}" onchange="fetchResTimeSlots('{{$app->id}}', this.value, '')">
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Preferred Time Slot</label>
                            <select name="time_slot" id="res_ts_{{$app->id}}" class="form-select fw-bold" onchange="toggleResSubmitBtn('{{$app->id}}')" required>
                                <option value="">Choose Date First...</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SECTION 5: PAYMENT METHOD SELECTOR WITH DYNAMIC QR CHANNELS --}}
                @if($app->batch_id)
                    <input type="hidden" name="payment_method" value="{{ $app->payment_method }}">
                @else
                    <div class="mb-2">
                        <h6 class="text-accent fw-bold mb-3 small uppercase">5. Settle Payment Method</h6>
                        @if($app->payment_status === 'paid')
                            {{-- Preserves already verified payment states with an informative alert --}}
                            <div class="alert alert-clinical border-success bg-success bg-opacity-10 text-success p-3 rounded mb-2">
                                <i class="bi bi-patch-check-fill me-2 fs-5"></i>
                                <strong>Payment Status: PAID</strong> Clinical transaction verified. No further actions or re-scanning are required for this resubmission.
                            </div>
                            <input type="hidden" name="payment_method" value="{{ $app->payment_method }}">
                        @else
                            {{-- FIXED: Added custom interactive layout highlights to selected payment cards --}}
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="res_pay_cash_{{$app->id}}" value="Cash" {{ old('payment_method', $app->payment_method) == 'Cash' ? 'checked' : '' }} onchange="toggleResPaymentFields('{{$app->id}}')">
                                    <label class="btn payment-method-card w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="res_pay_cash_{{$app->id}}">
                                        <i class="bi bi-cash-stack fs-2 mb-2"></i>
                                        <div class="small fw-bold uppercase payment-title">Cash on Site</div>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="res_pay_cashless_{{$app->id}}" value="Cashless" {{ old('payment_method', $app->payment_method) == 'Cashless' ? 'checked' : '' }} onchange="toggleResPaymentFields('{{$app->id}}')">
                                    <label class="btn payment-method-card w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="res_pay_cashless_{{$app->id}}">
                                        <i class="bi bi-qr-code-scan fs-2 mb-2"></i>
                                        <div class="small fw-bold uppercase payment-title">Online / E-Wallet</div>
                                    </label>
                                </div>
                            </div>
                            
                            {{-- Dynamic E-Wallet Selector --}}
                            <div id="res_provider_container_{{$app->id}}" class="mb-3 d-none animate-fade-in">
                                <label class="text-accent smaller fw-bold uppercase d-block mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Choose E-Wallet Provider</label>
                                <div class="row g-2">
                                    @if(isset($paymentProviders) && $paymentProviders->count() > 0)
                                        @foreach($paymentProviders as $provider)
                                            <div class="col-6">
                                                <input type="radio" class="btn-check res-provider-radio-{{$app->id}}" name="payment_provider_id" id="res_prov_{{$app->id}}_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" onchange="updateResQR('{{$app->id}}', this)">
                                                <label class="btn btn-outline-secondary w-100 p-2 text-center d-flex align-items-center justify-content-center gap-2" for="res_prov_{{$app->id}}_{{ $provider->id }}">
                                                    @if($provider->logo)
                                                        <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" style="height: 20px; object-fit: contain;">
                                                    @else
                                                        <i class="bi bi-wallet2 text-secondary"></i>
                                                    @endif
                                                    {{-- FIXED: Changed 'text-white' to 'text-main' to fix invisible text in light mode --}}
                                                    <span class="smaller fw-bold text-main uppercase" style="font-size: 0.65rem;">{{ $provider->name }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="alert alert-clinical text-center p-2 mb-0">
                                                <span class="small text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> No active payment gateways configured.</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- QR Code Display Box --}}
                            <div id="res_qr_section_{{$app->id}}" class="mb-3 d-none animate-fade-in">
                                <div class="p-3 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                                    <small class="text-main fw-bold mb-2 d-block uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Scan to Pay (<span id="res_selected_provider_name_{{$app->id}}" class="text-accent"></span>)</small>
                                    <div class="d-flex justify-content-center">
                                        {{-- FIXED: Configured proper click-to-zoom mapping on QR display --}}
                                        <div class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" onclick="window.zoomQR(document.getElementById('res_selected_provider_qr_{{$app->id}}').src)" title="Click to view full screen">
                                            <img src="" id="res_selected_provider_qr_{{$app->id}}" alt="Scan QR" style="width: 140px; height: 140px; object-fit: contain;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Receipt Uploader --}}
                            <div id="res_receipt_container_{{$app->id}}" class="mb-3 d-none animate-fade-in">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>
                                <input type="file" name="payment_receipt" id="res_receipt_input_{{$app->id}}" class="form-control" accept="image/*, application/pdf">
                                @if($app->payment_receipt)
                                    <div class="text-accent small mt-1"><i class="bi bi-file-earmark-check"></i> Existing Receipt File Attached on Server</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            
            {{-- Modal Footer --}}
            <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
                <div class="d-flex w-100">
                    <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="res_submit_btn_{{$app->id}}" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Submit For Approval</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('resubmitModal{{ $app->id }}');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', async () => {
                // Lazy load PSGC and slots on modal reveal
                @if(!$app->batch_id)
                    await initResAddress('{{ $app->id }}', '{{ $app->patient_province }}', '{{ $app->patient_city }}', '{{ $app->patient_barangay }}');
                    toggleResPaymentFields('{{ $app->id }}');
                @endif
                await fetchResTimeSlots('{{ $app->id }}', '{{ $app->appointment_date ? $app->appointment_date->format("Y-m-d") : "" }}', '{{ $app->time_slot }}');
            });
        }
    });

    // Real-time Step 3 test search filtering scoped to specific modal ID
    function filterResTests(appId) {
        const query = document.getElementById(`res_test_search_${appId}`).value.toUpperCase();
        document.querySelectorAll(`#res_test_list_${appId} .res-test-item-${appId}`).forEach(item => {
            const name = item.dataset.name || '';
            if (name.includes(query)) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
    }

    // PSGC Address API loaders uniquely scoped to individual modal ID
    async function initResAddress(appId, savedProv, savedCity, savedBrgy) {
        try {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/`);
            const data = await res.json();
            const sel = document.getElementById(`res_province_${appId}`);
            if (sel) {
                sel.innerHTML = '<option value="">Select Province</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                    sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
                });

                if (savedProv) {
                    let provOpt = Array.from(sel.options).find(opt => opt.text.toUpperCase() === savedProv.toUpperCase());
                    if (provOpt) {
                        sel.value = provOpt.value;
                        await fetchResCities(appId, sel.value, savedCity, savedBrgy);
                    }
                }
            }
        } catch (e) {
            console.error("Provinces fetch error", e);
        }
    }

    async function fetchResCities(appId, provCode, savedCity = '', savedBrgy = '') {
        if (!provCode) return;
        const citySel = document.getElementById(`res_city_${appId}`);
        const brgySel = document.getElementById(`res_brgy_${appId}`);
        if (citySel && brgySel) {
            citySel.disabled = true;
            brgySel.disabled = true;
            citySel.innerHTML = '<option value="">Loading Cities...</option>';

            try {
                const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
                const data = await res.json();
                citySel.innerHTML = '<option value="">Select City</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                    citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
                });
                citySel.disabled = false;

                if (savedCity) {
                    let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === savedCity.toUpperCase());
                    if (cityOpt) {
                        citySel.value = cityOpt.value;
                        await fetchResBarangays(appId, citySel.value, savedBrgy);
                    }
                }
            } catch (e) {
                console.error("Cities fetch error", e);
            }
        }
    }

    async function fetchResBarangays(appId, cityCode, savedBrgy = '') {
        if (!cityCode) return;
        const brgySel = document.getElementById(`res_brgy_${appId}`);
        if (brgySel) {
            brgySel.disabled = true;
            brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

            try {
                const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                const data = await res.json();
                brgySel.innerHTML = '<option value="">Select Barangay</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                    brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
                });
                brgySel.disabled = false;

                if (savedBrgy) {
                    let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === savedBrgy.toUpperCase());
                    if (brgyOpt) {
                        brgySel.value = brgyOpt.value;
                    }
                }
            } catch (e) {
                console.error("Barangays fetch error", e);
            }
        }
    }

    function compileResAddress(appId) {
        const brgy = document.getElementById(`res_brgy_${appId}`);
        const city = document.getElementById(`res_city_${appId}`);
        const prov = document.getElementById(`res_province_${appId}`);

        if (brgy && city && prov) {
            const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
            const cityName = city.options[city.selectedIndex]?.text || '';
            const provName = prov.options[prov.selectedIndex]?.text || '';

            if (brgyName && cityName && provName) {
                document.getElementById(`res_province_hidden_${appId}`).value = provName;
                document.getElementById(`res_city_hidden_${appId}`).value = cityName;
                document.getElementById(`res_barangay_hidden_${appId}`).value = brgyName;
            }
        }
    }

    // Scoped time slot generator with STRICT dynamic lead-time check
    async function fetchResTimeSlots(appId, date, savedSlot = '') {
        const select = document.getElementById(`res_ts_${appId}`);
        if (!date || !select) return;

        select.innerHTML = '<option value="">Checking slots...</option>';
        select.disabled = true;

        try {
            const res = await fetch(`/api/check-slots?date=${date}&exclude_id=${appId}`);
            const data = await res.json();

            if (data.is_closed) {
                select.innerHTML = '<option value="">CLINIC CLOSED</option>';
                return;
            }

            const config = data.config;
            let html = '<option value="">Choose Available Time</option>';
            let start = new Date(`2000-01-01 ${config.opening_time}`);
            let end = new Date(`2000-01-01 ${config.closing_time}`);
            let availableCount = 0;

            const now = new Date();
            const todayLocal = now.toLocaleDateString('en-CA');

            while (start < end) {
                let hours = start.getHours().toString().padStart(2, '0');
                let minutes = start.getMinutes().toString().padStart(2, '0');
                let tStr = `${hours}:${minutes}:00`;

                let isFull = data.full_slots.includes(tStr);
                let isLunch = (config.has_lunch_break && tStr >= config.lunch_start && tStr < config.lunch_end);

                // STRICT lead-time buffer checking logic when choosing time slots for "today"
                let isPast = false;
                if (date === todayLocal) {
                    const leadTimeMs = (parseInt(config.lead_time_hours) || 0) * 3600 * 1000;
                    const cutoffTime = now.getTime() + leadTimeMs;
                    const slotDate = new Date(`${date} ${tStr}`);
                    isPast = slotDate.getTime() < cutoffTime;
                }

                if (!isFull && !isLunch && !isPast) {
                    let disp = start.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                    let selectedAttr = (savedSlot === tStr) ? 'selected' : '';
                    html += `<option value="${tStr}" ${selectedAttr}>${disp}</option>`;
                    availableCount++;
                }

                start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
            }

            select.innerHTML = availableCount > 0 ? html : '<option value="">NO SLOTS AVAILABLE</option>';
            select.disabled = (availableCount === 0);
            toggleResSubmitBtn(appId);

        } catch (e) {
            console.error("Slots fetch error", e);
            select.innerHTML = '<option value="">Error syncing schedule</option>';
        }
    }

    function toggleResSubmitBtn(appId) {
        const select = document.getElementById(`res_ts_${appId}`);
        const submitBtn = document.getElementById(`res_submit_btn_${appId}`);
        if (select && submitBtn) {
            submitBtn.disabled = (select.value === "");
        }
    }

    // FIXED: Form validation and progressive disclosure fields display logic
    function toggleResPaymentFields(appId) {
        const payCashless = document.getElementById(`res_pay_cashless_${appId}`);
        const providerContainer = document.getElementById(`res_provider_container_${appId}`);
        const receiptContainer = document.getElementById(`res_receipt_container_${appId}`);
        const receiptInput = document.getElementById(`res_receipt_input_${appId}`);
        const qrSection = document.getElementById(`res_qr_section_${appId}`);
        const radios = document.querySelectorAll(`.res-provider-radio-${appId}`);

        if (payCashless && payCashless.checked) {
            if (providerContainer) providerContainer.classList.remove('d-none');

            // Progressive disclosure: Only reveal the receipt input once a cashless provider is active
            const activeRadio = document.querySelector(`.res-provider-radio-${appId}:checked`);
            if (activeRadio) {
                if (qrSection) qrSection.classList.remove('d-none');
                if (receiptContainer) receiptContainer.classList.remove('d-none');
                if (receiptInput) receiptInput.setAttribute('required', 'required');
            } else {
                if (qrSection) qrSection.classList.add('d-none');
                if (receiptContainer) receiptContainer.classList.add('d-none');
                if (receiptInput) receiptInput.removeAttribute('required');
            }
        } else {
            if (providerContainer) providerContainer.classList.add('d-none');
            if (receiptContainer) receiptContainer.classList.add('d-none');
            if (receiptInput) receiptInput.removeAttribute('required');
            if (qrSection) qrSection.classList.add('d-none');
            radios.forEach(radio => radio.checked = false);
        }
    }

    function updateResQR(appId, radio) {
        if (radio.checked) {
            document.getElementById(`res_selected_provider_qr_${appId}`).src = radio.dataset.qr;
            document.getElementById(`res_selected_provider_name_${appId}`).innerText = radio.dataset.name;
            document.getElementById(`res_qr_section_${appId}`).classList.remove('d-none');
            toggleResPaymentFields(appId);
        }
    }
</script>

<style>
/* Payment Method Selection Cards Style adjustments */
.payment-method-card {
    border: 1.5px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}
.payment-method-card i {
    color: var(--text-muted) !important;
    transition: all 0.2s ease;
}

/* Highlights selected payment method cleanly with themed border-accent glow */
.btn-check:checked + .payment-method-card {
    background-color: rgba(25, 211, 140, 0.07) !important;
    border-color: var(--brand-accent) !important;
    border-width: 2px !important;
    box-shadow: 0 0 12px rgba(25, 211, 140, 0.15) !important;
}
.btn-check:checked + .payment-method-card i,
.btn-check:checked + .payment-method-card .payment-title {
    color: var(--brand-accent) !important;
}
[data-bs-theme="light"] .btn-check:checked + .payment-method-card i,
[data-bs-theme="light"] .btn-check:checked + .payment-method-card .payment-title {
    color: #15b376 !important;
}
</style>