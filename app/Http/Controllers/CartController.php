<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index() {
        $cart = session()->get('cart', []);
        $services = \App\Models\Service::whereIn('id', array_keys($cart))->get();

        $totalPrice = $services->sum('price');

        // --- SMART TIME CALCULATION ---
        
        // 1. Filter out physical samples (Blood, Urine, etc.)
        $physicalTests = $services->where('sample_required', '!=', 'N/A');
        
        // Group by sample type and take the maximum time for each group
        // Example: If 3 tests need Blood (5m, 3m, 4m), it only adds 5m to the total.
        $physicalTime = $physicalTests->groupBy('sample_required')
            ->map(function ($group) {
                return $group->max('estimated_time');
            })->sum();

        // 2. Filter out N/A (Procedures like X-Ray)
        // These are summed individually because you can't do two X-rays at once.
        $proceduralTime = $services->where('sample_required', '==', 'N/A')->sum('estimated_time');

        $totalMinutes = $physicalTime + $proceduralTime;

        return view('cart.index', compact('services', 'totalPrice', 'totalMinutes'));
    }

    public function add(Service $service) {
        $cart = session()->get('cart', []);
        
        // Store metadata so JS can validate without extra database hits
        $cart[$service->id] = [
            'name' => $service->name,
            'gender' => $service->gender_restriction, // 'male', 'female', or 'both'
        ];
        
        session()->put('cart', $cart);
        return back()->with('success', $service->name . ' added to list.');
    }

    public function remove($id) {
        $cart = session()->get('cart', []);
        if(isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }
        return back()->with('success', 'Removed from list.');
    }
}