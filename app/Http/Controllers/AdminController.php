<?php

namespace App\Http\Controllers;

use App\Models\{User, Appointment, ActivityLog, LaboratoryHistory, LaboratoryHistoryRecord, Service};
use App\Events\QueueUpdated; // Broadcasts status/badge refreshes to the Hub and master queue [53]
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash};
use Illuminate\Support\Facades\Password as PasswordFacade;

class AdminController extends Controller
{
    /**
     * View all users (Shared by Admin & Staff)
     */
    public function index() 
    {
        // Fetch all users (including soft-deleted/deactivated accounts) except the logged-in admin [15, 102]
        $query = User::withTrashed()->where('id', '!=', Auth::id());

        // If the logged-in user is 'staff', hide 'admin' accounts from the list
        if (Auth::user()->role === 'staff') {
            $query->where('role', '!=', 'admin');
        }

        $users = $query->latest()->get();

        return view('admin.users', compact('users'));
    }

    /** 
     * ADMIN ONLY: Unified User Profile Editor & Deactivation Engine [15, 102].
     * Handles role changes, email re-verifications, password overrides, and soft-deletes.
     */
    public function updateUser(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Fetch user supporting both active and soft-deleted/deactivated profiles [15, 102]
        $user = User::withTrashed()->findOrFail($id);

        // Custom name validation rule block matching dynamic front-end parameters
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') {
                return; // Handled by nullable/required constraints
            }

            // 1. Allowed characters boundary validation (Letters, Spanish ñ/Ñ, periods, hyphens, spaces, apostrophes)
            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }

            // 2. Strict non-punctuation starting validation
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must start with a letter.");
                return;
            }

            // 3. Must possess at least one character letter to prevent punctuation-only values
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }

            // 4. Consecutive punctuation marks validation
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        $request->validate([
            'reason' => 'required|string|min:5',
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'], // Suffix support
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],                       // PH mobile format check
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')], // Enforce 18+ limit
            'sex' => 'required|string|in:Male,Female',
            
            // PSGC size standard matching
            'street' => 'required|string|max:150',
            'barangay' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            
            'role' => 'required|in:user,staff,lab_tech',
            'email' => ['required', 'email', 'unique:users,email,' . $user->id, 'regex:/^[^@]+@[^@]+$/'],
            'password_option' => 'nullable|in:send_link,manual',
            'password' => 'required_if:password_option,manual|nullable|string|min:8|confirmed',
            'deactivate' => 'nullable|boolean'
        ], [
            'email.regex' => 'The email address must contain exactly one @ symbol.',
            'phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'birthdate.before_or_equal' => 'Administrative Policy: Users must be at least 18 years old.',
            'suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ]);

        $reason = $request->input('reason');

        $fName = strtoupper(trim($request->first_name));
        $mName = ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') ? strtoupper(trim($request->middle_name)) : 'N/A';
        $lName = strtoupper(trim($request->last_name));
        $suffix = $request->filled('suffix') ? strtoupper(trim($request->suffix)) : '';

        // Compile combined display name, appending the suffix if it exists
        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) {
            $displayName .= " {$suffix}";
        }

        // 1. Bind basic demographics
        $user->fill([
            'first_name' => $fName,
            'middle_name' => $mName,
            'last_name' => $lName,
            'suffix' => $suffix ?: null,
            'name' => $displayName,
            'phone' => $request->phone,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'street' => strtoupper(trim($request->street)),
            'barangay' => strtoupper(trim($request->barangay)),
            'city' => strtoupper(trim($request->city)),
            'province' => strtoupper(trim($request->province)),
            'role' => $request->role,
        ]);

        // 2. Email Change -> Prompts re-verification for patients [50]
        if ($user->isDirty('email')) { 
            $user->email = $request->email;
            if ($user->isPatient()) {
                $user->email_verified_at = null;
                $user->sendEmailVerificationNotification();
            }
        }

        // 3. Password Overrides
        if ($request->input('password_option') === 'send_link') {
            // Option 1: Send a reset link to their email
            PasswordFacade::sendResetLink(['email' => $user->email]);
        } elseif ($request->input('password_option') === 'manual') {
            // Option 2: Manually set password & flag to force update on next login [102]
            $user->password = Hash::make($request->password);
            $user->password_change_required = true; 
        }

        // 4. Soft-delete / Deactivation Toggles [102]
        if ($request->has('deactivate') && $request->deactivate == '1') {
            if (!$user->trashed()) {
                $user->delete(); // Triggers soft-delete (deactivation) [102]
            }
        } else {
            if ($user->trashed()) {
                $user->restore(); // Restores/reactivates the account [102]
            }
        }

        $user->save();

        // Record audit log for HIPAA and compliance auditing [73]
        ActivityLog::record(
            'ADMIN USER EDIT', 
            "Admin updated user {$user->name}. Reason: {$reason}", 
            $user->name, 
            $user->id
        );

        // Dispatch live update for queue views in case role or details changed
        event(new QueueUpdated());

        return back()->with('success', "Account details for {$user->name} have been successfully updated.");
    }

    /**
     * View Patient Medical History (Accessed via the Reason-Gate)
     */
    public function patientHistory(User $user) 
    {
        if (!Auth::user()->isEmployee()) {
            abort(403);
        }

        if (!session()->has("access_granted_{$user->id}_history")) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Clinical authorization required to view patient records.');
        } 

        session()->forget("access_granted_{$user->id}_history");

        $targetUser = $user; 
        $labHistory = LaboratoryHistory::firstOrCreate(['user_id' => $targetUser->id]);

        $appointments = Appointment::with(['services', 'result'])
            ->where('user_id', $targetUser->id)
            ->latest()
            ->get();

        ActivityLog::record('VIEWED HISTORY', 'Accessed clinical archive via User Management', $targetUser->name);

        $availableServices = Service::where('is_available', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $recordsModels = LaboratoryHistoryRecord::whereHas('laboratoryHistory', function($q) use ($targetUser) {
            $q->where('user_id', $targetUser->id);
        })
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
                'scans' => $r->scans->map(function($s) {
                    return [
                        'label' => $s->label,
                        'file_path' => $s->file_path
                    ];
                })->toArray()
            ];
        })->toArray();

        return view('patient-history', compact('targetUser', 'appointments', 'labHistory', 'availableServices', 'existingRecords'));
    }

    /**
     * ADMIN ONLY: View System Audit Logs
     */
    public function viewLogs(Request $request) 
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $roleFilter = $request->query('role');
        $query = ActivityLog::with('user')->latest();

        if ($roleFilter) {
            $query->whereHas('user', function($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.logs', compact('logs'));
    }
}