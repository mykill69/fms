<!DOCTYPE html>
<html>
<head>
    <title>Feedback Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f3f4f6; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { background: #f9fafb; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>Feedback Report</h1>
    <p><strong>{{ $data['date_range']['label'] }}</strong></p>
    <p>Generated: {{ $data['generated_at'] }}</p>
    
    <div class="stats">
        <div class="stat-box"><strong>Total:</strong> {{ $data['stats']['total'] }}</div>
        <div class="stat-box"><strong>Avg Rating:</strong> {{ number_format($data['stats']['avg_rating'], 1) }}/5</div>
        <div class="stat-box"><strong>Positive:</strong> {{ $data['stats']['positive'] }}</div>
        <div class="stat-box"><strong>Neutral:</strong> {{ $data['stats']['neutral'] }}</div>
        <div class="stat-box"><strong>Negative:</strong> {{ $data['stats']['negative'] }}</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Role</th>
                <th>Department</th>
                <th>Type</th>
                <th>Rating</th>
                <th>Feedback</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['feedbacks'] as $f)
            <tr>
                <td>{{ $f->created_at->format('M d, Y') }}</td>
                <td>Anonymous</td>
                <td>{{ $f->role }}</td>
                <td>{{ $f->department }}</td>
                <td>{{ $f->type }}</td>
                <td>{{ $f->rating }}</td>
                <td>{{ $f->feedback }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>