@extends('layouts.app')

@section('title', 'Create Appointment')

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
            <h5 class="modal-title text-main fw-bold mb-2 uppercase tracking-tighter" id="wizardValidationTitle">Selection Required</h5>
            <div id="wizardValidationMsg" class="text-secondary small mb-4">Please select a family member before proceeding.</div>
            <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" data-bs-dismiss="modal">UNDERSTOOD</button>
        </div>
    </div>
</div>

{{-- SINGLE REUSABLE UNIFIED MULTI-FORMAT FILE PREVIEW LIGHTBOX OVERLAY --}}
@include('layouts.partials.lightbox-overlay')

@endsection

@push('scripts')
<script>
const user = @json(Auth::user());
const apiBase = "https://psgc.gitlab.io/api";
window.referralLocalData = null; // Caches base64 data for dynamic lightbox view

// --- REAL-TIME APPOINTMENT DRAFT SAVE ---
function saveAppointmentDraft() {
    const form = document.getElementById('appointmentWizard');
    if (!form) return;

    const draftData = {};
    const inputs = form.querySelectorAll('input:not([type="password"]):not([type="file"]), select, textarea');

    inputs.forEach(input => {
        if (input.name) {
            if (input.type === 'checkbox') {
                if (input.name.endsWith('[]')) {
                    if (!draftData[input.name]) draftData[input.name] = [];
                    if (input.checked) draftData[input.name].push(input.value);
                } else {
                    draftData[input.name] = input.checked;
                }
            } else if (input.type === 'radio') {
                if (input.checked) {
                    draftData[input.name] = input.value;
                }
            } else {
                draftData[input.name] = input.value;
            }
        }
    });
    localStorage.setItem('appointment_draft', JSON.stringify(draftData));
}

// --- 1. NAVIGATION CONTROLLER WITH STEP RETENTION ---
function goToPage(page) {
    document.querySelectorAll('.wiz-section').forEach(s => s.classList.add('d-none'));
    const target = document.getElementById('page-' + page);
    if (target) {
        target.classList.remove('d-none');
    }
    window.scrollTo(0, 0);
    localStorage.setItem('appointment_step', page);
}

// --- 2. STEP 1 & 2 LOGIC: TARGET SELECTION & AUTO-FILL ---
function handleTargetChange() {
    const typeElement = document.querySelector('input[name="target_type"]:checked');
    if (!typeElement) return;

    const type = typeElement.value;
    const depDiv = document.getElementById('dep_selector_div');

    if (depDiv) {
        depDiv.classList.toggle('d-none', type !== 'dependent');
    }

    if (type === 'self') {
        fillDetails(
            user.first_name,
            user.middle_name,
            user.last_name,
            user.suffix,
            user.sex,
            user.birthdate,
            user.phone,
            user.street,
            user.barangay,
            user.city,
            user.province
        );
        const typeSpan = document.getElementById('sum_patient_type');
        if (typeSpan) typeSpan.innerText = "Personal Account";
    } else if (type === 'dependent') {
        const sel = document.getElementById('dependent_id');
        const opt = sel ? sel.options[sel.selectedIndex] : null;
        if (opt && opt.value) {
            fillDetails(
                opt.dataset.first_name,
                opt.dataset.middle_name,
                opt.dataset.last_name,
                opt.dataset.suffix,
                opt.dataset.sex,
                opt.dataset.bday,
                user.phone,
                opt.dataset.street,
                opt.dataset.barangay,
                opt.dataset.city,
                opt.dataset.province
            );
            const typeSpan = document.getElementById('sum_patient_type');
            if (typeSpan) typeSpan.innerText = "Family Dependent";
        } else {
            clearDetails();
        }
    } else {
        clearDetails();
    }
    updateSummary();
    saveAppointmentDraft();
}

function resetPatientDetails() {
    handleTargetChange();
}

function fillDetails(f, m, l, suffix, sex, bday, phone, street, barangay, city, province) {
    const fnEl = document.getElementById('in_first_name');
    if (fnEl) fnEl.value = f || '';

    const middleInput = document.getElementById('in_middle_name');
    const noneMnSwitch = document.getElementById('profile_no_mn');
    if (m === 'N/A' || !m) {
        if (middleInput) {
            middleInput.value = 'N/A';
            middleInput.readOnly = true;
            middleInput.classList.add('opacity-50');
        }
        if (noneMnSwitch) noneMnSwitch.checked = true;
    } else {
        if (middleInput) {
            middleInput.value = m;
            middleInput.readOnly = false;
            middleInput.classList.remove('opacity-50');
        }
        if (noneMnSwitch) noneMnSwitch.checked = false;
    }

    const lnEl = document.getElementById('in_last_name');
    if (lnEl) lnEl.value = l || '';

    const sfxEl = document.getElementById('in_suffix');
    if (sfxEl) sfxEl.value = suffix || '';

    const sexEl = document.getElementById('in_sex');
    if (sexEl) sexEl.value = sex || '';

    const bdayEl = document.getElementById('in_bday');
    if (bdayEl) bdayEl.value = bday ? bday.split('T')[0] : '';

    const hiddenPhoneVal = phone || '';
    const displayPhoneInput = document.getElementById('phone_display');
    const hiddenPhoneInput = document.getElementById('in_phone');
    if (displayPhoneInput && hiddenPhoneInput) {
        if (hiddenPhoneVal.startsWith('09')) {
            displayPhoneInput.value = hiddenPhoneVal.substring(2);
            hiddenPhoneInput.value = hiddenPhoneVal;
        } else {
            displayPhoneInput.value = hiddenPhoneVal;
            hiddenPhoneInput.value = hiddenPhoneVal ? (hiddenPhoneVal.startsWith('09') ? hiddenPhoneVal : '09' + hiddenPhoneVal) : '';
        }
    }

    const streetEl = document.getElementById('addr_street');
    if (streetEl) streetEl.value = street || '';

    setAddressDropdowns(province, city, barangay);
}

function clearDetails() {
    ['in_first_name', 'in_middle_name', 'in_last_name', 'in_suffix', 'in_phone', 'in_bday', 'addr_street'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });
    const phoneDisp = document.getElementById('phone_display');
    if (phoneDisp) phoneDisp.value = "";
    const sexEl = document.getElementById('in_sex');
    if (sexEl) sexEl.value = "";
    const provEl = document.getElementById('addr_province');
    if (provEl) provEl.value = "";
    const cityEl = document.getElementById('addr_city');
    if (cityEl) {
        cityEl.innerHTML = '<option value="">Select Province First</option>';
        cityEl.disabled = true;
    }
    const brgyEl = document.getElementById('addr_brgy');
    if (brgyEl) {
        brgyEl.innerHTML = '<option value="">Select City First</option>';
        brgyEl.disabled = true;
    }
    updateCompiledAddress();
}

// --- Phone Digits Synchronization ---
function syncWizardPhone() {
    const displayInput = document.getElementById('phone_display');
    const hiddenInput = document.getElementById('in_phone');
    if (displayInput && hiddenInput) {
        const val = displayInput.value.trim();
        hiddenInput.value = val ? (val.startsWith('09') ? val : '09' + val) : '';
    }
    updateSummary();
    saveAppointmentDraft();
}

// --- Doctor's Referral Local Upload Handlers ---
window.handleReferralUpload = function(input) {
    const file = input.files[0];
    const inputWrapper = document.getElementById('referral_input_wrapper');
    const previewContainer = document.getElementById('referral_preview_container');
    const label = document.getElementById('referral_file_label');

    if (!file) {
        if (previewContainer) previewContainer.classList.add('d-none');
        if (inputWrapper) inputWrapper.classList.remove('d-none');
        window.referralLocalData = null;
        return;
    }

    if (inputWrapper) inputWrapper.classList.add('d-none');
    if (previewContainer) previewContainer.classList.remove('d-none');
    if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${file.name}`;

    const reader = new FileReader();
    reader.onload = function(e) {
        window.referralLocalData = e.target.result;
        localStorage.setItem('referral_base64', e.target.result);
        localStorage.setItem('referral_name', file.name);
    };
    reader.readAsDataURL(file);
    saveAppointmentDraft();
};

window.removeUploadedReferral = function() {
    const input = document.getElementById('in_referral');
    const inputWrapper = document.getElementById('referral_input_wrapper');
    const previewContainer = document.getElementById('referral_preview_container');
    if (input) input.value = '';
    if (inputWrapper) inputWrapper.classList.remove('d-none');
    if (previewContainer) previewContainer.classList.add('d-none');
    window.referralLocalData = null;
    localStorage.removeItem('referral_base64');
    localStorage.removeItem('referral_name');
    saveAppointmentDraft();
};

// --- Step 3 Accordion Detail Toggling ---
function toggleTestDetails(detailsId, btn) {
    const details = document.getElementById(detailsId);
    const icon = btn.querySelector('i');
    if (details) {
        if (details.classList.contains('d-none')) {
            details.classList.remove('d-none');
            if (icon) icon.className = 'bi bi-chevron-up fs-5 text-accent';
        } else {
            details.classList.add('d-none');
            if (icon) icon.className = 'bi bi-chevron-down fs-5';
        }
    }
}

// --- Step 3 Cart Selective Remove Handler ---
function removeSelectedTest(checkboxId) {
    const cb = document.getElementById(checkboxId);
    if (cb) {
        cb.checked = false;
        updateSummary();
        saveAppointmentDraft();
    }
}

// --- Dynamic DOM Re-sorting (Checked Items to Top) ---
function resortTestList() {
    const container = document.querySelector('.test-list-container');
    if (!container) return;

    const items = Array.from(container.querySelectorAll('.test-item'));

    items.sort((a, b) => {
        const aCheckbox = a.querySelector('.test-checkbox');
        const bCheckbox = b.querySelector('.test-checkbox');
        const aChecked = (aCheckbox && aCheckbox.checked) ? 1 : 0;
        const bChecked = (bCheckbox && bCheckbox.checked) ? 1 : 0;

        if (aChecked !== bChecked) {
            return bChecked - aChecked;
        }

        const aName = a.dataset.name || '';
        const bName = b.dataset.name || '';
        return aName.localeCompare(bName);
    });

    items.forEach(item => container.appendChild(item));
}

// --- 3. PSGC CASCADING ADDRESS API LOGIC ---
async function fetchProvinces() {
    try {
        const res = await fetch(`${apiBase}/provinces.json`);
        const data = await res.json();
        const sel = document.getElementById('addr_province');
        if (sel) {
            sel.innerHTML = '<option value="">Select Province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
            });
        }
    } catch (e) {
        console.error("Province API Error", e);
    }
}

async function fetchCities(provCode) {
    if (!provCode) return;
    const citySel = document.getElementById('addr_city');
    const brgySel = document.getElementById('addr_brgy');
    if (citySel) citySel.disabled = true;
    if (brgySel) brgySel.disabled = true;
    if (citySel) citySel.innerHTML = '<option value="">Loading Cities...</option>';
    try {
        const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities.json`);
        const data = await res.json();
        if (citySel) {
            citySel.innerHTML = '<option value="">Select City</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
            });
            citySel.disabled = false;
        }
    } catch (e) {
        console.error("City API Error", e);
    }
    updateCompiledAddress();
    saveAppointmentDraft();
}

async function fetchBarangays(cityCode) {
    if (!cityCode) return;
    const brgySel = document.getElementById('addr_brgy');
    if (brgySel) {
        brgySel.disabled = true;
        brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
    }
    try {
        const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays.json`);
        const data = await res.json();
        if (brgySel) {
            brgySel.innerHTML = '<option value="">Select Barangay</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
            });
            brgySel.disabled = false;
        }
    } catch (e) {
        console.error("Barangay API Error", e);
    }
    updateCompiledAddress();
    saveAppointmentDraft();
}

async function setAddressDropdowns(provinceName, cityName, barangayName) {
    if (!provinceName) return;
    const provSel = document.getElementById('addr_province');
    const citySel = document.getElementById('addr_city');
    const brgySel = document.getElementById('addr_brgy');

    if (!provSel) return;
    let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === provinceName.toUpperCase());
    if (provOpt) {
        provSel.value = provOpt.value;
        await fetchCities(provSel.value);

        if (citySel) {
            let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === cityName.toUpperCase());
            if (cityOpt) {
                citySel.value = cityOpt.value;
                await fetchBarangays(citySel.value);

                if (brgySel) {
                    let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === barangayName.toUpperCase());
                    if (brgyOpt) {
                        brgySel.value = brgyOpt.value;
                    }
                }
            }
        }
    }
    updateCompiledAddress();
}

function updateCompiledAddress() {
    const streetInput = document.getElementById('addr_street');
    const brgy = document.getElementById('addr_brgy');
    const city = document.getElementById('addr_city');
    const prov = document.getElementById('addr_province');

    const street = streetInput ? streetInput.value.trim() : '';
    const brgyName = brgy && brgy.selectedIndex >= 0 ? brgy.options[brgy.selectedIndex]?.text || '' : '';
    const cityName = city && city.selectedIndex >= 0 ? city.options[city.selectedIndex]?.text || '' : '';
    const provName = prov && prov.selectedIndex >= 0 ? prov.options[prov.selectedIndex]?.text || '' : '';

    const textEl = document.getElementById('compiled_address_text');
    const containerEl = document.getElementById('compiled_address_container');

    if (street || brgyName || cityName || provName) {
        let parts = [];
        if (street) parts.push(street);
        if (brgyName && !brgyName.includes('Select')) parts.push('BRGY. ' + brgyName);
        if (cityName && !cityName.includes('Select')) parts.push(cityName);
        if (provName && !provName.includes('Select')) parts.push(provName);

        const compiled = parts.join(', ').toUpperCase();
        if (textEl) textEl.innerText = compiled;
        if (containerEl) containerEl.classList.remove('d-none');
    } else {
        if (containerEl) containerEl.classList.add('d-none');
    }
}

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

// --- STEP 3: SIDEBAR & SELECTION CLASSIFICATION UPDATER ---
function updateSummary() {
    const fn = document.getElementById('in_first_name')?.value || '';
    const mn = document.getElementById('in_middle_name')?.value || '';
    const ln = document.getElementById('in_last_name')?.value || '';
    const fullName = fn + (mn && mn !== 'N/A' ? ' ' + mn : '') + ' ' + ln;

    const nameEl = document.getElementById('sum_name');
    if (nameEl) nameEl.innerText = fullName.trim() || 'Not specified';

    const sidebarBadge = document.getElementById('test_count_badge');
    const selected = document.querySelectorAll('.test-checkbox:checked');

    if (sidebarBadge) sidebarBadge.innerText = selected.length;

    let total = 0;
    let sidebarHtml = '';

    document.querySelectorAll('.test-item').forEach(item => {
        const cb = item.querySelector('.test-checkbox');
        if (cb && cb.checked) {
            item.classList.add('selected-test-item');
            const price = parseFloat(cb.dataset.price);
            total += price;

            sidebarHtml += `
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-truncate me-2 small uppercase">${cb.dataset.name}</span>
                    <span class="text-neon fw-bold small">₱${price.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                </div>
            `;
        } else {
            item.classList.remove('selected-test-item');
        }
    });

    const sumTests = document.getElementById('sum_tests');
    if (sumTests) {
        sumTests.innerHTML = sidebarHtml || '<div class="italic text-muted">No tests selected</div>';
    }

    const sumTotal = document.getElementById('sum_total');
    if (sumTotal) {
        sumTotal.innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    resortTestList();
}

// --- 5. STEP 4: TIME SLOTS FETCH & AUTO-SELECT RESTORATION ---
async function fetchTimeSlots() {
    const dateEl = document.getElementById('wiz_date');
    if (!dateEl) return;
    const date = dateEl.value;
    const container = document.getElementById('wiz_slots_container');
    if (!date || !container) return;

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

        const draft = JSON.parse(localStorage.getItem('appointment_draft') || '{}');
        const savedSlot = draft['time_slot'] || '';

        while (start < end) {
            let tStr = start.toTimeString().split(' ')[0];
            let disp = start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});

            let isFull = (data.full_slots || []).includes(tStr);
            let isLunch = (data.config.has_lunch_break && tStr >= data.config.lunch_start && tStr < data.config.lunch_end);

            let isPast = false;
            if (date === todayLocal) {
                const leadTimeMs = (parseInt(data.config.lead_time_hours) || 0) * 3600 * 1000;
                const cutoffTime = now.getTime() + leadTimeMs;
                const slotDate = new Date(`${date} ${tStr}`);
                isPast = slotDate.getTime() < cutoffTime;
            }

            if (!isLunch && !isPast) {
                const isChecked = (tStr === savedSlot) ? 'checked' : '';
                html += `<div class="col-4">
                    <input type="radio" class="btn-check" name="time_slot" id="slot_${tStr}" value="${tStr}" ${isFull ? 'disabled' : ''} ${isChecked} onchange="handleSlotSelection()">
                    <label class="btn ${isFull ? 'btn-danger opacity-25' : 'btn-outline-neon'} btn-sm w-100 py-2 fw-bold" for="slot_${tStr}">${disp}</label>
                </div>`;
                availableCount++;
            }
            start.setMinutes(start.getMinutes() + data.config.slot_duration);
        }

        if (availableCount > 0) {
            container.innerHTML = html;
            showSlotUI(true);

            const selectedRadio = container.querySelector('input[name="time_slot"]:checked');
            if (selectedRadio) {
                handleSlotSelection();
            }
        } else {
            container.innerHTML = '<div class="col-12 py-5 text-center text-warning border border-warning border-dashed rounded"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>No available slots remaining for today. Please pick another date.</div>';
            showSlotUI(false);
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div class="col-12 text-center py-4 text-danger">Error loading slots.</div>';
        showSlotUI(false);
    }
}

function showSlotUI(hasSlots) {
    const legend = document.getElementById('slot_legend');
    if (legend) {
        legend.classList.toggle('d-none', !hasSlots);
    }
}

function handleSlotSelection() {
    const selectedRadio = document.querySelector('input[name="time_slot"]:checked');
    if (selectedRadio) {
        const dateEl = document.getElementById('wiz_date');
        const date = dateEl ? dateEl.value : '';
        const timeLabel = selectedRadio.nextElementSibling.innerText;
        setSchedule(date, timeLabel);
        saveAppointmentDraft();
    }
}

function setSchedule(date, time) {
    const sumSched = document.getElementById('sum_schedule');
    if (sumSched) sumSched.classList.remove('d-none');
    const sumDate = document.getElementById('sum_date');
    if (sumDate) sumDate.innerText = date;
    const sumTime = document.getElementById('sum_time');
    if (sumTime) sumTime.innerText = time;
}

// --- 6. FLOW VALIDATORS ---
function proceedFromStep1() {
    const typeElement = document.querySelector('input[name="target_type"]:checked');
    const selectedType = typeElement ? typeElement.value : 'self';
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

function validateStep3() {
    const selected = document.querySelectorAll('.test-checkbox:checked');
    if (selected.length === 0) {
        showWizardAlert("Please select at least one laboratory test before proceeding.");
        return;
    }
    goToPage(4);
}

function validateStep4() {
    const dateInput = document.getElementById('wiz_date');
    const selectedSlot = document.querySelector('input[name="time_slot"]:checked');

    if (!dateInput || !dateInput.value) {
        showWizardAlert("Please select a preferred date for your laboratory visit.");
        return;
    }

    if (!selectedSlot) {
        showWizardAlert("Please select an available preferred time slot before proceeding.");
        return;
    }

    goToPage(5);
}

// --- DOM DOCUMENT INITIALIZER ---
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('appointmentWizard');

    // 1. Recover standard form fields safely from local draft storage
    try {
        const draftData = JSON.parse(localStorage.getItem('appointment_draft') || '{}');
        for (const [key, value] of Object.entries(draftData)) {
            if (Array.isArray(value)) {
                value.forEach(val => {
                    try {
                        const cb = document.querySelector(`input[name="${key}"][value="${val}"]`);
                        if (cb) cb.checked = true;
                    } catch (err) {}
                });
            } else {
                try {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = value;
                        } else if (input.type === 'radio') {
                            const targetRadio = document.querySelector(`input[name="${key}"][value="${value}"]`);
                            if (targetRadio) targetRadio.checked = true;
                        } else {
                            input.value = value;
                        }
                    }
                } catch (err) {}
            }
        }
    } catch (e) {
        console.warn("Draft recovery skipped due to empty or unformatted local parameters:", e);
    }

    // 2. Cascade asynchronous select elements safely without blocking wizard render
    try {
        await fetchProvinces();
    } catch (err) {
        console.warn("Network initialization failed while fetching provinces:", err);
    }

    handleTargetChange();

    // 3. Auto-populate cascading addresses from draft safely
    try {
        const draftData = JSON.parse(localStorage.getItem('appointment_draft') || '{}');
        if (draftData['patient_province']) {
            await setAddressDropdowns(
                draftData['patient_province'], 
                draftData['patient_city'], 
                draftData['patient_barangay']
            );
        }
    } catch (err) {
        console.warn("Cascade restoration failed for residential address properties:", err);
    }

    // 4. Force trigger time slot grid check on load if date exists safely
    try {
        const draftData = JSON.parse(localStorage.getItem('appointment_draft') || '{}');
        if (draftData['appointment_date']) {
            await fetchTimeSlots();
        }
    } catch (err) {
        console.warn("Schedule restoration failed for time slot properties:", err);
    }

    // 5. Watch for dependent dropdown changes to remove errors
    const depSelect = document.getElementById('dependent_id');
    if (depSelect) {
        depSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
            }
        });
    }

    // 6. Initialize Bootstrap Popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, { sanitize: false });
    });

    // 7. Restore active step from storage
    const savedStep = localStorage.getItem('appointment_step');
    if (savedStep) {
        goToPage(parseInt(savedStep));
    } else {
        goToPage(1);
    }

    // 8. Bind real-time input change observers to sync draft storage
    const draftFields = document.querySelectorAll('#appointmentWizard input:not([type="password"]):not([type="file"]), #appointmentWizard select, #appointmentWizard textarea');
    draftFields.forEach(element => {
        element.addEventListener('input', saveAppointmentDraft);
        element.addEventListener('change', saveAppointmentDraft);
    });

    // 9. Attach server-side validation error redirect handler
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

    // 10. Dynamically map and compile literal address strings on submit, then clear storage
    if (form) {
        form.addEventListener('submit', function() {
            compileAppointmentAddress();
            localStorage.removeItem('appointment_draft');
            localStorage.removeItem('appointment_step');
            localStorage.removeItem('referral_base64');
            localStorage.removeItem('referral_name');
            localStorage.removeItem('receipt_base64');
            localStorage.removeItem('receipt_name');
            localStorage.removeItem('saved_payment_method');
            localStorage.removeItem('saved_payment_provider_id');
        });
    }
});

function showWizardAlert(msg) {
    document.getElementById('wizardValidationTitle').innerText = "Selection Required";
    document.getElementById('wizardValidationMsg').innerText = msg;
    const modalEl = document.getElementById('wizardValidationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
@endpush