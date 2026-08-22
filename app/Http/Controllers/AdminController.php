<?php

namespace App\Http\Controllers;

use App\Models\{User, Dependent, Appointment, ActivityLog, LaboratoryHistory, LaboratoryHistoryRecord, Service};
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Crypt};
use Illuminate\Support\Facades\Password as PasswordFacade;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index() 
    {
        $query = User::withTrashed()->where('id', '!=', Auth::id());
        if (Auth::user()->role === 'staff') {
            $query->where('role', '!=', 'admin');
        }
        $users = $query->latest()->get();
        return view('admin.users', compact('users'));
    }

    public function createUser(Request $request) 
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $promotedDependent = null;
        if ($request->has('promote')) {
            try {
                $decryptedId = Crypt::decryptString($request->query('promote'));
                $promotedDependent = Dependent::withTrashed()->find($decryptedId);
            } catch (\Exception $e) {}
        }
        return view('admin.users.create', compact('promotedDependent'));
    }

    public function storeUser(Request $request)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') return;
            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };
        $isPromoted = $request->filled('promoted_dependent_id');
        $birthdateRule = $isPromoted 
            ? ['required', 'date', 'before_or_equal:today'] 
            : ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')];

        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'],
            'email' => ['required', 'email', 'unique:users,email', 'regex:/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'birthdate' => $birthdateRule,
            'sex' => 'required|string|in:Male,Female',
            'street' => 'required|string|max:150',
            'barangay' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'role' => 'required|in:user,staff,lab_tech',
            'password' => ['required', 'string', \Illuminate\Validation\Rules\Password::defaults(), 'confirmed'],
            'verify_email_now' => 'nullable|boolean',
            'promoted_dependent_id' => ['nullable', 'integer', 'exists:dependents,id'],
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $fName = mb_strtoupper(trim($request->first_name), 'UTF-8');
        $mName = ($request->middle_name && mb_strtoupper(trim($request->middle_name), 'UTF-8') !== 'N/A') ? mb_strtoupper(trim($request->middle_name), 'UTF-8') : 'N/A';
        $lName = mb_strtoupper(trim($request->last_name), 'UTF-8');
        $suffix = $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : '';
        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) $displayName .= " {$suffix}";
        $verifyEmailNow = $request->boolean('verify_email_now');

        $user = User::create([
            'first_name' => $fName,
            'middle_name' => $mName,
            'last_name' => $lName,
            'suffix' => $suffix ?: null,
            'name' => $displayName,
            'email' => $request->email,
            'phone' => $request->phone,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'street' => mb_strtoupper(trim($request->street), 'UTF-8'),
            'barangay' => mb_strtoupper(trim($request->barangay), 'UTF-8'),
            'city' => mb_strtoupper(trim($request->city), 'UTF-8'),
            'province' => mb_strtoupper(trim($request->province), 'UTF-8'),
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'email_verified_at' => $verifyEmailNow ? now() : null,
        ]);

        if ($request->filled('promoted_dependent_id')) {
            $depId = $request->input('promoted_dependent_id');
            Appointment::where('dependent_id', $depId)->update([
                'user_id' => $user->id,
                'dependent_id' => null,
            ]);
            Dependent::destroy($depId);
            ActivityLog::record('ACCOUNT PROMOTED', "Dependent account successfully promoted to independent user profile for {$user->name}", $user->name);
        } else {
            ActivityLog::record('ADMIN USER CREATE', "Admin created user account {$user->name} ({$user->role}). Reason: {$reasonText}", $user->name);
        }

        event(new QueueUpdated());
        return redirect()->route('admin.users.index')->with('success', "Account for {$user->name} created successfully!");
    }

    public function editUser($id)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($id);
        $user->load(['dependents' => fn($q) => $q->withTrashed()]);
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($id);
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') return;
            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'sex' => 'required|string|in:Male,Female',
            'street' => 'required|string|max:150',
            'barangay' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'role' => 'required|in:user,staff,lab_tech',
            'email' => ['required', 'email', 'unique:users,email,' . $user->id, 'regex:/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'password_option' => 'nullable|in:send_link,manual',
            'password' => 'required_if:password_option,manual|nullable|string|min:8|confirmed',
            'email_action' => 'nullable|in:verify_now,send_notification'
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $fName = mb_strtoupper(trim($request->first_name), 'UTF-8');
        $mName = ($request->middle_name && mb_strtoupper(trim($request->middle_name), 'UTF-8') !== 'N/A') ? mb_strtoupper(trim($request->middle_name), 'UTF-8') : 'N/A';
        $lName = mb_strtoupper(trim($request->last_name), 'UTF-8');
        $suffix = $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : '';
        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) $displayName .= " {$suffix}";

        $user->fill([
            'first_name' => $fName,
            'middle_name' => $mName,
            'last_name' => $lName,
            'suffix' => $suffix ?: null,
            'name' => $displayName,
            'phone' => $request->phone,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'street' => mb_strtoupper(trim($request->street), 'UTF-8'),
            'barangay' => mb_strtoupper(trim($request->barangay), 'UTF-8'),
            'city' => mb_strtoupper(trim($request->city), 'UTF-8'),
            'province' => mb_strtoupper(trim($request->province), 'UTF-8'),
            'role' => $request->role,
        ]);

        if ($user->isDirty('email')) { 
            $user->email = $request->email;
            if ($user->isPatient()) {
                $user->email_verified_at = null;
            }
        }

        if ($request->email_action === 'verify_now') {
            $user->email_verified_at = now();
        } elseif ($request->email_action === 'send_notification' && $user->isPatient()) {
            $user->sendEmailVerificationNotification();
        }

        if ($request->input('password_option') === 'send_link') {
            PasswordFacade::sendResetLink(['email' => $user->email]);
        } elseif ($request->input('password_option') === 'manual') {
            $user->password = Hash::make($request->password);
            $user->password_change_required = true; 
        }

        $user->save();
        ActivityLog::record('ADMIN USER EDIT', "Admin updated user {$user->name}. Reason: {$reasonText}", $user->name);
        event(new QueueUpdated()); 

        return redirect()->route('admin.users.index')->with('success', "Account details for {$user->name} updated successfully.");
    }

    public function toggleUserStatus(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $user = User::withTrashed()->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();
            $actionLabel = "REACTIVATED";
        } else {
            $user->delete();
            $actionLabel = "DEACTIVATED";
        }

        ActivityLog::record("ADMIN ACCOUNT {$actionLabel}", "Admin {$actionLabel} account for {$user->name}. Reason: {$reasonText}", $user->name);
        event(new QueueUpdated());

        return back()->with('success', "Account status for {$user->name} has been set to {$actionLabel}.");
    }

    public function sendVerificationEmail($id)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($id);
        if ($user->isPatient()) {
            $user->sendEmailVerificationNotification();
            ActivityLog::record('ADMIN SENT VERIFICATION', "Dispatched email verification notification to {$user->email}", $user->name);
            return back()->with('success', "Verification notification sent to {$user->email}.");
        }
        return back()->with('error', "Target user is not a patient account.");
    }

    public function createDependentForUser($id)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($id);
        return view('admin.users.dependents.create', compact('user'));
    }

    public function storeDependentForUser(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($id);
        $eighteenYearsAgo = Carbon::now()->subYears(18)->toDateString();

        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:10',
            'birthdate' => 'required|date|before_or_equal:today|after:' . $eighteenYearsAgo,
            'sex' => 'required|in:Male,Female',
            'street' => 'required|string|max:150',
            'barangay' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $dependent = $user->dependents()->create([
            'first_name' => mb_strtoupper(trim($request->first_name), 'UTF-8'),
            'middle_name' => ($request->middle_name && mb_strtoupper(trim($request->middle_name), 'UTF-8') !== 'N/A') ? mb_strtoupper(trim($request->middle_name), 'UTF-8') : 'N/A',
            'last_name' => mb_strtoupper(trim($request->last_name), 'UTF-8'),
            'suffix' => $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : null,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'street' => mb_strtoupper(trim($request->street), 'UTF-8'),
            'barangay' => mb_strtoupper(trim($request->barangay), 'UTF-8'),
            'city' => mb_strtoupper(trim($request->city), 'UTF-8'),
            'province' => mb_strtoupper(trim($request->province), 'UTF-8'),
        ]);

        ActivityLog::record('ADMIN ADD DEPENDENT', "Admin added dependent {$dependent->name} for user {$user->name}. Reason: {$reasonText}", $user->name);
        return redirect()->route('admin.users.edit', ['id' => $user->id, '#tab-dependents'])->with('success', "Dependent {$dependent->name} created successfully.");
    }

    public function editDependentForUser($userId, $dependentId) 
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($userId);
        $dependent = Dependent::withTrashed()->where('user_id', $user->id)->findOrFail($dependentId);
        return view('admin.users.dependents.edit', compact('user', 'dependent'));
    }

    public function updateDependentForUser(Request $request, $userId, $dependentId)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($userId);
        $dependent = Dependent::withTrashed()->where('user_id', $user->id)->findOrFail($dependentId);
        $eighteenYearsAgo = Carbon::now()->subYears(18)->toDateString();

        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:10',
            'birthdate' => 'required|date|before_or_equal:today|after:' . $eighteenYearsAgo,
            'sex' => 'required|in:Male,Female',
            'street' => 'required|string|max:150',
            'barangay' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $dependent->update([
            'first_name' => mb_strtoupper(trim($request->first_name), 'UTF-8'),
            'middle_name' => ($request->middle_name && mb_strtoupper(trim($request->middle_name), 'UTF-8') !== 'N/A') ? mb_strtoupper(trim($request->middle_name), 'UTF-8') : 'N/A',
            'last_name' => mb_strtoupper(trim($request->last_name), 'UTF-8'),
            'suffix' => $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : null,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'street' => mb_strtoupper(trim($request->street), 'UTF-8'),
            'barangay' => mb_strtoupper(trim($request->barangay), 'UTF-8'),
            'city' => mb_strtoupper(trim($request->city), 'UTF-8'),
            'province' => mb_strtoupper(trim($request->province), 'UTF-8'),
        ]);

        ActivityLog::record('ADMIN EDIT DEPENDENT', "Admin updated dependent {$dependent->name} for user {$user->name}. Reason: {$reasonText}", $user->name);
        return redirect()->route('admin.users.edit', ['id' => $user->id, '#tab-dependents'])->with('success', "Dependent {$dependent->name} updated successfully.");
    }

    public function destroyDependentForUser(Request $request, $userId, $dependentId)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($userId);
        $dependent = Dependent::withTrashed()->where('user_id', $user->id)->findOrFail($dependentId);
        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $depName = $dependent->name;
        $dependent->delete();

        ActivityLog::record('ADMIN ARCHIVE DEPENDENT', "Admin archived dependent {$depName} for user {$user->name}. Reason: {$reasonText}", $user->name);
        return back()->with('success', "Dependent {$depName} moved to archive.");
    }

    public function restoreDependentForUser(Request $request, $userId, $id)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($userId);
        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $dependent = Dependent::onlyTrashed()->where('user_id', $user->id)->findOrFail($id);
        $dependent->restore();

        ActivityLog::record('ADMIN RESTORE DEPENDENT', "Admin restored dependent {$dependent->name} for user {$user->name}. Reason: {$reasonText}", $user->name);
        return back()->with('success', "Dependent {$dependent->name} reactivated successfully.");
    }

    public function promoteDependentForUser(Request $request, $userId, $dependentId)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $user = User::withTrashed()->findOrFail($userId);
        $dependent = Dependent::withTrashed()->where('user_id', $user->id)->findOrFail($dependentId);
        $request->validate([
            'reason' => 'required|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');
        $promoUrl = route('admin.users.create', ['promote' => Crypt::encryptString($dependent->id)]);

        ActivityLog::record('ADMIN PROMOTE DEPENDENT', "Admin generated account promotion for dependent {$dependent->name}. Reason: {$reasonText}", $user->name);
        return redirect()->to($promoUrl);
    }

    public function patientHistory($id) 
    {
        if (!Auth::user()->isEmployee()) abort(403);
        $targetUser = User::withTrashed()->findOrFail($id);
        if (!session()->has("access_granted_{$targetUser->id}_history")) {
            return redirect()->route('admin.users.index')->with('error', 'Clinical authorization required to view patient records.');
        }

        $labHistory = LaboratoryHistory::firstOrCreate(['user_id' => $targetUser->id]);
        $appointments = Appointment::with(['services', 'result'])
            ->where('user_id', $targetUser->id)
            ->latest()
            ->get();

        ActivityLog::record('VIEWED HISTORY', 'Accessed clinical archive via User Management', $targetUser->name);

        $availableServices = Service::where('is_available', true)->orderBy('category')->orderBy('name')->get();
        $recordsModels = LaboratoryHistoryRecord::whereHas('laboratoryHistory', fn($q) => $q->where('user_id', $targetUser->id))
            ->with(['scans', 'procedures'])
            ->latest('date_of_record')
            ->get();

        $existingRecords = $recordsModels->map(function($r) {
            return [
                'id' => $r->id,
                'date_of_record' => $r->date_of_record ? $r->date_of_record->format('Y-m-d') : '',
                'requested_by' => $r->requested_by,
                'patient_name' => $r->patient_name,
                'age' => $r->age,
                'sex' => $r->sex,
                'address' => $r->patient_address,
                'tests_requested' => $r->procedures->pluck('procedure_name')->toArray(),
                'scans' => $r->scans->map(fn($s) => ['label' => $s->label, 'file_path' => $s->file_path, 'certificate_no' => $s->certificate_no ?? null])->toArray()
            ];
        })->toArray();

        return view('patient-history', compact('targetUser', 'appointments', 'labHistory', 'availableServices', 'existingRecords'));
    }

    public function viewLogs(Request $request) 
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $roleFilter = $request->query('role');
        $query = ActivityLog::with('user')->latest();
        if ($roleFilter) {
            $query->whereHas('user', fn($q) => $q->where('role', $roleFilter));
        }
        $logs = $query->paginate(20)->withQueryString();
        return view('admin.logs', compact('logs'));
    }

    /**
     * Dedicated Reports & Export Console View
     */
    public function reports(Request $request)
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $type = $request->query('type', 'transactions'); // transactions, accounts, logs
        
        // Transactions Filters
        $txPeriod = $request->query('tx_period', 'cumulative');
        $txDate = $request->query('tx_date', Carbon::today()->toDateString());
        $txMonth = $request->query('tx_month', Carbon::now()->format('Y-m'));
        $txYear = $request->query('tx_year', Carbon::now()->format('Y'));
        $txStatus = $request->query('tx_status', 'all');

        $txQuery = Appointment::with('services');
        if ($txStatus !== 'all') {
            $txQuery->where('payment_status', $txStatus);
        }
        if ($txPeriod === 'daily' && $txDate) {
            $txQuery->whereDate('appointment_date', $txDate);
        } elseif ($txPeriod === 'monthly' && $txMonth) {
            $mParts = explode('-', $txMonth);
            if (count($mParts) === 2) {
                $txQuery->whereYear('appointment_date', $mParts[0])->whereMonth('appointment_date', $mParts[1]);
            }
        } elseif ($txPeriod === 'yearly' && $txYear) {
            $txQuery->whereYear('appointment_date', $txYear);
        }
        $transactions = $txQuery->latest()->get();

        // Accounts Filters
        $accRole = $request->query('acc_role', 'all');
        $accQuery = User::withTrashed();
        if ($accRole === 'patients') {
            $accQuery->where('role', 'user');
        } elseif ($accRole === 'employees') {
            $accQuery->whereIn('role', ['staff', 'lab_tech']);
        } elseif ($accRole === 'admins') {
            $accQuery->where('role', 'admin');
        }
        $accounts = $accQuery->latest()->get();

        // Logs Filters
        $logPeriod = $request->query('log_period', 'cumulative');
        $logDate = $request->query('log_date', Carbon::today()->toDateString());
        $logMonth = $request->query('log_month', Carbon::now()->format('Y-m'));
        $logYear = $request->query('log_year', Carbon::now()->format('Y'));
        $logCategory = $request->query('log_category', 'all');

        $logQuery = ActivityLog::with('user');
        if ($logCategory !== 'all') {
            $logQuery->where('action', 'like', "%{$logCategory}%");
        }
        if ($logPeriod === 'daily' && $logDate) {
            $logQuery->whereDate('created_at', $logDate);
        } elseif ($logPeriod === 'monthly' && $logMonth) {
            $lParts = explode('-', $logMonth);
            if (count($lParts) === 2) {
                $logQuery->whereYear('created_at', $lParts[0])->whereMonth('created_at', $lParts[1]);
            }
        } elseif ($logPeriod === 'yearly' && $logYear) {
            $logQuery->whereYear('created_at', $logYear);
        }
        $logs = $logQuery->latest()->get();

        return view('admin.reports', compact(
            'type',
            'transactions', 'txPeriod', 'txDate', 'txMonth', 'txYear', 'txStatus',
            'accounts', 'accRole',
            'logs', 'logPeriod', 'logDate', 'logMonth', 'logYear', 'logCategory'
        ));
    }

    /**
     * CSV Stream Exporter for Reports Page
     */
    public function exportReport(Request $request)
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $reportType = $request->query('report_type', 'transactions');
        $filename = "medscreen_" . $reportType . "_report_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($request, $reportType) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($reportType === 'transactions') {
                fputcsv($file, ['Date (M/D/Y)', 'Reference ID', 'Patient Name', 'Services Requested', 'Method', 'Payment Status', 'Amount (PHP)']);
                
                $txPeriod = $request->query('tx_period', 'cumulative');
                $txDate = $request->query('tx_date', Carbon::today()->toDateString());
                $txMonth = $request->query('tx_month', Carbon::now()->format('Y-m'));
                $txYear = $request->query('tx_year', Carbon::now()->format('Y'));
                $txStatus = $request->query('tx_status', 'all');

                $txQuery = Appointment::with('services');
                if ($txStatus !== 'all') $txQuery->where('payment_status', $txStatus);
                if ($txPeriod === 'daily' && $txDate) $txQuery->whereDate('appointment_date', $txDate);
                elseif ($txPeriod === 'monthly' && $txMonth) {
                    $mParts = explode('-', $txMonth);
                    if (count($mParts) === 2) $txQuery->whereYear('appointment_date', $mParts[0])->whereMonth('appointment_date', $mParts[1]);
                } elseif ($txPeriod === 'yearly' && $txYear) $txQuery->whereYear('appointment_date', $txYear);

                foreach ($txQuery->latest()->get() as $tx) {
                    $amt = $tx->payment_amount ?: $tx->totalPrice();
                    fputcsv($file, [
                        $tx->appointment_date ? $tx->appointment_date->format('M d, Y') : $tx->created_at->format('M d, Y'),
                        '#' . $tx->id,
                        $tx->patient_name,
                        $tx->services->pluck('name')->implode(', '),
                        $tx->payment_method,
                        strtoupper($tx->payment_status),
                        number_format($amt, 2)
                    ]);
                }
            } elseif ($reportType === 'accounts') {
                fputcsv($file, ['ID', 'Full Name', 'Email Address', 'Phone Number', 'Role', 'Status', 'Registered Date']);
                
                $accRole = $request->query('acc_role', 'all');
                $accQuery = User::withTrashed();
                if ($accRole === 'patients') $accQuery->where('role', 'user');
                elseif ($accRole === 'employees') $accQuery->whereIn('role', ['staff', 'lab_tech']);
                elseif ($accRole === 'admins') $accQuery->where('role', 'admin');

                foreach ($accQuery->latest()->get() as $acc) {
                    fputcsv($file, [
                        '#' . $acc->id,
                        $acc->name,
                        $acc->email,
                        $acc->phone,
                        strtoupper($acc->role),
                        $acc->trashed() ? 'DEACTIVATED' : 'ACTIVE',
                        $acc->created_at ? $acc->created_at->format('M d, Y') : 'N/A'
                    ]);
                }
            } elseif ($reportType === 'logs') {
                fputcsv($file, ['Date & Time', 'Performer', 'Role', 'Action Event', 'Target Patient', 'Audit Reason']);
                
                $logPeriod = $request->query('log_period', 'cumulative');
                $logDate = $request->query('log_date', Carbon::today()->toDateString());
                $logMonth = $request->query('log_month', Carbon::now()->format('Y-m'));
                $logYear = $request->query('log_year', Carbon::now()->format('Y'));
                $logCategory = $request->query('log_category', 'all');

                $logQuery = ActivityLog::with('user');
                if ($logCategory !== 'all') $logQuery->where('action', 'like', "%{$logCategory}%");
                if ($logPeriod === 'daily' && $logDate) $logQuery->whereDate('created_at', $logDate);
                elseif ($logPeriod === 'monthly' && $logMonth) {
                    $lParts = explode('-', $logMonth);
                    if (count($lParts) === 2) $logQuery->whereYear('created_at', $lParts[0])->whereMonth('created_at', $lParts[1]);
                } elseif ($logPeriod === 'yearly' && $logYear) $logQuery->whereYear('created_at', $logYear);

                foreach ($logQuery->latest()->get() as $log) {
                    fputcsv($file, [
                        $log->created_at ? $log->created_at->format('M d, Y h:i A') : 'N/A',
                        $log->user->name ?? 'System/Deleted',
                        strtoupper($log->user->role ?? 'SYSTEM'),
                        $log->action,
                        $log->patient_name,
                        $log->reason
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

