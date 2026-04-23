<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportsController; // Updated namespace

// USER SIDE
Route::get('/', function () {
    return redirect('/feedback');
});

Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

// ADMIN SIDE
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/dashboard/data', [AdminController::class, 'dashboardData']);
    Route::get('/dashboard/poll', [AdminController::class, 'pollUpdates']);
    Route::get('/feedbacks', [AdminController::class, 'feedbacks']);
    Route::get('/feedback/{id}', [AdminController::class, 'show']);
    Route::delete('/feedback/{id}', [AdminController::class, 'delete']);
    Route::get('/test-ollama', [AdminController::class, 'testOllama']);
    
   Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports.index');
    Route::post('/reports/generate', [ReportsController::class, 'generate'])->name('admin.reports.generate');
    Route::get('/reports/download', [ReportsController::class, 'download'])->name('admin.reports.download');
});