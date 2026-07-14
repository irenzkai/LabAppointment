@extends('legal.layout')

@section('legal-content')
    <h2 class="fw-800 text-main mb-4 tracking-tight">Terms of Service</h2>
    <p class="text-muted small">Last updated: {{ now()->format('F d, Y') }}</p>
    <hr class="my-4">

    <div class="lh-lg">
        <h5 class="fw-800 text-main mb-3">1. Scope of Service</h5>
        <p class="text-muted">
            The Medscreen platform operates exclusively as an academic prototype. It provides automated appointment scheduling, notification routing, and digital clinical results rendering for educational and demonstration purposes. It is not an active commercial health platform.
        </p>

        <h5 class="fw-800 text-main mb-3">2. User Accounts & Verification</h5>
        <p class="text-muted">
            Access to administrative and laboratory portals requires authorization from the student dev team. System administrators reserve the right to audit, disable, or purge mock accounts that do not comply with our testing security standards.
        </p>

        <h5 class="fw-800 text-main mb-3">3. Mock Clinical Data</h5>
        <p class="text-muted">
            All medical records, diagnostic observations, test catalogs, and results generated within this system are fictional. They are intended solely to show software workflow and must not be used for actual medical decision-making.
        </p>

        <h5 class="fw-800 text-main mb-3">4. Intellectual Property</h5>
        <p class="text-muted">
            The source code, custom design sheets, and interface assets remain the intellectual property of the student development team. Unauthorized commercial redistribution is strictly prohibited.
        </p>
    </div>
@endsection