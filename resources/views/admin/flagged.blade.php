@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Flagged Feedbacks</h1>
        <p class="text-gray-600 dark:text-gray-400">Feedbacks requiring immediate attention</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs uppercase text-gray-400 font-medium">Critical (1 Star)</p>
            <p class="text-3xl font-bold text-red-500 mt-1">{{ $flaggedFeedbacks->where('rating', 1)->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs uppercase text-gray-400 font-medium">Warning (2 Stars)</p>
            <p class="text-3xl font-bold text-orange-500 mt-1">{{ $flaggedFeedbacks->where('rating', 2)->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs uppercase text-gray-400 font-medium">Total Flagged</p>
            <p class="text-3xl font-bold text-gray-700 dark:text-gray-200 mt-1">{{ $flaggedFeedbacks->total() }}</p>
        </div>
    </div>

    <div id="flaggedTableApp" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span x-show="filteredItems.length > 0">
                    Showing <span class="font-medium" x-text="((currentPage - 1) * pageSize) + 1"></span> 
                    to <span class="font-medium" x-text="Math.min(currentPage * pageSize, filteredItems.length)"></span> 
                    of <span class="font-medium" x-text="filteredItems.length"></span> records
                </span>
                <span x-show="filteredItems.length === 0">No records found</span>
            </div>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" x-model="search" @input="filterData()" placeholder="Search feedbacks..." 
                    class="pl-9 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 w-64">
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="inline-block min-w-full">
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th @click="sort('rating')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition select-none">
                                    <div class="flex items-center gap-1">Rating<i :class="getSortIcon('rating')"></i></div>
                                </th>
                                <th @click="sort('department')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition select-none">
                                    <div class="flex items-center gap-1">Department<i :class="getSortIcon('department')"></i></div>
                                </th>
                                <th @click="sort('type')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition select-none">
                                    <div class="flex items-center gap-1">Type<i :class="getSortIcon('type')"></i></div>
                                </th>
                                <th @click="sort('feedback')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition select-none">
                                    <div class="flex items-center gap-1">Feedback<i :class="getSortIcon('feedback')"></i></div>
                                </th>
                                <th @click="sort('timestamp')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition select-none">
                                    <div class="flex items-center gap-1">Date<i :class="getSortIcon('timestamp')"></i></div>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-right uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="item in paginatedItems" :key="item.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span :class="item.rating == 1 ? 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'">
                                            <span x-text="'★'.repeat(item.rating)"></span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="item.department"></p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500" x-text="item.role"></p>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300" x-text="item.type"></span>
                                    </td>
                                    <td class="px-5 py-4 max-w-xs">
                                        <p class="text-sm text-gray-600 dark:text-gray-300 truncate" x-text="item.feedback"></p>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="item.date"></td>
                                    <td class="px-5 py-4 whitespace-nowrap text-right">
                                        <button @click="resolveFlagged(item.id)" class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-xs font-medium hover:bg-green-600 transition">
                                            <i class="fas fa-check mr-1"></i> Resolve
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredItems.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                                    <p>No flagged feedback found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <select x-model="pageSize" @change="changePageSize()" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-emerald-500">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
            <div class="flex items-center gap-3">
                <p class="text-sm text-gray-600 dark:text-gray-400" x-show="filteredItems.length > 0">
                    <span x-text="((currentPage - 1) * pageSize) + 1"></span>-<span x-text="Math.min(currentPage * pageSize, filteredItems.length)"></span> of <span x-text="filteredItems.length"></span>
                </p>
                <div class="flex items-center gap-1">
                    <button @click="prevPage()" :disabled="currentPage === 1" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                    <template x-for="page in visiblePages" :key="page">
                        <button @click="goToPage(page)" :class="page === currentPage ? 'w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium bg-emerald-500 text-white' : 'w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition'">
                            <span x-text="page"></span>
                        </button>
                    </template>
                    <button @click="nextPage()" :disabled="currentPage === totalPages" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('flaggedTable', () => ({
        items: {!! json_encode($flaggedFeedbacks->map(function($f) {
            return [
                'id' => $f->id,
                'date' => $f->created_at->format('M d, Y'),
                'timestamp' => $f->created_at->timestamp,
                'department' => $f->department,
                'role' => $f->role,
                'type' => $f->type,
                'rating' => (int)$f->rating,
                'feedback' => Str::limit($f->feedback, 60),
                'fullFeedback' => $f->feedback
            ];
        })) !!},
        filteredItems: [],
        paginatedItems: [],
        search: '',
        sortField: 'timestamp',
        sortDirection: 'desc',
        currentPage: 1,
        pageSize: 10,
        totalPages: 1,

        init() {
            this.filteredItems = [...this.items];
            this.sortData();
            this.updatePagination();
        },
        sort(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.sortData();
            this.updatePagination();
        },
        getSortIcon(field) {
            if (this.sortField !== field) return 'fas fa-sort text-gray-400 text-xs';
            return this.sortDirection === 'asc' ? 'fas fa-sort-up text-emerald-500 text-xs' : 'fas fa-sort-down text-emerald-500 text-xs';
        },
        sortData() {
            var self = this;
            this.filteredItems.sort(function(a, b) {
                var aVal = a[self.sortField];
                var bVal = b[self.sortField];
                if (self.sortField === 'timestamp' || self.sortField === 'rating') {
                    aVal = Number(aVal);
                    bVal = Number(bVal);
                } else {
                    aVal = String(aVal).toLowerCase();
                    bVal = String(bVal).toLowerCase();
                }
                if (aVal < bVal) return self.sortDirection === 'asc' ? -1 : 1;
                if (aVal > bVal) return self.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
        },
        filterData() {
            var searchTerm = this.search.toLowerCase();
            var self = this;
            this.filteredItems = this.items.filter(function(item) {
                return item.department.toLowerCase().includes(searchTerm) ||
                       item.role.toLowerCase().includes(searchTerm) ||
                       item.type.toLowerCase().includes(searchTerm) ||
                       item.feedback.toLowerCase().includes(searchTerm) ||
                       item.fullFeedback.toLowerCase().includes(searchTerm);
            });
            this.sortData();
            this.currentPage = 1;
            this.updatePagination();
        },
        updatePagination() {
            this.totalPages = Math.ceil(this.filteredItems.length / this.pageSize) || 1;
            if (this.currentPage > this.totalPages) this.currentPage = 1;
            var start = (this.currentPage - 1) * this.pageSize;
            var end = start + this.pageSize;
            this.paginatedItems = this.filteredItems.slice(start, end);
        },
        changePageSize() { this.currentPage = 1; this.updatePagination(); },
        prevPage() { if (this.currentPage > 1) { this.currentPage--; this.updatePagination(); } },
        nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.updatePagination(); } },
        goToPage(page) { this.currentPage = page; this.updatePagination(); },
        get visiblePages() {
            var pages = [];
            var maxVisible = 5;
            var start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            var end = Math.min(this.totalPages, start + maxVisible - 1);
            if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1);
            for (var i = start; i <= end; i++) pages.push(i);
            return pages;
        },
        async resolveFlagged(id) {
            var result = await Swal.fire({
                title: 'Resolve Feedback?',
                text: 'This will mark the feedback as resolved and change rating to neutral.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, resolve it!'
            });
            if (result.isConfirmed) {
                try {
                    var response = await fetch('/admin/flagged/' + id + '/resolve', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    var data = await response.json();
                    if (data.success) {
                        Swal.fire('Resolved!', '', 'success');
                        setTimeout(function() { window.location.reload(); }, 1500);
                    }
                } catch (error) {
                    Swal.fire('Error!', 'Failed to resolve', 'error');
                }
            }
        }
    }));
});

document.getElementById('flaggedTableApp').setAttribute('x-data', 'flaggedTable');
</script>
@endsection