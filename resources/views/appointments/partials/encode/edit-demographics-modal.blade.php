@php
    // Defensive initialization to resolve IDE and static analysis undefined variable warnings
    $selectedTypes = $selectedTypes ?? $autoReportTypes ?? [];
    $isReadonly = $isReadonly ?? false;
@endphp

{{-- A. REVISE PATIENT DETAILS MODAL (API Address Integration, Service Modification & Payment Edit) --}}
<div class="modal fade" id="editAppointmentDetailsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('internal.appointment-details.update', $appointment->id) }}" method="POST" id="revise_details_form" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            @method('PUT')
            
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3 d-flex align-items-center justify-content-between">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0">
                    <i class="bi bi-pencil-square me-2"></i>Revise Patient Information
                </h5>
                <div class="d-flex align-items-center gap-3">
                    {{-- Refined Reset Edits Button --}}
                    <button type="button" class="btn btn-reset-custom" onclick="resetReviseForm('{{ $appointment->id }}')">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Edits
                    </button>
                    {{-- Context-aware Close Button --}}
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4">
                {{-- Horizontal Step Navigation --}}
                <ul class="nav nav-pills mb-4 justify-content-center" id="reviseTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active btn-sm uppercase px-3 py-1.5" id="revise-demo-tab" data-bs-toggle="pill" data-bs-target="#tab-revise-demo" type="button" role="tab">1. Identity</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link btn-sm uppercase px-3 py-1.5" id="revise-addr-tab" data-bs-toggle="pill" data-bs-target="#tab-revise-addr" type="button" role="tab" onclick="initializeReviseAddress()">2. Address</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link btn-sm uppercase px-3 py-1.5" id="revise-services-tab" data-bs-toggle="pill" data-bs-target="#tab-revise-services" type="button" role="tab">3. Services & Billing</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link btn-sm uppercase px-3 py-1.5" id="revise-audit-tab" data-bs-toggle="pill" data-bs-target="#tab-revise-audit" type="button" role="tab">4. Justification</button>
                    </li>
                </ul>

                <div class="tab-content" id="reviseTabsContent">
                    {{-- TAB 1: DEMOGRAPHICS SNAPSHOT --}}
                    <div class="tab-pane fade show active text-start" id="tab-revise-demo" role="tabpanel">
                        <h6 class="text-accent mb-3 small fw-bold uppercase">Personal Identity</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">First Name</label>
                                <input type="text" name="patient_first_name" class="form-control uppercase" value="{{ $appointment->patient_first_name }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Middle Name</label>
                                <input type="text" name="patient_middle_name" class="form-control uppercase" value="{{ $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name }}">
                            </div>
                            <div class="col-md-3">
                                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Last Name</label>
                                <input type="text" name="patient_last_name" class="form-control uppercase" value="{{ $appointment->patient_last_name }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Suffix (Opt.)</label>
                                <input type="text" name="patient_suffix" id="revise_suffix" list="suffix_options" class="form-control uppercase" value="{{ $appointment->patient_suffix }}">
                            </div>
                            <div class="col-md-6">
                                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Contact Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                                    <input type="text" id="revise_phone_display" class="form-control py-3 shadow-none" placeholder="171234567" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncRevisePhone();" required>
                                </div>
                                <input type="hidden" name="patient_phone" id="revise_in_phone" value="{{ $appointment->patient_phone }}">
                            </div>
                            <div class="col-md-3">
                                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Gender</label>
                                <select name="patient_sex" class="form-select" required>
                                    <option value="Male" {{ $appointment->patient_sex == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ $appointment->patient_sex == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Birthdate</label>
                                <input type="date" name="patient_birthdate" id="revise_bday" class="form-control" value="{{ $appointment->patient_birthdate ? $appointment->patient_birthdate->format('Y-m-d') : '' }}" required>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: RESIDENTIAL ADDRESS --}}
                    <div class="tab-pane fade text-start" id="tab-revise-addr" role="tabpanel">
                        <h6 class="text-accent mb-3 small fw-bold uppercase">Residential Address</h6>
                        <div class="alert alert-clinical p-2.5 mb-3 border border-secondary border-opacity-10 text-start" style="background-color: rgba(0,0,0,0.015); border-radius: 8px;">
                            <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Saved Address on File:</div>
                            <div class="text-main small">{{ $appointment->patient_address }}</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">Province</label>
                                <select name="patient_province" id="revise_province" class="form-select" required>
                                    <option value="">Loading Provinces...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">City / Municipality</label>
                                <select name="patient_city" id="revise_city" class="form-select" disabled required>
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">Barangay</label>
                                <select name="patient_barangay" id="revise_barangay" class="form-select" disabled required>
                                    <option value="">Select City First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">Street / House No.</label>
                                <input type="text" name="patient_street" id="revise_street" class="form-control uppercase" value="{{ $appointment->patient_street }}" required>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 3: SERVICES & BILLING --}}
                    <div class="tab-pane fade text-start" id="tab-revise-services" role="tabpanel">
                        <h6 class="text-accent mb-3 small fw-bold uppercase">Requested Medical Services & Billing</h6>
                        <div class="mb-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
                                <input type="text" id="revise_service_search" class="form-control form-control-sm" placeholder="Search services/tests...">
                            </div>
                        </div>
                        @php $currentServiceIds = $appointment->services->pluck('id')->toArray(); @endphp
                        <div class="p-3 border rounded row g-2" style="max-height: 180px; overflow-y: auto; background-color: var(--bg-main) !important; border: 1.5px solid var(--border-color) !important;">
                            @foreach($services as $service)
                                <div class="form-check col-md-6 mb-1 revise-service-item">
                                    <input class="form-check-input" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="revise_service_{{ $service->id }}" {{ in_array($service->id, $currentServiceIds) ? 'checked' : '' }}>
                                    <label class="form-check-label text-main small" for="revise_service_{{ $service->id }}">
                                        {{ $service->name }} ({{ number_format($service->price, 2) }})
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-12 mt-3">
                            <label class="smaller fw-bold uppercase mb-1 text-accent" style="font-size: 0.75rem;"><i class="bi bi-cash-coin me-1"></i>Collected Payment / Price (PHP)</label>
                            <input type="number" step="0.01" name="payment_amount" class="form-control py-2 fw-bold text-accent" value="{{ $appointment->payment_amount ?? $appointment->totalPrice() }}" required min="0">
                        </div>
                    </div>

                    {{-- TAB 4: JUSTIFICATION / AUDIT --}}
                    <div class="tab-pane fade text-start" id="tab-revise-audit" role="tabpanel">
                        <h6 class="text-danger mb-2 small fw-bold uppercase"><i class="bi bi-shield-exclamation me-1"></i>Administrative Justification</h6>
                        <div class="mb-3">
                            <label class="smaller text-muted d-block mb-1">Select the official reason for modifying this patient's details.</label>
                            <select id="revise_reason_select" name="reason" class="form-select" required>
                                <option value="" disabled selected>-- Select a valid justification --</option>
                                <option value="Routine administrative update / profile maintenance">Routine administrative update / profile maintenance</option>
                                <option value="Official request for details correction">Official request for details correction</option>
                                <option value="Correction of typographical / data entry error">Correction of typographical / data entry error</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                        </div>
                        <div id="revise_custom_reason_wrapper" class="mb-0 d-none">
                            <label class="smaller fw-bold uppercase mb-1">Specify Custom Reason</label>
                            <textarea id="revise_custom_reason" class="form-control" rows="2" placeholder="Explain the profile revision justification..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3">
                <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-accent flex-grow-1 fw-bold uppercase">SAVE REVISIONS</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // 1. REVERSION CONTROLLER: Reset form to originally persisted model snapshots
    window.resetReviseForm = async function(appId) {
        if (!confirm('Revert all changes back to originally saved values?')) return;

        const form = document.getElementById('revise_details_form');
        if (!form) return;

        // Reset Identity tab inputs
        form.querySelector('[name="patient_first_name"]').value = "{{ $appointment->patient_first_name }}";
        form.querySelector('[name="patient_middle_name"]').value = "{{ $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name }}";
        form.querySelector('[name="patient_last_name"]').value = "{{ $appointment->patient_last_name }}";
        
        const suffixInput = document.getElementById('revise_suffix');
        if (suffixInput) suffixInput.value = "{{ $appointment->patient_suffix }}";

        form.querySelector('[name="patient_sex"]').value = "{{ $appointment->patient_sex }}";
        form.querySelector('[name="patient_birthdate"]').value = "{{ $appointment->patient_birthdate ? $appointment->patient_birthdate->format('Y-m-d') : '' }}";

        // Reset phone display parameters
        let originalPhone = "{{ $appointment->patient_phone }}".trim();
        if (originalPhone.startsWith('+639')) originalPhone = '09' + originalPhone.substring(4);
        if (originalPhone.startsWith('639')) originalPhone = '09' + originalPhone.substring(3);
        const displayInput = document.getElementById('revise_phone_display');
        const hiddenInput = document.getElementById('revise_in_phone');
        if (displayInput && hiddenInput) {
            if (originalPhone.startsWith('09') && originalPhone.length === 11) {
                displayInput.value = originalPhone.substring(2);
                hiddenInput.value = originalPhone;
            } else {
                displayInput.value = originalPhone;
                hiddenInput.value = originalPhone;
            }
        }

        // Reset address parameters and restore dropdown selectors to matching database nodes
        await initializeReviseAddress();
        document.getElementById('revise_street').value = "{{ $appointment->patient_street }}";

        // Reset medical services checkboxes
        const originalServices = @json($currentServiceIds);
        form.querySelectorAll('[name="service_ids[]"]').forEach(cb => {
            cb.checked = originalServices.includes(parseInt(cb.value));
        });
        resortReviseServices();

        // Reset payment parameters
        form.querySelector('[name="payment_amount"]').value = "{{ $appointment->payment_amount ?? $appointment->totalPrice() }}";

        // Reset justification block parameters
        const reasonSelect = document.getElementById('revise_reason_select');
        reasonSelect.value = '';
        const textareaWrapper = document.getElementById('revise_custom_reason_wrapper');
        const textareaEl = document.getElementById('revise_custom_reason');
        textareaWrapper.classList.add('d-none');
        textareaEl.removeAttribute('required');
        textareaEl.removeAttribute('name');
        reasonSelect.setAttribute('name', 'reason');
        textareaEl.value = '';
    };

    // 2. Cascading PSGC Address Engine (Fixed with relative static targets to protect from CORS redirection blocks)
    async function initializeReviseAddress() {
        const savedProv = "{{ $appointment->patient_province }}";
        const savedCity = "{{ $appointment->patient_city }}";
        const savedBrgy = "{{ $appointment->patient_barangay }}";

        const provSel = document.getElementById('revise_province');
        if (!provSel) return;

        try {
            const res = await fetch('https://psgc.gitlab.io/api/provinces.json');
            const data = await res.json();
            provSel.innerHTML = '<option value="">Select Province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
            });

            if (savedProv) {
                let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === savedProv.toUpperCase());
                if (provOpt) {
                    provSel.value = provOpt.value;
                    await fetchReviseCities(provOpt.value, savedCity, savedBrgy);
                }
            }
        } catch (e) {
            console.error("Provinces fetch failed:", e);
        }
    }

    async function fetchReviseCities(provCode, savedCity = '', savedBrgy = '') {
        const citySel = document.getElementById('revise_city');
        const brgySel = document.getElementById('revise_barangay');
        if (!citySel || !brgySel) return;

        citySel.disabled = true;
        brgySel.disabled = true;
        citySel.innerHTML = '<option value="">Loading Cities...</option>';

        try {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities.json`);
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
                    await fetchReviseBarangays(cityOpt.value, savedBrgy);
                }
            }
        } catch (e) {
            console.error("Cities fetch failed:", e);
        }
    }

    async function fetchReviseBarangays(cityCode, savedBrgy = '') {
        const brgySel = document.getElementById('revise_barangay');
        if (!brgySel) return;

        brgySel.disabled = true;
        brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

        try {
            const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays.json`);
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
            console.error("Barangays fetch failed:", e);
        }
    }

    function compileReviseAddress() {
        const brgy = document.getElementById('revise_barangay');
        const city = document.getElementById('revise_city');
        const prov = document.getElementById('revise_province');

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

    // 3. Phone Digits display view sync
    function syncRevisePhone() {
        const displayInput = document.getElementById('revise_phone_display');
        const hiddenInput = document.getElementById('revise_in_phone');
        if (displayInput && hiddenInput) {
            hiddenInput.value = displayInput.value ? '09' + displayInput.value : '';
        }
    }

    // 4. Sort checklist to float checked service rows cleanly to top
    function resortReviseServices() {
        const container = document.querySelector('#editAppointmentDetailsModal .p-3.border.rounded');
        if (!container) return;

        const items = Array.from(container.querySelectorAll('.revise-service-item'));
        items.sort((a, b) => {
            const aChecked = a.querySelector('input[type="checkbox"]').checked ? 1 : 0;
            const bChecked = b.querySelector('input[type="checkbox"]').checked ? 1 : 0;
            
            if (aChecked !== bChecked) {
                return bChecked - aChecked;
            }
            const aText = a.querySelector('label').innerText.trim();
            const bText = b.querySelector('label').innerText.trim();
            return aText.localeCompare(bText);
        });

        items.forEach(item => container.appendChild(item));
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Sync local phone display and handle initial setups upon modal load
        const modalEl = document.getElementById('editAppointmentDetailsModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', () => {
                const trigger = document.getElementById('revise-demo-tab');
                if (trigger) {
                    const tab = bootstrap.Tab.getInstance(trigger) || new bootstrap.Tab(trigger);
                    tab.show();
                }

                let phoneVal = '{{ $appointment->patient_phone }}'.trim();
                if (phoneVal.startsWith('+639')) phoneVal = '09' + phoneVal.substring(4);
                if (phoneVal.startsWith('639')) phoneVal = '09' + phoneVal.substring(3);
                const displayInput = document.getElementById('revise_phone_display');
                const hiddenInput = document.getElementById('revise_in_phone');
                if (displayInput && hiddenInput) {
                    if (phoneVal.startsWith('09') && phoneVal.length === 11) {
                        displayInput.value = phoneVal.substring(2);
                        hiddenInput.value = phoneVal;
                    } else {
                        displayInput.value = phoneVal;
                        hiddenInput.value = phoneVal;
                    }
                }

                resortReviseServices();
            });
        }

        // Attach listeners on checklist checkboxes to dynamically float check states
        document.querySelectorAll('#editAppointmentDetailsModal .revise-service-item input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', resortReviseServices);
        });

        // Submit gate: verifies demographics and audit justifications meet compliance limits
        document.getElementById('revise_details_form')?.addEventListener('submit', function(e) {
            let errors = [];

            const firstName = document.querySelector('#editAppointmentDetailsModal [name="patient_first_name"]').value.trim();
            const lastName = document.querySelector('#editAppointmentDetailsModal [name="patient_last_name"]').value.trim();
            const birthdate = document.getElementById('revise_bday').value;
            const phone = document.getElementById('revise_in_phone').value;
            const displayPhone = document.getElementById('revise_phone_display').value;

            if (!firstName) errors.push("First Name is missing on Tab 1.");
            if (!lastName) errors.push("Last Name is missing on Tab 1.");

            if (!birthdate) {
                errors.push("Birthdate is required on Tab 1.");
            } else {
                const age = Math.floor((new Date() - new Date(birthdate)) / (1000 * 60 * 60 * 24 * 365.25));
                const isDependent = @json($appointment->dependent_id !== null);
                if (age < 0) {
                    errors.push("Birthdate cannot be in the future.");
                } else if (isDependent) {
                    if (age >= 18) {
                        errors.push("Dependents must be minors (under 18 years of age).");
                    }
                } else {
                    if (age < 18) {
                        errors.push("You must be at least 18 years old to book a personal appointment.");
                    }
                }
            }

            const phoneRegex = /^09\d{9}$/;
            if (!displayPhone) {
                errors.push("Contact Phone is missing on Tab 1.");
            } else if (!phoneRegex.test(phone)) {
                errors.push("Phone number must start with 09 and contain exactly 11 digits.");
            }

            const reasonSelect = document.getElementById('revise_reason_select');
            const customReason = document.getElementById('revise_custom_reason').value.trim();
            if (!reasonSelect.value) {
                errors.push("Administrative Justification is missing on Tab 4.");
            } else if (reasonSelect.value === 'Others' && customReason.length < 5) {
                errors.push("Custom Justification must be at least 5 characters long on Tab 4.");
            }

            if (errors.length > 0) {
                e.preventDefault();
                e.stopPropagation();
                alert("Omissions Found:\n" + errors.map(err => ` ${err}`).join("\n"));
                return false;
            }

            compileReviseAddress();
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Sleek Outline Custom Reset Button */
    .btn-reset-custom {
        background-color: transparent !important;
        border: 1.5px solid var(--text-muted) !important;
        color: var(--text-muted) !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 6px 12px !important;
        border-radius: 6px !important;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
    }
    .btn-reset-custom:hover {
        border-color: #ffc107 !important;
        color: #ffc107 !important;
        background-color: rgba(255, 193, 7, 0.05) !important;
    }

    /* High-contrast context-aware close button settings */
    #editAppointmentDetailsModal .btn-close {
        background-color: transparent !important;
        opacity: 0.6;
        transition: opacity 0.2s;
    }
    #editAppointmentDetailsModal .btn-close:hover {
        opacity: 1;
    }
    [data-bs-theme="dark"] #editAppointmentDetailsModal .btn-close {
        filter: invert(1) grayscale(1) brightness(2);
    }

    /* Fixed backdrop positioning issues caused by CSS transforms / stacking contexts in list view */
    #editAppointmentDetailsModal.show {
        background-color: rgba(0, 0, 0, 0.6) !important;
        backdrop-filter: blur(4px);
    }
</style>
@endpush