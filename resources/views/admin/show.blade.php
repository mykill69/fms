<h2>Feedback Details</h2>

<p><b>Role:</b> {{ $feedback->role }}</p>
<p><b>Department:</b> {{ $feedback->department }}</p>
<p><b>Feedback:</b> {{ $feedback->feedback }}</p>
<p><b>Rating:</b> {{ $feedback->rating }}</p>

<hr>

<h3>🧠 AI Analysis</h3>

@if($feedback->analysis)
    <p><b>Keywords:</b> {{ $feedback->analysis->keyword_summary }}</p>
    <p><b>Sentiment:</b> {{ $feedback->analysis->sentiment_summary }}</p>
    <p><b>Problem:</b> {{ $feedback->analysis->problem_detected }}</p>
    <p><b>Recommendation:</b> {{ $feedback->analysis->recommendation }}</p>
@else
    <p>No AI analysis available.</p>
@endif