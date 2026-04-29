@extends('layouts.app')

@section('content')
<div class="container text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-neon fw-bold mb-0 uppercase">PATIENT ARCHIVE</h2>
            <p class="text-white small mb-0">Records for: <span class="text-neon">{{ strtoupper($targetUser->name) }}</span></p>
        </div>
        <a href="{{ route('appointments.index') }}" class="btn-custom btn-neon">
            <i class="bi bi-arrow-left me-2"></i> BACK TO APPOINTMENTS
        </a>
    </div>

    <!-- TABS -->
    <ul class="nav nav-pills mb-4 border-bottom border-secondary pb-3 gap-2" id="historyTabs">
        <li class="nav-item">
            <button class="nav-link active fw-bold px-4 text-neon" data-bs-toggle="pill" data-bs-target="#app-history">APPOINTMENT HISTORY</button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-4 text-neon" data-bs-toggle="pill" data-bs-target="#lab-history">LABORATORY RECORDS</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- 1. APPOINTMENT HISTORY (REUSING YOUR LIST COMPONENT) -->
        <div class="tab-pane fade show active" id="app-history">
            @include('appointments.partials.list', ['apps' => $appointments, 'type' => 'history', 'is_staff' => Auth::user()->isEmployee()])
        </div>

        <!-- 2. LABORATORY HISTORY -->
        <div class="tab-pane fade" id="lab-history">
            @php $status = $labHistory->permission_status; @endphp

            {{-- ==========================================
                PHASE 1: PERMISSION HANDSHAKE
                ========================================== --}}
            
            {{-- INITIAL STATE: No one has asked yet --}}
            @if($status == 'none')
                <div class="card p-5 text-center border-secondary bg-dark bg-opacity-25 shadow-lg">
                    <i class="bi bi-shield-lock fs-1 text-secondary mb-3"></i>
                    <h4 class="text-white uppercase fw-bold">Historical Data Connection</h4>
                    @if(Auth::user()->isPatient())
                        <p class="text-secondary">Your physical records are not digitized yet. Request staff to import them.</p>
                        <form action="{{ route('history.request') }}" method="POST">@csrf
                            <button class="btn-custom btn-neon px-5 py-2">REQUEST DATA IMPORT</button>
                        </form>
                    @else
                        <p class="text-secondary">This patient has not requested a data import yet.</p>
                        <form action="{{ route('history.staff-trigger', $targetUser->id) }}" method="POST">@csrf
                            <button class="btn-custom btn-outline-neon px-5 py-2">ASK PATIENT FOR PERMISSION</button>
                        </form>
                    @endif
                </div>

            {{-- PENDING STAFF: Patient asked, Staff must accept --}}
            @elseif($status == 'pending_staff')
                <div class="card p-5 text-center border-info bg-dark bg-opacity-25 shadow-lg">
                    @if(Auth::user()->isEmployee())
                        <i class="bi bi-person-check fs-1 text-info mb-3"></i>
                        <h4 class="text-info uppercase fw-bold">Import Request Received</h4>
                        <p class="text-secondary">The patient is requesting to have their physical records digitized.</p>
                        <form action="{{ route('history.accept', ['user' => $targetUser->id]) }}" method="POST">@csrf
                            <button class="btn-custom btn-neon px-5 py-3 fw-bold">ACCEPT & OPEN IMPORT TOOL</button>
                        </form>
                    @else
                        <i class="bi bi-hourglass-split fs-1 text-info mb-3"></i>
                        <h4 class="text-info uppercase fw-bold">Waiting for Staff</h4>
                        <p class="text-secondary">Your request has been sent. Waiting for a technician to begin the process.</p>
                    @endif
                </div>

            {{-- PENDING PATIENT: Staff asked, Patient must allow --}}
            @elseif($status == 'pending_patient')
                <div class="card p-5 text-center border-warning bg-dark bg-opacity-25 shadow-lg">
                    @if(Auth::user()->isPatient())
                        <i class="bi bi-shield-exclamation fs-1 text-warning mb-3"></i>
                        <h4 class="text-warning uppercase fw-bold">Permission Required</h4>
                        <p class="text-secondary">The laboratory wants to digitize your previous physical records. Do you allow this?</p>
                        <form action="{{ route('history.accept') }}" method="POST">@csrf
                            <button class="btn-custom btn-neon px-5 py-3 fw-bold">ALLOW DIGITIZATION</button>
                        </form>
                    @else
                        <i class="bi bi-hourglass-split fs-1 text-warning mb-3"></i>
                        <h4 class="text-warning uppercase fw-bold">Awaiting Patient Approval</h4>
                        <p class="text-secondary">We have sent a request to the patient to allow us to import their records.</p>
                    @endif
                </div>

            {{-- ==========================================
                PHASE 2: DATA MANAGEMENT (Status: Granted)
                ========================================== --}}
            @elseif($status == 'granted')
                
                {{-- STAFF ONLY: THE IMPORT TOOL --}}
                @if(Auth::user()->isEmployee())
                    <div class="card p-4 border-neon bg-black mb-4 shadow-lg">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h5 class="text-neon fw-bold mb-0">RECORD IMPORT TOOL</h5>
                            <div class="btn-group shadow-sm">
                                <button type="button" class="btn btn-sm btn-outline-neon px-3 border-neon" onclick="addColumn()"><i class="bi bi-plus-lg"></i> COL</button>
                                <button type="button" class="btn btn-sm btn-outline-neon px-3 border-neon" onclick="addRow()"><i class="bi bi-plus-lg"></i> ROW</button>
                                <label class="btn btn-sm btn-outline-neon px-3 border-neon mb-0 cursor-pointer">
                                    <i class="bi bi-file-earmark-excel"></i> IMPORT EXCEL
                                    <input type="file" id="excelInput" hidden accept=".xlsx, .xls, .csv" onchange="importFromExcel(this)">
                                </label>
                            </div>
                        </div>

                        <form action="{{ route('history.save-manual', $targetUser->id) }}" method="POST">
                            @csrf
                            <div class="table-responsive border border-secondary rounded mb-3">
                                <table class="table table-dark mb-0 align-middle" id="manualTable">
                                    <thead class="bg-dark"><tr id="manualHead"></tr></thead>
                                    <tbody id="manualBody"></tbody>
                                </table>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-custom btn-neon flex-grow-1 py-3 fw-bold">SAVE ALL ARCHIVED DATA</button>
                                <button type="button" class="btn-custom btn-outline-neon px-4" onclick="resetTable()">RESET</button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- SHOW DATA TABLE (Visible to both, but "No data" only for Patient) --}}
                @if($labHistory->dynamic_data)
                    <h5 class="text-neon mb-3 uppercase small fw-bold">Digitized Historical Records</h5>
                    <div class="table-responsive border border-secondary rounded shadow-lg">
                        <table class="table table-dark table-striped mb-0">
                            <thead class="bg-black">
                                <tr>
                                    @foreach($labHistory->dynamic_data['headers'] as $h)
                                        <th class="text-neon small uppercase">{{ $h }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($labHistory->dynamic_data['rows'] as $row)
                                    <tr>
                                        @foreach($row as $cell)
                                            <td class="text-white">{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(Auth::user()->isPatient())
                    {{-- This empty state box is ONLY shown to the patient --}}
                    <div class="card p-5 text-center border-secondary bg-dark bg-opacity-25">
                        <p class="text-secondary mb-0 italic">No data has been entered yet.</p>
                    </div>
                @endif

            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
    // Initialize data from PHP/Database
    let tableData = @json($labHistory->dynamic_data);

    document.addEventListener('DOMContentLoaded', function() {
        if (tableData && tableData.headers) {
            renderTableFromData(tableData);
        } else {
            // Default starting table if empty
            renderTableFromData({
                headers: ['DATE', 'TEST NAME', 'RESULT', 'REFERENCE'],
                rows: [['', '', '', '']]
            });
        }
    });

    function renderTableFromData(data) {
        const head = document.getElementById('manualHead');
        const body = document.getElementById('manualBody');
        head.innerHTML = '';
        body.innerHTML = '';

        // Render Headers
        data.headers.forEach((h, i) => {
            let th = document.createElement('th');
            th.className = "pb-4";
            th.innerHTML = `
                <div class="d-flex align-items-center">
                    <input type="text" name="headers[]" value="${h}" class="form-control form-control-sm bg-transparent border-0 text-neon fw-bold flex-grow-1">
                    <button type="button" class="btn btn-link text-danger p-0 ms-2" onclick="removeColumn(${i})"><i class="bi bi-trash3"></i></button>
                </div>
            `;
            head.appendChild(th);
        });
        // Add empty th for the row action column
        head.insertAdjacentHTML('beforeend', '<th style="width:50px"></th>');

        // Render Rows
        data.rows.forEach((row, rowIdx) => {
            let tr = document.createElement('tr');
            row.forEach((cell, colIdx) => {
                let td = document.createElement('td');
                td.innerHTML = `<input type="text" name="rows[${rowIdx}][]" value="${cell || ''}" class="form-control form-control-sm bg-black text-white border-0">`;
                tr.appendChild(td);
            });
            // Add row delete button
            tr.insertAdjacentHTML('beforeend', `<td><button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove()"><i class="bi bi-trash3"></i></button></td>`);
            body.appendChild(tr);
        });
    }

    function addColumn() {
        const head = document.getElementById('manualHead');
        const th = document.createElement('th');
        th.className = "position-relative pb-4";
        const index = head.cells.length - 1;
        th.innerHTML = `
            <input type="text" name="headers[]" placeholder="NEW COL" class="form-control form-control-sm bg-transparent border-0 text-neon fw-bold p-0 mb-1">
            <button type="button" class="btn btn-link text-danger p-0 position-absolute" style="bottom:5px; left:0; font-size: 0.7rem;" onclick="removeColumn(${index})">DELETE COL</button>
        `;
        head.insertBefore(th, head.lastElementChild);

        document.querySelectorAll('#manualBody tr').forEach((tr, rIdx) => {
            let td = document.createElement('td');
            td.innerHTML = `<input type="text" name="rows[${rIdx}][]" class="form-control form-control-sm bg-dark text-white border-0">`;
            tr.insertBefore(td, tr.lastElementChild);
        });
    }

    function addRow() {
        const body = document.getElementById('manualBody');
        const headCount = document.getElementById('manualHead').cells.length - 1;
        const tr = document.createElement('tr');
        const rIdx = body.rows.length;

        for(let i=0; i<headCount; i++) {
            tr.innerHTML += `<td><input type="text" name="rows[${rIdx}][]" class="form-control form-control-sm bg-dark text-white border-0"></td>`;
        }
        tr.innerHTML += `<td><button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove()"><i class="bi bi-trash3"></i></button></td>`;
        body.appendChild(tr);
    }

    function removeColumn(index) {
        if(!confirm('Delete this entire column?')) return;
        document.getElementById('manualHead').deleteCell(index);
        document.querySelectorAll('#manualBody tr').forEach(tr => tr.deleteCell(index));
    }

    function importFromExcel(input) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheet = workbook.SheetNames[0];
            const jsonData = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], {header: 1});

            if (jsonData.length > 0) {
                renderTableFromData({
                    headers: jsonData[0], // First row as headers
                    rows: jsonData.slice(1) // Rest as data
                });
            }
        };
        reader.readAsArrayBuffer(file);
    }

    function resetTable() {
        if(confirm('Clear all unsaved changes?')) location.reload();
    }
</script>
@endpush
@endsection