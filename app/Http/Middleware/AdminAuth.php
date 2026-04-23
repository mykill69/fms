<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'You must be logged in to access this page.');
        }

        // Optional: Check if user has a valid role for admin access
        $user = Auth::user();
        $allowedRoles = ['super_admin', 'admin', 'manager', 'viewer'];
        
        if (!in_array($user->role, $allowedRoles)) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'You do not have permission to access the admin area.');
        }

        // Check account status
        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account is inactive. Please contact administrator.');
        }

        $response = $next($request);

        // Prevent caching for security
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}