<div class="bg-white rounded-lg shadow" x-data="{
    items: {{ json_encode($feedbacks->map(function($f) {
        return [
            'date' => $f->created_at->format('M d, Y'),
            'timestamp' => $f->created_at->timestamp,
            'name' => $f->name ?? 'Anonymous',
            'role' => $f->role,
            'department' => $f->department,
            'type' => $f->type,
            'rating' => $f->rating,
            'feedback' => Str::limit($f->feedback, 60),
            'fullFeedback' => $f->feedback
        ];
    })) }},
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
        if (this.sortField !== field) {
            return 'fas fa-sort text-neutral-400 text-xs';
        }
        return this.sortDirection === 'asc' 
            ? 'fas fa-sort-up text-blue-600 text-xs' 
            : 'fas fa-sort-down text-blue-600 text-xs';
    },
    
    sortData() {
        this.filteredItems.sort((a, b) => {
            let aVal = a[this.sortField];
            let bVal = b[this.sortField];
            
            if (this.sortField === 'timestamp' || this.sortField === 'rating') {
                aVal = Number(aVal);
                bVal = Number(bVal);
            } else {
                aVal = String(aVal).toLowerCase();
                bVal = String(bVal).toLowerCase();
            }
            
            if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
    },
    
    filterData() {
        const searchTerm = this.search.toLowerCase();
        this.filteredItems = this.items.filter(item => {
            return item.name.toLowerCase().includes(searchTerm) ||
                   item.role.toLowerCase().includes(searchTerm) ||
                   item.department.toLowerCase().includes(searchTerm) ||
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
        if (this.currentPage > this.totalPages) {
            this.currentPage = 1;
        }
        
        const start = (this.currentPage - 1) * this.pageSize;
        const end = start + this.pageSize;
        this.paginatedItems = this.filteredItems.slice(start, end);
    },
    
    changePageSize() {
        this.currentPage = 1;
        this.updatePagination();
    },
    
    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.updatePagination();
        }
    },
    
    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.updatePagination();
        }
    },
    
    goToPage(page) {
        this.currentPage = page;
        this.updatePagination();
    },
    
    get visiblePages() {
        const pages = [];
        const maxVisible = 5;
        let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
        let end = Math.min(this.totalPages, start + maxVisible - 1);
        
        if (end - start + 1 < maxVisible) {
            start = Math.max(1, end - maxVisible + 1);
        }
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        return pages;
    }
}" x-init="init()">
    
    <div class="flex justify-between items-center p-6 pb-4 border-b border-neutral-200">
        <div>
            <h2 class="text-xl font-bold text-neutral-800">{{ $date_range['label'] }}</h2>
            <p class="text-neutral-500 text-sm">Generated: {{ $generated_at }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.reports.download', ['format' => 'pdf']) }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center gap-1">
                <i class="fas fa-file-pdf"></i>PDF
            </a>
            <a href="{{ route('admin.reports.download', ['format' => 'excel']) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-1">
                <i class="fas fa-file-excel"></i>Excel
            </a>
            <a href="{{ route('admin.reports.download', ['format' => 'csv']) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                <i class="fas fa-file-csv"></i>CSV
            </a>
            <button onclick="window.print()" class="bg-neutral-600 text-white px-4 py-2 rounded-lg hover:bg-neutral-700 transition flex items-center gap-1">
                <i class="fas fa-print"></i>Print
            </button>
        </div>
    </div>

    <div class="grid grid-cols-5 gap-4 p-6 pb-4">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
            <p class="text-sm text-blue-600 font-medium">Total</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg border border-green-100">
            <p class="text-sm text-green-600 font-medium">Avg Rating</p>
            <p class="text-3xl font-bold text-green-900">{{ number_format($stats['avg_rating'], 1) }}/5</p>
        </div>
        <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-100">
            <p class="text-sm text-emerald-600 font-medium">Positive</p>
            <p class="text-3xl font-bold text-emerald-900">{{ $stats['positive'] }}</p>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
            <p class="text-sm text-yellow-600 font-medium">Neutral</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $stats['neutral'] }}</p>
        </div>
        <div class="bg-red-50 p-4 rounded-lg border border-red-100">
            <p class="text-sm text-red-600 font-medium">Negative</p>
            <p class="text-3xl font-bold text-red-900">{{ $stats['negative'] }}</p>
        </div>
    </div>

    <div class="px-6 pb-4 flex justify-between items-center">
        <div class="text-sm text-neutral-600">
            Showing <span x-text="filteredItems.length" class="font-medium"></span> records
        </div>
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm"></i>
            <input type="text" x-model="search" @input="filterData()" placeholder="Search..." 
                class="pl-9 pr-3 py-2 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
        </div>
    </div>

    <div class="overflow-x-auto px-6 pb-4">
        <div class="inline-block min-w-full">
            <div class="overflow-hidden border border-neutral-200 rounded-lg">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr class="text-neutral-500">
                            <th @click="sort('timestamp')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider cursor-pointer hover:bg-neutral-100 transition select-none">
                                <div class="flex items-center gap-1">
                                    Date
                                    <i :class="getSortIcon('timestamp')"></i>
                                </div>
                            </th>
                            <th @click="sort('name')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider cursor-pointer hover:bg-neutral-100 transition select-none">
                                <div class="flex items-center gap-1">
                                    Name
                                    <i :class="getSortIcon('name')"></i>
                                </div>
                            </th>
                            <th @click="sort('role')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider cursor-pointer hover:bg-neutral-100 transition select-none">
                                <div class="flex items-center gap-1">
                                    Role
                                    <i :class="getSortIcon('role')"></i>
                                </div>
                            </th>
                            <th @click="sort('department')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider cursor-pointer hover:bg-neutral-100 transition select-none">
                                <div class="flex items-center gap-1">
                                    Department
                                    <i :class="getSortIcon('department')"></i>
                                </div>
                            </th>
                            <th @click="sort('type')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider cursor-pointer hover:bg-neutral-100 transition select-none">
                                <div class="flex items-center gap-1">
                                    Type
                                    <i :class="getSortIcon('type')"></i>
                                </div>
                            </th>
                            <th @click="sort('rating')" class="px-5 py-3 text-xs font-medium text-center uppercase tracking-wider cursor-pointer hover:bg-neutral-100 transition select-none">
                                <div class="flex items-center justify-center gap-1">
                                    Rating
                                    <i :class="getSortIcon('rating')"></i>
                                </div>
                            </th>
                            <th @click="sort('feedback')" class="px-5 py-3 text-xs font-medium text-left uppercase tracking-wider cursor-pointer hover:bg-neutral-100 transition select-none">
                                <div class="flex items-center gap-1">
                                    Feedback
                                    <i :class="getSortIcon('feedback')"></i>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white">
                        <template x-for="item in paginatedItems" :key="item.timestamp + '-' + item.name + '-' + Math.random()">
                            <tr class="text-neutral-800 hover:bg-neutral-50 transition">
                                <td class="px-5 py-4 text-sm whitespace-nowrap" x-text="item.date"></td>
                                <td class="px-5 py-4 text-sm font-medium whitespace-nowrap" x-text="item.name"></td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap" x-text="item.role"></td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap" x-text="item.department"></td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap" x-text="item.type"></td>
                                <td class="px-5 py-4 text-sm text-center whitespace-nowrap">
                                    <span :class="{
                                        'px-2.5 py-1 rounded-full text-xs font-medium': true,
                                        'bg-green-100 text-green-800': item.rating >= 4,
                                        'bg-yellow-100 text-yellow-800': item.rating == 3,
                                        'bg-red-100 text-red-800': item.rating <= 2
                                    }" x-text="item.rating + ' ★'"></span>
                                </td>
                                <td class="px-5 py-4 text-sm max-w-xs truncate" x-text="item.feedback"></td>
                            </tr>
                        </template>
                        <tr x-show="filteredItems.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-neutral-500">
                                <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                                <p>No feedback found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between px-6 py-4 border-t border-neutral-200">
        <div>
            <select x-model="pageSize" @change="changePageSize()" 
                class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="150">150</option>
            </select>
        </div>
        
        <div class="flex items-center gap-2">
            <p class="text-sm text-neutral-600" x-show="filteredItems.length > 0">
                Showing <span class="font-medium" x-text="((currentPage - 1) * pageSize) + 1"></span> 
                to <span class="font-medium" x-text="Math.min(currentPage * pageSize, filteredItems.length)"></span> 
                of <span class="font-medium" x-text="filteredItems.length"></span> results
            </p>
            <nav class="ml-4">
                <ul class="flex items-center text-sm leading-tight bg-white border divide-x rounded-lg h-9 text-neutral-500 divide-neutral-200 border-neutral-200">
                    <li class="h-full">
                        <button @click="prevPage()" :disabled="currentPage === 1" 
                            class="relative inline-flex items-center h-full px-3 rounded-l-lg group hover:text-neutral-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:text-neutral-500">
                            <span>Previous</span>
                        </button>
                    </li>
                    
                    <template x-for="page in visiblePages" :key="page">
                        <li class="hidden h-full md:block">
                            <button @click="goToPage(page)" 
                                :class="{
                                    'relative inline-flex items-center h-full px-3 group': true,
                                    'text-neutral-900 bg-neutral-50': page === currentPage,
                                    'hover:text-neutral-900': page !== currentPage
                                }">
                                <span x-text="page"></span>
                                <span x-show="page === currentPage" 
                                    class="box-content absolute bottom-0 left-0 w-full h-px -mx-px translate-y-px border-l border-r bg-neutral-900 border-neutral-900"></span>
                                <span x-show="page !== currentPage"
                                    class="box-content absolute bottom-0 w-0 h-px -mx-px duration-200 ease-out translate-y-px border-transparent bg-neutral-900 group-hover:border-l group-hover:border-r group-hover:border-neutral-900 left-1/2 group-hover:left-0 group-hover:w-full"></span>
                            </button>
                        </li>
                    </template>
                    
                    <li class="h-full">
                        <button @click="nextPage()" :disabled="currentPage === totalPages"
                            class="relative inline-flex items-center h-full px-3 rounded-r-lg group hover:text-neutral-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:text-neutral-500">
                            <span>Next</span>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>