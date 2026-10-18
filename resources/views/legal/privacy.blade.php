@extends('legal.layout')
@section('title', 'Privacy Policy')

@section('legal-content')
<div class="animate-page text-start">
    {{-- Header --}}
    <div class="mb-4">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2 border border-accent border-opacity-25" style="background-color: rgba(25, 211, 140, 0.08);">
            <i class="bi bi-shield-check text-accent fs-6"></i>
            <span class="text-accent fw-bold uppercase tracking-wider" style="font-size: 0.7rem; letter-spacing: 0.5px;">Data Privacy & Governance</span>
        </div>
        <h2 class="fw-800 text-main mb-1 tracking-tight" style="font-size: 1.85rem;">Clinical Privacy Policy</h2>
        <p class="text-muted small mb-0">Effective Date: {{ now()->format('F d, Y') }} &bull; Version 2.4 (DPA RA 10173 Compliant)</p>
    </div>
    <hr class="my-4" style="border-color: var(--border-color) !important;">

    <div class="lh-lg text-muted" style="color: var(--text-muted) !important;">
        {{-- Section 1: Introduction & Regulatory Commitment --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                1. Commitment to Clinical Privacy & Regulatory Scope
            </h5>
            <p class="mb-3">
                <strong>Medscreen Diagnostic Laboratory</strong> ("Medscreen", "we", "our", or "us"), located at Atis St, General Santos City, South Cotabato, operates in strict compliance with <strong>Republic Act No. 10173</strong>, otherwise known as the <strong>Philippine Data Privacy Act of 2012 (DPA)</strong>, its Implementing Rules and Regulations (IRR), and all applicable administrative directives issued by the <strong>Department of Health (DOH)</strong> and the <strong>National Privacy Commission (NPC)</strong>.
            </p>
            <p class="mb-0">
                This Clinical Privacy Policy governs the collection, processing, storage, transmission, and disposal of Personal Information (PI) and Sensitive Personal Information (SPI) gathered through our clinic desk, official website portal, and mobile clinical clients. We ensure that all patient data is handled with the highest standards of transparency, legitimate purpose, proportionality, and medical confidentiality.
            </p>
        </section>

        {{-- Section 2: Categories of Data Collected --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                2. Categories of Information Collected
            </h5>
            <p class="mb-3">To provide reliable clinical diagnostic services, patient safety tracking, and automated schedule reservations, we collect the following categories of information:</p>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase">A. Patient Identification (1NF Atomic)</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Full legal name (First Name, Middle Name, Last Name, and Name Suffix such as JR, SR, III) aligned with Philippine Statistics Authority (PSA) civil registry standards, Date of Birth, Age, and Biological Sex.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase">B. Contact & Geographic Data (PSGC)</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Standard 11-digit Philippine mobile telephone number (09XXXXXXXXX), verified email address, and residential address normalized to the Philippine Standard Geographic Code (Street/House Number, Barangay, Municipality/City, Province).
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase">C. Clinical Requisition Data</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Physician referrals, doctor prescription notes (PDF/image uploads), selected diagnostic examinations (Blood Chemistry, Hematology, Urinalysis, Fecalysis, Serology, Chest X-Ray, Drug Testing), fasting/preparation records, and scheduled clinical time blocks.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <span class="fw-bold text-main d-block mb-1 small uppercase">D. Diagnostic Findings & Results (SPI)</span>
                        <p class="smaller mb-0" style="font-size: 0.78rem;">
                            Quantitative and qualitative specimen values, reference ranges, laboratory technologist certifications, pathologist evaluations, radiology DICOM/X-ray impressions, medical fitness assessments, and accredited physician license numbers.
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-3 border rounded-3" style="background-color: rgba(255, 193, 7, 0.05); border-color: rgba(255, 193, 7, 0.3) !important;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <strong class="text-main small uppercase">Classification of Sensitive Personal Information</strong>
                </div>
                <p class="smaller mb-0" style="font-size: 0.78rem;">
                    Pursuant to Section 3(l) of RA 10173, all diagnostic results, laboratory values, and medical diagnoses are classified as <strong>Sensitive Personal Information</strong>. They receive heightened technical protection, physical segregation, and strict access gating.
                </p>
            </div>
        </section>

        {{-- Section 3: Legal Basis & Purpose of Processing --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                3. Legal Basis & Purpose of Processing
            </h5>
            <p class="mb-3">We process your personal and medical data under the following lawful criteria:</p>
            <ul class="mb-0 ps-3">
                <li class="mb-2"><strong>Explicit Patient Consent:</strong> Authorized upon user registration, appointment booking submission, and digitization handshake approvals.</li>
                <li class="mb-2"><strong>Medical Treatment & Clinical Necessity (DPA Sec. 13b):</strong> Necessary for medical consultation, laboratory analysis, diagnostic determination, and quality assurance by licensed medical technologists and physicians.</li>
                <li class="mb-2"><strong>Compliance with Regulatory Obligations:</strong> Retention and transmission required under DOH licensing guidelines, Dangerous Drugs Board (DDB) reporting for drug screening, and National Privacy Commission directives.</li>
                <li class="mb-2"><strong>Security, Anti-Fraud & Authentication:</strong> Verifying identity via One-Time Passwords (OTP), confirming cashless transactions, preventing slot scalping, and enforcing appointment lead times.</li>
            </ul>
        </section>

        {{-- Section 4: The Reason-Gate Security Architecture --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                4. The "Reason-Gate" Access Control Protocol
            </h5>
            <p class="mb-3">
                To prevent unauthorized or casual browsing of patient records, Medscreen implements a zero-trust administrative mechanism designated as the <strong>"Reason-Gate" Protocol</strong>:
            </p>
            <div class="p-3 border rounded-3 mb-3" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <ol class="mb-0 ps-3 small">
                    <li class="mb-2">
                        <strong>Mandatory Clinical Justification:</strong> Laboratory staff, medical technologists, and administrators must select or input an auditable business justification (e.g., <em>"Clinical Quality Review"</em>, <em>"Physician Inquiry"</em>, <em>"Patient-Requested Result Correction"</em>) before accessing released patient folders or historical archives.
                    </li>
                    <li class="mb-2">
                        <strong>Immutable Audit Trail:</strong> Every access request, status modification, record revision, and results delivery creates an unalterable entry in our <code class="text-accent">activity_logs</code> repository, capturing the staff ID, timestamp, patient target, action performed, and stated reason.
                    </li>
                    <li class="mb-0">
                        <strong>Batch Coordinator Privacy Shield:</strong> When corporate coordinators schedule bulk employee bookings, they are technologically restricted from inspecting individual, unreleased worksheets of colleagues prior to official clinical verification and release.
                    </li>
                </ol>
            </div>
        </section>

        {{-- Section 5: Encryption & Verification Gateway --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                5. Results Security, Password Encryption & Public Verification
            </h5>
            <p class="mb-3">
                Released diagnostic reports are protected through multi-layered cryptographic safeguards:
            </p>
            <ul class="mb-3 ps-3">
                <li class="mb-2">
                    <strong>Standard Cryptographic Password Encryption:</strong> All PDF clinical results dispatched via email or generated for export are encrypted with an automated alphanumeric key formulated as:
                    <div class="p-2.5 my-2 border rounded font-monospace fw-bold text-main small" style="background-color: rgba(25, 211, 140, 0.05); border-color: var(--border-color) !important;">
                        Pattern: MMDDYYYY + Capitalized Full Name Initials (e.g., "10242005JDC")
                    </div>
                </li>
                <li class="mb-2">
                    <strong>Public QR Code Verification:</strong> Each official clinical document includes a tamper-resistant verification QR code and unique tracking number (Laboratory Case Number, Radiology Case Number, or DOH Drug Test Certificate Number).
                </li>
                <li class="mb-0">
                    <strong>Real-time Document Authentication:</strong> Third-party entities (employers, academic registrars, government agencies) can authenticate legitimacy through our <strong>Public Verification Gateway</strong> without gaining access to unrelated patient history.
                </li>
            </ul>
        </section>

        {{-- Section 6: Data Retention & Automated Purging --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                6. Data Retention, Archiving & Self-Cleaning Policies
            </h5>
            <p class="mb-3">Medscreen adheres to automated, rules-based data retention policies:</p>
            <div class="table-responsive mb-3">
                <table class="table table-bordered small mb-0" style="border-color: var(--border-color) !important; color: var(--text-main);">
                    <thead style="background-color: rgba(0,0,0,0.03);">
                        <tr>
                            <th class="py-2.5 px-3">Data Classification</th>
                            <th class="py-2.5 px-3">Retention Window</th>
                            <th class="py-2.5 px-3">Disposal / Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Unprogressed Draft Bookings</td>
                            <td class="px-3 py-2.5">90 Days</td>
                            <td class="px-3 py-2.5">Automated permanent deletion via daily system sweep.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Released Clinical Diagnostic Records</td>
                            <td class="px-3 py-2.5">10 Years</td>
                            <td class="px-3 py-2.5">Statutory medical retention compliant with DOH and hospital accreditation standards.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">One-Time Password (OTP) Cache</td>
                            <td class="px-3 py-2.5">10 to 15 Minutes</td>
                            <td class="px-3 py-2.5">Instant automated cache expiration and cryptographic destruction.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Deactivated Accounts</td>
                            <td class="px-3 py-2.5">10-Year Holding Period</td>
                            <td class="px-3 py-2.5">Placed in regulatory soft-delete state; user authentication credentials released for re-registration.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Section 7: Minor Dependents & Account Promotion --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                7. Protection of Minors & Dependent Account Promotion
            </h5>
            <p class="mb-3">
                In strict adherence to child protection and medical privacy protocols, family dependent profiles are reserved exclusively for minor children under <strong>18 years of age</strong>.
            </p>
            <p class="mb-0">
                When a registered dependent attains the age of 18, their minor status immediately expires on our platform. Profile editing is locked to preserve clinical provenance, and an <strong>"Account Promotion"</strong> workflow is triggered. This permits the young adult to establish their own independent account. Upon successful verification, all historical diagnostic records, scans, and past bookings are transferred to their independent portal file.
            </p>
        </section>

        {{-- Section 8: Patient Rights under RA 10173 --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                8. Your Statutory Rights as a Data Subject
            </h5>
            <p class="mb-3">Under Chapter III of the Data Privacy Act of 2012, you possess the following statutory rights:</p>
            <ul class="mb-3 ps-3">
                <li class="mb-2"><strong>Right to be Informed:</strong> You have the right to know whether personal data pertaining to you is being processed, including the purpose and scope of such processing.</li>
                <li class="mb-2"><strong>Right to Access:</strong> You may inspect and review your clinical profile, historical test results, and transaction receipts at any time through the secure patient portal.</li>
                <li class="mb-2"><strong>Right to Rectification:</strong> If a booking or profile record contains inaccuracies, our <strong>"Update & Resubmit"</strong> workflow enables correction of erroneous data.</li>
                <li class="mb-2"><strong>Right to Erasure or Blocking:</strong> You may request the voluntary deactivation of your account via the Settings panel, subject to statutory healthcare retention limits.</li>
                <li class="mb-2"><strong>Right to Data Portability:</strong> You may download, export, and securely forward encrypted copies of your diagnostic reports in standard portable electronic formats (PDF).</li>
                <li class="mb-0"><strong>Right to File a Complaint:</strong> If you believe your privacy rights have been violated, you have the right to file a complaint with the <strong>National Privacy Commission (NPC)</strong> at <a href="https://privacy.gov.ph" target="_blank" class="text-accent text-decoration-none">privacy.gov.ph</a>.</li>
            </ul>
        </section>

        {{-- Section 9: DPO Contact Information --}}
        <section class="p-4 rounded-3 border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.1rem;">
                9. Data Protection Officer (DPO) Contact Information
            </h5>
            <p class="small text-muted mb-3">
                For inquiries regarding data privacy compliance, exercise of data subject rights, or security reporting, you may contact our designated Data Protection Officer:
            </p>
            <div class="row g-2 text-muted small">
                <div class="col-12 col-md-6">
                    <div><strong class="text-main">Office:</strong> Medscreen Diagnostic Laboratory</div>
                    <div><strong class="text-main">Location:</strong> Atis St, General Santos City, 9500 South Cotabato</div>
                </div>
                <div class="col-12 col-md-6">
                    <div><strong class="text-main">Email:</strong> medscreen.lab@gmail.com</div>
                    <div><strong class="text-main">Telephone:</strong> (083) 823 8754</div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection