@extends('layouts.admin')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-2">Generate Report</h1>
    <p class="text-gray-600 mb-6">Filter and export feedback data</p>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form id="reportForm" method="POST" action="{{ route('admin.reports.generate') }}">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Date Range</label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-range="today" class="range-btn px-4 py-2 border rounded hover:bg-gray-100">Today</button>
                    <button type="button" data-range="week" class="range-btn px-4 py-2 border rounded hover:bg-gray-100">This Week</button>
                    <button type="button" data-range="month" class="range-btn px-4 py-2 border rounded bg-blue-600 text-white">This Month</button>
                    <button type="button" data-range="year" class="range-btn px-4 py-2 border rounded hover:bg-gray-100">This Year</button>
                    <button type="button" data-range="custom" class="range-btn px-4 py-2 border rounded hover:bg-gray-100">Custom</button>
                </div>
                <input type="hidden" name="date_range" id="date_range" value="month">
            </div>

            <div id="customDates" class="hidden mb-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Start Date</label>
                    <input type="date" name="start_date" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">End Date</label>
                    <input type="date" name="end_date" class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Department</label>
                    <select name="department" class="w-full border rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach($departments as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select name="type" class="w-full border rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <select name="role" class="w-full border rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach($roles as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Rating</label>
                    <select name="rating" class="w-full border rounded px-3 py-2">
                        <option value="">Any</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4+ Stars</option>
                        <option value="3">3+ Stars</option>
                        <option value="2">2+ Stars</option>
                        <option value="1">1+ Stars</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Generate Report
            </button>
        </form>
    </div>

    <div id="reportResult"></div>
</div>

<script>
document.querySelectorAll('.range-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.range-btn').forEach(b => {
            b.classList.remove('bg-blue-600', 'text-white');
            b.classList.add('hover:bg-gray-100');
        });
        this.classList.add('bg-blue-600', 'text-white');
        this.classList.remove('hover:bg-gray-100');
        
        document.getElementById('date_range').value = this.dataset.range;
        document.getElementById('customDates').classList.toggle('hidden', this.dataset.range !== 'custom');
    });
});

document.getElementById('reportForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const response = await fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    });
    
    document.getElementById('reportResult').innerHTML = await response.text();
});
</script>
@endsection