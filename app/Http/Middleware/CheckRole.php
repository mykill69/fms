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
            'super_admin' => 8,
            'president' => 7,
            'vpaa' => 6,
            'vpaf' => 5,
            'campus_administrator' => 4,
            'director' => 3,
            'quality_assurance' => 2,
            'office_head' => 1
        ];
        
        if ($userRole === 'super_admin') {
            return $next($request);
        }
        
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