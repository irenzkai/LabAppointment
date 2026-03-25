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