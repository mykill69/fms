<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'You must be logged in to access this page.');
        }

        $user = Auth::user();
        
        $allowedRoles = ['super_admin', 'campus_administrator', 'office_head', 'director', 'vpaa', 'vpaf', 'president', 'quality_assurance'];
        
        if (!in_array($user->role, $allowedRoles)) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'You do not have permission to access the admin area.');
        }

        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account is inactive. Please contact administrator.');
        }

        $currentRoute = $request->route()->getName();
        $pagePermissionMap = [
    'admin.dashboard' => 'dashboard',
    'admin.dashboard.data' => 'dashboard',
    'admin.dashboard.poll' => 'dashboard',
    'admin.feedbacks' => 'feedbacks',
    'admin.feedback.show' => 'feedbacks',
    'admin.feedback.delete' => 'feedbacks',
    'admin.flagged.index' => 'flagged',
    'admin.flagged.resolve' => 'flagged',
    'admin.reports.index' => 'reports',
    'admin.reports.generate' => 'reports',
    'admin.reports.download' => 'reports',
    'admin.settings.index' => 'settings',
    'admin.settings.update' => 'settings',
    'admin.users.index' => 'user_management',
    'admin.users.store' => 'user_management',
    'admin.users.update' => 'user_management',
    'admin.users.destroy' => 'user_management',
    'admin.users.toggle-status' => 'user_management',
];

        if ($user->role !== 'super_admin' && isset($pagePermissionMap[$currentRoute])) {
            $requiredPage = $pagePermissionMap[$currentRoute];
            $permissions = $user->access_permissions;
            if (!is_array($permissions)) {
                $permissions = [];
            }
            if (!in_array($requiredPage, $permissions)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthorized access.'], 403);
                }
                abort(403, 'You do not have permission to access this page.');
            }
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
