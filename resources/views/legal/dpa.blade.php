@extends('legal.layout')
@section('title', 'Data Privacy Act Compliance')

@section('legal-content')
<div class="animate-page text-start">
    {{-- Header --}}
    <div class="mb-4">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2 border border-accent border-opacity-25" style="background-color: rgba(25, 211, 140, 0.08);">
            <i class="bi bi-shield-shaded text-accent fs-6"></i>
            <span class="text-accent fw-bold uppercase tracking-wider" style="font-size: 0.7rem; letter-spacing: 0.5px;">National Privacy Commission Compliance</span>
        </div>
        <h2 class="fw-800 text-main mb-1 tracking-tight" style="font-size: 1.85rem;">Data Privacy Act (RA 10173) Compliance</h2>
        <p class="text-muted small mb-0">Statutory Framework &bull; Republic of the Philippines &bull; National Privacy Commission (NPC)</p>
    </div>
    <hr class="my-4" style="border-color: var(--border-color) !important;">

    <div class="lh-lg text-muted" style="color: var(--text-muted) !important;">
        {{-- Section 1: Statutory Framework --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                1. Statutory Framework & Institutional Role
            </h5>
            <p class="mb-3">
                <strong>Medscreen Diagnostic Laboratory</strong> operates in full compliance with <strong>Republic Act No. 10173</strong>, otherwise known as the <strong>Data Privacy Act of 2012 (DPA)</strong>, its Implementing Rules and Regulations (IRR), and all relevant issuances, advisories, and circulars of the <strong>National Privacy Commission (NPC)</strong> of the Philippines.
            </p>
            <p class="mb-0">
                Under the DPA, Medscreen acts as a <strong>Personal Information Controller (PIC)</strong> with respect to the personal data of patients, family dependents, corporate clients, and healthcare staff. We recognize our fundamental obligation to uphold the constitutional right to information privacy while delivering diagnostic excellence.
            </p>
        </section>

        {{-- Section 2: Cardinal Principles of Data Privacy --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                2. Implementation of Cardinal Data Privacy Principles
            </h5>
            <p class="mb-3">All processing of personal data within the Medscreen ecosystem strictly adheres to the three foundational principles set forth in Section 11 of the DPA:</p>

            <div class="row g-3 mb-0">
                <div class="col-12 col-md-4">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-accent">
                            <i class="bi bi-eye-fill fs-5"></i>
                            <span class="fw-bold text-main small uppercase">Transparency</span>
                        </div>
                        <p class="smaller mb-0" style="font-size: 0.78rem; line-height: 1.45;">
                            Patients are explicitly informed prior to data collection regarding the specific nature, clinical scope, and retention periods of their information through clear disclosures and real-time consent banners.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-info">
                            <i class="bi bi-briefcase-fill fs-5"></i>
                            <span class="fw-bold text-main small uppercase">Legitimate Purpose</span>
                        </div>
                        <p class="smaller mb-0" style="font-size: 0.78rem; line-height: 1.45;">
                            Data is processed solely for authorized clinical healthcare delivery, laboratory analysis, medical fitness certification, official accreditation reporting, and regulatory auditing.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                            <i class="bi bi-rulers fs-5"></i>
                            <span class="fw-bold text-main small uppercase">Proportionality</span>
                        </div>
                        <p class="smaller mb-0" style="font-size: 0.78rem; line-height: 1.45;">
                            Data collection is strictly minimized to what is necessary. We capture atomic PSA-compliant demographics and 3NF PSGC addresses without gathering irrelevant personal background details.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 3: Sensitive Personal Information in Healthcare --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                3. Lawful Processing of Sensitive Personal Information (SPI)
            </h5>
            <p class="mb-3">
                Pursuant to Section 3(l) of RA 10173, individual health records, laboratory evaluations, medical diagnoses, diagnostic scans, and clinical observations are formally classified as <strong>Sensitive Personal Information (SPI)</strong>.
            </p>
            <div class="p-3 border rounded-3 mb-3" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <strong class="text-main small d-block mb-2 uppercase">Statutory Basis Under Section 13 of the DPA:</strong>
                <p class="smaller mb-2" style="font-size: 0.82rem;">
                    Medscreen processes medical SPI pursuant to <strong>Section 13(b) of RA 10173</strong>, which authorizes processing that <em>"is necessary to protect the life and health of the data subject or another person"</em> or is undertaken for <em>"medical treatment by a physician or health facility"</em>.
                </p>
                <p class="smaller mb-0 text-muted" style="font-size: 0.8rem;">
                    Every examination is conducted, encoded, verified, and signed by certified Medical Technologists, Radiologists, and Pathologists bound by professional statutory codes of medical confidentiality.
                </p>
            </div>
        </section>

        {{-- Section 4: Security Measures (Technical, Organizational, Physical) --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                4. Tripartite Security Measures (NPC Circular 16-01)
            </h5>
            <p class="mb-3">
                In compliance with the security guidelines of the National Privacy Commission, Medscreen maintains a comprehensive security program comprising technical, organizational, and physical safeguards:
            </p>

            <div class="row g-3 mb-3">
                {{-- Technical Security --}}
                <div class="col-12 col-md-4">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <h6 class="text-accent fw-bold small uppercase mb-2">Technical Safeguards</h6>
                        <ul class="ps-3 smaller mb-0 text-muted" style="font-size: 0.78rem;">
                            <li class="mb-1.5">End-to-end TLS 1.3 encryption across all web and mobile endpoints.</li>
                            <li class="mb-1.5">SHA-256 hashed password storage with strict complexity enforcement.</li>
                            <li class="mb-1.5">Automated PDF password encryption for all exported clinical results (<code class="text-accent">MMDDYYYY + Initials</code>).</li>
                            <li class="mb-1.5">Automated rate-limiting on authentication and verification OTP dispatch.</li>
                            <li class="mb-0">Expiring bearer token sessions managed via Laravel Sanctum.</li>
                        </ul>
                    </div>
                </div>

                {{-- Organizational Security --}}
                <div class="col-12 col-md-4">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <h6 class="text-info fw-bold small uppercase mb-2">Organizational Safeguards</h6>
                        <ul class="ps-3 smaller mb-0 text-muted" style="font-size: 0.78rem;">
                            <li class="mb-1.5">Role-Based Access Control (RBAC) separating Patient, Staff, Lab Tech, and Administrator.</li>
                            <li class="mb-1.5"><strong>The Reason-Gate Protocol:</strong> Mandatory logged justification before accessing patient files.</li>
                            <li class="mb-1.5">Designated Data Protection Officer (DPO) overseeing regulatory audits.</li>
                            <li class="mb-1.5">Dual-verification sign-offs (v1 Technologist & v2 Pathologist) for medical reports.</li>
                            <li class="mb-0">Periodic security awareness training for clinical and administrative staff.</li>
                        </ul>
                    </div>
                </div>

                {{-- Physical Security --}}
                <div class="col-12 col-md-4">
                    <div class="p-3 border rounded-3 h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <h6 class="text-warning fw-bold small uppercase mb-2">Physical Safeguards</h6>
                        <ul class="ps-3 smaller mb-0 text-muted" style="font-size: 0.78rem;">
                            <li class="mb-1.5">Controlled biometric/badge access to laboratory testing workstations.</li>
                            <li class="mb-1.5">Clean desk and locked archival storage for physical specimen logbooks.</li>
                            <li class="mb-1.5">Secure, climate-controlled specimen disposal and chemical containment.</li>
                            <li class="mb-1.5">Surveillance cameras (CCTV) at specimen receiving and admissions desks.</li>
                            <li class="mb-0">Secure shredding protocols for discarded preliminary printed worksheets.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 5: Reason-Gate Protocol & Audit Logging --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                5. The "Reason-Gate" Protocol & Audit Traceability
            </h5>
            <p class="mb-3">
                Section 20 of the DPA mandates that Personal Information Controllers implement measures to track access to personal data. To satisfy this requirement:
            </p>
            <div class="p-3 border rounded-3" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <p class="small mb-2" style="font-size: 0.82rem;">
                    Every attempt by clinical personnel to inspect released patient records, edit laboratory details, or review digitized archives triggers our <strong>Reason-Gate Modal</strong>. The user must provide a documented clinical purpose before access is granted.
                </p>
                <div class="row g-2 smaller text-muted pt-2 border-top" style="border-color: var(--border-color) !important; font-size: 0.78rem;">
                    <div class="col-12 col-md-6">
                        <strong class="text-main d-block mb-1">Audited Operations:</strong>
                        <ul class="ps-3 mb-0">
                            <li>Result Hub Access & Demographic Edits</li>
                            <li>Radiology Scan Overrides & Sign-Offs</li>
                            <li>Manual Legacy Data Import Requests</li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-6">
                        <strong class="text-main d-block mb-1">Captured Audit Variables:</strong>
                        <ul class="ps-3 mb-0">
                            <li>Staff Account ID & Full Legal Name</li>
                            <li>Exact Timestamp & Patient Target ID</li>
                            <li>Validated Clinical Justification Stored in Database</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 6: Data Breach Response Protocol --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                6. Security Incident & Data Breach Management (NPC Circular 16-03)
            </h5>
            <p class="mb-3">
                Medscreen maintains an institutional Incident Response Plan. In the unlikely event of a security breach involving Sensitive Personal Information:
            </p>
            <ul class="ps-3 mb-0">
                <li class="mb-2"><strong>72-Hour NPC Notification:</strong> The Data Protection Officer will notify the National Privacy Commission and affected data subjects within <strong>72 hours</strong> of knowledge of the breach, where the incident poses significant risk to patient rights.</li>
                <li class="mb-2"><strong>Containment & Forensics:</strong> Impacted accounts and API endpoints are instantly isolated, affected session tokens revoked, and database access logs preserved for forensic analysis.</li>
                <li class="mb-0"><strong>Mitigation Assistance:</strong> Affected patients will receive direct guidance on securing their credentials and identity safeguards.</li>
            </ul>
        </section>

        {{-- Section 7: Data Subject Rights & Redress --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                7. Rights of Data Subjects & Redress Mechanisms
            </h5>
            <p class="mb-3">Patients interacting with Medscreen enjoy the full protection of data subject rights under Chapter III of the DPA:</p>
            <div class="table-responsive mb-3">
                <table class="table table-bordered small mb-0" style="border-color: var(--border-color) !important; color: var(--text-main);">
                    <thead style="background-color: rgba(0,0,0,0.03);">
                        <tr>
                            <th class="py-2.5 px-3">Data Subject Right</th>
                            <th class="py-2.5 px-3">Implementation Mechanism on Medscreen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Right to Access</td>
                            <td class="px-3 py-2.5">Real-time patient dashboard, Result Archive, and Medical History screens.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Right to Rectification</td>
                            <td class="px-3 py-2.5">Patient profile editor (Settings) and the "Update & Resubmit" workflow for returned bookings.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Right to Erasure / Blocking</td>
                            <td class="px-3 py-2.5">Voluntary account deactivation tool in Settings (releasing email/phone while preserving audit history for 10-year statutory compliance).</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Right to Data Portability</td>
                            <td class="px-3 py-2.5">Encrypted PDF downloads and one-click email forwarding for released clinical reports.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3 py-2.5">Right to Damages</td>
                            <td class="px-3 py-2.5">Right to seek compensation under Section 18 of the DPA for damages sustained due to inaccurate, incomplete, or unlawful data processing.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Section 8: DPO Contact Directory --}}
        <section class="p-4 rounded-3 border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.1rem;">
                8. Data Protection Office Contact Information
            </h5>
            <p class="small text-muted mb-3">
                To submit data subject access requests, report suspected security incidents, or request assistance regarding Philippine data protection regulations, please contact:
            </p>
            <div class="row g-2 text-muted small">
                <div class="col-12 col-md-6">
                    <div><strong class="text-main">Facility:</strong> Medscreen Diagnostic Laboratory</div>
                    <div><strong class="text-main">Location:</strong> Atis St, General Santos City, 9500 South Cotabato</div>
                </div>
                <div class="col-12 col-md-6">
                    <div><strong class="text-main">Direct Privacy Email:</strong> medscreen.lab@gmail.com</div>
                    <div><strong class="text-main">General Telephone:</strong> (083) 823 8754</div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection