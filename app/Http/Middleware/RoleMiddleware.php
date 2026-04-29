<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // If user is not logged in OR their role is NOT in the list provided in web.php
        if (!Auth::check() || !in_array(Auth::user()->role, $roles)) {
            abort(403, 'Access Denied: Your role does not have permission for this area.');
        }

        return $next($request);
    }
}