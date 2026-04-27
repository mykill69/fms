<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        $roles = User::getRoles();
        $availablePages = User::AVAILABLE_PAGES;
        $offices = Office::orderBy('office_name', 'asc')->get();
        return view('admin.users.index', compact('users', 'roles', 'availablePages', 'offices'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'department' => 'nullable|string|max:255',
                'password' => 'required|string|min:6',
                'role' => ['required', Rule::in(array_keys(User::getRoles()))],
                'status' => 'required|in:active,inactive',
                'access_permissions' => 'nullable|array'
            ]);

            $validated['password'] = Hash::make($validated['password']);
            
            if ($validated['role'] === User::ROLE_SUPER_ADMIN) {
                $validated['access_permissions'] = null;
            } else {
                $validated['access_permissions'] = $request->access_permissions ?? [];
            }
            
            User::create($validated);

            return response()->json([
                'success' => true, 
                'message' => 'User created successfully'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'department' => 'nullable|string|max:255',
                'role' => ['required', Rule::in(array_keys(User::getRoles()))],
                'status' => 'required|in:active,inactive',
                'access_permissions' => 'nullable|array'
            ]);

            if ($request->filled('password')) {
                $validated['password'] = Hash::make($request->password);
            }

            if ($validated['role'] === User::ROLE_SUPER_ADMIN) {
                $validated['access_permissions'] = null;
            } else {
                $validated['access_permissions'] = $request->access_permissions ?? [];
            }

            $user->update($validated);

            return response()->json([
                'success' => true, 
                'message' => 'User updated successfully'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true, 
            'message' => 'User deleted successfully'
        ]);
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot deactivate your own account'
            ], 403);
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json([
            'success' => true, 
            'status' => $user->status,
            'message' => 'Status updated successfully'
        ]);
    }
}