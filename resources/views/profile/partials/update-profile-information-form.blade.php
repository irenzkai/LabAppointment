<div class="card p-4 p-md-5 border-secondary shadow-lg animate-page">
    <h5 class="text-main fw-bold mb-4 border-bottom border-secondary border-opacity-25 pb-2 uppercase" style="letter-spacing: 1px;">Personal Information</h5>

    {{-- Active Address on File --}}
    <div class="alert alert-clinical border-secondary bg-dark bg-opacity-10 py-3 mb-4 text-start">
        <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Registered Address on File:</div>
        <div class="text-main small">{{ $user->address }}</div>
    </div>

    <form method="post" action="{{ route('profile.update') }}" onsubmit="compileProfileAddress()">
        @csrf
        @method('patch')

        <input type="hidden" name="address" id="profile_address_hidden" value="{{ $user->address }}">

        <div class="row g-3 text-start">
            {{-- Name Separation --}}
            <div class="col-md-4">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">First Name</label>
                <input type="text" name="first_name" class="form-control uppercase" value="{{ old('first_name', $user->first_name) }}" required>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="smaller text-secondary fw-bold mb-0 uppercase">Middle Name</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="profile_no_mn" onclick="toggleProfileMN(this)" {{ $user->middle_name == 'N/A' ? 'checked' : '' }}>
                        <label class="smaller text-muted" style="font-size: 0.65rem;" for="profile_no_mn">None</label>
                    </div>
                </div>
                <input type="text" name="middle_name" id="profile_middle_name" class="form-control uppercase" value="{{ old('middle_name', $user->middle_name) }}" {{ $user->middle_name == 'N/A' ? 'readonly' : '' }}>
            </div>
            <div class="col-md-4">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Last Name</label>
                <input type="text" name="last_name" class="form-control uppercase" value="{{ old('last_name', $user->last_name) }}" required>
            </div>

            {{-- Contact Information --}}
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Email Address</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
            </div>

            {{-- Demographics --}}
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                <input type="date" name="birthdate" class="form-control" value="{{ $user->birthdate ? $user->birthdate->format('Y-m-d') : '' }}" required max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                <select name="sex" class="form-select" required>
                    <option value="Male" {{ old('sex', $user->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('sex', $user->sex) == 'Female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>

            {{-- Interactive Address Selection (API Driven) --}}
            <div class="col-12 border-top border-secondary border-opacity-10 pt-3 mt-4">
                <h6 class="text-accent smaller fw-bold mb-3 uppercase">Update Home Address</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Province</label>
                        <select id="addr_province" name="province" class="form-select" onchange="fetchCities(this.value)" required>
                            <option value="">Loading Provinces...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                        <select id="addr_city" name="city" class="form-select" onchange="fetchBarangays(this.value)" disabled required>
                            <option value="">Select Province First</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Barangay</label>
                        <select id="addr_brgy" name="barangay" class="form-select" disabled required>
                            <option value="">Select City First</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                        <input type="text" id="addr_street" name="street" class="form-control uppercase" value="{{ old('street', $user->street) }}" placeholder="House/Lot/Block/Street" required>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn-custom btn-accent mt-4 px-4 uppercase fw-bold">SAVE DETAILS</button>
    </form>
</div>

@push('scripts')
<script>
const savedProvince = "{{ old('province', $user->province) }}";
const savedCity = "{{ old('city', $user->city) }}";
const savedBarangay = "{{ old('barangay', $user->barangay) }}";

// --- SEPARATE NAME MIDDLE-NAME SWITCH ---
function toggleProfileMN(checkbox) {
    const input = document.getElementById('profile_middle_name');
    if (checkbox.checked) {
        input.value = "N/A";
        input.readOnly = true;
        input.classList.add('opacity-50');
    } else {
        input.value = "";
        input.readOnly = false;
        input.classList.remove('opacity-50');
    }
}

// --- PSGC ADDRESS API ---
const apiBaseUrl = "https://psgc.gitlab.io/api";

async function fetchProvinces() {
    try {
        const res = await fetch(`${apiBaseUrl}/provinces/`);
        const data = await res.json();
        const sel = document.getElementById('addr_province');
        sel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });
    } catch (e) { 
        console.error("Province API Error"); 
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
        const res = await fetch(`${apiBaseUrl}/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;
    } catch (e) { 
        console.error("City API Error"); 
    }
}

async function fetchBarangays(cityCode) {
    if (!cityCode) return;
    const brgySel = document.getElementById('addr_brgy');
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    try {
        const res = await fetch(`${apiBaseUrl}/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;
    } catch (e) { 
        console.error("Barangay API Error"); 
    }
}

/**
 * Compiles selected PSGC inputs to a single uppercase address string and rewrites 
 * code keys to text literals prior to submission.
 */
function compileProfileAddress() {
    const street = document.getElementById('addr_street').value.trim();
    const brgy = document.getElementById('addr_brgy');
    const city = document.getElementById('addr_city');
    const prov = document.getElementById('addr_province');
    
    const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
    const cityName = city.options[city.selectedIndex]?.text || '';
    const provName = prov.options[prov.selectedIndex]?.text || '';

    if (street && brgyName && cityName && provName) {
        // Rewrite Option codes to names so form submissions send full text names
        prov.options[prov.selectedIndex].value = provName;
        city.options[city.selectedIndex].value = cityName;
        brgy.options[brgy.selectedIndex].value = brgyName;

        document.getElementById('profile_address_hidden').value = `${street}, BRGY. ${brgyName}, ${cityName}, ${provName}`.toUpperCase();
    }
}

async function initializeAddress() {
    await fetchProvinces();
    if (savedProvince) {
        const provSel = document.getElementById('addr_province');
        let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === savedProvince.toUpperCase());
        if (provOpt) {
            provSel.value = provOpt.value;
            await fetchCities(provSel.value);
            
            const citySel = document.getElementById('addr_city');
            let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === savedCity.toUpperCase());
            if (cityOpt) {
                citySel.value = cityOpt.value;
                await fetchBarangays(citySel.value);
                
                const brgySel = document.getElementById('addr_brgy');
                let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === savedBarangay.toUpperCase());
                if (brgyOpt) {
                    brgySel.value = brgyOpt.value;
                }
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await initializeAddress();
    
    // Check if middle name is N/A to trigger standard opacity adjustments on page load
    const mnCheck = document.getElementById('profile_no_mn');
    if (mnCheck && mnCheck.checked) {
        document.getElementById('profile_middle_name').classList.add('opacity-50');
    }
});
</script>
@endpush