<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\FeedbackAnalysis;
use App\Services\OllamaService;
use App\Models\Office;

use Illuminate\Support\Facades\Cache;

class FeedbackController extends Controller
{
    public function create()
{
    $offices = Office::orderBy('office_name', 'asc')->get();

    return view('feedback', compact('offices'));
}
    public function store(Request $request)
{
    $request->validate([
        'role' => 'required',
        'department' => 'required',
        'feedback' => 'required',
        'type' => 'required',
        'rating' => 'nullable|integer|min:0|max:5'
    ]);

    Feedback::create([
        'name' => null,
        'role' => $request->role,
        'department' => $request->department,
        'feedback' => $request->feedback,
        'rating' => (int) $request->rating,
        'type' => $request->type,
    ]);


    return back()->with('success', 'Feedback submitted successfully');
}
}
