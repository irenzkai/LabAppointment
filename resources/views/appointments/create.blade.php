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
@endsection

@push('scripts')
<script>
    const user = @json(Auth::user());
    const apiBase = "https://psgc.gitlab.io/api";
    let referralLocalData = null; // Caches base64 data for dynamic lightbox view

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
        document.getElementById('page-' + page).classList.remove('d-none');
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
            document.getElementById('sum_patient_type').innerText = "Personal Account";
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
                    user.phone, // Dependents inherit parent contact phone
                    opt.dataset.street,
                    opt.dataset.barangay,
                    opt.dataset.city,
                    opt.dataset.province
                );
                document.getElementById('sum_patient_type').innerText = "Family Dependent";
            } else {
                clearDetails();
            }
        } else {
            clearDetails();
        }
        updateSummary();
        saveAppointmentDraft();
    }

    // --- Reset Patient Details (Refetch from target) ---
    function resetPatientDetails() {
        handleTargetChange();
    }

    function fillDetails(f, m, l, suffix, sex, bday, phone, street, barangay, city, province) {
        document.getElementById('in_first_name').value = f || '';

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

        document.getElementById('in_last_name').value = l || '';
        document.getElementById('in_suffix').value = suffix || '';
        document.getElementById('in_sex').value = sex || '';
        document.getElementById('in_bday').value = bday ? bday.split('T')[0] : '';

        // Sync Phone Prefix display
        const hiddenPhoneVal = phone || '';
        const displayPhoneInput = document.getElementById('phone_display');
        const hiddenPhoneInput = document.getElementById('in_phone');
        if (hiddenPhoneVal.startsWith('09')) {
            displayPhoneInput.value = hiddenPhoneVal.substring(2);
            hiddenPhoneInput.value = hiddenPhoneVal;
        } else {
            displayPhoneInput.value = hiddenPhoneVal;
            hiddenPhoneInput.value = hiddenPhoneVal;
        }

        document.getElementById('addr_street').value = street || '';
        setAddressDropdowns(province, city, barangay);
    }

    function clearDetails() {
        ['in_first_name', 'in_middle_name', 'in_last_name', 'in_suffix', 'in_phone', 'in_bday', 'addr_street'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = "";
        });
        document.getElementById('phone_display').value = "";
        document.getElementById('in_sex').value = "";
        document.getElementById('addr_province').value = "";
        document.getElementById('addr_city').innerHTML = '<option value="">Select Province First</option>';
        document.getElementById('addr_city').disabled = true;
        document.getElementById('addr_brgy').innerHTML = '<option value="">Select City First</option>';
        document.getElementById('addr_brgy').disabled = true;
        updateCompiledAddress();
    }

    // --- Phone Digits synchronization ---
    function syncWizardPhone() {
        const displayInput = document.getElementById('phone_display');
        const hiddenInput = document.getElementById('in_phone');
        if (displayInput && hiddenInput) {
            hiddenInput.value = displayInput.value ? '09' + displayInput.value : '';
        }
        updateSummary();
        saveAppointmentDraft();
    }

    // --- Doctor's Referral Local Upload handlers ---
    function handleReferralUpload(input) {
        const file = input.files[0];
        const inputWrapper = document.getElementById('referral_input_wrapper');
        const previewContainer = document.getElementById('referral_preview_container');
        const label = document.getElementById('referral_file_label');
        const viewBtn = document.getElementById('btn_view_referral');

        if (!file) {
            previewContainer.classList.add('d-none');
            inputWrapper.classList.remove('d-none');
            referralLocalData = null;
            return;
        }

        inputWrapper.classList.add('d-none');
        previewContainer.classList.remove('d-none');
        label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${file.name}`;

        const reader = new FileReader();
        reader.onload = function(e) {
            referralLocalData = e.target.result;
            viewBtn.onclick = function() {
                viewReferralFile(referralLocalData);
            };
        };
        reader.readAsDataURL(file);
        saveAppointmentDraft();
    }

    function removeUploadedReferral() {
        const input = document.getElementById('in_referral');
        const inputWrapper = document.getElementById('referral_input_wrapper');
        const previewContainer = document.getElementById('referral_preview_container');
        if (input) input.value = '';
        if (inputWrapper) inputWrapper.classList.remove('d-none');
        if (previewContainer) previewContainer.classList.add('d-none');
        referralLocalData = null;
        localStorage.removeItem('referral_base64');
        localStorage.removeItem('referral_name');
        saveAppointmentDraft();
    }

    // --- Lightbox Independent View Handler ---
    function viewReferralFile(fileSrc) {
        if (!fileSrc) return;
        const isPdf = fileSrc.toLowerCase().endsWith('.pdf') || fileSrc.startsWith('data:application/pdf');
        const img = document.getElementById('lightbox_qr_img');
        const iframe = document.getElementById('lightbox_pdf_viewer');
        const controls = document.getElementById('lightbox_zoom_controls');
        const lightbox = document.getElementById('qr_lightbox');

        if (!lightbox) return;

        // Reset zoom properties on lightbox initialization
        currentScale = 1;
        translateX = 0;
        translateY = 0;
        if (img) {
            img.style.transform = 'translate(0px, 0px) scale(1)';
            img.style.cursor = 'default';
        }
        const percentEl = document.getElementById('zoom_percent');
        if (percentEl) percentEl.innerText = '100%';

        if (isPdf) {
            if (img) img.classList.add('d-none');
            if (controls) controls.classList.add('d-none');
            if (iframe) {
                iframe.src = fileSrc;
                iframe.classList.remove('d-none');
            }
        } else {
            if (iframe) {
                iframe.classList.add('d-none');
                iframe.src = '';
            }
            if (img) {
                img.src = fileSrc;
                img.classList.remove('d-none');
            }
            if (controls) controls.classList.remove('d-none');
        }

        lightbox.classList.remove('d-none');
        lightbox.classList.add('d-flex');
    }

    // --- Step 3 Accordion detail toggling ---
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

    // --- Step 3 Cart selective remove handler ---
    function removeSelectedTest(checkboxId) {
        const cb = document.getElementById(checkboxId);
        if (cb) {
            cb.checked = false;
            updateSummary();
            saveAppointmentDraft();
        }
    }

    // --- Dynamic DOM re-sorting (Checked items to top) ---
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
                return bChecked - aChecked; // Transport Checked (1) above Unchecked (0)
            }

            // Secondary fallback: Sort alphabetically by element dataset name
            const aName = a.dataset.name || '';
            const bName = b.dataset.name || '';
            return aName.localeCompare(bName);
        });

        // Re-append to DOM container natively to apply the sorted order smoothly
        items.forEach(item => container.appendChild(item));
    }

    // --- 3. PSGC CASCADING ADDRESS API LOGIC ---
    async function fetchProvinces() {
        try {
            const res = await fetch(`${apiBase}/provinces.json`);
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
            const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities.json`);
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
        saveAppointmentDraft();
    }

    async function fetchBarangays(cityCode) {
        if (!cityCode) return;
        const brgySel = document.getElementById('addr_brgy');
        brgySel.disabled = true;
        brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
        try {
            const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays.json`);
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
        saveAppointmentDraft();
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
        const f = document.getElementById('in_first_name').value;
        const m = document.getElementById('in_middle_name').value;
        const l = document.getElementById('in_last_name').value;
        const fullName = f + (m && m !== 'N/A' ? ' ' + m : '') + ' ' + l;

        document.getElementById('sum_name').innerText = fullName.trim() || 'Not specified';

        const sidebarBadge = document.getElementById('test_count_badge');
        const selected = document.querySelectorAll('.test-checkbox:checked');

        if (sidebarBadge) sidebarBadge.innerText = selected.length;

        let total = 0;
        let sidebarHtml = '';

        // Dynamically toggle highlighted classes and compute estimated costs
        document.querySelectorAll('.test-item').forEach(item => {
            const cb = item.querySelector('.test-checkbox');
            if (cb && cb.checked) {
                item.classList.add('selected-test-item');
                const price = parseFloat(cb.dataset.price);
                total += price;

                sidebarHtml += `
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-truncate me-2 small uppercase">${cb.dataset.name}</span>
                    <span class="text-neon fw-bold small"> ${price.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
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

        // Trigger safe DOM list re-sorting
        resortTestList();
    }

    // --- 5. STEP 4: TIME SLOTS FETCH & AUTO-SELECT RESTORATION ---
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

            // Intercept time slot matching based on stored draft parameters
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

                // Auto-refresh the sidebar selection state if a slot was matched
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

    // --- Dynamic Time Slot Selector and Sidebar update handlers ---
    function handleSlotSelection() {
        const selectedRadio = document.querySelector('input[name="time_slot"]:checked');
        if (selectedRadio) {
            const date = document.getElementById('wiz_date').value;
            const timeLabel = selectedRadio.nextElementSibling.innerText;
            setSchedule(date, timeLabel);
            saveAppointmentDraft();
        }
    }

    function setSchedule(date, time) {
        document.getElementById('sum_schedule').classList.remove('d-none');
        document.getElementById('sum_date').innerText = date;
        document.getElementById('sum_time').innerText = time;
    }

    // --- 6. FLOW VALIDATORS ---
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

    function validateStep2() {
        let isValid = true;

        // Clean previous states
        document.querySelectorAll('#page-2 .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('#page-2 .invalid-feedback').forEach(el => {
            el.classList.add('d-none');
            el.innerText = '';
        });
        const phoneErr = document.getElementById('err_phone');
        if (phoneErr) phoneErr.innerText = '';

        function setFieldInvalid(elementId, errorDivId, message) {
            const input = document.getElementById(elementId);
            const errorDiv = document.getElementById(errorDivId);
            if (input) input.classList.add('is-invalid');
            if (errorDiv) {
                errorDiv.innerText = message;
                errorDiv.classList.remove('d-none');
            }
            isValid = false;
        }

        // Name Validation matching backend limits
        const firstName = document.getElementById('in_first_name').value.trim();
        const lastName = document.getElementById('in_last_name').value.trim();
        const middleName = document.getElementById('in_middle_name').value.trim();
        const suffix = document.getElementById('in_suffix').value.trim();

        const nameRegex = /^[a-zA-Z \s.\'-]+$/;
        const startRegex = /^[a-zA-Z ]/;
        const consecutiveRegex = /[.\'-]{2,}/;
        const letterRegex = /[a-zA-Z ]/;

        function validateNameString(val, fieldName, elementId, errorDivId, required = true) {
            if (!val) {
                if (required) setFieldInvalid(elementId, errorDivId, `${fieldName} is required.`);
                return;
            }
            if (val === 'N/A') return;

            if (!nameRegex.test(val)) {
                setFieldInvalid(elementId, errorDivId, `${fieldName} may only contain letters, spaces, periods, hyphens, and apostrophes.`);
            } else if (!startRegex.test(val)) {
                setFieldInvalid(elementId, errorDivId, `${fieldName} must start with a letter.`);
            } else if (!letterRegex.test(val)) {
                setFieldInvalid(elementId, errorDivId, `${fieldName} must contain at least one letter.`);
            } else if (consecutiveRegex.test(val)) {
                setFieldInvalid(elementId, errorDivId, `${fieldName} cannot contain consecutive punctuation marks.`);
            } else if (val.length > 60) {
                setFieldInvalid(elementId, errorDivId, `${fieldName} cannot exceed 60 characters.`);
            }
        }

        validateNameString(firstName, "First Name", "in_first_name", "err_first_name", true);
        validateNameString(lastName, "Last Name", "in_last_name", "err_last_name", true);
        validateNameString(middleName, "Middle Name", "in_middle_name", "err_middle_name", false);

        if (suffix) {
            const suffixRegex = /^[a-zA-Z0-9\s.]+$/;
            if (!suffixRegex.test(suffix)) {
                setFieldInvalid("in_suffix", "err_suffix", "Suffix may only contain letters, numbers, spaces, and periods.");
            } else if (suffix.length > 10) {
                setFieldInvalid("in_suffix", "err_suffix", "Suffix cannot exceed 10 characters.");
            }
        }

        // Sex Validation
        const sex = document.getElementById('in_sex').value;
        if (!sex) {
            setFieldInvalid("in_sex", "err_sex", "Please select a gender.");
        }

        // Birthdate Validation
        const bday = document.getElementById('in_bday').value;
        if (!bday) {
            setFieldInvalid("in_bday", "err_birthdate", "Birthdate is required.");
        } else {
            const age = Math.floor((new Date() - new Date(bday)) / (1000 * 60 * 60 * 24 * 365.25));
            if (age < 0) {
                setFieldInvalid("in_bday", "err_birthdate", "Birthdate cannot be in the future.");
            }
        }

        // Contact Phone Validation (Strictly 11 digits)
        const phoneVal = document.getElementById('in_phone').value;
        const displayVal = document.getElementById('phone_display').value;
        const phoneRegex = /^09\d{9}$/;
        if (!displayVal) {
            setFieldInvalid("phone_display", "err_phone", "Contact number is required.");
        } else if (!phoneRegex.test(phoneVal)) {
            setFieldInvalid("phone_display", "err_phone", "Phone number must start with 09 and contain exactly 11 digits.");
        }

        // FIXED: Corrected ID targets from the non-existent ID 'addr_barangay' to the actual element ID 'addr_brgy'
        const prov = document.getElementById('addr_province').value;
        const city = document.getElementById('addr_city').value;
        const brgy = document.getElementById('addr_brgy').value;
        const street = document.getElementById('addr_street').value.trim();

        if (!prov) setFieldInvalid("addr_province", "err_province", "Province is required.");
        if (!city) setFieldInvalid("addr_city", "err_city", "City selection is required.");
        if (!brgy) setFieldInvalid("addr_brgy", "err_barangay", "Barangay selection is required.");
        if (!street) setFieldInvalid("addr_street", "err_street", "Street address is required.");

        if (isValid) {
            goToPage(3);
        } else {
            showWizardAlert("Please review the fields in Step 2. Correct any highlighted validation errors before proceeding.");
        }
    }

    // Helper to clear error state on user input
    function clearInlineError(inputElement) {
        if (inputElement) {
            inputElement.classList.remove('is-invalid');
            let parent = inputElement.parentElement;
            let errorDiv = parent.classList.contains('input-group') 
                ? parent.parentElement.querySelector('.invalid-feedback') 
                : parent.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.classList.add('d-none');
                errorDiv.innerText = '';
            }
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

    // FIXED: Added missing step 4 schedule validator
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
                        } catch (err) {
                            // Suppress selector warnings for brackets and special characters
                        }
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
                    } catch (err) {
                        // Suppress warnings for custom virtual elements
                    }
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
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                sanitize: false
            });
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

        // 10. FIXED: Dynamically map and compile literal address strings on submit, then clear storage
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