<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role;
        $roleHierarchy = [
            'super_admin' => 4, 
            'admin' => 3, 
            'manager' => 2, 
            'viewer' => 1
        ];
        
        foreach ($roles as $role) {
            if (isset($roleHierarchy[$userRole]) && 
                isset($roleHierarchy[$role]) && 
                $roleHierarchy[$userRole] >= $roleHierarchy[$role]) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access.');
    }
}