@extends('layouts.app')

@section('content')
<div class="row justify-content-center animate-page">
    <div class="col-lg-11 col-xl-10 text-start">
        <div class="card p-0 border-secondary bg-card shadow-lg overflow-hidden">
            <div class="row g-0 align-items-stretch">
                {{-- LEFT: WIZARD FORM PANEL --}}
                <div class="col-md-8 border-end border-secondary border-opacity-25 p-4 p-md-5">
                    <form id="appointmentWizard" method="POST" action="{{ route('appointments.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('appointments.partials.wizard.step-1') {{-- Target Selection --}}
                        @include('appointments.partials.wizard.step-2') {{-- Patient Details --}}
                        @include('appointments.partials.wizard.step-3') {{-- Test Selection --}}
                        @include('appointments.partials.wizard.step-4') {{-- Schedule --}}
                        @include('appointments.partials.wizard.step-5') {{-- Payment & Finalize --}}
                    </form>
                </div>

                {{-- RIGHT: STICKY SUMMARY SIDEBAR --}}
                <div class="col-md-4 bg-secondary bg-opacity-10 p-4 p-md-5">
                    @include('appointments.partials.wizard.summary')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CUSTOM THEME-COMPATIBLE VALIDATION ALERT MODAL --}}
<div class="modal fade" id="wizardValidationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-accent display-4 d-block"></i>
            </div>
            <h5 class="text-main fw-bold mb-2 uppercase tracking-tighter" id="wizardValidationTitle">Selection Required</h5>
            <div id="wizardValidationMsg" class="text-secondary small mb-4">Please select a family member before proceeding.</div>
            <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" data-bs-dismiss="modal">UNDERSTOOD</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const user = @json(Auth::user());
const apiBase = "https://psgc.gitlab.io/api";

document.addEventListener('DOMContentLoaded', async () => {
    await fetchProvinces();
    handleTargetChange(); // Autofill "Myself" initial state on page load
 
    // Watch for dependent dropdown changes to remove is-invalid flags dynamically
    const depSelect = document.getElementById('dependent_id');
    if (depSelect) {
        depSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
            }
        });
    }

    // Trigger custom validation alert modal on server-side validation error redirect
    @if ($errors->any())
        document.getElementById('wizardValidationTitle').innerText = "Action Required";
        let errorHtml = '<div class="text-start mb-3 small text-white-50">Please correct the following fields to proceed:</div>';
        errorHtml += '<ul class="text-start small text-secondary mb-0 ps-3">';
        @foreach ($errors->all() as $error)
            errorHtml += `<li>{{ $error }}</li>`;
        @endforeach
        errorHtml += '</ul>';
 
        document.getElementById('wizardValidationMsg').innerHTML = errorHtml;
        const modalEl = document.getElementById('wizardValidationModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    @endif
});

// Helper to trigger the custom Bootstrap 5 alert modal
function showWizardAlert(msg) {
    document.getElementById('wizardValidationTitle').innerText = "Selection Required";
    document.getElementById('wizardValidationMsg').innerText = msg;
    const modalEl = document.getElementById('wizardValidationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

// 1. Navigation Controller
function goToPage(page) {
    document.querySelectorAll('.wiz-section').forEach(s => s.classList.add('d-none'));
    document.getElementById('page-' + page).classList.remove('d-none');
    window.scrollTo(0, 0);
}

// Block proceeding if "Dependent" is selected but no dependent has been selected from the dropdown
function proceedFromStep1() {
    const selectedType = document.querySelector('input[name="target_type"]:checked').value;
    if (selectedType === 'bulk') {
        window.location.href = "{{ route('appointments.bulk') }}";
    } else if (selectedType === 'dependent') {
        const depSelect = document.getElementById('dependent_id');
        if (!depSelect || !depSelect.value) {
            depSelect.classList.add('is-invalid');
            showWizardAlert("Please select a family member before proceeding.");
            depSelect.focus();
            return;
        }
        depSelect.classList.remove('is-invalid');
        goToPage(2);
    } else {
        goToPage(2);
    }
}

// Validation function for Step 2 Patient Details
function validateStep2() {
    const section = document.getElementById('page-2');
    const inputs = section.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        const val = input.value ? input.value.trim() : '';
        if (!val || val === "" || (input.tagName === 'SELECT' && val.includes('Select'))) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    if (isValid) {
        goToPage(3);
    } else {
        showWizardAlert("Please fill in all required fields and complete your address selection before proceeding.");
    }
}

// Validation function for Step 3 Test Selections
function validateStep3() {
    const selected = document.querySelectorAll('.test-checkbox:checked');
    if (selected.length === 0) {
        showWizardAlert("Please select at least one laboratory test before proceeding.");
        return;
    }
    goToPage(4);
}

// Validation function for Step 4 Date & Time Slot
function validateStep4() {
    const selectedDate = document.getElementById('wiz_date').value;
    const selectedSlot = document.querySelector('input[name="time_slot"]:checked');

    if (!selectedDate) {
        showWizardAlert("Please pick a preferred date before proceeding.");
        return;
    }
    if (!selectedSlot || !selectedSlot.value) {
        showWizardAlert("Please select a preferred time slot before proceeding.");
        return;
    }
    goToPage(5);
}

// 2. Step 1 & 2 Logic: Target Selection & Auto-fill
function handleTargetChange() {
    const type = document.querySelector('input[name="target_type"]:checked').value;
    const depDiv = document.getElementById('dep_selector_div');

    depDiv?.classList.toggle('d-none', type !== 'dependent');

    if (type === 'self') {
        fillDetails(
            user.first_name, 
            user.middle_name, 
            user.last_name, 
            user.sex, 
            user.birthdate, 
            user.phone, 
            user.street, 
            user.barangay, 
            user.city, 
            user.province
        );
        document.getElementById('sum_patient_type').innerText = "Personal Account";
    } else if (type === 'dependent') {
        const sel = document.getElementById('dependent_id');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            fillDetails(
                opt.dataset.first_name,
                opt.dataset.middle_name,
                opt.dataset.last_name,
                opt.dataset.sex,
                opt.dataset.bday,
                user.phone, // contact number is fetched from parent
                opt.dataset.street,
                opt.dataset.barangay,
                opt.dataset.city,
                opt.dataset.province
            );
            document.getElementById('sum_patient_type').innerText = "Family Dependent";
        } else {
            clearDetails();
        }
    }
    updateSummary();
}

function fillDetails(f, m, l, sex, bday, phone, street, barangay, city, province) {
    document.getElementById('in_first_name').value = f || '';
 
    const middleInput = document.getElementById('in_middle_name');
    const noneMnSwitch = document.getElementById('profile_no_mn');
    if (m === 'N/A' || !m) {
        middleInput.value = 'N/A';
        middleInput.readOnly = true;
        middleInput.classList.add('opacity-50');
        if (noneMnSwitch) noneMnSwitch.checked = true;
    } else {
        middleInput.value = m;
        middleInput.readOnly = false;
        middleInput.classList.remove('opacity-50');
        if (noneMnSwitch) noneMnSwitch.checked = false;
    }

    document.getElementById('in_last_name').value = l || '';
    document.getElementById('in_sex').value = sex || '';
    document.getElementById('in_bday').value = bday ? bday.split('T')[0] : '';
    document.getElementById('in_phone').value = phone || '';
    document.getElementById('addr_street').value = street || '';
 
    // Load cascading address dropdowns
    setAddressDropdowns(province, city, barangay);
}

function clearDetails() {
    ['in_first_name', 'in_middle_name', 'in_last_name', 'in_phone', 'in_bday', 'addr_street'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.value = "";
    });
    document.getElementById('in_sex').value = "";
    document.getElementById('addr_province').value = "";
    document.getElementById('addr_city').innerHTML = '<option value="">Select Province First</option>';
    document.getElementById('addr_city').disabled = true;
    document.getElementById('addr_brgy').innerHTML = '<option value="">Select City First</option>';
    document.getElementById('addr_brgy').disabled = true;
    updateCompiledAddress();
}

// 3. PSGC Cascading Address API Logic
async function fetchProvinces() {
    try {
        const res = await fetch(`${apiBase}/provinces/`);
        const data = await res.json();
        const sel = document.getElementById('addr_province');
        sel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });
    } catch (e) {
        console.error("Province API Error", e);
    }
}

async function fetchCities(provCode) {
    if (!provCode) return;
    const citySel = document.getElementById('addr_city');
    const brgySel = document.getElementById('addr_brgy');
    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';
    try {
        const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;
    } catch (e) {
        console.error("City API Error", e);
    }
    updateCompiledAddress();
}

async function fetchBarangays(cityCode) {
    if (!cityCode) return;
    const brgySel = document.getElementById('addr_brgy');
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
    try {
        const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;
    } catch (e) {
        console.error("Barangay API Error", e);
    }
    updateCompiledAddress();
}

async function setAddressDropdowns(provinceName, cityName, barangayName) {
    if (!provinceName) return;
    const provSel = document.getElementById('addr_province');
    const citySel = document.getElementById('addr_city');
    const brgySel = document.getElementById('addr_brgy');

    let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === provinceName.toUpperCase());
    if (provOpt) {
        provSel.value = provOpt.value;
        await fetchCities(provSel.value);

        let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === cityName.toUpperCase());
        if (cityOpt) {
            citySel.value = cityOpt.value;
            await fetchBarangays(citySel.value);

            let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === barangayName.toUpperCase());
            if (brgyOpt) {
                brgySel.value = brgyOpt.value;
            }
        }
    }
    updateCompiledAddress();
}

function updateCompiledAddress() {
    const street = document.getElementById('addr_street').value.trim();
    const brgy = document.getElementById('addr_brgy');
    const city = document.getElementById('addr_city');
    const prov = document.getElementById('addr_province');

    const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
    const cityName = city.options[city.selectedIndex]?.text || '';
    const provName = prov.options[prov.selectedIndex]?.text || '';

    if (street || brgyName || cityName || provName) {
        let parts = [];
        if (street) parts.push(street);
        if (brgyName && !brgyName.includes('Select')) parts.push('BRGY. ' + brgyName);
        if (cityName && !cityName.includes('Select')) parts.push(cityName);
        if (provName && !provName.includes('Select')) parts.push(provName);

        const compiled = parts.join(', ').toUpperCase();
        document.getElementById('compiled_address_text').innerText = compiled;
        document.getElementById('compiled_address_container').classList.remove('d-none');
    } else {
        document.getElementById('compiled_address_container').classList.add('d-none');
    }
}

// 4. Overwrite numerical select values with literal names before form submission
function compileAppointmentAddress() {
    const brgy = document.getElementById('addr_brgy');
    const city = document.getElementById('addr_city');
    const prov = document.getElementById('addr_province');

    if (brgy && city && prov) {
        const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
        const cityName = city.options[city.selectedIndex]?.text || '';
        const provName = prov.options[prov.selectedIndex]?.text || '';

        if (brgyName && cityName && provName) {
            prov.options[prov.selectedIndex].value = provName;
            city.options[city.selectedIndex].value = cityName;
            brgy.options[brgy.selectedIndex].value = brgyName;
        }
    }
}

document.getElementById('appointmentWizard').addEventListener('submit', function(e) {
    compileAppointmentAddress();
 
    // Normalizes slash date formats (DD/MM/YYYY) to YYYY-MM-DD before form submit hits backend validation
    const bdayInput = document.getElementById('in_bday');
    if (bdayInput && bdayInput.value.includes('/')) {
        const parts = bdayInput.value.split('/');
        if (parts.length === 3) {
            bdayInput.value = `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
    }
});

// 5. Update Summary Sidebar Panel
function updateSummary() {
    const f = document.getElementById('in_first_name').value;
    const m = document.getElementById('in_middle_name').value;
    const l = document.getElementById('in_last_name').value;
    const fullName = f + (m && m !== 'N/A' ? ' ' + m : '') + ' ' + l;

    document.getElementById('sum_name').innerText = fullName.trim() || 'Not specified';

    let total = 0;
    let testHtml = '';
    const selected = document.querySelectorAll('.test-checkbox:checked');

    selected.forEach(cb => {
        total += parseFloat(cb.dataset.price);
        testHtml += `<div class="d-flex justify-content-between mb-2">
            <span class="text-truncate me-2 small uppercase">${cb.dataset.name}</span>
            <span class="text-neon fw-bold small"> ${parseFloat(cb.dataset.price).toLocaleString()}</span>
        </div>`;
    });

    document.getElementById('sum_tests').innerHTML = testHtml || '<div class="italic text-muted">No tests selected</div>';
    document.getElementById('sum_total').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
}

// 6. Step 4: Slots & Scheduling with dynamic past/lead-time checking
async function fetchTimeSlots() {
    const date = document.getElementById('wiz_date').value;
    const container = document.getElementById('wiz_slots_container');
    if (!date) return;
 
    container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-neon"></div></div>';
 
    try {
        const res = await fetch(`/api/check-slots?date=${date}`);
        const data = await res.json();
        if (data.is_closed) {
            container.innerHTML = '<div class="col-12 py-5 text-center text-danger border border-danger border-dashed rounded">Clinic Closed</div>';
            showSlotUI(false);
            return;
        }
        let html = '';
        let start = new Date(`2000-01-01 ${data.config.opening_time}`);
        let end = new Date(`2000-01-01 ${data.config.closing_time}`);
        let availableCount = 0;

        const now = new Date();
        const todayLocal = now.toLocaleDateString('en-CA');
 
        while (start < end) {
            let tStr = start.toTimeString().split(' ')[0];
            let disp = start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
 
            let isFull = (data.full_slots || []).includes(tStr);
            let isLunch = (data.config.has_lunch_break && tStr >= data.config.lunch_start && tStr < data.config.lunch_end);
 
            // STRICT dynamic past and lead-time buffer checking
            let isPast = false;
            if (date === todayLocal) {
                const leadTimeMs = (parseInt(data.config.lead_time_hours) || 0) * 3600 * 1000;
                const cutoffTime = now.getTime() + leadTimeMs;
                const slotDate = new Date(`${date} ${tStr}`);
                isPast = slotDate.getTime() < cutoffTime;
            }

            // If a slot is a lunch break or is in the past/lead-time cutoff, we DO NOT render it at all!
            if (!isLunch && !isPast) {
                html += `<div class="col-4">
                    <input type="radio" class="btn-check" name="time_slot" id="slot_${tStr}" value="${tStr}" ${isFull ? 'disabled' : ''} onchange="handleSlotSelection()">
                    <label class="btn ${isFull ? 'btn-danger opacity-25' : 'btn-outline-neon'} btn-sm w-100 py-2 fw-bold" for="slot_${tStr}">${disp}</label>
                </div>`;
                availableCount++;
            }
            start.setMinutes(start.getMinutes() + data.config.slot_duration);
        }

        if (availableCount > 0) {
            container.innerHTML = html;
            showSlotUI(true);
        } else {
            container.innerHTML = '<div class="col-12 py-5 text-center text-warning border border-warning border-dashed rounded"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>No available slots remaining for today. Please pick another date.</div>';
            showSlotUI(false);
        }
    } catch (e) {
        container.innerHTML = '<div class="col-12 text-center py-4 text-danger">Error loading slots.</div>';
        showSlotUI(false);
    }
}

function showSlotUI(hasSlots) {
    const legend = document.getElementById('slot_legend');
    if (legend) {
        if (hasSlots) {
            legend.classList.remove('d-none');
        } else {
            legend.classList.add('d-none');
        }
    }
}

function handleSlotSelection() {
    const selectedRadio = document.querySelector('input[name="time_slot"]:checked');
    if (selectedRadio) {
        const date = document.getElementById('wiz_date').value;
        const timeLabel = selectedRadio.nextElementSibling.innerText;
        setSchedule(date, timeLabel);
    }
}

function setSchedule(date, time) {
    document.getElementById('sum_schedule').classList.remove('d-none');
    document.getElementById('sum_date').innerText = date;
    document.getElementById('sum_time').innerText = time;
}

// FIXED: Scoped payment UI logic & submission gates (supports deferred module bindings)
function initializeSingleWizardEvents() {
    const payCash = document.getElementById('pay_cash');
    const payCashless = document.getElementById('pay_cashless');
    const providerContainer = document.getElementById('provider_selection_container');
    const receiptContainer = document.getElementById('receipt_upload_container');
    const receiptInput = document.getElementById('in_receipt');
    const qrSection = document.getElementById('qr_section');
    const providerRadios = document.querySelectorAll('.provider-radio');
    const qrImage = document.getElementById('selected_provider_qr');
    const qrLabel = document.getElementById('selected_provider_name');

    // FIXED: Enforce "required" attributes on the payment provider radio group to trigger form invalidation
    function togglePaymentFields() {
        if (payCashless && payCashless.checked) {
            if (providerContainer) providerContainer.classList.remove('d-none');
            
            // Inject required attribute on all payment gateway radios
            providerRadios.forEach(radio => radio.setAttribute('required', 'required'));

            const activeRadio = document.querySelector('.provider-radio:checked');
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
            if (qrSection) qrSection.classList.add('d-none');
            if (receiptInput) receiptInput.removeAttribute('required');
            
            // Remove required validations since Cash on Site is active
            providerRadios.forEach(radio => {
                radio.removeAttribute('required');
                radio.checked = false;
            });
        }
    }

    [payCash, payCashless].forEach(input => {
        if (input) input.addEventListener('change', togglePaymentFields);
    });

    providerRadios.forEach(radio => {
        if (radio) {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    if (qrImage) qrImage.src = this.dataset.qr;
                    if (qrLabel) qrLabel.innerText = this.dataset.name;
                    // Re-calculate visible blocks (reveals the uploader input and QR block)
                    togglePaymentFields();
                }
            });
        }
    });

    // Strict submit interceptor validating that provider and receipt are selected
    const wizardForm = document.getElementById('appointmentWizard');
    if (wizardForm) {
        wizardForm.addEventListener('submit', function(e) {
            if (payCashless && payCashless.checked) {
                const selectedProvider = document.querySelector('input[name="payment_provider_id"]:checked');
                if (!selectedProvider) {
                    e.preventDefault();
                    showWizardAlert("Please select an E-Wallet provider (e.g. GCash, Maya) to scan the payment QR code before submitting.");
                    return;
                }

                if (receiptInput && !receiptInput.files[0]) {
                    e.preventDefault();
                    showWizardAlert("Please upload an image or PDF copy of your GCash/Maya transaction receipt to finalize.");
                    return;
                }
            }
        });
    }
    
    // Run initial toggling check
    togglePaymentFields();
}

// FIXED: Defensive readyState checker ensuring listeners bind immediately if document is already parsed
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSingleWizardEvents);
} else {
    initializeSingleWizardEvents();
}

// Fullscreen lightbox zoom controls
function zoomQR() {
    const qrSrc = document.getElementById('selected_provider_qr').src;
    if (qrSrc) {
        document.getElementById('lightbox_qr_img').src = qrSrc;
        document.getElementById('qr_lightbox').classList.remove('d-none');
        document.getElementById('qr_lightbox').classList.add('d-flex');
    }
}

function closeQRLightbox() {
    document.getElementById('qr_lightbox').classList.add('d-none');
    document.getElementById('qr_lightbox').classList.remove('d-flex');
}
</script>

<style>
.is-invalid {
    border-color: #ff4d4d !important;
    background-image: none !important;
}

/* Visual high-contrast selected highlight for card radio options across Step 1 & Step 5 */
.btn-check:checked + label.btn-outline-accent {
    background-color: rgba(25, 211, 140, 0.06) !important;
    border-color: var(--brand-accent) !important;
    border-width: 2.2px !important;
    color: var(--brand-accent) !important;
    box-shadow: 0 0 12px rgba(25, 211, 140, 0.12) !important;
}
.btn-check:checked + label.btn-outline-accent i {
    color: var(--brand-accent) !important;
}
</style>
@endpush