@extends('legal.layout')

@section('legal-content')
    <h2 class="fw-800 text-main mb-4 tracking-tight">Privacy Policy</h2>
    <p class="text-muted small">Last updated: {{ now()->format('F d, Y') }}</p>
    <hr class="my-4">

    <div class="lh-lg">
        <h5 class="fw-800 text-main mb-3">1. Information Collection</h5>
        <p class="text-muted">
            We collect essential information necessary to simulate laboratory bookings and user profiles. This includes: first name, middle name, last name, date of birth, sex, contact number, email address, physical address, and requested laboratory services. 
        </p>

        <h5 class="fw-800 text-main mb-3">2. Processing & Clinical Justification</h5>
        <p class="text-muted">
            Collected data is processed strictly to generate diagnostic records, schedule appointments, and dispatch progress notifications. For strict security, sensitive clinical records (such as laboratory results) are protected by our "Reason-Gate" security protocol, which logs and audits all administrative access.
        </p>

        <h5 class="fw-800 text-main mb-3">3. Data Retention & Academic Mocking</h5>
        <p class="text-muted">
            As this is an academic prototype, any information entered is treated as mock data and stored securely in our database. Real-world sensitive medical files must not be uploaded to this server. We periodically purge database entries to maintain a clean testing environment.
        </p>

        <h5 class="fw-800 text-main mb-3">4. Your Information Rights</h5>
        <p class="text-muted">
            In compliance with student developmental guidelines, users retain full control over their accounts. You may update your profile details, manage family dependents, or completely purge your account and associated mock medical history directly through the Settings panel.
        </p>
    </div>
@endsection