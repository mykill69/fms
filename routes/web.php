<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminController;


// USER SIDE
// Route::get('/feedback', [FeedbackController::class, 'create']);
// Route::post('/feedback', [FeedbackController::class, 'store']);

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
Route::get('/feedback', [FeedbackController::class, 'create']);
Route::post('/feedback', [FeedbackController::class, 'store']);
Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
Route::get('/admin/dashboard/data', [AdminController::class, 'dashboardData']);
Route::get('/admin/dashboard/poll', [AdminController::class, 'pollUpdates']);
Route::get('/admin/feedbacks', [AdminController::class, 'feedbacks']);
Route::get('/admin/feedback/{id}', [AdminController::class, 'show']);
Route::delete('/admin/feedback/{id}', [AdminController::class, 'delete']);

Route::get('/admin/test-ollama', [AdminController::class, 'testOllama']);