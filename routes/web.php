<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;

Route::get('/', function () {
    return redirect('/feedback');
});

// Public routes
Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin routes
Route::group(['prefix' => 'admin', 'middleware' => ['admin.auth']], function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard/data', [AdminController::class, 'dashboardData']);
    Route::get('/dashboard/poll', [AdminController::class, 'pollUpdates']);
    
    // Feedbacks
    Route::get('/feedbacks', [AdminController::class, 'feedbacks'])->name('admin.feedbacks');
    Route::get('/feedback/{id}', [AdminController::class, 'show'])->name('admin.feedback.show');
    Route::delete('/feedback/{id}', [AdminController::class, 'delete'])->name('admin.feedback.delete');
    
    
    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports.index');
    Route::post('/reports/generate', [ReportsController::class, 'generate'])->name('admin.reports.generate');
    Route::get('/reports/download', [ReportsController::class, 'download'])->name('admin.reports.download');
    
    // User Management
    Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
    Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('admin.users.toggle-status');
});