@extends('legal.layout')
@section('title', 'About & FAQ')

@section('legal-content')
<div class="animate-page text-start">
    {{-- Header --}}
    <div class="mb-4">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2 border border-accent border-opacity-25" style="background-color: rgba(25, 211, 140, 0.08);">
            <i class="bi bi-info-circle-fill text-accent fs-6"></i>
            <span class="text-accent fw-bold uppercase tracking-wider" style="font-size: 0.7rem; letter-spacing: 0.5px;">Laboratory Information & Guidance</span>
        </div>
        <h2 class="fw-800 text-main mb-1 tracking-tight" style="font-size: 1.85rem;">About Medscreen & Frequently Asked Questions</h2>
        <p class="text-muted small mb-0">Learn about our diagnostic capabilities, appointment policies, preparation requirements, and digital results system.</p>
    </div>
    <hr class="my-4" style="border-color: var(--border-color) !important;">

    {{-- =========================================================================
         SECTION 1: ABOUT MEDSCREEN DIAGNOSTIC LABORATORY
    ========================================================================= --}}
    <div class="mb-5">
        <h4 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.25rem;">
            <i class="bi bi-hospital text-accent me-2"></i>About Our Facility
        </h4>
        <p class="text-muted lh-lg mb-3">
            <strong>Medscreen Diagnostic Laboratory</strong> is a premier clinical testing facility committed to providing accurate, timely, and accessible diagnostic healthcare services to patients, families, and corporate organizations in South Cotabato and Region XII.
        </p>
        <p class="text-muted lh-lg mb-4">
            Our modernized laboratory portal bridges clinical excellence with patient empowerment. Patients can seamlessly register, manage minor dependents, schedule individual or package laboratory examinations, settle payments through digital channels, and securely receive tamper-evident, password-protected electronic results.
        </p>

        {{-- Core Clinical Capabilities Grid --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-accent" style="width: 36px; height: 36px; background-color: rgba(25, 211, 140, 0.12); border: 1px solid var(--brand-accent);">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                        <span class="fw-bold small text-main uppercase">DOH & PSA Standardized</span>
                    </div>
                    <p class="text-muted smaller mb-0" style="font-size: 0.78rem; line-height: 1.45;">
                        Strict adherence to Department of Health (DOH) clinical standards, Philippine Statistics Authority (PSA) name formats, and National Privacy Commission regulations.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-info" style="width: 36px; height: 36px; background-color: rgba(13, 202, 240, 0.12); border: 1px solid #0dcaf0;">
                            <i class="bi bi-lock-fill fs-5"></i>
                        </div>
                        <span class="fw-bold small text-main uppercase">Reason-Gate Privacy Shield</span>
                    </div>
                    <p class="text-muted smaller mb-0" style="font-size: 0.78rem; line-height: 1.45;">
                        All medical records, diagnostic scans, and clinical modifications are protected by mandatory access justifications logged directly to immutable security audit trails.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-warning" style="width: 36px; height: 36px; background-color: rgba(255, 193, 7, 0.12); border: 1px solid #ffc107;">
                            <i class="bi bi-file-earmark-lock2-fill fs-5"></i>
                        </div>
                        <span class="fw-bold small text-main uppercase">Encrypted PDF Results</span>
                    </div>
                    <p class="text-muted smaller mb-0" style="font-size: 0.78rem; line-height: 1.45;">
                        Released clinical reports are encrypted with biometric-style cryptographic password patterns (<code class="text-accent fw-bold">MMDDYYYY + Initials</code>) for patient security.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-accent" style="width: 36px; height: 36px; background-color: rgba(25, 211, 140, 0.12); border: 1px solid var(--brand-accent);">
                            <i class="bi bi-qr-code-scan fs-5"></i>
                        </div>
                        <span class="fw-bold small text-main uppercase">Public Verification Gateway</span>
                    </div>
                    <p class="text-muted smaller mb-0" style="font-size: 0.78rem; line-height: 1.45;">
                        Every released certificate, laboratory case, radiology report, and drug test carries a cryptographic QR code that allows hospitals, employers, and schools to authenticate legitimacy in real-time.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         SECTION 2: FREQUENTLY ASKED QUESTIONS (ACCORDION)
    ========================================================================= --}}
    <div class="mb-5">
        <h4 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.25rem;">
            <i class="bi bi-question-circle text-accent me-2"></i>Frequently Asked Questions
        </h4>
        <p class="text-muted small mb-4">Click any question below to view detailed procedural guidance and clinical requirements.</p>

        <div class="accordion custom-faq-accordion" id="medscreenFaqAccordion">
            {{-- Category A: Account Registration & Family Profiles --}}
            <div class="mb-2">
                <span class="text-accent fw-bold uppercase tracking-wider small d-block mb-2" style="font-size: 0.72rem;">1. Account Registration & Dependents</span>
            </div>

            {{-- FAQ Item 1 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading1">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="false" aria-controls="faqCollapse1" style="background-color: var(--bg-card); color: var(--text-main);">
                        Who is eligible to register for a personal patient account?
                    </button>
                </h2>
                <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faqHeading1" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        In compliance with Philippine legal and medical consent laws, independent account registration is reserved strictly for individuals aged <strong>18 years or older</strong>. Minor children must be registered under a parent or legal guardian's profile as a <strong>Family Dependent</strong>.
                    </div>
                </div>
            </div>

            {{-- FAQ Item 2 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading2">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2" style="background-color: var(--bg-card); color: var(--text-main);">
                        How do I manage bookings for my children or minor dependents?
                    </button>
                </h2>
                <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Parents and guardians can add dependents under the <strong>Dependent Records</strong> section within their profile. When using the 5-Step Appointment Wizard, select <strong>"FOR A DEPENDENT"</strong> in Step 1 to choose your registered child. When tests are performed, their results will be securely cataloged under your account.
                    </div>
                </div>
            </div>

            {{-- FAQ Item 3 --}}
            <div class="accordion-item border mb-3 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading3">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3" style="background-color: var(--bg-card); color: var(--text-main);">
                        What happens when my dependent turns 18 years old?
                    </button>
                </h2>
                <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Once a dependent reaches age 18, their minor status expires under our clinical regulations. Profile editing is locked and an <strong>"Account Promotion"</strong> action becomes available. You can either generate a shareable registration link or use <strong>"Logout & Register Them Now"</strong>. All historic laboratory results, reports, and previous bookings automatically transfer to their new independent account without data loss.
                    </div>
                </div>
            </div>

            {{-- Category B: Scheduling, Hours & Test Preparation --}}
            <div class="mb-2 mt-4">
                <span class="text-accent fw-bold uppercase tracking-wider small d-block mb-2" style="font-size: 0.72rem;">2. Scheduling, Clinic Hours & Test Preparation</span>
            </div>

            {{-- FAQ Item 4 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading4">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4" style="background-color: var(--bg-card); color: var(--text-main);">
                        What are your clinic operating hours and appointment slots?
                    </button>
                </h2>
                <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Our laboratory operates <strong>Monday through Saturday from 8:00 AM to 5:00 PM</strong> (Sundays and designated holidays closed). 
                        <ul class="mt-2 mb-2 ps-3">
                            <li>Appointment blocks are structured in <strong>1-hour intervals</strong>.</li>
                            <li>A daily clinical sanitation and staff break is scheduled from <strong>12:00 PM to 1:00 PM</strong> (no slots available).</li>
                            <li>Same-day bookings enforce a strict <strong>2-hour lead time</strong> to guarantee specimen preparation and prevent slot contention.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- FAQ Item 5 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading5">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5" style="background-color: var(--bg-card); color: var(--text-main);">
                        What clinical preparations are required prior to my visit?
                    </button>
                </h2>
                <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Preparation instructions depend on the specific test:
                        <ul class="mt-2 mb-2 ps-3">
                            <li><strong>Fasting Blood Sugar (FBS):</strong> Strict 8 to 10 hours fasting (plain water is permitted).</li>
                            <li><strong>Lipid Profile (Cholesterol, Triglycerides, HDL, LDL):</strong> Strict 10 to 12 hours overnight fasting.</li>
                            <li><strong>Urinalysis:</strong> Mid-stream clean-catch urine collected upon arrival or in a sterile container within 1 hour.</li>
                            <li><strong>Fecalysis:</strong> Fresh pea-sized stool sample submitted in a clean, dry container within 2 hours of excretion.</li>
                            <li><strong>Chest X-Ray:</strong> Avoid wearing clothing with metal zippers, buttons, or jewelry around the chest and neck.</li>
                            <li><strong>Drug Testing:</strong> Bring a valid government ID or company ID; sample collection is witnessed under DOH protocols.</li>
                        </ul>
                        Every test in our <strong>Services Catalog</strong> displays exact preparation and specimen guidelines.
                    </div>
                </div>
            </div>

            {{-- FAQ Item 6 --}}
            <div class="accordion-item border mb-3 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading6">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse6" aria-expanded="false" aria-controls="faqCollapse6" style="background-color: var(--bg-card); color: var(--text-main);">
                        Can I book appointments for companies, schools, or large groups?
                    </button>
                </h2>
                <div id="faqCollapse6" class="accordion-collapse collapse" aria-labelledby="faqHeading6" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Yes. Corporate, school, and organization bookings can be processed via our <strong>Bulk Booking Portal</strong> on the web. Batch coordinators can import patient lists via standardized Excel spreadsheets or register up to dozens of candidates under a single company invoice with unified cashless verification.
                    </div>
                </div>
            </div>

            {{-- Category C: Payment, Cancellations & Refunds --}}
            <div class="mb-2 mt-4">
                <span class="text-accent fw-bold uppercase tracking-wider small d-block mb-2" style="font-size: 0.72rem;">3. Payments, Cancellations & Refund Policies</span>
            </div>

            {{-- FAQ Item 7 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading7">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse7" aria-expanded="false" aria-controls="faqCollapse7" style="background-color: var(--bg-card); color: var(--text-main);">
                        What payment methods are supported?
                    </button>
                </h2>
                <div id="faqCollapse7" class="accordion-collapse collapse" aria-labelledby="faqHeading7" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        We support two payment modes:
                        <ol class="mt-2 mb-2 ps-3">
                            <li><strong>Cash on Site:</strong> Pay directly at the admissions counter upon your arrival at the clinic.</li>
                            <li><strong>Online / E-Wallet (Cashless):</strong> Pay via verified merchant QR codes (such as GCash) and upload a screenshot of your transaction receipt for staff verification.</li>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- FAQ Item 8 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading8">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse8" aria-expanded="false" aria-controls="faqCollapse8" style="background-color: var(--bg-card); color: var(--text-main);">
                        What is your cancellation and refund policy?
                    </button>
                </h2>
                <div id="faqCollapse8" class="accordion-collapse collapse" aria-labelledby="faqHeading8" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Our refund policy is governed by the time of cancellation:
                        <ul class="mt-2 mb-2 ps-3">
                            <li><strong>More Than 24 Hours in Advance:</strong> 100% full refund on confirmed cashless payments. Cash bookings are canceled with zero penalty.</li>
                            <li><strong>Within 24 Hours of Appointment:</strong> Subject to a <strong>50% administrative cancellation fee</strong> (50% will be refunded for confirmed cashless payments).</li>
                            <li><strong>No-Shows / Unattended Appointments:</strong> Missed appointments without prior cancellation are non-refundable.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- FAQ Item 9 --}}
            <div class="accordion-item border mb-3 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading9">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse9" aria-expanded="false" aria-controls="faqCollapse9" style="background-color: var(--bg-card); color: var(--text-main);">
                        Can I reschedule or correct an appointment that was returned?
                    </button>
                </h2>
                <div id="faqCollapse9" class="accordion-collapse collapse" aria-labelledby="faqHeading9" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Yes! If our staff flags a booking as <strong>Returned</strong> (e.g., missing doctor referral or invalid receipt), or if an unprogressed appointment expires after 24 hours, click <strong>"Update & Resubmit"</strong> on your appointment card. You can select a new schedule, update your tests, or upload new files. If your payment was already verified as paid, it automatically rolls over with zero additional charges.
                    </div>
                </div>
            </div>

            {{-- Category D: Results, Security & Verification --}}
            <div class="mb-2 mt-4">
                <span class="text-accent fw-bold uppercase tracking-wider small d-block mb-2" style="font-size: 0.72rem;">4. Diagnostic Results, Encryption & Verification</span>
            </div>

            {{-- FAQ Item 10 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading10">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse10" aria-expanded="false" aria-controls="faqCollapse10" style="background-color: var(--bg-card); color: var(--text-main);">
                        How do I open and decrypt my released medical results?
                    </button>
                </h2>
                <div id="faqCollapse10" class="accordion-collapse collapse" aria-labelledby="faqHeading10" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Once clinical analysis is signed off and released, results can be viewed in your portal and are delivered to your email as encrypted PDF files.
                        <div class="p-3 my-2 border rounded" style="background-color: rgba(25, 211, 140, 0.05); border-color: var(--border-color) !important;">
                            <strong class="text-accent uppercase small d-block mb-1">Decryption Password Formula:</strong>
                            <span class="font-monospace fw-bold text-main">MMDDYYYY + Capitalized Initials</span><br>
                            <span class="text-muted smaller">Example: For a patient born on October 24, 2005 named Juan Dela Cruz, the password is: <strong class="text-accent">10242005JDC</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FAQ Item 11 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading11">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse11" aria-expanded="false" aria-controls="faqCollapse11" style="background-color: var(--bg-card); color: var(--text-main);">
                        How does the Public Verification Gateway work?
                    </button>
                </h2>
                <div id="faqCollapse11" class="accordion-collapse collapse" aria-labelledby="faqHeading11" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Any third party (such as a visa office, school admissions officer, or employer) can scan the QR code printed on the clinical report or enter the Case / Certificate Number into our <a href="{{ route('result.verify-search') }}" class="text-accent fw-bold text-decoration-none">Verify Result</a> tool to confirm authenticity directly against our secure database.
                    </div>
                </div>
            </div>

            {{-- FAQ Item 12 --}}
            <div class="accordion-item border mb-2 rounded-3 overflow-hidden" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <h2 class="accordion-header" id="faqHeading12">
                    <button class="accordion-button collapsed py-3 px-4 fw-bold small text-main" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse12" aria-expanded="false" aria-controls="faqCollapse12" style="background-color: var(--bg-card); color: var(--text-main);">
                        Can I request digital copies of my historical physical lab records?
                    </button>
                </h2>
                <div id="faqCollapse12" class="accordion-collapse collapse" aria-labelledby="faqHeading12" data-bs-parent="#medscreenFaqAccordion">
                    <div class="accordion-body px-4 pb-4 pt-1 text-muted small lh-lg" style="color: var(--text-muted) !important;">
                        Yes. Under the <strong>Medical History</strong> page, patients can initiate a <strong>"Request Data Import"</strong> handshake. Our laboratory records technicians will pull your legacy physical file, scan the records, attach the certificate number, and digitize them into your lifetime account archive under the Philippine Data Privacy Act.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         SECTION 3: CLINIC DIRECTORY & CONTACT CARD
    ========================================================= --}}
    <div class="p-4 rounded-3 border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
        <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.1rem;">
            <i class="bi bi-geo-alt-fill text-accent me-2"></i>Contact & Clinic Directory
        </h5>
        <div class="row g-3 text-muted small">
            <div class="col-12 col-md-6">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <i class="bi bi-building text-accent mt-0.5"></i>
                    <div>
                        <strong class="text-main d-block">Physical Address:</strong>
                        <span>Atis St, General Santos City (Dadiangas), 9500 South Cotabato</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-telephone text-accent"></i>
                    <div>
                        <strong class="text-main">Telephone:</strong> (083) 823 8754
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-envelope text-accent"></i>
                    <div>
                        <strong class="text-main">Support Email:</strong> medscreen.lab@gmail.com
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-clock text-accent"></i>
                    <div>
                        <strong class="text-main">Hours:</strong> Mon - Sat: 8:00 AM - 5:00 PM (Sun: Closed)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.custom-faq-accordion .accordion-button:not(.collapsed) {
    background-color: rgba(25, 211, 140, 0.08) !important;
    color: var(--brand-accent) !important;
    box-shadow: none !important;
}

.custom-faq-accordion .accordion-button:focus {
    box-shadow: none !important;
    border-color: var(--brand-accent) !important;
}

.custom-faq-accordion .accordion-button::after {
    filter: invert(0.5);
}

[data-bs-theme="dark"] .custom-faq-accordion .accordion-button::after {
    filter: invert(1);
}
</style>
@endpush