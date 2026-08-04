@php
    $isCanceled = ($app->status === 'canceled');
@endphp

<div class="modal fade resubmit-modal" id="resubmitModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="{{ route('appointments.update', $app->id) }}" id="resubmitForm_{{$app->id}}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg border-0" onsubmit="validateResForm('{{$app->id}}', event)">
            @csrf
            @method('PUT')

            {{-- Cascading Address Trackers --}}
            <input type="hidden" name="patient_province" id="res_province_hidden_{{$app->id}}" value="{{ $app->patient_province }}">
            <input type="hidden" name="patient_city" id="res_city_hidden_{{$app->id}}" value="{{ $app->patient_city }}">
            <input type="hidden" name="patient_barangay" id="res_barangay_hidden_{{$app->id}}" value="{{ $app->patient_barangay }}">

            {{-- File Removal Tracking Flags --}}
            <input type="hidden" name="remove_referral" id="remove_referral_{{$app->id}}" value="0">
            <input type="hidden" name="remove_receipt" id="remove_receipt_{{$app->id}}" value="0">

            {{-- Modal Header --}}
            <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0">
                    Resubmit Appointment #{{ $app->id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start text-main" style="max-height: 70vh; overflow-y: auto; background-color: var(--bg-card);">

                {{-- Expiration Resubmit Warnings --}}
                @if($app->isExpired())
                    <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-warning p-3 rounded mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <strong>Rescheduling Notice:</strong> This expired appointment is currently inactive. Resubmitting with a new schedule will reactivate this record on your dashboard.
                    </div>
                @endif

                {{-- Unrefunded Canceled Appointment Warning --}}
                @if($app->status === 'canceled' && $app->payment_status === 'paid')
                    <div id="unrefunded_warning_flag_{{$app->id}}" class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 rounded mb-4">
                        <i class="bi bi-shield-fill-exclamation me-2 fs-5"></i>
                        <strong>Important: Unrefunded Payment Rollover</strong>
                        <p class="mb-0 small mt-1">This appointment was canceled with a confirmed payment that has not yet been refunded. By resubmitting, you agree that your existing payment will be rolled over to validate this new booking, and it will no longer be eligible for a refund.</p>
                    </div>
                @endif

                {{-- Step-by-Step Horizontal Tab Navigation --}}
                <ul class="nav nav-pills mb-4 justify-content-center gap-1.5" id="resubmitTabs_{{$app->id}}" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active btn-sm" id="res-btn-step-1-{{$app->id}}" onclick="goToResStep('{{$app->id}}', 1)">1. Demographics</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link btn-sm" id="res-btn-step-2-{{$app->id}}" onclick="goToResStep('{{$app->id}}', 2)">2. Address</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link btn-sm" id="res-btn-step-3-{{$app->id}}" onclick="goToResStep('{{$app->id}}', 3)">3. Tests</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link btn-sm" id="res-btn-step-4-{{$app->id}}" onclick="goToResStep('{{$app->id}}', 4)">4. Schedule</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link btn-sm" id="res-btn-step-5-{{$app->id}}" onclick="goToResStep('{{$app->id}}', 5)">5. Payment</button>
                    </li>
                </ul>

                {{-- Custom Dynamic File Confirmation Card Overlay --}}
                <div id="file_confirm_overlay_{{$app->id}}" class="file-confirm-overlay d-none">
                    <div class="card border-danger p-4 text-center mx-auto shadow-lg bg-card" style="max-width: 380px;">
                        <i class="bi bi-trash-fill text-danger display-4 mb-2"></i>
                        <h6 class="text-main fw-bold">Remove Attachment?</h6>
                        <p class="text-secondary small mb-4">Are you sure you want to remove this attached file? This action will take effect once you save and resubmit your details.</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-50" onclick="cancelFileRemoval('{{$app->id}}')">Cancel</button>
                            <button type="button" class="btn btn-sm btn-danger-custom w-50" onclick="confirmFileRemoval('{{$app->id}}')">Yes, Remove</button>
                        </div>
                    </div>
                </div>

                {{-- STEP-BY-STEP CONTENTS --}}
                <div class="tab-content">

                    {{-- STEP 1: DEMOGRAPHICS & REFERRAL --}}
                    <div class="res-step-pane" id="res-step-1-{{$app->id}}">
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
                                    <input type="file" name="referral_note" id="res_referral_input_{{$app->id}}" class="form-control mb-2" accept="image/*, application/pdf" onchange="handleFileChange(this, '{{$app->id}}', 'referral')">
                                    
                                    {{-- File preview container --}}
                                    <div id="existing_referral_container_{{$app->id}}" class="d-flex align-items-center gap-2 mt-2 bg-light bg-opacity-5 p-2.5 rounded border border-secondary border-opacity-10 @if(!$app->referral_note) d-none @endif">
                                        <span id="referral_label_{{$app->id}}" class="text-accent small"><i class="bi bi-file-earmark-check"></i> Existing Referral on Server</span>
                                        <div class="ms-auto d-flex gap-1.5">
                                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5" onclick="viewExistingOrLocalFile('{{$app->id}}', 'referral', '{{ $app->referral_note ? Storage::url($app->referral_note) : '' }}')">
                                                <i class="bi bi-eye-fill"></i> View
                                            </button>
                                            <button type="button" id="referral_remove_btn_{{$app->id}}" class="btn btn-sm btn-outline-danger py-1 px-2.5" onclick="promptFileRemoval('{{$app->id}}', 'referral')">
                                                <i class="bi bi-trash-fill"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn-custom btn-accent" onclick="goToResStep('{{$app->id}}', 2)">Proceed to Address <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>

                    {{-- STEP 2: ADDRESS (PSGC API DRIVEN) --}}
                    <div class="res-step-pane d-none" id="res-step-2-{{$app->id}}">
                        <h6 class="text-accent fw-bold mb-3 small uppercase">2. Correct Residential Address</h6>
                        <div class="row g-3">
                            @if($app->batch_id)
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

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-custom btn-outline-secondary" onclick="goToResStep('{{$app->id}}', 1)"><i class="bi bi-arrow-left"></i> Back</button>
                            <button type="button" class="btn-custom btn-accent" onclick="goToResStep('{{$app->id}}', 3)">Proceed to Tests <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>

                    {{-- STEP 3: LABORATORY TESTS --}}
                    <div class="res-step-pane d-none" id="res-step-3-{{$app->id}}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-accent fw-bold small uppercase mb-0">3. Correct Laboratory Tests Selections</h6>
                            <span class="small text-muted">Selected: <span id="res_selected_tests_summary_{{$app->id}}" class="text-neon fw-bold">None</span></span>
                        </div>
                        <div class="mb-3">
                            <input type="text" id="res_test_search_{{ $app->id }}" class="form-control form-control-sm" placeholder="Search test name..." onkeyup="filterResTests('{{ $app->id }}')">
                        </div>
                        <div class="row g-2 overflow-auto" id="res_test_list_{{ $app->id }}" style="max-height: 250px;">
                            @php $linkedTests = $app->services->pluck('id')->toArray(); @endphp
                            @foreach($services as $s)
                                <div class="col-md-6 col-12 res-test-item-{{ $app->id }}" data-name="{{ strtoupper($s->name) }}">
                                    <div class="form-check p-2 border border-secondary border-opacity-10 rounded bg-secondary bg-opacity-5">
                                        <input class="form-check-input ms-0 me-2 test-checkbox" type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="res_test_{{$app->id}}_{{ $s->id }}" data-label="{{ $s->name }}" {{ in_array($s->id, $linkedTests) ? 'checked' : '' }} onchange="updateSelectedTestsSummary('{{$app->id}}')">
                                        <label class="form-check-label text-white small" for="res_test_{{$app->id}}_{{ $s->id }}">
                                            {{ strtoupper($s->name) }} ({{ number_format($s->price) }})
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-custom btn-outline-secondary" onclick="goToResStep('{{$app->id}}', 2)"><i class="bi bi-arrow-left"></i> Back</button>
                            <button type="button" class="btn-custom btn-accent" onclick="goToResStep('{{$app->id}}', 4)">Proceed to Schedule <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>

                    {{-- STEP 4: SCHEDULE VISIT --}}
                    <div class="res-step-pane d-none" id="res-step-4-{{$app->id}}">
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

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-custom btn-outline-secondary" onclick="goToResStep('{{$app->id}}', 3)"><i class="bi bi-arrow-left"></i> Back</button>
                            <button type="button" class="btn-custom btn-accent" onclick="goToResStep('{{$app->id}}', 5)">Proceed to Payment <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>

                    {{-- STEP 5: PAYMENT & FINALIZE --}}
                    <div class="res-step-pane d-none" id="res-step-5-{{$app->id}}">
                        @if($app->batch_id)
                            <input type="hidden" name="payment_method" value="{{ $app->payment_method }}">
                        @else
                            <div class="mb-2">
                                <h6 class="text-accent fw-bold mb-3 small uppercase">5. Settle Payment Method</h6>
                                @if($app->status === 'returned' && $app->payment_status === 'paid')
                                    {{-- normal returned with confirmed payment --}}
                                    <div class="alert alert-clinical border-success bg-success bg-opacity-10 text-success p-3 rounded mb-2">
                                        <i class="bi bi-patch-check-fill me-2 fs-5"></i>
                                        <strong>Payment Locked (PAID)</strong>: This appointment has been verified as paid. Your payment details are locked and cannot be modified.
                                    </div>
                                    <input type="hidden" name="payment_method" value="{{ $app->payment_method }}">
                                @else
                                    @if($app->status === 'returned')
                                        {{-- normal returned with unconfirmed payment --}}
                                        <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-warning p-3 rounded mb-3">
                                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                            <strong>Important Note</strong>: Changes to your payment method or receipt in this area will not be eligible for a refund. For refunds, cancel your appointment instead.
                                        </div>
                                    @endif

                                    <div class="row g-3 mb-4">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="payment_method" id="res_pay_cash_{{$app->id}}" value="Cash" @if($isCanceled || old('payment_method', $app->payment_method) == 'Cash') checked @endif onchange="toggleResPaymentFields('{{$app->id}}')">
                                            <label class="btn payment-method-card w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="res_pay_cash_{{$app->id}}">
                                                <i class="bi bi-cash-stack fs-2 mb-2"></i>
                                                <div class="small fw-bold uppercase payment-title">Cash on Site</div>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="payment_method" id="res_pay_cashless_{{$app->id}}" value="Cashless" @if(!$isCanceled && old('payment_method', $app->payment_method) == 'Cashless') checked @endif onchange="toggleResPaymentFields('{{$app->id}}')">
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
                                                        <input type="radio" class="btn-check res-provider-radio-{{$app->id}}" name="payment_provider_id" id="res_prov_{{$app->id}}_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" onchange="updateResQR('{{$app->id}}', this)" @if(!$isCanceled && $app->payment_receipt && $loop->first) checked @endif>
                                                        <label class="btn btn-outline-secondary w-100 p-2 text-center d-flex align-items-center justify-content-center gap-2" for="res_prov_{{$app->id}}_{{ $provider->id }}">
                                                            @if($provider->logo)
                                                                <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" style="height: 20px; object-fit: contain;">
                                                            @else
                                                                <i class="bi bi-wallet2 text-secondary"></i>
                                                            @endif
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
                                                <div class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" onclick="window.zoomQR(document.getElementById('res_selected_provider_qr_{{$app->id}}').src)" title="Click to view full screen">
                                                    <img src="" id="res_selected_provider_qr_{{$app->id}}" alt="Scan QR" style="width: 140px; height: 140px; object-fit: contain;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Receipt Uploader --}}
                                    <div id="res_receipt_container_{{$app->id}}" class="mb-3 d-none animate-fade-in">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Upload New Proof of Payment / Receipt</label>
                                        <input type="file" name="payment_receipt" id="res_receipt_input_{{$app->id}}" class="form-control mb-2" accept="image/*, application/pdf" onchange="handleFileChange(this, '{{$app->id}}', 'receipt')">
                                        {{-- Hide the "Existing receipt" alert if they are returning from a canceled, refunded, or invalid state, mandating a new file upload --}}
                                        <div id="existing_receipt_container_{{$app->id}}" class="d-flex align-items-center gap-2 mt-2 bg-light bg-opacity-5 p-2.5 rounded border border-secondary border-opacity-10 @if(!$app->payment_receipt || in_array($app->status, ['canceled']) || in_array($app->payment_status, ['invalid', 'refunded'])) d-none @endif">
                                            <span id="receipt_label_{{$app->id}}" class="text-accent small"><i class="bi bi-file-earmark-check"></i> Existing Receipt on Server</span>
                                            <div class="ms-auto d-flex gap-1.5">
                                                <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5" onclick="viewExistingOrLocalFile('{{$app->id}}', 'receipt', '{{ $app->payment_receipt ? Storage::url($app->payment_receipt) : '' }}')">
                                                    <i class="bi bi-eye-fill"></i> View
                                                </button>
                                                <button type="button" id="receipt_remove_btn_{{$app->id}}" class="btn btn-sm btn-outline-danger py-1 px-2.5" onclick="promptFileRemoval('{{$app->id}}', 'receipt')">
                                                    <i class="bi bi-trash-fill"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="d-flex justify-content-between mt-4 border-top border-secondary border-opacity-10 pt-3">
                            <button type="button" class="btn-custom btn-outline-secondary" onclick="goToResStep('{{$app->id}}', 4)"><i class="bi bi-arrow-left"></i> Back</button>
                            <button type="button" id="res_submit_btn_{{$app->id}}" class="btn-custom btn-accent px-4 fw-bold" onclick="handleResSubmit('{{$app->id}}')">Submit Resubmission</button>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

{{-- Dynamic Resubmission Validation Alert Modal --}}
<div class="modal fade" id="resubmitValidationModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="z-index: 1080;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4">
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-danger display-4 d-block"></i>
            </div>
            <h5 class="text-main fw-bold mb-2 uppercase">Lacking Details</h5>
            <div id="res_validation_msg_{{$app->id}}" class="text-secondary small mb-4 text-start"></div>
            <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" data-bs-dismiss="modal">Understood</button>
        </div>
    </div>
</div>

{{-- Rollover Confirmation Warning Modal --}}
<div class="modal fade" id="resubmitRolloverConfirmModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="z-index: 1080;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content border-warning bg-card shadow-lg p-4 text-center">
            <div class="mb-3">
                <i class="bi bi-shield-fill-exclamation text-warning display-4"></i>
            </div>
            <h5 class="text-main fw-bold mb-2 uppercase">Confirm Payment Rollover</h5>
            <p class="text-secondary small mb-4">This appointment has a confirmed cashless payment that has not been refunded yet. Resubmitting will roll over this payment to the new active appointment. <strong>No refund will be processed</strong> for the original cancellation once resubmitted. Do you wish to proceed?</p>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary w-50" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-accent w-50" onclick="submitResForm('{{$app->id}}')">Yes, Proceed</button>
            </div>
        </div>
    </div>
</div>

{{-- Non-Duplicate Scripts block utilizing the standard @once container --}}
@once
@push('scripts')
<script>
    let activeRemovalType_global = null;
    let activeRemovalAppId_global = null;

    /**
     * Controls horizontal tab wizard navigation
     */
    function goToResStep(appId, step) {
        // Enforce basic front-end validation of current visible panel before moving forward
        const currentPane = document.querySelector(`.resubmit-modal#resubmitModal${appId} .res-step-pane:not(.d-none)`);
        if (currentPane) {
            const currentStepNum = parseInt(currentPane.id.split('-').pop());
            if (step > currentStepNum) {
                const inputs = currentPane.querySelectorAll('input[required], select[required]');
                let valid = true;
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        valid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                if (!valid) {
                    alert('Please complete all required fields on this step first.');
                    return;
                }
            }
        }

        // Hide all step sections
        document.querySelectorAll(`.resubmit-modal#resubmitModal${appId} .res-step-pane`).forEach(pane => {
            pane.classList.add('d-none');
        });

        // Reveal selected step section
        const targetPane = document.getElementById(`res-step-${step}-${appId}`);
        if (targetPane) targetPane.classList.remove('d-none');

        // Update active navigation pills styling
        const tabButtons = document.querySelectorAll(`.resubmit-modal#resubmitModal${appId} #resubmitTabs_${appId} .nav-link`);
        tabButtons.forEach((btn, index) => {
            if (index + 1 === step) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    /**
     * Appends selected services dynamically to the header label [comma formatted]
     */
    function updateSelectedTestsSummary(appId) {
        const checkboxes = document.querySelectorAll(`.resubmit-modal#resubmitModal${appId} .test-checkbox:checked`);
        const summaryLabel = document.getElementById(`res_selected_tests_summary_${appId}`);
        if (summaryLabel) {
            if (checkboxes.length > 0) {
                const labels = Array.from(checkboxes).map(cb => cb.dataset.label);
                summaryLabel.innerText = labels.join(', ');
            } else {
                summaryLabel.innerText = 'None';
            }
        }
    }

    /**
     * Filters standard diagnostic services in the selection list
     */
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

    /**
     * Triggers dynamic confirmation overlays cleanly inside the modal body without z-index scroll traps
     */
    function promptFileRemoval(appId, type) {
        activeRemovalType_global = type;
        activeRemovalAppId_global = appId;
        const overlay = document.getElementById(`file_confirm_overlay_${appId}`);
        if (overlay) {
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
        }
    }

    function cancelFileRemoval(appId) {
        activeRemovalType_global = null;
        activeRemovalAppId_global = null;
        const overlay = document.getElementById(`file_confirm_overlay_${appId}`);
        if (overlay) {
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
        }
    }

    function confirmFileRemoval(appId) {
        const type = activeRemovalType_global;
        if (type === 'referral') {
            const container = document.getElementById(`existing_referral_container_${appId}`);
            if (container) container.classList.add('d-none');

            const hiddenInput = document.getElementById(`remove_referral_${appId}`);
            if (hiddenInput) hiddenInput.value = '1';

            const fileInput = document.getElementById(`res_referral_input_${appId}`);
            if (fileInput) fileInput.value = '';

        } else if (type === 'receipt') {
            const container = document.getElementById(`existing_receipt_container_${appId}`);
            if (container) container.classList.add('d-none');

            const hiddenInput = document.getElementById(`remove_receipt_${appId}`);
            if (hiddenInput) hiddenInput.value = '1';

            const fileInput = document.getElementById(`res_receipt_input_${appId}`);
            if (fileInput) {
                fileInput.value = '';
                // Since old verified receipt is discarded, require a new upload for cashless resubmission
                fileInput.setAttribute('required', 'required'); 
            }
        }
        cancelFileRemoval(appId);
    }

    /**
     * Dynamically displays new uploaded files with full viewing and custom removals
     */
    function handleFileChange(input, appId, type) {
        const file = input.files[0];
        if (!file) return;

        const containerId = type === 'referral' ? `existing_referral_container_${appId}` : `existing_receipt_container_${appId}`;
        let container = document.getElementById(containerId);

        if (!container) {
            const parent = input.parentElement;
            container = document.createElement('div');
            container.id = containerId;
            container.className = "d-flex align-items-center gap-2 mt-2 bg-light bg-opacity-5 p-2.5 rounded border border-secondary border-opacity-10 animate-fade-in";
            parent.appendChild(container);
        }

        container.classList.remove('d-none');
        container.innerHTML = `
            <span class="text-accent small"><i class="bi bi-file-earmark-check"></i> Selected File: ${file.name}</span>
            <div class="ms-auto d-flex gap-1.5">
                <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5" onclick="viewExistingOrLocalFile('${appId}', '${type}', '')">
                    <i class="bi bi-eye-fill"></i> View
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5" onclick="clearLocalFileSelection('${appId}', '${type}')">
                    <i class="bi bi-trash-fill"></i> Remove
                </button>
            </div>
        `;

        const hiddenInput = document.getElementById(`remove_${type}_${appId}`);
        if (hiddenInput) hiddenInput.value = '0';
    }

    function clearLocalFileSelection(appId, type) {
        const fileInput = document.getElementById(`res_${type}_input_${appId}`);
        if (fileInput) fileInput.value = '';

        const container = document.getElementById(`existing_${type}_container_${appId}`);
        if (container) container.classList.add('d-none');

        if (type === 'receipt') {
            const payCashless = document.getElementById(`res_pay_cashless_${appId}`);
            if (payCashless && payCashless.checked) {
                fileInput.setAttribute('required', 'required');
            }
        }
    }

    function viewExistingOrLocalFile(appId, type, serverUrl) {
        const fileInput = document.getElementById(`res_${type}_input_${appId}`);
        if (fileInput && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                zoomQR(e.target.result);
            };
            reader.readAsDataURL(fileInput.files[0]);
        } else if (serverUrl) {
            zoomQR(serverUrl);
        }
    }

    /**
     * Client-side validation checking before submitting form
     */
    function handleResSubmit(appId) {
        let errors = [];

        // 1. Demographics check (Step 1)
        const firstName = document.querySelector(`.resubmit-modal#resubmitModal${appId} [name="patient_first_name"]`).value.trim();
        const lastName = document.querySelector(`.resubmit-modal#resubmitModal${appId} [name="patient_last_name"]`).value.trim();
        const phone = document.querySelector(`.resubmit-modal#resubmitModal${appId} [name="patient_phone"]`).value.trim();
        const birthdate = document.querySelector(`.resubmit-modal#resubmitModal${appId} [name="patient_birthdate"]`).value;

        if (!firstName) errors.push("First Name is required on Step 1.");
        if (!lastName) errors.push("Last Name is required on Step 1.");
        if (!phone) errors.push("Contact Number is required on Step 1.");
        if (!birthdate) errors.push("Birthdate is required on Step 1.");

        // 2. Address check (Step 2)
        const isBulk = document.querySelector(`.resubmit-modal#resubmitModal${appId} [name="patient_street"]`) !== null && document.getElementById(`res_province_${appId}`) === null;
        if (isBulk) {
            const street = document.querySelector(`.resubmit-modal#resubmitModal${appId} [name="patient_street"]`).value.trim();
            if (!street) errors.push("Home Address is required on Step 2.");
        } else {
            const prov = document.getElementById(`res_province_${appId}`).value;
            const city = document.getElementById(`res_city_${appId}`).value;
            const brgy = document.getElementById(`res_brgy_${appId}`).value;
            const street = document.getElementById(`res_street_${appId}`).value.trim();
            if (!prov || !city || !brgy || !street) {
                errors.push("Complete residential address is required on Step 2.");
            }
        }

        // 3. Tests check (Step 3)
        const selectedTests = document.querySelectorAll(`.resubmit-modal#resubmitModal${appId} .test-checkbox:checked`);
        if (selectedTests.length === 0) {
            errors.push("Please select at least one laboratory test on Step 3.");
        }

        // 4. Schedule check (Step 4)
        const date = document.getElementById(`res_date_${appId}`).value;
        const timeSlot = document.getElementById(`res_ts_${appId}`).value;
        if (!date) errors.push("Preferred Date is required on Step 4.");
        if (!timeSlot) errors.push("Preferred Time Slot is required on Step 4.");

        // 5. Payment check (Step 5)
        const payCashless = document.getElementById(`res_pay_cashless_${appId}`);
        if (payCashless && payCashless.checked) {
            const checkedProvider = document.querySelector(`.res-provider-radio-${appId}:checked`);
            if (!checkedProvider) {
                errors.push("Please choose an E-Wallet provider on Step 5.");
            }
            const receiptInput = document.getElementById(`res_receipt_input_${appId}`);
            const existingReceipt = document.getElementById(`existing_receipt_container_${appId}`);
            const isReceiptRemoved = document.getElementById(`remove_receipt_${appId}`).value === '1';

            if (receiptInput && !receiptInput.files[0] && (!existingReceipt || existingReceipt.classList.contains('d-none') || isReceiptRemoved)) {
                errors.push("Please upload a new proof of payment receipt on Step 5.");
            }
        }

        if (errors.length > 0) {
            // Render error list inside modal
            let html = '<ul class="mb-0 ps-3 text-danger">';
            errors.forEach(err => {
                html += `<li class="mb-1">${err}</li>`;
            });
            html += '</ul>';
            document.getElementById(`res_validation_msg_${appId}`).innerHTML = html;

            // Trigger dynamic validation error modal
            const validationModal = new bootstrap.Modal(document.getElementById(`resubmitValidationModal${appId}`));
            validationModal.show();
            return;
        }

        // Check if unrefunded rollover confirmation warning modal is needed
        const hasUnrefundedWarning = document.getElementById(`unrefunded_warning_flag_${appId}`) !== null;
        if (hasUnrefundedWarning) {
            const rolloverModal = new bootstrap.Modal(document.getElementById(`resubmitRolloverConfirmModal${appId}`));
            rolloverModal.show();
            return;
        }

        // Submit form directly if all checks pass and no warning is needed
        submitResForm(appId);
    }

    function submitResForm(appId) {
        // Compile address numerical values to textual strings first
        compileResAddress(appId);

        // Close rollover modal if open
        const rolloverModalEl = document.getElementById(`resubmitRolloverConfirmModal${appId}`);
        if (rolloverModalEl) {
            const modalInstance = bootstrap.Modal.getInstance(rolloverModalEl);
            if (modalInstance) modalInstance.hide();
        }

        // Disable submit button to prevent double-submits
        const submitBtn = document.getElementById(`res_submit_btn_${appId}`);
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Submitting... <span class="spinner-border spinner-border-sm"></span>';
        }

        document.getElementById(`resubmitForm_${appId}`).submit();
    }

    /**
     * PSGC Cascading Address API loaders uniquely scoped to individual modal ID
     */
    async function initResAddress(appId, savedProv, savedCity, savedBrgy) {
        const provSel = document.getElementById(`res_province_${appId}`);
        if (!provSel) return;
        if (provSel.options.length > 1) return; // Prevent double-fetching if already loaded

        try {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/`);
            const data = await res.json();
            
            provSel.innerHTML = '<option value="">Select Province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
            });

            if (savedProv) {
                let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === savedProv.toUpperCase());
                if (provOpt) {
                    provSel.value = provOpt.value;
                    await fetchResCities(appId, provOpt.value, savedCity, savedBrgy);
                }
            }
        } catch (e) {
            console.error("Resubmit province fetch failed:", e);
        }
    }

    async function fetchResCities(appId, provCode, savedCity = '', savedBrgy = '') {
        const citySel = document.getElementById(`res_city_${appId}`);
        const brgySel = document.getElementById(`res_brgy_${appId}`);
        if (!citySel || !brgySel) return;

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
                    await fetchResBarangays(appId, cityOpt.value, savedBrgy);
                }
            }
        } catch (e) {
            console.error("Resubmit city fetch failed:", e);
        }
    }

    async function fetchResBarangays(appId, cityCode, savedBrgy = '') {
        const brgySel = document.getElementById(`res_brgy_${appId}`);
        if (!brgySel) return;

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
            console.error("Resubmit barangay fetch failed:", e);
        }
    }

    /**
     * Scoped time slot generator with STRICT dynamic lead-time check
     */
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

                let isFull = (data.full_slots || []).includes(tStr);
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

    /**
     * Form validation and progressive disclosure fields display logic
     */
    function toggleResPaymentFields(appId) {
        const payCashless = document.getElementById(`res_pay_cashless_${appId}`);
        const providerContainer = document.getElementById(`res_provider_container_${appId}`);
        const receiptContainer = document.getElementById(`res_receipt_container_${appId}`);
        const receiptInput = document.getElementById(`res_receipt_input_${appId}`);
        const qrSection = document.getElementById(`res_qr_section_${appId}`);
        const qrImage = document.getElementById(`res_selected_provider_qr_${appId}`);
        const qrLabel = document.getElementById(`res_selected_provider_name_{{$app->id}}`);
        const radios = document.querySelectorAll(`.res-provider-radio-${appId}`);

        if (payCashless && payCashless.checked) {
            if (providerContainer) providerContainer.classList.remove('d-none');

            // Progressive disclosure: Only reveal the receipt input once a cashless provider is active
            const activeRadio = document.querySelector(`.res-provider-radio-${appId}:checked`);
            if (activeRadio) {
                if (qrImage && activeRadio.dataset.qr) qrImage.src = activeRadio.dataset.qr;
                if (qrLabel && activeRadio.dataset.name) qrLabel.innerText = activeRadio.dataset.name;
                if (qrSection) qrSection.classList.remove('d-none');
                if (receiptContainer) receiptContainer.classList.remove('d-none');
                
                // If there's an existing receipt already loaded, it bypasses the required validation checks
                const existingReceipt = document.getElementById(`existing_receipt_container_${appId}`);
                const isReceiptRemoved = document.getElementById(`remove_receipt_${appId}`).value === '1';
                if (receiptInput && (existingReceipt && !existingReceipt.classList.contains('d-none') && !isReceiptRemoved)) {
                    receiptInput.removeAttribute('required');
                } else if (receiptInput) {
                    receiptInput.setAttribute('required', 'required');
                }
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
        const qrImage = document.getElementById(`res_selected_provider_qr_${appId}`);
        const qrLabel = document.getElementById(`res_selected_provider_name_${appId}`);
        if (radio.checked) {
            if (qrImage) qrImage.src = radio.dataset.qr;
            if (qrLabel) qrLabel.innerText = radio.dataset.name;
            document.getElementById(`res_qr_section_${appId}`).classList.remove('d-none');
            toggleResPaymentFields(appId);
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
</script>
@endpush
@endonce

{{-- Non-Duplicate Instance Initialization Listeners --}}
@push('scripts')
<script>
    (function() {
        const appId = '{{ $app->id }}';
        
        function initResubmitModalInstance() {
            const modalEl = document.getElementById(`resubmitModal${appId}`);
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', async () => {
                    // Initialize default first step on open
                    goToResStep(appId, 1);
                    updateSelectedTestsSummary(appId);
                    
                    @if(!$app->batch_id)
                        await initResAddress(appId, '{{ $app->patient_province }}', '{{ $app->patient_city }}', '{{ $app->patient_barangay }}');
                        toggleResPaymentFields(appId);
                    @endif
                    await fetchResTimeSlots(appId, '{{ $app->appointment_date ? $app->appointment_date->format("Y-m-d") : "" }}', '{{ $app->time_slot }}');
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initResubmitModalInstance);
        } else {
            initResubmitModalInstance();
        }
    })();
</script>
@endpush