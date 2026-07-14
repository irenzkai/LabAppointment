@extends('legal.layout')

@section('legal-content')
    <h2 class="fw-800 text-main mb-4 tracking-tight">Data Privacy Act Compliance</h2>
    <p class="text-muted small">Philippine Republic Act No. 10173</p>
    <hr class="my-4">

    <div class="lh-lg">
        <p class="text-muted">
            In compliance with **Republic Act No. 10173**, otherwise known as the **Data Privacy Act of 2012 (DPA)**, and its Implementing Rules and Regulations, this prototype is designed with technical, organizational, and physical security measures to safeguard patient data.
        </p>

        <h5 class="fw-800 text-main mb-3">1. Processing of Sensitive Personal Information</h5>
        <p class="text-muted">
            Health records, medical diagnoses, and laboratory results are classified as **Sensitive Personal Information** under Philippine law. This platform processes these variables strictly under principles of Transparency, Proportionality, and Legitimate Purpose. All access to clinical files by administrative staff requires a verified business justification logged through our audit trail.
        </p>

        <h5 class="fw-800 text-main mb-3">2. Patient Rights Safeguarded</h5>
        <p class="text-muted">
            Pursuant to the guidelines of the National Privacy Commission (NPC), patients interacting with this digital portal retain the following rights:
        </p>
        <ul class="text-muted small">
            <li class="mb-2"><strong>Right to be Informed:</strong> Clear explanations of what data is collected and how it is utilized for diagnostic scheduling.</li>
            <li class="mb-2"><strong>Right to Access:</strong> Secure patient portal allowing records and historical logs to be reviewed at any time.</li>
            <li class="mb-2"><strong>Right to Rectification:</strong> Resubmission flow enabling patients to correct and update clinical records rejected by staff.</li>
            <li class="mb-2"><strong>Right to Erasure:</strong> Account termination tool that purges patient metadata and associated records from active storage.</li>
        </ul>

        <h5 class="fw-800 text-main mb-3">3. Data Protection Officer (DPO)</h5>
        <p class="text-muted">
            For questions regarding Philippine data protection compliance or data subject rights within this academic prototype, you may contact the student developers at the email address provided in our contact directory.
        </p>
    </div>
@endsection