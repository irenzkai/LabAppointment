@extends('legal.layout')
@section('title', 'Terms of Service')

@section('legal-content')
<div class="animate-page text-start">
    {{-- Header --}}
    <div class="mb-4">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2 border border-accent border-opacity-25" style="background-color: rgba(25, 211, 140, 0.08);">
            <i class="bi bi-file-earmark-text text-accent fs-6"></i>
            <span class="text-accent fw-bold uppercase tracking-wider" style="font-size: 0.7rem; letter-spacing: 0.5px;">Patient & Service Agreement</span>
        </div>
        <h2 class="fw-800 text-main mb-1 tracking-tight" style="font-size: 1.85rem;">Terms of Service</h2>
        <p class="text-muted small mb-0">Effective Date: {{ now()->format('F d, Y') }} &bull; Version 2.4 (Philippine Healthcare Standards)</p>
    </div>
    <hr class="my-4" style="border-color: var(--border-color) !important;">

    <div class="lh-lg text-muted" style="color: var(--text-muted) !important;">
        {{-- Section 1: Acceptance & Scope --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                1. Acceptance of Terms & Clinical Scope
            </h5>
            <p class="mb-3">
                These Terms of Service ("Terms") constitute a legally binding agreement between you ("Patient", "User", or "Coordinator") and <strong>Medscreen Diagnostic Laboratory</strong> ("Medscreen", "we", "us", or "our"), regarding your use of our physical facilities, web portal, and mobile clinical applications.
            </p>
            <p class="mb-0">
                By registering an account, booking a diagnostic appointment, submitting clinical specimens, or downloading electronic medical reports, you acknowledge that you have read, understood, and agreed to be bound by these Terms, along with our <a href="{{ route('legal.privacy') }}" class="text-accent fw-bold text-decoration-none">Clinical Privacy Policy</a> and <a href="{{ route('legal.dpa') }}" class="text-accent fw-bold text-decoration-none">Data Privacy Act Disclosures</a>.
            </p>
        </section>

        {{-- Section 2: Patient Eligibility & Dependents --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                2. User Accounts, Eligibility & Minor Dependents
            </h5>
            <ul class="mb-3 ps-3">
                <li class="mb-2">
                    <strong>Adult Eligibility (18+):</strong> Direct registration of an independent patient profile is strictly reserved for individuals aged <strong>18 years or older</strong>. Users are required to provide accurate, PSA-standard identification data (First, Middle, Last Name, Suffix, Birthdate, and Sex).
                </li>
                <li class="mb-2">
                    <strong>Family Dependents:</strong> Parents and legal guardians may register minor children under their primary account. Dependents must strictly be under 18 years of age. All appointment bookings, test notifications, and billing settlements for dependents are managed through the parent's portal.
                </li>
                <li class="mb-0">
                    <strong>Age 18 Promotion Protocol:</strong> When a dependent reaches 18 years of age, their dependent profile is locked to preserve legal consent integrity. The parent or guardian may trigger an <strong>"Account Promotion"</strong> to generate a secure registration link, allowing the young adult to establish their own independent account. All previous diagnostic histories and scans will seamlessly migrate to the new independent account.
                </li>
            </ul>
        </section>

        {{-- Section 3: Booking, Operating Rules & Lead Time --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                3. Appointment Scheduling, Operating Hours & Quotas
            </h5>
            <p class="mb-3">To ensure sanitary laboratory conditions, quality specimen processing, and minimal wait times, our booking system operates under strict clinical rules:</p>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase"><i class="bi bi-clock me-1 text-accent"></i> Operating Schedule</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Clinic hours are Monday through Saturday, 8:00 AM to 5:00 PM. Sundays and officially declared national holidays are closed. A daily clinical break is observed from 12:00 PM to 1:00 PM (no appointment slots).
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase"><i class="bi bi-hourglass-split me-1 text-accent"></i> 2-Hour Lead Time</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Same-day appointment reservations enforce a mandatory 2-hour minimum lead time prior to the chosen time slot to allow our phlebotomy and imaging team to prepare requisition batches.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase"><i class="bi bi-people me-1 text-accent"></i> Capacity Limits</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Each 1-hour appointment block has an enforced patient capacity quota. Once a block is fully booked, the slot automatically locks to prevent overcrowding.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase"><i class="bi bi-exclamation-circle me-1 text-accent"></i> 24-Hour Expiration Rule</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Appointments scheduled more than 24 hours in the past that have not progressed to clinical sampling ('tested', 'retest', 'encoded') expire automatically.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 4: Clinical Preparation & Specimen Compliance --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                4. Patient Clinical Preparation & Specimen Integrity
            </h5>
            <p class="mb-3">
                Accurate diagnostic results depend upon patient compliance with mandatory pre-test guidelines. By booking a test, you agree to observe all instructions displayed in our Service Catalog:
            </p>
            <ul class="mb-3 ps-3">
                <li class="mb-2"><strong>Fasting Guidelines:</strong> Strict fasting (no food or caloric drinks; plain water allowed) must be observed for Fasting Blood Sugar (8–10 hours) and Lipid Profiles (10–12 hours). Failure to fast may produce clinically distorted findings requiring paid recollects.</li>
                <li class="mb-2"><strong>Specimen Cleanliness:</strong> Urine and stool samples must be collected in sterile laboratory containers and submitted within the specified clinical timeline (1 hour for urine; 2 hours for stool).</li>
                <li class="mb-2"><strong>Prescription & Referral Verification:</strong> For tests requiring an attending physician's prescription or formal medical referral, clear and readable copies must be uploaded during booking or presented during on-site admission.</li>
                <li class="mb-0"><strong>Retesting Protocol:</strong> If a collected sample is hemolyzed, clotted, insufficient in volume, or compromised, our laboratory will flag the appointment as <strong>"Retest Required"</strong>. Patients must return for re-sampling at no additional charge for the laboratory procedure.</li>
            </ul>
        </section>

        {{-- Section 5: Cancellation & Refund Policy --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                5. Cancellation, Administrative Fees & Refund Policy
            </h5>
            <p class="mb-3">
                Because clinic time slots, specialized reagents, and medical technologist shifts are allocated in advance, appointments canceled by patients are subject to our dynamic 24-hour policy:
            </p>

            <div class="table-responsive mb-4">
                <table class="table table-bordered small mb-0" style="border-color: var(--border-color) !important; color: var(--text-main);">
                    <thead style="background-color: rgba(0,0,0,0.03);">
                        <tr>
                            <th class="py-2.5 px-3">Cancellation Timing</th>
                            <th class="py-2.5 px-3">Cash on Site Booking</th>
                            <th class="py-2.5 px-3">Cashless Booking (GCash / Maya)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">More than 24 hours in advance</td>
                            <td class="px-3 py-2.5 text-success fw-bold">Free cancellation (No charge)</td>
                            <td class="px-3 py-2.5 text-success fw-bold">100% Full Refund</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Within 24 hours of appointment</td>
                            <td class="px-3 py-2.5 text-warning fw-bold">Canceled with Notice</td>
                            <td class="px-3 py-2.5 text-warning fw-bold">50% Administrative Cancellation Fee (50% Refund)</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Missed Slot / No-Show</td>
                            <td class="px-3 py-2.5 text-danger fw-bold">Marked Expired / Forfeited</td>
                            <td class="px-3 py-2.5 text-danger fw-bold">0% Refund (Non-refundable)</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Approved Appointments</td>
                            <td colspan="2" class="px-3 py-2.5 text-muted fst-italic">
                                Online self-cancellation is locked. Patients must contact clinic reception directly to request rescheduling or authorized cancellation.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="small mb-0 text-muted">
                <strong>Refund Processing:</strong> Approved cashless refunds are processed by our administrative staff back to the original funding account within <strong>3 to 5 business days</strong>, following payment verification.
            </p>
        </section>

        {{-- Section 6: Payment Methods & Receipt Verification --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                6. Payment Settlement & Receipt Verification
            </h5>
            <ul class="mb-0 ps-3">
                <li class="mb-2"><strong>Cash on Site:</strong> Payable at our reception cashier upon arrival. Official paper receipts (BIR compliant) are issued immediately.</li>
                <li class="mb-2"><strong>Cashless E-Wallet Transactions:</strong> Payments made via merchant QR codes (GCash, Maya) require uploading an authentic, legible transaction screenshot showing the exact reference number, amount, and timestamp.</li>
                <li class="mb-2"><strong>Invalid Receipts:</strong> Fraudulent, tampered, blurry, or duplicate payment receipts will result in the payment status being flagged as <strong>"Invalid"</strong>, voiding refund eligibility and pausing booking approval.</li>
                <li class="mb-0"><strong>Payment Rollover Guarantee:</strong> If a booking with confirmed payment is returned for schedule adjustment or patient correction, the full paid amount is automatically rolled over to the resubmitted appointment with zero penalty.</li>
            </ul>
        </section>

        {{-- Section 7: Corporate & Bulk Booking Obligations --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                7. Corporate & Institutional Bulk Bookings
            </h5>
            <p class="mb-3">Organizations, companies, and academic institutions using our Bulk Booking Portal agree to the following additional covenants:</p>
            <ul class="mb-0 ps-3">
                <li class="mb-2"><strong>Authorized Representation:</strong> The batch coordinator warrants that they possess legal authorization and explicit patient consent to register candidate demographics and schedule clinical testing.</li>
                <li class="mb-2"><strong>Template Integrity:</strong> Bulk roster uploads must utilize our official XLSX template without altering column headers, ensuring accurate PSGC geographic coding and 11-digit mobile formatting.</li>
                <li class="mb-0"><strong>Privacy Shield Compliance:</strong> In accordance with employee confidentiality standards, batch coordinators can track aggregate status and verify invoices, but cannot view individual, unreleased raw clinical worksheets before medical release.</li>
            </ul>
        </section>

        {{-- Section 8: Electronic Results & Disclaimer --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                8. Electronic Results, Encryption & Clinical Disclaimer
            </h5>
            <p class="mb-3">
                Electronic clinical results released through the Medscreen portal are protected by password encryption (<code class="text-accent fw-bold">MMDDYYYY + Initials</code>) and signed with digital credentials of licensed Medical Technologists, Radiologists, and Pathologists.
            </p>
            <div class="alert alert-clinical border-info bg-info bg-opacity-10 p-3 rounded-3 mb-0 smaller text-muted" style="border-left: 4px solid #0dcaf0 !important;">
                <strong class="text-main d-block mb-1 uppercase">Clinical Diagnostic Notice</strong>
                Laboratory and diagnostic findings are intended for interpretation by a licensed physician in conjunction with the patient's comprehensive medical history. Test results do not constitute a standalone medical prescription or definitive diagnosis. Patients should consult their primary physician for clinical evaluation.
            </div>
        </section>

        {{-- Section 9: Modifications & Inquiries --}}
        <section class="p-4 rounded-3 border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.1rem;">
                9. Revisions & Support Directory
            </h5>
            <p class="small text-muted mb-3">
                Medscreen reserves the right to modify these Terms to reflect clinical, legal, or technical changes. Continued use of our services constitutes acceptance of revised terms. For concerns or clarifications, contact our administrative office:
            </p>
            <div class="row g-2 text-muted small">
                <div class="col-12 col-md-6">
                    <div><strong class="text-main">Clinic Desk:</strong> Medscreen Diagnostic Laboratory</div>
                    <div><strong class="text-main">Address:</strong> Atis St, General Santos City, 9500 South Cotabato</div>
                </div>
                <div class="col-12 col-md-6">
                    <div><strong class="text-main">Support Email:</strong> medscreen.lab@gmail.com</div>
                    <div><strong class="text-main">Phone:</strong> (083) 823 8754</div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection