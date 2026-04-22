<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminController;


// USER SIDE
Route::get('/', function () {
    return redirect('/feedback');
});

Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

// // ADMIN SIDE
// Route::get('/admin/dashboard/data', [AdminController::class, 'dashboardData']);
// Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
// Route::get('/admin/dashboard/live', [AdminController::class, 'dashboardLive']);
// Route::get('/admin/feedbacks', [AdminController::class, 'feedbacks']);
// Route::get('/admin/feedback/{id}', [AdminController::class, 'show']);
// Route::delete('/admin/feedback/{id}', [AdminController::class, 'delete']);
// Route::get('/admin/analysis', [AdminController::class, 'analysis']);

// Route::get('/admin/ai-insights', [AdminController::class, 'aiInsights']);

// USER SIDE
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/dashboard/data', [AdminController::class, 'dashboardData']);
    Route::get('/dashboard/poll', [AdminController::class, 'pollUpdates']);
    Route::get('/feedbacks', [AdminController::class, 'feedbacks']);
    Route::get('/feedback/{id}', [AdminController::class, 'show']);
    Route::delete('/feedback/{id}', [AdminController::class, 'delete']);
    Route::get('/test-ollama', [AdminController::class, 'testOllama']);
});