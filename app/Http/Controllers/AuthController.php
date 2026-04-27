<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'super_admin') {
                return redirect()->route('admin.dashboard');
            }
            $permissions = $user->access_permissions;
            if (!is_array($permissions)) {
                $permissions = [];
            }
            if (!empty($permissions)) {
                $firstPage = $permissions[0];
                return $this->redirectToPage($firstPage);
            }
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No account found with this email address.',
            ])->withInput($request->only('email'));
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'email' => 'Your account is inactive. Please contact the administrator.',
            ])->withInput($request->only('email'));
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Invalid password. Please try again.',
            ])->withInput($request->only('email'));
        }

        $allowedRoles = array_keys(User::getRoles());
        if (!in_array($user->role, $allowedRoles)) {
            return back()->withErrors([
                'email' => 'Your account does not have proper permissions.',
            ])->withInput($request->only('email'));
        }

        Auth::login($user, $request->boolean('remember'));

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        $request->session()->regenerate();

        if ($user->role === 'super_admin') {
            return redirect()->intended(route('admin.dashboard'));
        }

        $permissions = $user->access_permissions;
        if (!is_array($permissions)) {
            $permissions = [];
        }
        if (!empty($permissions)) {
            $firstPage = $permissions[0];
            return $this->redirectToPage($firstPage);
        }

        Auth::logout();
        return back()->withErrors([
            'email' => 'Your account has no pages assigned. Contact the administrator.',
        ])->withInput($request->only('email'));
    }

    private function redirectToPage($page)
    {
        $pageRoutes = [
            'dashboard' => 'admin.dashboard',
            'feedbacks' => 'admin.feedbacks',
            'reports' => 'admin.reports.index',
            'user_management' => 'admin.users.index',
        ];

        $routeName = isset($pageRoutes[$page]) ? $pageRoutes[$page] : 'admin.dashboard';
        return redirect()->route($routeName);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}