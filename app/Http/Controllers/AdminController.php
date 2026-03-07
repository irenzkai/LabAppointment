<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // View all users
    public function index() {
        $users = User::where('id', '!=', auth()->id())->get(); // hide current admin
        return view('admin.users', compact('users'));
    }

    // Promote to Staff
    public function promote($id) {
        User::where('id', $id)->update(['role' => 'staff']);
        return back()->with('success', 'User promoted to Staff.');
    }

    // Demote to User
    public function demote($id) {
        User::where('id', $id)->update(['role' => 'user']);
        return back()->with('success', 'Staff demoted to User.');
    }

    // Admin can delete any account
    public function destroy($id) {
        User::destroy($id);
        return back()->with('success', 'Account deleted.');
    }
}