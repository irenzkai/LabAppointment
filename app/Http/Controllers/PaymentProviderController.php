<?php

namespace App\Http\Controllers;

use App\Models\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PaymentProviderController extends Controller
{
    public function index()
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $providers = PaymentProvider::latest()->get();
        return view('admin.payment-providers.index', compact('providers'));
    }

    public function store(Request $request)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:payment_providers,name',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'qr_code' => 'required|image|mimes:png,jpg,jpeg|max:5120',
        ]);

        $data = ['name' => strtoupper($request->name)];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('providers/logos', 'public');
        }

        if ($request->hasFile('qr_code')) {
            $data['qr_code'] = $request->file('qr_code')->store('providers/qrs', 'public');
        }

        PaymentProvider::create($data);

        return back()->with('success', 'Payment provider successfully configured.');
    }

    /**
     * FIXED: Added update function to support gateway editing & document replacement
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
        ]);

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

        return back()->with('success', "Payment provider details for {$provider->name} successfully updated.");
    }

    public function toggle(PaymentProvider $provider)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $provider->update(['is_active' => !$provider->is_active]);
        $status = $provider->is_active ? 'ENABLED' : 'DISABLED';

        return back()->with('success', "Payment gateway for {$provider->name} is now {$status}.");
    }

    public function destroy(PaymentProvider $provider)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        if ($provider->logo) {
            Storage::disk('public')->delete($provider->logo);
        }
        Storage::disk('public')->delete($provider->qr_code);

        $provider->delete();

        return back()->with('success', 'Payment provider successfully removed.');
    }
}