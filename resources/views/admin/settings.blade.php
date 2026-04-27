@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-3xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Settings</h1>
        <p class="text-gray-600 dark:text-gray-400">System configuration and preferences</p>
    </div>

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">General Settings</h2>
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">System Name</label>
                        <input type="text" name="system_name" value="CPSU Feedback System" 
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Feedback Cooldown (seconds)</label>
                        <input type="number" value="30" 
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Maintenance Mode</p>
                            <p class="text-xs text-gray-400">Disable feedback submissions temporarily</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="maintenance_mode" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>
                    <div class="pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition shadow-md">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">System Information</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-400">Version</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300">1.1.0</p>
                </div>
                <div>
                    <p class="text-gray-400">Environment</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ app()->environment() }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Total Feedbacks</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ \App\Models\Feedback::count() }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Total Users</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ \App\Models\User::count() }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Last Updated</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300">
                        @php
                            $lastFeedback = \App\Models\Feedback::orderBy('updated_at', 'desc')->first();
                        @endphp
                        {{ $lastFeedback ? $lastFeedback->updated_at->format('M d, Y h:i A') : 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-400">Cache Driver</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ config('cache.default') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection