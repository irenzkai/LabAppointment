<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     * Intercepts users who have an administrative "force password reset" flag active [102].
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If the user is logged in and flagged as requiring a password change [102]
        if ($user && $user->password_change_required) {
            // Bypass redirection for profile and logout routes so they can actually change it or exit [19, 505]
            if (! $request->routeIs('profile.edit', 'profile.update', 'profile.password.update', 'logout')) {
                return redirect()->to(route('profile.edit') . '#tab-password')
                    ->with('error', 'Administrative Security Protocol: You are required to update your temporary password before accessing clinical folders.');
            }
        }

        return $next($request);
    }
}