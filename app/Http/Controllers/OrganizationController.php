<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Service;
use App\Models\OrganizationMember;
use App\Models\ActivityLog;
use App\Notifications\AppointmentNotification;
use App\Events\QueueUpdated;
use App\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class OrganizationController extends Controller
{
    /**
     * Display the Organization Management Portal.
     * Segregates Staff Management Console from Patient Multi-Organization Self-Service.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isEmployee = $user->isEmployee();
        $viewMode = $request->query('view');
        $isStaffView = $isEmployee && ($viewMode !== 'patient');

        if ($isStaffView) {
            // =================================================================
            // 1. STAFF MANAGEMENT CONSOLE (OVERSIGHT & DIRECTORY AUDIT)
            // =================================================================
            $pendingApplications = Organization::whereIn('status', ['pending', 'pending_reactivation'])
                ->orWhereNotNull('pending_name')
                ->with('user')
                ->latest()
                ->get();

            // Only fetch packages that are genuinely pending clinical evaluation (excluding declined or disabled)
            $pendingPackages = Package::whereNotNull('organization_id')
                ->where('is_available', false)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'pending');
                })
                ->with(['organization', 'services'])
                ->latest()
                ->get();

            $recognizedOrgs = Organization::where('status', 'approved')
                ->withCount(['packages', 'members'])
                ->orderBy('name')
                ->get();

            $selectedOrgId = $request->query('org_id');
            $selectedOrg = $selectedOrgId ? Organization::with('user')->find($selectedOrgId) : null;
            
            $selectedOrgPackages = collect();
            $selectedOrgRosterGroups = collect();
            $selectedOrgRosterMembers = collect();
            $activeGroup = $request->query('group');

            if ($selectedOrg) {
                // Staff audit includes active, pending, declined, and archived packages
                $selectedOrgPackages = Package::withTrashed()
                    ->where('organization_id', $selectedOrg->id)
                    ->with('services')
                    ->orderBy('name')
                    ->get();

                $selectedOrgRosterGroups = OrganizationMember::where('organization_id', $selectedOrg->id)
                    ->select('group_name')
                    ->distinct()
                    ->pluck('group_name');

                if ($activeGroup) {
                    $selectedOrgRosterMembers = OrganizationMember::where('organization_id', $selectedOrg->id)
                        ->where('group_name', $activeGroup)
                        ->latest()
                        ->get();
                }
            }

            return view('organization.index', compact(
                'user',
                'isEmployee',
                'isStaffView',
                'pendingApplications',
                'pendingPackages',
                'recognizedOrgs',
                'selectedOrg',
                'selectedOrgPackages',
                'selectedOrgRosterGroups',
                'selectedOrgRosterMembers',
                'activeGroup'
            ));
        }

        // =================================================================
        // 2. PATIENT MULTI-ORGANIZATION SELF-SERVICE VIEW
        // =================================================================
        $userOrganizations = Organization::where('user_id', $user->id)->latest()->get();
        
        $selectedOrgId = $request->query('org_id');
        $currentOrg = $selectedOrgId 
            ? $userOrganizations->firstWhere('id', $selectedOrgId) 
            : ($userOrganizations->firstWhere('status', 'approved') ?: $userOrganizations->first());

        $orgPackages = collect();
        $rosterGroups = collect();
        $rosterMembers = collect();
        $activeGroup = $request->query('group');

        if ($currentOrg) {
            // Patient view retrieves all active, pending, and declined packages (excluding soft-deleted/archived ones)
            $orgPackages = Package::where('organization_id', $currentOrg->id)
                ->with('services')
                ->orderBy('name')
                ->get();

            $rosterGroups = OrganizationMember::where('organization_id', $currentOrg->id)
                ->select('group_name')
                ->distinct()
                ->pluck('group_name');

            if ($activeGroup) {
                $rosterMembers = OrganizationMember::where('organization_id', $currentOrg->id)
                    ->where('group_name', $activeGroup)
                    ->latest()
                    ->get();
            }
        }

        $availableServices = Service::where('is_available', true)->orderBy('name')->get();

        return view('organization.index', compact(
            'user',
            'isEmployee',
            'isStaffView',
            'userOrganizations',
            'currentOrg',
            'orgPackages',
            'rosterGroups',
            'activeGroup',
            'rosterMembers',
            'availableServices'
        ));
    }

    // =========================================================================
    // ORGANIZATION PARTNERSHIP LIFECYCLE (REPRESENTATIVE & STAFF ACTIONS)
    // =========================================================================

    /**
     * Patient: Submit a new application to become a Recognized Organization.
     * Only the organization name is required; the authenticated user is automatically bound as the authorized representative.
     */
    public function apply(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'organization_name' => 'required|string|max:150',
        ]);

        $orgName = strtoupper(trim($request->organization_name));

        $org = Organization::create([
            'user_id' => $user->id,
            'name' => $orgName,
            'representative_name' => $user->name,
            'representative_phone' => $user->phone ?: '09000000000',
            'status' => 'pending',
        ]);

        ActivityLog::record(
            'ORGANIZATION APPLICATION',
            "User {$user->name} applied for Recognized Organization partnership: {$orgName}",
            $user->name
        );

        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($internalStaff as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'New Organization Application',
                'message' => "{$orgName} has requested Recognized Organization partnership.",
                'url' => route('organization.index'),
                'type' => 'info',
            ]));
            event(new NotificationSent($staff->id, 'New Organization Application', "{$orgName} has requested Recognized Organization partnership."));
        }

        event(new QueueUpdated());

        return back()->with('success', "Application for {$orgName} submitted successfully for clinic verification.");
    }

    /**
     * Representative: Submit name change request (Subject to staff approval).
     */
    public function requestNameChange(Request $request, Organization $organization)
    {
        if ($organization->user_id !== Auth::id()) abort(403);

        $request->validate([
            'new_organization_name' => 'required|string|max:150',
        ]);

        $newName = strtoupper(trim($request->new_organization_name));

        $organization->update([
            'pending_name' => $newName,
        ]);

        ActivityLog::record(
            'ORGANIZATION NAME EDIT REQUEST',
            "Representative {$organization->representative_name} requested name change for {$organization->name} to: {$newName}",
            Auth::user()->name
        );

        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($internalStaff as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Organization Name Change Request',
                'message' => "{$organization->name} requested to change their official name to '{$newName}'.",
                'url' => route('organization.index'),
                'type' => 'info',
            ]));
        }

        event(new QueueUpdated());

        return back()->with('success', "Name change request for '{$newName}' submitted. Awaiting clinic staff approval.");
    }

    /**
     * Staff/Admin: Approve organization name change.
     */
    public function approveNameChange(Request $request, Organization $organization)
    {
        if (Gate::denies('isStaff')) abort(403);

        if (!$organization->pending_name) {
            return back()->with('error', 'No pending name change found for this organization.');
        }

        $oldName = $organization->name;
        $newName = $organization->pending_name;

        $organization->update([
            'name' => $newName,
            'pending_name' => null,
        ]);

        ActivityLog::record(
            'ORGANIZATION NAME APPROVED',
            "Staff approved name change from '{$oldName}' to '{$newName}'",
            Auth::user()->name
        );

        $organization->user->notify(new AppointmentNotification([
            'title' => 'Organization Name Change Approved',
            'message' => "Your organization name has been officially updated to '{$newName}'.",
            'url' => route('organization.index', ['org_id' => $organization->id]),
            'type' => 'success',
        ]));

        event(new QueueUpdated());

        return back()->with('success', "Organization name updated to '{$newName}'.");
    }

    /**
     * Staff/Admin: Reject organization name change.
     */
    public function rejectNameChange(Request $request, Organization $organization)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = ($request->reason === 'Others') ? $request->custom_reason : $request->reason;
        $declinedName = $organization->pending_name;

        $organization->update(['pending_name' => null]);

        ActivityLog::record(
            'ORGANIZATION NAME REJECTED',
            "Staff declined name change to '{$declinedName}' for {$organization->name}. Reason: {$reasonText}",
            Auth::user()->name
        );

        $organization->user->notify(new AppointmentNotification([
            'title' => 'Organization Name Change Declined',
            'message' => "Your name change request to '{$declinedName}' was declined: \"{$reasonText}\"",
            'url' => route('organization.index', ['org_id' => $organization->id]),
            'type' => 'danger',
        ]));

        event(new QueueUpdated());

        return back()->with('success', "Name change request declined.");
    }

    /**
     * Representative: Voluntarily deactivate an organization.
     * Deactivation disables all packages and rosters.
     */
    public function deactivateByRep(Request $request, Organization $organization)
    {
        if ($organization->user_id !== Auth::id()) abort(403);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = $request->input('reason') ?: 'Voluntarily deactivated by authorized representative.';

        DB::beginTransaction();
        try {
            $organization->update([
                'status' => 'deactivated',
                'status_reason' => $reason,
            ]);

            // Disable all active packages belonging to this organization
            $organization->packages()->where('status', '!=', 'declined')->update([
                'is_available' => false,
                'status' => 'disabled',
            ]);

            ActivityLog::record(
                'ORGANIZATION DEACTIVATED BY REP',
                "Representative deactivated organization {$organization->name}. All packages disabled.",
                Auth::user()->name
            );

            DB::commit();
            event(new QueueUpdated());

            return back()->with('success', "Organization '{$organization->name}' has been deactivated. Packages and rosters are temporarily disabled.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error during deactivation: ' . $e->getMessage());
        }
    }

    /**
     * Representative: Request reactivation of a deactivated organization.
     */
    public function requestReactivation(Request $request, Organization $organization)
    {
        if ($organization->user_id !== Auth::id()) abort(403);

        $organization->update([
            'status' => 'pending_reactivation',
        ]);

        ActivityLog::record(
            'ORGANIZATION REACTIVATION REQUESTED',
            "Representative requested reactivation for {$organization->name}",
            Auth::user()->name
        );

        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($internalStaff as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Organization Reactivation Request',
                'message' => "{$organization->name} has requested partnership reactivation.",
                'url' => route('organization.index'),
                'type' => 'info',
            ]));
        }

        event(new QueueUpdated());

        return back()->with('success', "Reactivation request submitted for '{$organization->name}'. Awaiting clinic review.");
    }

    /**
     * Staff/Admin: Approve partnership application or reactivation request.
     */
    public function approveOrganization(Request $request, Organization $organization)
    {
        if (Gate::denies('isStaff')) abort(403);

        $wasReactivation = ($organization->status === 'pending_reactivation' || $organization->status === 'deactivated');

        $organization->update([
            'status' => 'approved',
            'status_reason' => null,
        ]);

        if ($wasReactivation) {
            // Re-enable previously disabled packages, avoiding declined ones
            $organization->packages()
                ->whereNull('deleted_at')
                ->where('status', '!=', 'declined')
                ->update(['is_available' => true, 'status' => 'approved']);
        }

        ActivityLog::record(
            'ORGANIZATION APPROVED',
            "Staff approved Recognized Organization status for {$organization->name}",
            Auth::user()->name
        );

        $organization->user->notify(new AppointmentNotification([
            'title' => 'Organization Approved',
            'message' => "Congratulations! {$organization->name} is now an active Recognized Organization.",
            'url' => route('organization.index', ['org_id' => $organization->id]),
            'type' => 'success',
        ]));

        event(new QueueUpdated());

        return back()->with('success', "Organization {$organization->name} has been approved.");
    }

    /**
     * Staff/Admin: Decline partnership application or reactivation request.
     */
    public function rejectOrganization(Request $request, Organization $organization)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = ($request->reason === 'Others') ? $request->custom_reason : $request->reason;
        $orgName = $organization->name;

        $organization->update([
            'status' => 'rejected',
            'status_reason' => $reasonText,
        ]);

        ActivityLog::record(
            'ORGANIZATION REJECTED',
            "Staff declined application for {$orgName}. Reason: {$reasonText}",
            Auth::user()->name
        );

        $organization->user->notify(new AppointmentNotification([
            'title' => 'Organization Application Update',
            'message' => "Your application for {$orgName} was declined: \"{$reasonText}\"",
            'url' => route('organization.index'),
            'type' => 'danger',
        ]));

        event(new QueueUpdated());

        return back()->with('success', "Organization application for {$orgName} has been declined.");
    }

    /**
     * Staff/Admin: Disable an active Recognized Organization with a required reason.
     */
    public function disableOrganization(Request $request, Organization $organization)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = ($request->reason === 'Others') ? $request->custom_reason : $request->reason;
        $orgName = $organization->name;

        DB::beginTransaction();
        try {
            $organization->update([
                'status' => 'deactivated',
                'status_reason' => $reasonText,
            ]);

            $organization->packages()->where('status', '!=', 'declined')->update([
                'is_available' => false,
                'status' => 'disabled',
            ]);

            ActivityLog::record(
                'ORGANIZATION DISABLED',
                "Staff disabled Recognized Organization status for {$orgName}. Reason: {$reasonText}",
                Auth::user()->name
            );

            $organization->user->notify(new AppointmentNotification([
                'title' => 'Organization Partnership Deactivated',
                'message' => "Your Recognized Organization status for {$orgName} has been deactivated. Reason: {$reasonText}",
                'url' => route('organization.index'),
                'type' => 'danger',
            ]));

            DB::commit();
            event(new QueueUpdated());

            return back()->with('success', "Organization {$orgName} has been deactivated.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error disabling organization: ' . $e->getMessage());
        }
    }

    /**
     * Patient / Representative: Delete a declined or unapproved organization application.
     */
    public function destroy(Organization $organization)
    {
        $user = Auth::user();
        if ($organization->user_id !== $user->id && !$user->isEmployee()) {
            abort(403, 'Unauthorized action.');
        }

        if ($organization->isApproved()) {
            return back()->with('error', 'Active approved organizations cannot be deleted directly. Please deactivate first.');
        }

        $orgName = $organization->name;
        $organization->delete();

        ActivityLog::record(
            'ORGANIZATION APPLICATION REMOVED',
            "User {$user->name} deleted organization application: {$orgName}",
            $user->name
        );

        event(new QueueUpdated());

        return redirect()->route('organization.index', ['view' => 'patient'])->with('success', "Organization application for '{$orgName}' has been removed.");
    }

    // =========================================================================
    // ORGANIZATION CUSTOM PACKAGES (PROPOSE, EDIT, TOGGLE, APPROVAL)
    // =========================================================================

    /**
     * Organization: Propose a new custom diagnostic package.
     */
    public function storePackage(Request $request)
    {
        $user = Auth::user();
        $orgId = $request->input('organization_id');
        $organization = Organization::where('id', $orgId)->where('user_id', $user->id)->first();

        if (!$organization && !$user->isEmployee()) {
            abort(403, 'Unauthorized organization package creation.');
        }

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'gender_restriction' => 'required|in:male,female,both',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
        ], [
            'service_ids.required' => 'At least one clinical test must be assigned to this package proposal.',
            'service_ids.min' => 'A custom package must include at least one diagnostic examination.',
        ]);

        $existing = Package::where('organization_id', $organization->id)
            ->where('name', $validated['name'])
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['name' => "A package named '{$validated['name']}' already exists for this organization."]);
        }

        $basePrice = Service::whereIn('id', $validated['service_ids'])->sum('price');

        $package = Package::create([
            'organization_id' => $organization->id,
            'name' => strtoupper(trim($validated['name'])),
            'price' => $basePrice,
            'description' => $validated['description'] ?? null,
            'preparation' => null,
            'estimated_time' => null,
            'gender_restriction' => $validated['gender_restriction'],
            'is_available' => false,
            'status' => 'pending',
            'status_reason' => null,
        ]);

        $package->services()->sync($validated['service_ids']);

        ActivityLog::record(
            'PACKAGE PROPOSED',
            "Organization {$organization->name} proposed custom package: {$package->name}",
            $user->name
        );

        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($internalStaff as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Custom Package Proposal',
                'message' => "{$organization->name} proposed package '{$package->name}' for review.",
                'url' => route('organization.index'),
                'type' => 'info',
            ]));
        }

        event(new QueueUpdated());

        return back()->with('success', "Custom package proposal '{$package->name}' submitted. Awaiting clinic staff fee approval.");
    }

    /**
     * Representative: Edit or resubmit their custom package proposal (Subject to staff reapproval).
     */
    public function updatePackageByRep(Request $request, Package $package)
    {
        $user = Auth::user();
        if ($package->organization?->user_id !== $user->id && !$user->isEmployee()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'gender_restriction' => 'required|in:male,female,both',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
        ]);

        $basePrice = Service::whereIn('id', $validated['service_ids'])->sum('price');
        $isResubmission = ($package->status === 'declined');

        $package->update([
            'name' => strtoupper(trim($validated['name'])),
            'price' => $basePrice,
            'description' => $validated['description'] ?? null,
            'gender_restriction' => $validated['gender_restriction'],
            'is_available' => false, // Resets to inactive pending re-evaluation
            'status' => 'pending',   // Resets status to pending review
            'status_reason' => null, // Clears previous decline reason
        ]);

        $package->services()->sync($validated['service_ids']);

        $actionTitle = $isResubmission ? 'PACKAGE RESUBMITTED' : 'PACKAGE MODIFIED';
        $logMessage = $isResubmission
            ? "Representative resubmitted custom package {$package->name} for clinic evaluation."
            : "Representative edited package {$package->name}. Status reset to pending re-approval.";

        ActivityLog::record($actionTitle, $logMessage, $user->name);

        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($internalStaff as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => $isResubmission ? 'Package Proposal Resubmitted' : 'Package Proposal Revised',
                'message' => "Package '{$package->name}' was " . ($isResubmission ? 'resubmitted' : 'modified') . " by {$package->organization?->name} and requires evaluation.",
                'url' => route('organization.index'),
                'type' => 'info',
            ]));
        }

        event(new QueueUpdated());

        $feedbackMsg = $isResubmission 
            ? "Package '{$package->name}' resubmitted successfully. Awaiting clinic staff fee evaluation."
            : "Package '{$package->name}' updated. Awaiting staff re-approval for pricing and duration.";

        return back()->with('success', $feedbackMsg);
    }

    /**
     * Representative/Staff: Toggle package availability.
     * Strictly prevents activating packages that are pending review or declined.
     */
    public function togglePackageAvailability(Package $package)
    {
        $user = Auth::user();
        if ($package->organization?->user_id !== $user->id && !$user->isEmployee()) {
            abort(403);
        }

        if (!$package->is_available && ($package->status === 'declined' || empty($package->estimated_time) || is_null($package->price) || $package->price <= 0)) {
            return back()->with('error', 'Unapproved or declined packages cannot be activated without medical staff fee approval.');
        }

        $newAvailability = !$package->is_available;
        $package->update([
            'is_available' => $newAvailability,
            'status' => $newAvailability ? 'approved' : 'disabled',
        ]);

        $status = $newAvailability ? 'enabled' : 'disabled';
        ActivityLog::record(
            'PACKAGE TOGGLED',
            "Package '{$package->name}' {$status}",
            $user->name
        );

        event(new QueueUpdated());

        return back()->with('success', "Package '{$package->name}' has been {$status}.");
    }

    /**
     * Staff/Admin: Approve custom package proposal and set price, duration, and preparation.
     */
    public function approvePackage(Request $request, Package $package)
    {
        if (Gate::denies('isStaff')) abort(403);

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'estimated_time' => 'required|integer|min:1',
            'preparation' => 'nullable|string|max:255',
        ]);

        $package->update([
            'price' => $validated['price'],
            'estimated_time' => $validated['estimated_time'],
            'preparation' => $validated['preparation'] ?? null,
            'is_available' => true,
            'status' => 'approved',
            'status_reason' => null,
        ]);

        ActivityLog::record(
            'PACKAGE APPROVED',
            "Clinic staff approved package {$package->name} for {$package->organization?->name} at ₱" . number_format($validated['price'], 2),
            Auth::user()->name
        );

        if ($package->organization && $package->organization->user) {
            $package->organization->user->notify(new AppointmentNotification([
                'title' => 'Custom Package Approved',
                'message' => "Your proposed diagnostic package '{$package->name}' was approved at ₱" . number_format($validated['price'], 2) . " and is now active for bulk booking.",
                'url' => route('organization.index', ['org_id' => $package->organization_id]),
                'type' => 'success',
            ]));
        }

        event(new QueueUpdated());

        return back()->with('success', "Package '{$package->name}' approved at ₱" . number_format($validated['price'], 2) . " and activated.");
    }

    /**
     * Staff/Admin: Disable an active custom package with a justification reason.
     */
    public function disablePackage(Request $request, Package $package)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = ($request->reason === 'Others') ? $request->custom_reason : $request->reason;
        $pkgName = $package->name;

        $package->update([
            'is_available' => false,
            'status' => 'disabled',
            'status_reason' => $reasonText,
        ]);

        ActivityLog::record(
            'PACKAGE DISABLED',
            "Clinic staff disabled package '{$pkgName}'. Reason: {$reasonText}",
            Auth::user()->name
        );

        if ($package->organization && $package->organization->user) {
            $package->organization->user->notify(new AppointmentNotification([
                'title' => 'Package Deactivated',
                'message' => "Your package '{$pkgName}' has been disabled by clinic staff. Reason: {$reasonText}",
                'url' => route('organization.index', ['org_id' => $package->organization_id]),
                'type' => 'warning',
            ]));
        }

        event(new QueueUpdated());

        return back()->with('success', "Package '{$pkgName}' has been disabled.");
    }

    /**
     * Staff/Admin: Decline a pending custom package proposal with a reason.
     * DOES NOT soft-delete so the patient can review the reason and resubmit.
     */
    public function rejectPackage(Request $request, Package $package)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = ($request->reason === 'Others') ? $request->custom_reason : $request->reason;
        $pkgName = $package->name;

        $package->update([
            'is_available' => false,
            'status' => 'declined',
            'status_reason' => $reasonText,
        ]);

        ActivityLog::record(
            'PACKAGE REJECTED',
            "Clinic staff declined package proposal '{$pkgName}'. Reason: {$reasonText}",
            Auth::user()->name
        );

        if ($package->organization && $package->organization->user) {
            $package->organization->user->notify(new AppointmentNotification([
                'title' => 'Custom Package Declined',
                'message' => "Your proposed package '{$pkgName}' was declined: \"{$reasonText}\"",
                'url' => route('organization.index', ['org_id' => $package->organization_id]),
                'type' => 'danger',
            ]));
        }

        event(new QueueUpdated());

        return back()->with('success', "Package '{$pkgName}' proposal was declined.");
    }

    /**
     * Archive/Delete a custom organization package (Soft-Delete).
     */
    public function destroyPackage(Package $package)
    {
        $user = Auth::user();
        if ($package->organization?->user_id !== $user->id && !$user->isEmployee()) {
            abort(403);
        }

        $pkgName = $package->name;
        $package->delete();

        ActivityLog::record('PACKAGE ARCHIVED', "Archived custom package: {$pkgName}", $user->name);
        event(new QueueUpdated());

        return back()->with('success', "Package '{$pkgName}' archived.");
    }

    /**
     * Reactivate an archived custom organization package (Staff/Employee action).
     */
    public function restorePackage($id)
    {
        $user = Auth::user();
        $package = Package::onlyTrashed()->findOrFail($id);

        if ($package->organization?->user_id !== $user->id && !$user->isEmployee()) {
            abort(403);
        }

        $package->restore();
        $package->update([
            'is_available' => false,
            'status' => 'disabled',
        ]);

        ActivityLog::record('PACKAGE RESTORED', "Restored archived package: {$package->name}", $user->name);
        event(new QueueUpdated());

        return back()->with('success', "Package '{$package->name}' restored.");
    }

    // =========================================================================
    // PRE-RECORDED ROSTER MANAGEMENT (PEOPLE CATALOGS)
    // =========================================================================

    /**
     * Store an individual pre-recorded roster member.
     * Enforces mandatory contact info (email & phone), intra-group uniqueness, and MANDATORY address.
     */
    public function storeRosterMember(Request $request): JsonResponse
    {
        $user = Auth::user();
        $orgId = $request->input('organization_id');
        $organization = Organization::where('id', $orgId)->where('user_id', $user->id)->first();

        if (!$organization && !$user->isEmployee()) {
            return response()->json(['error' => 'Unauthorized or non-existent organization.'], 403);
        }

        $eighteenYearsAgo = Carbon::now()->subYears(18)->toDateString();

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
        };

        // Contact info and address fields are strictly required for every member
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'group_name' => 'required|string|max:100',
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birthdate' => ['required', 'date', 'before_or_equal:' . $eighteenYearsAgo],
            'sex' => ['required', 'in:Male,Female'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['required', 'email', 'max:191'],
            'street' => ['required', 'string', 'max:150'],
            'barangay' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'default_package_id' => 'nullable|exists:packages,id',
            'default_service_ids' => 'nullable|array',
        ], [
            'birthdate.required' => 'Birthdate is required for all roster participants.',
            'birthdate.before_or_equal' => 'Roster participants must be at least 18 years of age.',
            'phone.required' => 'Contact number is required.',
            'phone.regex' => 'Phone numbers must be in the format 09XXXXXXXXX (11 digits).',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'province.required' => 'Province selection is required.',
            'city.required' => 'City / Municipality selection is required.',
            'barangay.required' => 'Barangay selection is required.',
            'street.required' => 'Street / House No. is required.',
        ]);

        $groupName = strtoupper(trim($validated['group_name']));
        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        $cleanEmail = strtolower(trim($request->email));

        // Intra-group uniqueness check: No duplicate phone in same roster group
        $phoneExistsInGroup = OrganizationMember::where('organization_id', $organization->id)
            ->where('group_name', $groupName)
            ->where('phone', $cleanPhone)
            ->exists();

        if ($phoneExistsInGroup) {
            return response()->json([
                'success' => false,
                'message' => "Contact number {$cleanPhone} is already assigned to a participant in group '{$groupName}'.",
            ], 422);
        }

        // Intra-group uniqueness check: No duplicate email in same roster group
        $emailExistsInGroup = OrganizationMember::where('organization_id', $organization->id)
            ->where('group_name', $groupName)
            ->where('email', $cleanEmail)
            ->exists();

        if ($emailExistsInGroup) {
            return response()->json([
                'success' => false,
                'message' => "Email address {$cleanEmail} is already assigned to a participant in group '{$groupName}'.",
            ], 422);
        }

        $member = OrganizationMember::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'group_name' => $groupName,
            'first_name' => mb_strtoupper(trim($request->first_name), 'UTF-8'),
            'middle_name' => $request->filled('middle_name') && mb_strtoupper(trim($request->middle_name), 'UTF-8') !== 'N/A'
                ? mb_strtoupper(trim($request->middle_name), 'UTF-8')
                : 'N/A',
            'last_name' => mb_strtoupper(trim($request->last_name), 'UTF-8'),
            'suffix' => $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : null,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'phone' => $cleanPhone,
            'email' => $cleanEmail,
            'street' => mb_strtoupper(trim($request->street), 'UTF-8'),
            'barangay' => mb_strtoupper(trim($request->barangay), 'UTF-8'),
            'city' => mb_strtoupper(trim($request->city), 'UTF-8'),
            'province' => mb_strtoupper(trim($request->province), 'UTF-8'),
            'default_package_id' => null,
            'default_service_ids' => [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Roster member successfully recorded.',
            'member' => $member,
        ], 201);
    }

    /**
     * Batch-save compiled table entries as a permanent organization roster.
     */
    public function storeRosterBatch(Request $request): JsonResponse
    {
        $user = Auth::user();
        $orgId = $request->input('organization_id');
        $organization = Organization::where('id', $orgId)->where('user_id', $user->id)->first();

        if (!$organization && !$user->isEmployee()) {
            return response()->json(['error' => 'Unauthorized or non-existent organization.'], 403);
        }

        $eighteenYearsAgo = Carbon::now()->subYears(18)->toDateString();

        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'group_name' => 'required|string|max:100',
            'members' => 'required|array|min:1',
            'members.*.first_name' => 'required|string|max:60',
            'members.*.last_name' => 'required|string|max:60',
            'members.*.birthdate' => ['required', 'date', 'before_or_equal:' . $eighteenYearsAgo],
            'members.*.sex' => 'required|in:Male,Female',
            'members.*.phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'members.*.email' => ['required', 'email', 'max:191'],
        ], [
            'members.*.birthdate.required' => 'Birthdate is required for every patient entry in the roster.',
            'members.*.birthdate.before_or_equal' => 'Every roster participant must be at least 18 years old.',
            'members.*.phone.required' => 'Contact number is required for all roster participants.',
            'members.*.phone.regex' => 'Contact numbers must start with 09 and contain 11 digits.',
            'members.*.email.required' => 'Email address is required for all roster participants.',
        ]);

        $groupName = strtoupper(trim($request->group_name));
        $savedCount = 0;

        // Intra-batch duplicate check
        $seenBatchPhones = [];
        $seenBatchEmails = [];

        foreach ($request->members as $idx => $m) {
            $num = $idx + 1;
            $cleanPhone = preg_replace('/[^0-9]/', '', $m['phone'] ?? '');
            $cleanEmail = strtolower(trim($m['email'] ?? ''));

            if (isset($seenBatchPhones[$cleanPhone])) {
                return response()->json(['error' => "Duplicate phone '{$cleanPhone}' detected on row {$num} (already on row {$seenBatchPhones[$cleanPhone]})."], 422);
            }
            $seenBatchPhones[$cleanPhone] = $num;

            if (isset($seenBatchEmails[$cleanEmail])) {
                return response()->json(['error' => "Duplicate email '{$cleanEmail}' detected on row {$num} (already on row {$seenBatchEmails[$cleanEmail]})."], 422);
            }
            $seenBatchEmails[$cleanEmail] = $num;
        }

        DB::beginTransaction();
        try {
            foreach ($request->members as $m) {
                $mName = !empty($m['middle_name']) && strtoupper(trim($m['middle_name'])) !== 'N/A'
                    ? strtoupper(trim($m['middle_name']))
                    : 'N/A';

                $cleanPhone = preg_replace('/[^0-9]/', '', $m['phone']);
                $cleanEmail = strtolower(trim($m['email']));

                OrganizationMember::create([
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                    'group_name' => $groupName,
                    'first_name' => strtoupper(trim($m['first_name'])),
                    'middle_name' => $mName,
                    'last_name' => strtoupper(trim($m['last_name'])),
                    'suffix' => !empty($m['suffix']) ? strtoupper(trim($m['suffix'])) : null,
                    'birthdate' => $m['birthdate'],
                    'sex' => $m['sex'],
                    'phone' => $cleanPhone,
                    'email' => $cleanEmail,
                    'street' => !empty($m['street']) ? strtoupper(trim($m['street'])) : null,
                    'barangay' => !empty($m['barangay']) ? strtoupper(trim($m['barangay'])) : null,
                    'city' => !empty($m['city']) ? strtoupper(trim($m['city'])) : null,
                    'province' => !empty($m['province']) ? strtoupper(trim($m['province'])) : null,
                    'default_package_id' => null,
                    'default_service_ids' => [],
                ]);
                $savedCount++;
            }
            DB::commit();

            ActivityLog::record(
                'ROSTER SAVED',
                "Organization {$organization->name} saved {$savedCount} member(s) into roster group '{$groupName}'",
                $user->name
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully registered {$savedCount} patient(s) into roster group '{$groupName}'.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Database error while saving roster: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing roster member.
     * Enforces mandatory contact info (email & phone), intra-group uniqueness, and MANDATORY address.
     */
    public function updateRosterMember(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $member = OrganizationMember::where('user_id', $user->id)->findOrFail($id);
        $eighteenYearsAgo = Carbon::now()->subYears(18)->toDateString();

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
        };

        $request->validate([
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birthdate' => ['required', 'date', 'before_or_equal:' . $eighteenYearsAgo],
            'sex' => ['required', 'in:Male,Female'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['required', 'email', 'max:191'],
            'street' => ['required', 'string', 'max:150'],
            'barangay' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
        ], [
            'birthdate.required' => 'Birthdate is required.',
            'birthdate.before_or_equal' => 'Participant must be at least 18 years of age.',
            'phone.required' => 'Contact number is required.',
            'phone.regex' => 'Phone numbers must be in the format 09XXXXXXXXX (11 digits).',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'province.required' => 'Province selection is required.',
            'city.required' => 'City / Municipality selection is required.',
            'barangay.required' => 'Barangay selection is required.',
            'street.required' => 'Street / House No. is required.',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        $cleanEmail = strtolower(trim($request->email));

        // Intra-group uniqueness check (excluding current member)
        $phoneExistsInGroup = OrganizationMember::where('organization_id', $member->organization_id)
            ->where('group_name', $member->group_name)
            ->where('id', '!=', $member->id)
            ->where('phone', $cleanPhone)
            ->exists();

        if ($phoneExistsInGroup) {
            return response()->json([
                'success' => false,
                'message' => "Contact number {$cleanPhone} is already assigned to another participant in group '{$member->group_name}'.",
            ], 422);
        }

        $emailExistsInGroup = OrganizationMember::where('organization_id', $member->organization_id)
            ->where('group_name', $member->group_name)
            ->where('id', '!=', $member->id)
            ->where('email', $cleanEmail)
            ->exists();

        if ($emailExistsInGroup) {
            return response()->json([
                'success' => false,
                'message' => "Email address {$cleanEmail} is already assigned to another participant in group '{$member->group_name}'.",
            ], 422);
        }

        $member->update([
            'first_name' => mb_strtoupper(trim($request->first_name), 'UTF-8'),
            'middle_name' => $request->filled('middle_name') && mb_strtoupper(trim($request->middle_name), 'UTF-8') !== 'N/A'
                ? mb_strtoupper(trim($request->middle_name), 'UTF-8')
                : 'N/A',
            'last_name' => mb_strtoupper(trim($request->last_name), 'UTF-8'),
            'suffix' => $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : null,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'phone' => $cleanPhone,
            'email' => $cleanEmail,
            'street' => mb_strtoupper(trim($request->street), 'UTF-8'),
            'barangay' => mb_strtoupper(trim($request->barangay), 'UTF-8'),
            'city' => mb_strtoupper(trim($request->city), 'UTF-8'),
            'province' => mb_strtoupper(trim($request->province), 'UTF-8'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Roster member details updated.',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Delete an individual roster member.
     */
    public function destroyRosterMember($id): JsonResponse
    {
        $user = Auth::user();
        $member = OrganizationMember::where('user_id', $user->id)->findOrFail($id);
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Roster member removed.',
        ]);
    }

    /**
     * Update/Rename an entire roster group category.
     */
    public function updateRosterGroup(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'old_group_name' => 'required|string|max:100',
            'new_group_name' => 'required|string|max:100',
        ]);

        $oldGroup = strtoupper(trim($request->old_group_name));
        $newGroup = strtoupper(trim($request->new_group_name));

        OrganizationMember::where('user_id', $user->id)
            ->where('group_name', $oldGroup)
            ->update(['group_name' => $newGroup]);

        ActivityLog::record(
            'ROSTER GROUP RENAMED',
            "Renamed roster group '{$oldGroup}' to '{$newGroup}'",
            $user->name
        );

        return back()->with('success', "Roster group renamed to '{$newGroup}'.");
    }

    /**
     * Delete an entire roster group and its associated members.
     */
    public function destroyRosterGroup(Request $request)
    {
        $user = Auth::user();
        $request->validate(['group_name' => 'required|string']);

        $groupName = $request->group_name;
        $deleted = OrganizationMember::where('user_id', $user->id)
            ->where('group_name', $groupName)
            ->delete();

        ActivityLog::record(
            'ROSTER GROUP DELETED',
            "Deleted roster group '{$groupName}' ({$deleted} members removed)",
            $user->name
        );

        return back()->with('success', "Roster group '{$groupName}' and its members were removed.");
    }
}