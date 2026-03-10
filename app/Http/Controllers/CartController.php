<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index() {
        $cart = session()->get('cart', []);
        // Get the actual service models based on IDs in the cart
        $services = Service::whereIn('id', array_keys($cart))->get();
        
        $totalPrice = $services->sum('price');
        
        return view('cart.index', compact('services', 'totalPrice'));
    }

    public function add(Service $service) {
        // Gender Check
        if (Auth::check() && $service->gender_restriction !== 'both') {
            if (Auth::user()->sex !== $service->gender_restriction) {
                return back()->with('error', "This test is only available for " . $service->gender_restriction . " patients.");
            }
        }

        $cart = session()->get('cart', []);
        
        // Add to cart if not already there
        if (!isset($cart[$service->id])) {
            $cart[$service->id] = true;
            session()->put('cart', $cart);
            return back()->with('success', $service->name . ' added to your list.');
        }

        return back()->with('info', $service->name . ' is already in your list.');
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