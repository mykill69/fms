<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FeedbackReportExport;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    public function index()
    {
        $query = Feedback::distinct();
        $query = $this->applyDepartmentFilter($query);
        
        $departments = $query->pluck('department')->filter()->values();
        $roles = Feedback::distinct()->pluck('role')->filter()->values();
        $types = Feedback::distinct()->pluck('type')->filter()->values();
        
        return view('admin.reports.index', compact('departments', 'roles', 'types'));
    }

    public function generate(Request $request)
    {
        $rules = [
            'date_range' => 'required|in:today,week,month,year,custom',
            'department' => 'nullable|string',
            'type' => 'nullable|string',
            'role' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5'
        ];
        
        if ($request->date_range === 'custom') {
            $rules['start_date'] = 'required|date_format:Y-m-d';
            $rules['end_date'] = 'required|date_format:Y-m-d|after_or_equal:start_date';
        }
        
        $request->validate($rules);

        $dates = $this->getDateRange($request);
        
        $query = Feedback::whereBetween('created_at', [$dates['start'], $dates['end']]);
        $query = $this->applyDepartmentFilter($query);
        
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        
        $feedbacks = $query->orderBy('created_at', 'desc')->get();
        
        $stats = [
            'total' => $feedbacks->count(),
            'avg_rating' => round($feedbacks->avg('rating') ?? 0, 2),
            'positive' => $feedbacks->where('rating', '>=', 4)->count(),
            'neutral' => $feedbacks->where('rating', 3)->count(),
            'negative' => $feedbacks->where('rating', '<=', 2)->count(),
        ];
        
        $reportData = [
            'feedbacks' => $feedbacks,
            'stats' => $stats,
            'filters' => $request->only(['department', 'type', 'role', 'rating']),
            'date_range' => $dates,
            'generated_at' => now()->toDateTimeString()
        ];
        
        session(['report_data' => $reportData]);
        
        if ($request->ajax()) {
            return view('admin.reports.result', $reportData);
        }
        
        return view('admin.reports.result', $reportData);
    }

    private function applyDepartmentFilter($query)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $query;
        }
        
        if ($user->role === 'super_admin' || $user->role === 'quality_assurance') {
            return $query;
        }
        
        if ($user->department) {
            return $query->where('department', $user->department);
        }
        
        return $query;
    }

    private function getDateRange(Request $request): array
    {
        $now = Carbon::now();
        
        switch ($request->date_range) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                    'label' => 'Today (' . $now->format('M d, Y') . ')'
                ];
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek(),
                    'label' => 'This Week (' . $now->startOfWeek()->format('M d') . ' - ' . $now->endOfWeek()->format('M d, Y') . ')'
                ];
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'label' => 'This Month (' . $now->format('F Y') . ')'
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                    'label' => 'This Year (' . $now->year . ')'
                ];
            case 'custom':
                return [
                    'start' => Carbon::parse($request->start_date)->startOfDay(),
                    'end' => Carbon::parse($request->end_date)->endOfDay(),
                    'label' => Carbon::parse($request->start_date)->format('M d, Y') . ' - ' . Carbon::parse($request->end_date)->format('M d, Y')
                ];
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'label' => 'This Month (' . $now->format('F Y') . ')'
                ];
        }
    }

    public function download(Request $request)
    {
        $format = $request->get('format', 'pdf');
        $data = session('report_data', []);
        
        if (empty($data)) {
            return back()->with('error', 'No report data. Please generate a report first.');
        }
        
        if ($format === 'excel') {
            return Excel::download(new FeedbackReportExport($data), 'feedback-report-' . date('Y-m-d') . '.xlsx');
        }
        
        if ($format === 'csv') {
            return $this->exportCsv($data);
        }
        
        $pdf = Pdf::loadView('admin.reports.pdf', ['data' => $data]);
        return $pdf->download('feedback-report-' . date('Y-m-d') . '.pdf');
    }

    private function exportCsv(array $data)
    {
        $feedbacks = $data['feedbacks'] ?? collect();
        
        $callback = function() use ($feedbacks) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Name', 'Role', 'Department', 'Type', 'Rating', 'Feedback']);
            
            foreach ($feedbacks as $f) {
                fputcsv($file, [
                    $f->created_at->format('Y-m-d H:i'),
                    $f->name,
                    $f->role,
                    $f->department,
                    $f->type,
                    $f->rating,
                    $f->feedback
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="feedback-report-' . date('Y-m-d') . '.csv"',
        ]);
    }
}