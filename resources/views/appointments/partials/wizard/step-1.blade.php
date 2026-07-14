<!-- PAGE 1: TARGET SELECTION -->
<div class="wiz-section text-start" id="page-1">
    <div class="mb-4">
        <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 1: Patient Selection</h3>
        <p class="text-secondary small">Who are you booking this appointment for?</p>
    </div>

    <div class="row g-3">
        {{-- Option 1: Myself --}}
        <div class="col-12">
            <input type="radio" class="btn-check" name="target_type" id="target_self" value="self" checked onchange="handleTargetChange()">
            <label class="btn btn-outline-accent w-100 p-4 text-start hover-bg" for="target_self">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold fs-5 uppercase">For Myself</div>
                        <div class="smaller text-secondary">Use my registered profile information for this record.</div>
                    </div>
                    <i class="bi bi-person-circle fs-2 text-accent"></i>
                </div>
            </label>
        </div>

        {{-- Option 2: Dependent (Conditional) --}}
        @if(Auth::user()->dependents->count() > 0)
        <div class="col-12">
            <input type="radio" class="btn-check" name="target_type" id="target_dep" value="dependent" onchange="handleTargetChange()">
            <label class="btn btn-outline-accent w-100 p-4 text-start hover-bg" for="target_dep">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold fs-5 uppercase">For a Dependent</div>
                        <div class="smaller text-secondary">Select from your registered family members or children.</div>
                    </div>
                    <i class="bi bi-people-fill fs-2 text-accent"></i>
                </div>
            </label>

            <div id="dep_selector_div" class="mt-3 d-none animate-fade-in">
                <select name="dependent_id" id="dependent_id" class="form-select py-3 shadow-none fw-bold" onchange="handleTargetChange()">
                    <option value="">-- SELECT FAMILY MEMBER --</option>
                    @foreach(Auth::user()->dependents as $dep)
                    <option value="{{ $dep->id }}" 
                        data-first_name="{{ $dep->first_name }}"
                        data-middle_name="{{ $dep->middle_name }}"
                        data-last_name="{{ $dep->last_name }}"
                        data-sex="{{ $dep->sex }}" 
                        data-bday="{{ $dep->birthdate->format('Y-m-d') }}"
                        data-street="{{ $dep->street }}"
                        data-barangay="{{ $dep->barangay }}"
                        data-city="{{ $dep->city }}"
                        data-province="{{ $dep->province }}">
                        {{ strtoupper($dep->name) }} ({{ strtoupper($dep->relationship) }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif

        {{-- Option 3: Bulk / Organization --}}
        <div class="col-12">
            <input type="radio" class="btn-check" name="target_type" id="target_bulk" value="bulk" onchange="handleTargetChange()">
            <label class="btn btn-outline-accent w-100 p-4 text-start hover-bg" for="target_bulk">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold fs-5 uppercase">Bulk / Organization</div>
                        <div class="smaller text-secondary">Book for a company, group, or multiple unnamed patients.</div>
                    </div>
                    <i class="bi bi-buildings fs-2 text-accent"></i>
                </div>
            </label>
        </div>
    </div>

    {{-- Navigation CTA --}}
    <div class="mt-5">
        <button type="button" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase shadow-sm" onclick="proceedFromStep1()">
            PROCEED TO DETAILS <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</div>