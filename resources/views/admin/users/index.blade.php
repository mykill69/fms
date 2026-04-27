@extends('layouts.admin')

@section('content')
    <div class="p-6" x-data="userManagement()">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                <p class="text-gray-600">Manage system users and their access levels</p>
            </div>
            <button @click="openModal('create')"
                class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Add User</span>
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" x-data="{
            items: {{ Js::from($users->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'department' => $u->department ?? 'No office assigned',
                    'role' => $u->role,
                    'role_display' => $u->role_display ?? ucfirst(str_replace('_', ' ', $u->role)),
                    'role_badge' => $u->role_badge_color ?? 'bg-gray-100 text-gray-700',
                    'is_super' => $u->isSuperAdmin(),
                    'permissions' => $u->access_permissions ?? [],
                    'status' => $u->status,
                    'last_login' => $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never',
                    'last_login_ts' => $u->last_login_at ? $u->last_login_at->timestamp : 0,
                    'can_edit' => auth()->user()->isSuperAdmin() || !$u->isSuperAdmin(),
                    'can_delete' => auth()->id() !== $u->id && (auth()->user()->isSuperAdmin() || !$u->isSuperAdmin()),
                ];
            })) }},
            currentUserId: {{ auth()->id() }},
            filteredItems: [],
            paginatedItems: [],
            search: '',
            sortField: 'name',
            sortDirection: 'asc',
            currentPage: 1,
            pageSize: 10,
            totalPages: 1,
            pageLabels: {'dashboard':'Dashboard','feedbacks':'Feedbacks','reports':'Reports','flagged':'Flagged','user_management':'Users','settings':'Settings'},

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
                if (this.sortField !== field) return 'fas fa-sort text-gray-300 text-xs ml-1';
                return this.sortDirection === 'asc' ? 'fas fa-sort-up text-emerald-500 text-xs ml-1' : 'fas fa-sort-down text-emerald-500 text-xs ml-1';
            },
            sortData() {
                var self = this;
                this.filteredItems.sort(function(a, b) {
                    var aVal = a[self.sortField];
                    var bVal = b[self.sortField];
                    if (self.sortField === 'last_login_ts') {
                        aVal = Number(aVal); bVal = Number(bVal);
                    } else {
                        aVal = String(aVal).toLowerCase(); bVal = String(bVal).toLowerCase();
                    }
                    if (aVal < bVal) return self.sortDirection === 'asc' ? -1 : 1;
                    if (aVal > bVal) return self.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });
            },
            filterData() {
                var s = this.search.toLowerCase();
                this.filteredItems = this.items.filter(function(i) {
                    return i.name.toLowerCase().includes(s) || i.email.toLowerCase().includes(s) || i.department.toLowerCase().includes(s) || i.role_display.toLowerCase().includes(s);
                });
                this.sortData();
                this.currentPage = 1;
                this.updatePagination();
            },
            updatePagination() {
                this.totalPages = Math.ceil(this.filteredItems.length / this.pageSize) || 1;
                if (this.currentPage > this.totalPages) this.currentPage = 1;
                var start = (this.currentPage - 1) * this.pageSize;
                this.paginatedItems = this.filteredItems.slice(start, start + this.pageSize);
            },
            changePageSize() { this.currentPage = 1; this.updatePagination(); },
            prevPage() { if (this.currentPage > 1) { this.currentPage--; this.updatePagination(); } },
            nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.updatePagination(); } },
            goToPage(p) { this.currentPage = p; this.updatePagination(); },
            get visiblePages() {
                var p = [], max = 5, s = Math.max(1, this.currentPage - Math.floor(max/2)), e = Math.min(this.totalPages, s + max - 1);
                if (e - s + 1 < max) s = Math.max(1, e - max + 1);
                for (var i = s; i <= e; i++) p.push(i);
                return p;
            }
        }" x-init="init()">
            
            <div class="px-6 py-4 flex justify-between items-center border-b border-gray-200">
                <div class="text-sm text-gray-600">
                    <span x-show="filteredItems.length > 0">
                        Showing <span class="font-medium" x-text="((currentPage - 1) * pageSize) + 1"></span> 
                        to <span class="font-medium" x-text="Math.min(currentPage * pageSize, filteredItems.length)"></span> 
                        of <span class="font-medium" x-text="filteredItems.length"></span> users
                    </span>
                </div>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" x-model="search" @input="filterData()" placeholder="Search users..." 
                        class="pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 w-64">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th @click="sort('name')" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition select-none">
                                <div class="flex items-center">User<i :class="getSortIcon('name')"></i></div>
                            </th>
                            <th @click="sort('role_display')" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition select-none">
                                <div class="flex items-center">Role<i :class="getSortIcon('role_display')"></i></div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Access</th>
                            <th @click="sort('status')" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition select-none">
                                <div class="flex items-center">Status<i :class="getSortIcon('status')"></i></div>
                            </th>
                            <th @click="sort('last_login_ts')" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition select-none">
                                <div class="flex items-center">Last Login<i :class="getSortIcon('last_login_ts')"></i></div>
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="item in paginatedItems" :key="item.id">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center text-white font-semibold">
                                            <span x-text="item.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800" x-text="item.name"></p>
                                            <p class="text-sm text-gray-500" x-text="item.department"></p>
                                            <p class="text-xs text-gray-400" x-text="item.email"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium" :class="item.role_badge" x-text="item.role_display"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span x-show="item.is_super" class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-crown mr-1"></i>Full Access
                                    </span>
                                    <div x-show="!item.is_super" class="flex flex-wrap gap-1">
                                        <template x-for="perm in item.permissions" :key="perm">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700" x-text="pageLabels[perm] || perm"></span>
                                        </template>
                                        <span x-show="item.permissions.length === 0" class="text-xs text-gray-400">No access</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <button @click="toggleStatus(item.id)" class="relative inline-flex items-center cursor-pointer">
                                        <span class="w-2 h-2 rounded-full mr-2" :class="item.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></span>
                                        <span class="text-sm" :class="item.status === 'active' ? 'text-green-600' : 'text-red-600'" x-text="item.status.charAt(0).toUpperCase() + item.status.slice(1)"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="item.last_login"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button x-show="item.can_edit" @click='openModal("edit", item)' class="relative w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 transition-all duration-200 flex items-center justify-center group" title="Edit User">
                                            <i class="fas fa-pen-to-square text-sm group-hover:scale-110 transition-transform"></i>
                                        </button>
                                        <button x-show="item.can_delete" @click="deleteUser(item.id)" class="relative w-9 h-9 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 transition-all duration-200 flex items-center justify-center group" title="Delete User">
                                            <i class="fas fa-trash-can text-sm group-hover:scale-110 transition-transform"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredItems.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-3xl mb-2 opacity-50"></i>
                                <p>No users found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                <select x-model="pageSize" @change="changePageSize()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <div class="flex items-center gap-3">
                    <p class="text-sm text-gray-600" x-show="filteredItems.length > 0">
                        <span x-text="((currentPage - 1) * pageSize) + 1"></span>-<span x-text="Math.min(currentPage * pageSize, filteredItems.length)"></span> of <span x-text="filteredItems.length"></span>
                    </p>
                    <div class="flex items-center gap-1">
                        <button @click="prevPage()" :disabled="currentPage === 1" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </button>
                        <template x-for="page in visiblePages" :key="page">
                            <button @click="goToPage(page)" :class="page === currentPage ? 'w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium bg-emerald-500 text-white' : 'w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium border border-gray-300 text-gray-600 hover:bg-gray-100 transition'">
                                <span x-text="page"></span>
                            </button>
                        </template>
                        <button @click="nextPage()" :disabled="currentPage === totalPages" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <div x-show="modalOpen" x-transition.opacity
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            style="display: none;">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
                @click.away="modalOpen = false">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-800" x-text="modalTitle"></h3>
                </div>

                <form @submit.prevent="saveUser">
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" x-model="form.name" required
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Office / Department</label>
                                <select x-model="form.department"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="">Select Office</option>
                                    <option value="CPSU - University Wide">CPSU - University Wide</option>
                                    @foreach ($offices as $office)
                                        <option value="{{ $office->office_name }}">{{ $office->office_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" x-model="form.email" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Password <span x-show="modalMode === 'create'" class="text-red-500">*</span>
                                <span x-show="modalMode === 'edit'" class="text-gray-400 text-xs">(Leave blank to keep unchanged)</span>
                            </label>
                            <input type="password" x-model="form.password" :required="modalMode === 'create'"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select x-model="form.role" required @change="onRoleChange()"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="form.status" required
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div x-show="form.role !== 'super_admin'" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-lock mr-1 text-emerald-500"></i>
                                Page Access Permissions
                            </label>
                            <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                @foreach ($availablePages as $value => $label)
                                    <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded-lg transition">
                                        <input type="checkbox" value="{{ $value }}"
                                            x-model="form.access_permissions"
                                            class="w-4 h-4 text-emerald-500 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 focus:ring-2">
                                        <span class="text-sm text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Select which pages this user can access. Super Admin has full access by default.
                            </p>
                        </div>

                        <div x-show="form.role === 'super_admin'" class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <p class="text-sm text-green-800">
                                <i class="fas fa-check-circle mr-2"></i>
                                Super Administrator has full access to all pages by default.
                            </p>
                        </div>
                    </div>

                    <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                        <button type="button" @click="modalOpen = false"
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition shadow-md">
                            <span x-text="modalMode === 'create' ? 'Create User' : 'Update User'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function userManagement() {
            return {
                modalOpen: false,
                modalMode: 'create',
                modalTitle: 'Create New User',
                availablePages: @json($availablePages),
                form: { id: null, name: '', email: '', department: '', password: '', role: 'office_head', status: 'active', access_permissions: [] },

                onRoleChange() {
                    if (this.form.role === 'super_admin') this.form.access_permissions = [];
                },

                openModal(mode, userData = null) {
                    this.modalMode = mode;
                    this.modalTitle = mode === 'create' ? 'Create New User' : 'Edit User';
                    if (mode === 'edit' && userData) {
                        this.form = {
                            id: userData.id, name: userData.name, email: userData.email,
                            department: userData.department || '', password: '',
                            role: userData.role || 'office_head',
                            status: userData.status, access_permissions: userData.permissions || []
                        };
                    } else {
                        this.form = { id: null, name: '', email: '', department: '', password: '', role: 'office_head', status: 'active', access_permissions: [] };
                    }
                    this.modalOpen = true;
                },

                showLoading() { Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() }); },
                showError(m) { Swal.fire({ icon: 'error', title: 'Error!', text: m, confirmButtonColor: '#ef4444' }); },
                showValidationErrors(errors) {
                    let h = '<ul class="text-left">';
                    for (const [f, ms] of Object.entries(errors)) h += `<li><strong>${f}:</strong> ${ms.join(', ')}</li>`;
                    Swal.fire({ icon: 'error', title: 'Validation Error', html: h, confirmButtonColor: '#ef4444' });
                },

                async saveUser() {
                    const url = this.modalMode === 'create' ? '{{ route('admin.users.store') }}' : '{{ route('admin.users.update', ['user' => '__ID__']) }}'.replace('__ID__', this.form.id);
                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (!token) return this.showError('Security token not found.');
                    const fd = {...this.form};
                    if (this.modalMode === 'edit' && !fd.password) delete fd.password;
                    if (this.modalMode === 'edit') fd._method = 'PUT';
                    this.modalOpen = false; this.showLoading();
                    try {
                        const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(fd) });
                        const d = await r.json();
                        Swal.close();
                        if (!r.ok) { if (d.errors) this.showValidationErrors(d.errors); else this.showError(d.message || 'Error'); return; }
                        if (d.success) {
                            await Swal.fire({ icon: 'success', title: 'Success!', text: this.modalMode === 'create' ? 'User created!' : 'User updated!', timer: 1500, showConfirmButton: false });
                            window.location.reload();
                        } else this.showError(d.message || 'Error');
                    } catch (e) { Swal.close(); this.showError('Failed to save user.'); }
                },

                async toggleStatus(id) {
                    this.showLoading();
                    try {
                        const r = await fetch('{{ route('admin.users.toggle-status', ['user' => '__ID__']) }}'.replace('__ID__', id), { method: 'PATCH', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                        const d = await r.json(); Swal.close();
                        if (d.success) { await Swal.fire({ icon: 'success', title: 'Success!', timer: 1500, showConfirmButton: false }); window.location.reload(); }
                        else this.showError(d.message || 'Failed');
                    } catch (e) { Swal.close(); this.showError('Failed to update status.'); }
                },

                async deleteUser(id) {
                    const result = await Swal.fire({ icon: 'warning', title: 'Delete User?', text: 'This action cannot be undone.', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Delete' });
                    if (!result.isConfirmed) return;
                    this.showLoading();
                    try {
                        const r = await fetch('{{ route('admin.users.destroy', ['user' => '__ID__']) }}'.replace('__ID__', id), { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                        const d = await r.json(); Swal.close();
                        if (d.success) { await Swal.fire({ icon: 'success', title: 'Deleted!', timer: 1500, showConfirmButton: false }); window.location.reload(); }
                        else this.showError(d.message || 'Failed');
                    } catch (e) { Swal.close(); this.showError('Failed to delete user.'); }
                }
            }
        }
    </script>
@endsection