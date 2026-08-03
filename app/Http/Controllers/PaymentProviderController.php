<?php

namespace App\Http\Controllers;

use App\Models\PaymentProvider;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PaymentProviderController extends Controller
{
    /**
     * Display your active and archived payment gateways [48].
     */
    public function index()
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $providers = PaymentProvider::latest()->get(); // Soft-deleted providers are automatically excluded [102]

        // Fetch archived (soft-deleted) gateways for on-page reactivation [15, 102]
        $archivedProviders = PaymentProvider::onlyTrashed()->latest()->get();

        return view('admin.payment-providers.index', compact('providers', 'archivedProviders'));
    }

    /**
     * Store new payment gateway [48].
     */
    public function store(Request $request)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:payment_providers,name',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'qr_code' => 'required|image|mimes:png,jpg,jpeg|max:5120',
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');

        $data = ['name' => strtoupper($request->name)];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('providers/logos', 'public');
        }

        if ($request->hasFile('qr_code')) {
            $data['qr_code'] = $request->file('qr_code')->store('providers/qrs', 'public');
        }

        $provider = PaymentProvider::create($data);

        // Record audit trail
        ActivityLog::record(
            'PAYMENT GATEWAY CONFIGURATION',
            "Created gateway: {$provider->name}. Reason: {$reasonText}",
            'PAYMENT GATEWAY',
            null
        );

        return back()->with('success', 'Payment provider successfully configured.');
    }

    /**
     * Update gateway details [48].
     */
    public function update(Request $request, PaymentProvider $provider)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:payment_providers,name,' . $provider->id,
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'qr_code' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');

        $data = ['name' => strtoupper($request->name)];

        if ($request->hasFile('logo')) {
            if ($provider->logo) {
                Storage::disk('public')->delete($provider->logo);
            }
            $data['logo'] = $request->file('logo')->store('providers/logos', 'public');
        }

        if ($request->hasFile('qr_code')) {
            if ($provider->qr_code) {
                Storage::disk('public')->delete($provider->qr_code);
            }
            $data['qr_code'] = $request->file('qr_code')->store('providers/qrs', 'public');
        }

        $provider->update($data);

        // Record audit trail
        ActivityLog::record(
            'PAYMENT GATEWAY MODIFICATION',
            "Updated parameters for gateway: {$provider->name}. Reason: {$reasonText}",
            'PAYMENT GATEWAY',
            null
        );

        return back()->with('success', "Payment provider details for {$provider->name} successfully updated.");
    }

    /**
     * Toggle gateway state [49].
     */
    public function toggle(Request $request, PaymentProvider $provider)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');

        $provider->update(['is_active' => !$provider->is_active]);
        $status = $provider->is_active ? 'ENABLED' : 'DISABLED';

        // Record audit trail
        ActivityLog::record(
            "PAYMENT GATEWAY {$status}",
            "Toggled state to {$status} for gateway: {$provider->name}. Reason: {$reasonText}",
            'PAYMENT GATEWAY',
            null
        );

        return back()->with('success', "Payment gateway for {$provider->name} is now {$status}.");
    }

    /**
     * Archive Payment Gateway (Soft-Deletes the record) [49, 102].
     */
    public function destroy(Request $request, PaymentProvider $provider)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');

        $provider->delete(); // Soft-deletes the record

        // Record audit trail
        ActivityLog::record(
            'PAYMENT GATEWAY DEACTIVATION',
            "Archived gateway: {$provider->name}. Reason: {$reasonText}",
            'PAYMENT GATEWAY',
            null
        );

        return back()->with('success', 'Payment provider successfully moved to archives.');
    }

    /**
     * Reactivate an archived payment gateway (Reverses Soft-Delete) [102].
     */
    public function restore(Request $request, $id)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $reasonText = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');

        // Find the soft-deleted record and restore it [15, 102]
        $provider = PaymentProvider::onlyTrashed()->findOrFail($id);
        $provider->restore();

        // Record audit trail
        ActivityLog::record(
            'PAYMENT GATEWAY RECONSTRUCTION',
            "Reactivated gateway: {$provider->name}. Reason: {$reasonText}",
            'PAYMENT GATEWAY',
            null
        );

        return back()->with('success', "Payment gateway {$provider->name} has been successfully reactivated.");
    }
}