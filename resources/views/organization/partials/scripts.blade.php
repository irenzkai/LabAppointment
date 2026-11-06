<!-- resources/views/organization/partials/scripts.blade.php -->
@push('scripts')
<script>
const psgcApiBase = "https://psgc.gitlab.io/api";
let isSubmittingRosterMember = false;

document.addEventListener('DOMContentLoaded', () => {
    fetchRosterProvinces();

    // Roster Member phone number input normalizer & freeze 09 handler
    const rmPhoneDisp = document.getElementById('rm_phone_display');
    if (rmPhoneDisp) {
        rmPhoneDisp.addEventListener('input', function() {
            let val = this.value.replace(/[^0-9]/g, '');
            if (val.startsWith('09')) val = val.substring(2);
            else if (val.startsWith('639')) val = val.substring(3);
            this.value = val.slice(0, 9);

            const hidden = document.getElementById('rm_phone');
            if (hidden) {
                hidden.value = this.value ? ('09' + this.value) : '';
            }

            const errDiv = document.getElementById('err_rm_phone');
            if (errDiv) {
                errDiv.classList.add('d-none');
                errDiv.innerText = '';
            }
            this.classList.remove('is-invalid');
        });
    }

    // Clear validation styling when user types or changes required fields
    const inputsToClear = [
        'rm_first_name', 
        'rm_last_name', 
        'rm_birthdate', 
        'rm_email', 
        'rm_phone_display',
        'rm_province',
        'rm_city',
        'rm_barangay',
        'rm_street'
    ];

    inputsToClear.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const clearFn = function() {
                this.classList.remove('is-invalid');
                const errId = id === 'rm_phone_display' ? 'err_rm_phone' : 'err_' + id;
                const err = document.getElementById(errId);
                if (err) {
                    err.classList.add('d-none');
                    err.innerText = '';
                }
                const topAlert = document.getElementById('rm_modal_error_alert');
                if (topAlert) {
                    topAlert.classList.add('d-none');
                    topAlert.innerHTML = '';
                }
            };
            el.addEventListener('input', clearFn);
            el.addEventListener('change', clearFn);
        }
    });

    // Initialize package proposal service filters
    filterProposalServices();
    filterEditProposalServices();

    // Initialize tab state preservation across navigation & reloads
    initTabStatePersistence();
});

// =========================================================================
// TAB STATE PERSISTENCE (KEEPS ROSTERS / PACKAGES INTACT ON REFRESH & CLICKS)
// =========================================================================
function initTabStatePersistence() {
    document.querySelectorAll('button[data-bs-toggle="pill"], button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            if (target) {
                localStorage.setItem('org_hub_active_tab', target);
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, target);
                } else {
                    window.location.hash = target;
                }
            }
        });
    });

    const hash = window.location.hash;
    const urlParams = new URLSearchParams(window.location.search);
    const hasGroupParam = urlParams.has('group');
    const savedTab = localStorage.getItem('org_hub_active_tab');

    let targetTab = null;

    if (hash && document.querySelector(`button[data-bs-target="${hash}"]`)) {
        targetTab = hash;
    } else if (hasGroupParam) {
        if (document.querySelector('button[data-bs-target="#tab-rosters"]')) {
            targetTab = '#tab-rosters';
        } else if (document.querySelector('button[data-bs-target="#insp-rosters"]')) {
            targetTab = '#insp-rosters';
        }
    } else if (savedTab && document.querySelector(`button[data-bs-target="${savedTab}"]`)) {
        targetTab = savedTab;
    }

    if (targetTab) {
        const triggerBtn = document.querySelector(`button[data-bs-target="${targetTab}"]`);
        if (triggerBtn && !triggerBtn.classList.contains('active')) {
            const tabInstance = bootstrap.Tab.getOrCreateInstance(triggerBtn);
            tabInstance.show();
        }
    }
}

// Dropdown "Others" Toggle Helper
function toggleReasonOthers(selectEl, customContainerId) {
    const container = document.getElementById(customContainerId);
    if (!container) return;
    const isOthers = (selectEl.value === 'Others');
    container.classList.toggle('d-none', !isOthers);
    const textarea = container.querySelector('textarea');
    if (textarea) textarea.required = isOthers;
}

// =========================================================================
// 1. PROPOSE PACKAGE (SEARCH & GENDER COMPATIBILITY FILTER)
// =========================================================================
function openProposePackageModal(orgId, orgName) {
    const orgInput = document.getElementById('prop_org_id');
    if (orgInput) orgInput.value = orgId;

    const titleEl = document.getElementById('prop_modal_title');
    if (titleEl) titleEl.innerText = `Propose Package for ${orgName}`;

    const searchInput = document.getElementById('propServiceSearch');
    if (searchInput) searchInput.value = '';

    const nameInput = document.querySelector('#createPackageModal input[name="name"]');
    if (nameInput) nameInput.value = '';

    const descInput = document.querySelector('#createPackageModal textarea[name="description"]');
    if (descInput) descInput.value = '';

    const genderSelect = document.getElementById('prop_gender_restriction');
    if (genderSelect) genderSelect.value = 'both';

    document.querySelectorAll('.prop-svc-chk').forEach(chk => {
        chk.checked = false;
    });

    filterProposalServices();

    const modalEl = document.getElementById('createPackageModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function filterProposalServices() {
    const searchInput = document.getElementById('propServiceSearch');
    const q = (searchInput?.value || '').toUpperCase().trim();
    const pkgGender = (document.getElementById('prop_gender_restriction')?.value || 'both').toLowerCase();

    document.querySelectorAll('.prop-service-item').forEach(item => {
        const name = (item.dataset.name || '').toUpperCase();
        const svcGender = (item.dataset.gender || 'both').toLowerCase();

        let genderMatch = false;
        if (pkgGender === 'male') {
            genderMatch = (svcGender === 'male' || svcGender === 'both');
        } else if (pkgGender === 'female') {
            genderMatch = (svcGender === 'female' || svcGender === 'both');
        } else {
            genderMatch = (svcGender === 'both');
        }

        const textMatch = !q || name.includes(q);
        const isVisible = genderMatch && textMatch;

        item.classList.toggle('d-none', !isVisible);

        if (!genderMatch) {
            const chk = item.querySelector('.prop-svc-chk');
            if (chk && chk.checked) {
                chk.checked = false;
            }
        }
    });

    recalculateProposalTotal();
}

function recalculateProposalTotal() {
    let sum = 0;
    document.querySelectorAll('.prop-svc-chk:checked').forEach(chk => {
        sum += parseFloat(chk.dataset.price || 0);
    });

    const totalEl = document.getElementById('prop_base_total');
    if (totalEl) {
        totalEl.innerText = '₱' + sum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

// =========================================================================
// 2. EDIT / RESUBMIT CUSTOM PACKAGE PROPOSAL
// =========================================================================
function openEditPackageModal(pkg, serviceIds, isResubmit = false) {
    if (!pkg) return;

    const form = document.getElementById('editPackageForm');
    form.action = `/organization/package/${pkg.id}`;

    document.getElementById('edit_pkg_id').value = pkg.id;
    document.getElementById('edit_pkg_name').value = pkg.name || '';
    document.getElementById('edit_pkg_gender_restriction').value = pkg.gender_restriction || 'both';
    document.getElementById('edit_pkg_description').value = pkg.description || '';

    const titleEl = document.getElementById('edit_pkg_modal_title');
    const submitBtn = document.getElementById('edit_pkg_submit_btn');
    const warningText = document.getElementById('edit_pkg_warning_text');

    if (isResubmit || pkg.status === 'declined') {
        if (titleEl) titleEl.innerText = 'Resubmit Custom Package Proposal';
        if (submitBtn) submitBtn.innerText = 'Resubmit Proposal for Review';
        if (warningText) {
            warningText.innerHTML = '<strong>Resubmission Notice:</strong> Modify your examination bundle or parameters to address clinical feedback. Upon submission, clinic staff will re-evaluate pricing and preparation instructions.';
        }
    } else {
        if (titleEl) titleEl.innerText = 'Edit Custom Package Proposal';
        if (submitBtn) submitBtn.innerText = 'Save & Submit for Re-approval';
        if (warningText) {
            warningText.innerHTML = '<strong>Re-approval Notice:</strong> Editing an approved package resets its status to <strong>Pending Clinic Review</strong>. Clinic staff will re-evaluate pricing, duration, and patient instructions before reactivation.';
        }
    }

    const selectedIds = Array.isArray(serviceIds) ? serviceIds.map(String) : [];

    document.querySelectorAll('.edit-prop-svc-chk').forEach(chk => {
        chk.checked = selectedIds.includes(String(chk.value));
    });

    const searchInput = document.getElementById('editPkgServiceSearch');
    if (searchInput) searchInput.value = '';

    filterEditProposalServices();

    const modalEl = document.getElementById('editPackageModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openDeletePackageModal(pkgId, pkgName) {
    const form = document.getElementById('deletePackageForm');
    if (form) form.action = `/organization/package/${pkgId}`;

    const nameEl = document.getElementById('del_pkg_name');
    if (nameEl) nameEl.innerText = `"${pkgName}"`;

    const modalEl = document.getElementById('deletePackageModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function filterEditProposalServices() {
    const searchInput = document.getElementById('editPkgServiceSearch');
    const q = (searchInput?.value || '').toUpperCase().trim();
    const pkgGender = (document.getElementById('edit_pkg_gender_restriction')?.value || 'both').toLowerCase();

    document.querySelectorAll('.edit-prop-service-item').forEach(item => {
        const name = (item.dataset.name || '').toUpperCase();
        const svcGender = (item.dataset.gender || 'both').toLowerCase();

        let genderMatch = false;
        if (pkgGender === 'male') {
            genderMatch = (svcGender === 'male' || svcGender === 'both');
        } else if (pkgGender === 'female') {
            genderMatch = (svcGender === 'female' || svcGender === 'both');
        } else {
            genderMatch = (svcGender === 'both');
        }

        const textMatch = !q || name.includes(q);
        const isVisible = genderMatch && textMatch;

        item.classList.toggle('d-none', !isVisible);

        if (!genderMatch) {
            const chk = item.querySelector('.edit-prop-svc-chk');
            if (chk && chk.checked) {
                chk.checked = false;
            }
        }
    });

    recalculateEditProposalTotal();
}

function recalculateEditProposalTotal() {
    let sum = 0;
    document.querySelectorAll('.edit-prop-svc-chk:checked').forEach(chk => {
        sum += parseFloat(chk.dataset.price || 0);
    });

    const totalEl = document.getElementById('edit_prop_base_total');
    if (totalEl) {
        totalEl.innerText = '₱' + sum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

// =========================================================================
// 3. ORGANIZATION NAME EDIT & DEACTIVATION CONTROLLERS (REP SIDE)
// =========================================================================
function openEditOrgNameModal(orgId, currentName) {
    const form = document.getElementById('editOrgNameForm');
    form.action = `/organization/${orgId}/name`;
    document.getElementById('new_org_name_input').value = currentName;

    const modalEl = document.getElementById('editOrgNameModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openDeactivateOrgModal(orgId, orgName) {
    const form = document.getElementById('deactivateOrgForm');
    form.action = `/organization/${orgId}/deactivate`;

    const modalEl = document.getElementById('deactivateOrgModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

// =========================================================================
// 4. PSGC ADDRESS ENGINE FOR ROSTER MEMBERS
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

async function fetchRosterProvinces(targetProv = null, targetCity = null, targetBrgy = null) {
    const sel = document.getElementById('rm_province');
    if (!sel) return;

    try {
        const res = await fetch(`${psgcApiBase}/provinces.json`);
        const data = await res.json();
        sel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.dataset.code = p.code;
            opt.textContent = p.name;
            sel.appendChild(opt);
        });

        if (targetProv) {
            const matched = findOptionFlexibly(sel, targetProv);
            if (matched) {
                sel.value = matched.value;
                await fetchRosterCities(matched.value, targetCity, targetBrgy);
            }
        }
    } catch(e) { console.error("Provinces fetch error:", e); }
}

async function fetchRosterCities(provName, targetCity = null, targetBrgy = null) {
    const provSel = document.getElementById('rm_province');
    const citySel = document.getElementById('rm_city');
    const brgySel = document.getElementById('rm_barangay');
    if (!citySel || !brgySel) return;

    citySel.disabled = true; 
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';
    brgySel.innerHTML = '<option value="">Select City First</option>';

    if (!provName) return;

    const matchedProv = findOptionFlexibly(provSel, provName);
    const code = matchedProv ? matchedProv.dataset.code : null;

    if (!code) {
        citySel.innerHTML = '<option value="">Select City</option>';
        return;
    }

    try {
        const res = await fetch(`${psgcApiBase}/provinces/${code}/cities-municipalities.json`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.dataset.code = c.code;
            opt.textContent = c.name;
            citySel.appendChild(opt);
        });
        citySel.disabled = false;

        if (targetCity) {
            const matchedCity = findOptionFlexibly(citySel, targetCity);
            if (matchedCity) {
                citySel.value = matchedCity.value;
                await fetchRosterBarangays(matchedCity.value, targetBrgy);
            }
        }
    } catch(e) { console.error("Cities fetch error:", e); }
}

async function fetchRosterBarangays(cityName, targetBrgy = null) {
    const citySel = document.getElementById('rm_city');
    const brgySel = document.getElementById('rm_barangay');
    if (!brgySel) return;

    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    if (!cityName) return;

    const matchedCity = findOptionFlexibly(citySel, cityName);
    const code = matchedCity ? matchedCity.dataset.code : null;

    if (!code) {
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        return;
    }

    try {
        const res = await fetch(`${psgcApiBase}/cities-municipalities/${code}/barangays.json`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.name;
            opt.dataset.code = b.code;
            opt.textContent = b.name;
            brgySel.appendChild(opt);
        });
        brgySel.disabled = false;

        if (targetBrgy) {
            const matchedBrgy = findOptionFlexibly(brgySel, targetBrgy);
            if (matchedBrgy) {
                brgySel.value = matchedBrgy.value;
            }
        }
    } catch(e) { console.error("Barangays fetch error:", e); }
}

// =========================================================================
// 5. ROSTER MEMBER & GROUP WORKFLOW CONTROLLERS
// =========================================================================
function clearRosterModalErrors() {
    const errorAlert = document.getElementById('rm_modal_error_alert');
    if (errorAlert) {
        errorAlert.classList.add('d-none');
        errorAlert.innerHTML = '';
    }
    document.querySelectorAll('#rosterMemberModal .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#rosterMemberModal .invalid-feedback').forEach(el => {
        el.classList.add('d-none');
        el.innerText = '';
    });
}

function openAddRosterMemberModal(defaultGroup = '', orgId = null) {
    clearRosterModalErrors();
    isSubmittingRosterMember = false;

    const btn = document.getElementById('rm_submit_btn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Save Member';
    }

    document.getElementById('rm_id').value = '';
    if (orgId) {
        document.getElementById('rm_org_id').value = orgId;
    }
    document.getElementById('rm_modal_title').innerText = defaultGroup 
        ? `Add Roster Member to ${defaultGroup}` 
        : 'Add Roster Member';
    document.getElementById('rm_group_name').value = defaultGroup || '';
    document.getElementById('rm_first_name').value = '';
    document.getElementById('rm_middle_name').value = '';
    document.getElementById('rm_last_name').value = '';
    document.getElementById('rm_suffix').value = '';
    document.getElementById('rm_sex').value = 'Male';
    document.getElementById('rm_birthdate').value = '';
    document.getElementById('rm_phone_display').value = '';
    document.getElementById('rm_phone').value = '';
    document.getElementById('rm_email').value = '';
    document.getElementById('rm_street').value = '';

    fetchRosterProvinces();

    const modalEl = document.getElementById('rosterMemberModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
}

function editRosterMember(member) {
    if (!member) return;

    clearRosterModalErrors();
    isSubmittingRosterMember = false;

    const btn = document.getElementById('rm_submit_btn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Save Changes';
    }

    document.getElementById('rm_id').value = member.id;
    document.getElementById('rm_modal_title').innerText = `Edit Roster Member (${member.name})`;
    document.getElementById('rm_group_name').value = member.group_name || '';
    document.getElementById('rm_first_name').value = member.first_name || '';
    document.getElementById('rm_middle_name').value = (member.middle_name === 'N/A' ? '' : member.middle_name) || '';
    document.getElementById('rm_last_name').value = member.last_name || '';
    document.getElementById('rm_suffix').value = member.suffix || '';
    document.getElementById('rm_sex').value = member.sex || 'Male';
    
    if (member.birthdate) {
        const bdate = member.birthdate.split('T')[0];
        document.getElementById('rm_birthdate').value = bdate;
    } else {
        document.getElementById('rm_birthdate').value = '';
    }

    let phone = member.phone || '';
    if (phone.startsWith('09')) phone = phone.substring(2);
    else if (phone.startsWith('+639')) phone = phone.substring(4);
    else if (phone.startsWith('639')) phone = phone.substring(3);

    document.getElementById('rm_phone_display').value = phone;
    document.getElementById('rm_phone').value = member.phone || '';
    document.getElementById('rm_email').value = member.email || '';
    document.getElementById('rm_street').value = member.street || '';

    fetchRosterProvinces(member.province, member.city, member.barangay);

    const modalEl = document.getElementById('rosterMemberModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
}

function openDeleteRosterMemberModal(id, name) {
    document.getElementById('del_rm_id').value = id;
    document.getElementById('del_rm_name').innerText = `"${name}"`;

    const modalEl = document.getElementById('deleteRosterMemberModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

async function executeDeleteRosterMember() {
    const id = document.getElementById('del_rm_id')?.value;
    if (!id) return;

    const btn = document.getElementById('confirm_del_rm_btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...';
    }

    try {
        const res = await fetch(`/organization/roster/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Failed to remove member.');
            if (btn) {
                btn.disabled = false;
                btn.innerText = 'Confirm Delete';
            }
        }
    } catch (e) {
        console.error("Delete error:", e);
        alert('Failed to delete member.');
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'Confirm Delete';
        }
    }
}

function openDeleteRosterGroupModal(groupName, memberCount) {
    const input = document.getElementById('del_group_name_input');
    const display = document.getElementById('del_group_name_display');
    const countEl = document.getElementById('del_group_member_count');

    if (input) input.value = groupName;
    if (display) display.innerText = `"${groupName}"`;
    if (countEl) countEl.innerText = memberCount;

    const modalEl = document.getElementById('deleteRosterGroupModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function startNewRosterGroup() {
    const gName = document.getElementById('quick_new_group_name').value.trim();
    if (!gName) return alert('Please enter a group name.');
    
    const groupModalEl = document.getElementById('newGroupModal');
    if (groupModalEl) {
        const inst = bootstrap.Modal.getInstance(groupModalEl);
        if (inst) inst.hide();
    }

    openAddRosterMemberModal(gName.toUpperCase());
}

function openEditGroupModal(currentGroupName) {
    document.getElementById('edit_old_group_name').value = currentGroupName;
    document.getElementById('edit_new_group_name').value = currentGroupName;
    const modalEl = document.getElementById('editGroupModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

// =========================================================================
// SUBMIT ROSTER MEMBER (STRICT ADDRESS VALIDATION & ERROR MAPPING)
// =========================================================================
async function handleRosterMemberSubmit(e) {
    e.preventDefault();
    if (isSubmittingRosterMember) return;

    clearRosterModalErrors();

    const btn = document.getElementById('rm_submit_btn');
    const editId = document.getElementById('rm_id')?.value;
    const groupName = document.getElementById('rm_group_name')?.value.trim().toUpperCase();
    const errorAlert = document.getElementById('rm_modal_error_alert');

    function markInvalid(inputEl, errEl, msg) {
        if (inputEl) inputEl.classList.add('is-invalid');
        if (errEl) {
            errEl.classList.remove('d-none');
            errEl.innerText = msg;
        }
    }

    // 1. Validate First and Last Name
    const fNameInput = document.getElementById('rm_first_name');
    const lNameInput = document.getElementById('rm_last_name');
    const fName = fNameInput?.value.trim();
    const lName = lNameInput?.value.trim();

    if (!fName) {
        markInvalid(fNameInput, document.getElementById('err_rm_first_name'), 'First name is required.');
        fNameInput?.focus();
        return;
    }
    if (!lName) {
        markInvalid(lNameInput, document.getElementById('err_rm_last_name'), 'Last name is required.');
        lNameInput?.focus();
        return;
    }

    // 2. Validate Birthdate (18+)
    const bdayInput = document.getElementById('rm_birthdate');
    const bday = bdayInput?.value;
    if (!bday) {
        markInvalid(bdayInput, document.getElementById('err_rm_birthdate'), 'Birthdate is required.');
        bdayInput?.focus();
        return;
    }
    const dob = new Date(bday);
    const eighteenYearsAgo = new Date();
    eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
    if (dob > eighteenYearsAgo) {
        markInvalid(bdayInput, document.getElementById('err_rm_birthdate'), 'Participant must be at least 18 years of age.');
        bdayInput?.focus();
        return;
    }

    // 3. Validate Contact Number (09XXXXXXXXX)
    const phoneInput = document.getElementById('rm_phone_display');
    const phoneDisp = phoneInput?.value.trim();
    if (!phoneDisp || !/^\d{9}$/.test(phoneDisp)) {
        markInvalid(phoneInput, document.getElementById('err_rm_phone'), 'Contact number is required (11 digits starting with 09).');
        phoneInput?.focus();
        return;
    }
    const phoneFull = '09' + phoneDisp;

    // 4. Validate Email Address
    const emailInput = document.getElementById('rm_email');
    const email = emailInput?.value.trim().toLowerCase();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!email || !emailRegex.test(email)) {
        markInvalid(emailInput, document.getElementById('err_rm_email'), 'A valid email address is required.');
        emailInput?.focus();
        return;
    }

    // 5. Validate Address Fields (Province, City, Barangay, Street STRICTLY REQUIRED)
    const provInput = document.getElementById('rm_province');
    const prov = provInput?.value;
    if (!prov) {
        markInvalid(provInput, document.getElementById('err_rm_province'), 'Province selection is required.');
        provInput?.focus();
        return;
    }

    const cityInput = document.getElementById('rm_city');
    const city = cityInput?.value;
    if (!city) {
        markInvalid(cityInput, document.getElementById('err_rm_city'), 'City / Municipality selection is required.');
        cityInput?.focus();
        return;
    }

    const brgyInput = document.getElementById('rm_barangay');
    const brgy = brgyInput?.value;
    if (!brgy) {
        markInvalid(brgyInput, document.getElementById('err_rm_barangay'), 'Barangay selection is required.');
        brgyInput?.focus();
        return;
    }

    const streetInput = document.getElementById('rm_street');
    const street = streetInput?.value.trim();
    if (!street) {
        markInvalid(streetInput, document.getElementById('err_rm_street'), 'Street / House No. is required.');
        streetInput?.focus();
        return;
    }

    // 6. Intra-Group Duplicate Check against current table rows
    const rows = document.querySelectorAll('#tab-rosters tbody tr');
    let duplicateContactFound = false;
    rows.forEach(tr => {
        const actionBtn = tr.querySelector('button[onclick*="openDeleteRosterMemberModal"]');
        const rowMemberId = actionBtn?.getAttribute('onclick')?.match(/\d+/)?.[0];
        if (editId && rowMemberId && String(rowMemberId) === String(editId)) {
            return;
        }

        const rowText = tr.innerText || '';
        if (rowText.includes(phoneFull)) {
            markInvalid(phoneInput, document.getElementById('err_rm_phone'), `Contact number ${phoneFull} is already assigned in this group.`);
            duplicateContactFound = true;
        }
        if (rowText.toLowerCase().includes(email)) {
            markInvalid(emailInput, document.getElementById('err_rm_email'), `Email address ${email} is already assigned in this group.`);
            duplicateContactFound = true;
        }
    });

    if (duplicateContactFound) {
        if (errorAlert) {
            errorAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1.5"></i> Duplicate participant contact detected in this cohort.';
            errorAlert.classList.remove('d-none');
        }
        return;
    }

    // Lock button state during transfer
    isSubmittingRosterMember = true;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5"></span> Saving...';
    }

    const payload = {
        organization_id: document.getElementById('rm_org_id')?.value || null,
        group_name: groupName,
        first_name: fName,
        middle_name: document.getElementById('rm_middle_name')?.value.trim() || 'N/A',
        last_name: lName,
        suffix: document.getElementById('rm_suffix')?.value.trim() || null,
        sex: document.getElementById('rm_sex')?.value || 'Male',
        birthdate: bday,
        phone: phoneFull,
        email: email,
        province: prov,
        city: city,
        barangay: brgy,
        street: street
    };

    const url = editId ? `/organization/roster/${editId}` : '{{ route("organization.roster.store") }}';
    const method = editId ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (res.ok && data.success) {
            // 1. Hide modal immediately
            const modalEl = document.getElementById('rosterMemberModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }

            // 2. Prepare destination URL
            const orgParam = payload.organization_id ? `&org_id=${payload.organization_id}` : '';
            const targetUrl = new URL(`{{ route('organization.index') }}`, window.location.origin);
            targetUrl.searchParams.set('view', 'patient');
            if (payload.organization_id) targetUrl.searchParams.set('org_id', payload.organization_id);
            if (payload.group_name) targetUrl.searchParams.set('group', payload.group_name);
            targetUrl.hash = '#tab-rosters';

            // 3. Force clean reload to bypass browser same-URL hash anchor stall
            if (window.location.href === targetUrl.toString()) {
                window.location.reload();
            } else {
                window.location.href = targetUrl.toString();
                setTimeout(() => window.location.reload(), 150);
            }
        } else {
            // Restore button
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = editId ? 'Save Changes' : 'Save Member';
            }
            isSubmittingRosterMember = false;

            // Parse and present specific Laravel validation errors
            const errorList = [];

            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    const messages = data.errors[key];
                    const msg = Array.isArray(messages) ? messages[0] : messages;
                    errorList.push(msg);

                    let inputId = 'rm_' + key;
                    let errId = 'err_rm_' + key;
                    if (key === 'phone') inputId = 'rm_phone_display';

                    const inputEl = document.getElementById(inputId);
                    const errEl = document.getElementById(errId);
                    markInvalid(inputEl, errEl, msg);
                });
            } else if (data.message) {
                errorList.push(data.message);
                if (data.message.toLowerCase().includes('contact number') || data.message.toLowerCase().includes('phone')) {
                    markInvalid(document.getElementById('rm_phone_display'), document.getElementById('err_rm_phone'), data.message);
                }
                if (data.message.toLowerCase().includes('email')) {
                    markInvalid(document.getElementById('rm_email'), document.getElementById('err_rm_email'), data.message);
                }
                if (data.message.toLowerCase().includes('province')) {
                    markInvalid(document.getElementById('rm_province'), document.getElementById('err_rm_province'), data.message);
                }
                if (data.message.toLowerCase().includes('city')) {
                    markInvalid(document.getElementById('rm_city'), document.getElementById('err_rm_city'), data.message);
                }
                if (data.message.toLowerCase().includes('barangay')) {
                    markInvalid(document.getElementById('rm_barangay'), document.getElementById('err_rm_barangay'), data.message);
                }
                if (data.message.toLowerCase().includes('street')) {
                    markInvalid(document.getElementById('rm_street'), document.getElementById('err_rm_street'), data.message);
                }
            } else if (data.error) {
                errorList.push(data.error);
            } else {
                errorList.push('Validation failed. Please verify your entries.');
            }

            if (errorAlert) {
                errorAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1.5"></i>' + errorList.join('<br>');
                errorAlert.classList.remove('d-none');
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    } catch(err) {
        console.error("Roster submit error:", err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = editId ? 'Save Changes' : 'Save Member';
        }
        isSubmittingRosterMember = false;

        if (errorAlert) {
            errorAlert.innerHTML = '<i class="bi bi-wifi-off me-1.5"></i> Network error occurred while saving. Please try again.';
            errorAlert.classList.remove('d-none');
        }
    }
}

// =========================================================================
// 6. STAFF APPROVAL CONTROLS (PRICING, DURATION, AND DEACTIVATIONS)
// =========================================================================
let activePackageBasePrice = 0;

function openApprovePkgModal(pkgId, pkgName, orgName, basePrice, defaultEstTime = 15, defaultPrep = '') {
    activePackageBasePrice = parseFloat(basePrice) || 0;
    const form = document.getElementById('approvePackageForm');
    form.action = `/admin/organization/package/approve/${pkgId}`;
    
    document.getElementById('app_pkg_title').innerText = pkgName;
    document.getElementById('app_pkg_org').innerText = orgName;
    document.getElementById('app_pkg_base_price').innerText = '₱' + activePackageBasePrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    document.getElementById('app_discount_pct').value = '';
    document.getElementById('app_final_price').value = activePackageBasePrice.toFixed(2);
    
    const estInput = document.getElementById('app_estimated_time');
    if (estInput) {
        estInput.value = defaultEstTime || 15;
    }
    const prepInput = document.getElementById('app_preparation');
    if (prepInput) {
        prepInput.value = defaultPrep || '';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('approvePackageModal'));
    modal.show();
}

function calculateDiscountByPercent(pct) {
    const p = parseFloat(pct) || 0;
    if (activePackageBasePrice > 0) {
        const discounted = activePackageBasePrice * (1 - (p / 100));
        document.getElementById('app_final_price').value = Math.max(0, discounted).toFixed(2);
    }
}

function calculateDiscountByPrice(price) {
    const finalPrice = parseFloat(price) || 0;
    if (activePackageBasePrice > 0) {
        const pct = ((activePackageBasePrice - finalPrice) / activePackageBasePrice) * 100;
        document.getElementById('app_discount_pct').value = Math.max(0, Math.round(pct));
    }
}

function openDisableOrgModal(orgId, orgName) {
    const form = document.getElementById('disableOrgForm');
    if (!form) return;
    form.action = `/admin/organization/disable/${orgId}`;

    const prompt = document.getElementById('disableOrgPrompt');
    if (prompt) prompt.innerText = `Select justification reason for deactivating recognized organization partnership with "${orgName}":`;

    const select = document.getElementById('disable_org_reason_select');
    if (select) select.value = '';

    const customContainer = document.getElementById('disable_org_custom_reason');
    if (customContainer) {
        customContainer.classList.add('d-none');
        const textarea = customContainer.querySelector('textarea');
        if (textarea) {
            textarea.value = '';
            textarea.required = false;
        }
    }

    const modal = new bootstrap.Modal(document.getElementById('disableOrgModal'));
    modal.show();
}

function openDisablePkgModal(pkgId, pkgName) {
    const form = document.getElementById('disablePkgForm');
    if (!form) return;
    form.action = `/admin/organization/package/disable/${pkgId}`;

    const prompt = document.getElementById('disablePkgPrompt');
    if (prompt) prompt.innerText = `Select justification reason for disabling custom package "${pkgName}":`;

    const select = document.getElementById('disable_pkg_reason_select');
    if (select) select.value = '';

    const customContainer = document.getElementById('disable_pkg_custom_reason');
    if (customContainer) {
        customContainer.classList.add('d-none');
        const textarea = customContainer.querySelector('textarea');
        if (textarea) {
            textarea.value = '';
            textarea.required = false;
        }
    }

    const modal = new bootstrap.Modal(document.getElementById('disablePkgModal'));
    modal.show();
}

// =========================================================================
// 7. DYNAMIC DECLINE REASONS ENGINE
// =========================================================================
const orgDeclineReasons = [
    "Unverifiable Entity Credentials / Incomplete Information",
    "Duplicate or Conflicting Organization Record",
    "Unauthorized Representative / Missing Official Endorsement",
    "Non-Compliance with Clinic Accreditation / Partnership Terms",
    "Capacity Constraint / Unable to Support Requested Cohort Volume",
    "Others"
];

const pkgDeclineReasons = [
    "Incompatible Test Combination / Clinical Guideline Conflict",
    "Reagent Shortage / Test Discontinuation",
    "Pricing Discrepancy / Fee Negotiation Unsuccessful",
    "Duplicate Package Proposal",
    "Preparation Requirements Non-Standard",
    "Others"
];

const nameChangeDeclineReasons = [
    "Mismatched Legal Entity Registration Documents",
    "Conflicting or Duplicate Organization Name",
    "Incomplete Verification / Unauthorized Request",
    "Others"
];

function populateStaffDeclineReasons(reasons) {
    const select = document.getElementById('staff_decline_reason_select');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select Justification --</option>';
    reasons.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r;
        opt.textContent = r;
        select.appendChild(opt);
    });

    const customContainer = document.getElementById('staff_decline_custom_reason');
    if (customContainer) {
        customContainer.classList.add('d-none');
        const textarea = customContainer.querySelector('textarea');
        if (textarea) {
            textarea.value = '';
            textarea.required = false;
        }
    }
}

function openDeclineOrgModal(orgId, orgName) {
    const form = document.getElementById('staffDeclineForm');
    if (!form) return;
    form.action = `/admin/organization/reject/${orgId}`;

    const titleEl = document.getElementById('staffDeclineTitle');
    if (titleEl) {
        titleEl.innerHTML = '<i class="bi bi-x-circle fs-5 me-1"></i><span>Decline Organization Application</span>';
    }

    const promptEl = document.getElementById('staffDeclinePrompt');
    if (promptEl) {
        promptEl.innerText = `Specify the reason for declining partnership with "${orgName}":`;
    }

    populateStaffDeclineReasons(orgDeclineReasons);

    const modal = new bootstrap.Modal(document.getElementById('staffDeclineModal'));
    modal.show();
}

function openDeclinePkgModal(pkgId, pkgName) {
    const form = document.getElementById('staffDeclineForm');
    if (!form) return;
    form.action = `/admin/organization/package/reject/${pkgId}`;

    const titleEl = document.getElementById('staffDeclineTitle');
    if (titleEl) {
        titleEl.innerHTML = '<i class="bi bi-x-circle fs-5 me-1"></i><span>Decline Package Proposal</span>';
    }

    const promptEl = document.getElementById('staffDeclinePrompt');
    if (promptEl) {
        promptEl.innerText = `Specify the reason for declining package proposal '${pkgName}':`;
    }

    populateStaffDeclineReasons(pkgDeclineReasons);

    const modal = new bootstrap.Modal(document.getElementById('staffDeclineModal'));
    modal.show();
}

function openDeclineNameChangeModal(orgId, currentName) {
    const form = document.getElementById('staffDeclineForm');
    if (!form) return;
    form.action = `/admin/organization/reject-name/${orgId}`;

    const titleEl = document.getElementById('staffDeclineTitle');
    if (titleEl) {
        titleEl.innerHTML = '<i class="bi bi-x-circle fs-5 me-1"></i><span>Decline Name Change Request</span>';
    }

    const promptEl = document.getElementById('staffDeclinePrompt');
    if (promptEl) {
        promptEl.innerText = `Specify the reason for declining name change for "${currentName}":`;
    }

    populateStaffDeclineReasons(nameChangeDeclineReasons);

    const modal = new bootstrap.Modal(document.getElementById('staffDeclineModal'));
    modal.show();
}
</script>
@endpush