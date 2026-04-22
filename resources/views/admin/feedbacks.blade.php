@extends('layouts.admin')

@section('content')

<h2>📋 Feedback List</h2>

<table class="table table-hover mt-3">
    <thead>
        <tr>
            <th>ID</th>
            <th>Role</th>
            <th>Department</th>
            <th>Feedback</th>
            <th>Rating</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        @foreach($feedbacks as $fb)
        <tr>
            <td>{{ $fb->id }}</td>
            <td>{{ $fb->role }}</td>
            <td>{{ $fb->department }}</td>
            <td>{{ Str::limit($fb->feedback, 50) }}</td>
            <td>{{ $fb->rating }}</td>
            <td>
                <a class="btn btn-sm btn-primary" href="/admin/feedback/{{ $fb->id }}">View</a>

                <form method="POST" action="/admin/feedback/{{ $fb->id }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{ $feedbacks->links() }}

@endsection