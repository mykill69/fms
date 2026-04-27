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

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Role</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Access</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Last Login</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($users as $user)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">{{ $user->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $user->department ?? 'No office assigned' }}
                                            </p>
                                            <p class="text-xs text-gray-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium {{ $user->role_badge_color ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $user->role_display ?? ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($user->isSuperAdmin())
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <i class="fas fa-crown mr-1"></i>Full Access
                                        </span>
                                    @else
                                        @php
                                            $permissions = $user->access_permissions ?? [];
                                            if (!is_array($permissions)) {
                                                $permissions = [];
                                            }
                                            $pageLabels = [
                                                'dashboard' => 'Dashboard',
                                                'feedbacks' => 'Feedbacks',
                                                'reports' => 'Reports',
                                                'user_management' => 'Users',
                                            ];
                                        @endphp
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($permissions as $perm)
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                    {{ $pageLabels[$perm] ?? $perm }}
                                                </span>
                                            @endforeach
                                            @if (empty($permissions))
                                                <span class="text-xs text-gray-400">No access</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <button @click="toggleStatus({{ $user->id }})"
                                        class="relative inline-flex items-center cursor-pointer">
                                        <span
                                            class="w-2 h-2 rounded-full mr-2 {{ $user->status === 'active' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        <span
                                            class="text-sm {{ $user->status === 'active' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click='openModal("edit", @json($user))'
                                            class="relative w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 transition-all duration-200 flex items-center justify-center group"
                                            title="Edit User">
                                            <i
                                                class="fas fa-pen-to-square text-sm group-hover:scale-110 transition-transform"></i>
                                            <span
                                                class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                                Edit
                                            </span>
                                        </button>
                                        @if ($user->id !== auth()->id())
                                            <button @click="deleteUser({{ $user->id }})"
                                                class="relative w-9 h-9 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 transition-all duration-200 flex items-center justify-center group"
                                                title="Delete User">
                                                <i
                                                    class="fas fa-trash-can text-sm group-hover:scale-110 transition-transform"></i>
                                                <span
                                                    class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                                    Delete
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $users->links() }}
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

                            <div class="grid grid-cols-1z gap-4">


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
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" x-model="form.email" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Password <span x-show="modalMode === 'create'" class="text-red-500">*</span>
                                <span x-show="modalMode === 'edit'" class="text-gray-400 text-xs">(Leave blank to keep
                                    unchanged)</span>
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

                        <!-- Page Access Section - THIS IS THE IMPORTANT PART -->
                        <div x-show="form.role !== 'super_admin'" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-lock mr-1 text-emerald-500"></i>
                                Page Access Permissions
                            </label>
                            <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                @foreach ($availablePages as $value => $label)
                                    <label
                                        class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded-lg transition">
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

                        <!-- Super Admin Notice -->
                        <div x-show="form.role === 'super_admin'"
                            class="bg-green-50 border border-green-200 rounded-xl p-4">
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
                form: {
                    id: null,
                    name: '',
                    email: '',
                    department: '',
                    password: '',
                    role: 'office_head',
                    status: 'active',
                    access_permissions: []
                },

                onRoleChange() {
                    // Reset access permissions when role changes to super_admin
                    if (this.form.role === 'super_admin') {
                        this.form.access_permissions = [];
                    }
                },

                openModal(mode, userData = null) {
                    this.modalMode = mode;
                    this.modalTitle = mode === 'create' ? 'Create New User' : 'Edit User';

                    if (mode === 'edit' && userData) {
                        let permissions = userData.access_permissions;
                        if (typeof permissions === 'string') {
                            try {
                                permissions = JSON.parse(permissions);
                            } catch (e) {
                                permissions = [];
                            }
                        }

                        this.form = {
                            id: userData.id,
                            name: userData.name,
                            email: userData.email,
                            department: userData.department || '',
                            password: '',
                            role: userData.role,
                            status: userData.status,
                            access_permissions: permissions || []
                        };
                    } else {
                        this.form = {
                            id: null,
                            name: '',
                            email: '',
                            department: '',
                            password: '',
                            role: 'office_head',
                            status: 'active',
                            access_permissions: []
                        };
                    }

                    this.modalOpen = true;
                },

                // Show loading state
                showLoading() {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },

                // Show success message
                showSuccess(message) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: message,
                        timer: 2000,
                        showConfirmButton: true,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'OK'
                    });
                },

                // Show error message
                showError(message) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: message,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'OK'
                    });
                },

                // Show validation errors
                showValidationErrors(errors) {
                    let errorHtml = '<ul class="text-left">';
                    for (const [field, messages] of Object.entries(errors)) {
                        errorHtml += `<li class="mb-1"><strong>${field}:</strong> ${messages.join(', ')}</li>`;
                    }
                    errorHtml += '</ul>';

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorHtml,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'OK'
                    });
                },

                // Confirm delete
                async confirmDelete(userId, userName) {
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: 'Delete User?',
                        html: `Are you sure you want to delete <strong>${userName}</strong>?<br>This action cannot be undone.`,
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    });

                    if (result.isConfirmed) {
                        this.deleteUser(userId);
                    }
                },

                // Confirm status toggle
                async confirmToggleStatus(userId, userName, currentStatus) {
                    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                    const action = currentStatus === 'active' ? 'Deactivate' : 'Activate';

                    const result = await Swal.fire({
                        icon: 'question',
                        title: `${action} User?`,
                        html: `Are you sure you want to ${action.toLowerCase()} <strong>${userName}</strong>?`,
                        showCancelButton: true,
                        confirmButtonColor: currentStatus === 'active' ? '#f59e0b' : '#10b981',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: `Yes, ${action}!`,
                        cancelButtonText: 'Cancel'
                    });

                    if (result.isConfirmed) {
                        this.toggleStatus(userId);
                    }
                },

                async saveUser() {
                    const url = this.modalMode === 'create' ?
                        '{{ route('admin.users.store') }}' :
                        '{{ route('admin.users.update', ['user' => '__ID__']) }}'.replace('__ID__', this.form.id);

                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                    if (!csrfToken) {
                        this.showError('Security token not found. Please refresh the page.');
                        return;
                    }

                    // Prepare form data
                    const formData = {
                        ...this.form
                    };

                    // Remove empty password for edit mode
                    if (this.modalMode === 'edit' && !formData.password) {
                        delete formData.password;
                    }

                    // Set method for Laravel
                    if (this.modalMode === 'edit') {
                        formData._method = 'PUT';
                    }

                    // Close modal
                    this.modalOpen = false;

                    // Show loading
                    this.showLoading();

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(formData)
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            Swal.close();
                            if (data.errors) {
                                this.showValidationErrors(data.errors);
                            } else {
                                this.showError(data.message || 'An error occurred');
                            }
                            return;
                        }

                        if (data.success) {
                            // Close loading and show success
                            Swal.close();

                            const successMessage = this.modalMode === 'create' ?
                                'User created successfully!' :
                                'User updated successfully!';

                            await Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: successMessage,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            window.location.reload();
                        } else {
                            Swal.close();
                            this.showError(data.message || 'An error occurred');
                        }
                    } catch (error) {
                        Swal.close();
                        console.error('Error:', error);
                        this.showError('Failed to save user. Please try again.');
                    }
                },

                async toggleStatus(userId) {
                    try {
                        const url = '{{ route('admin.users.toggle-status', ['user' => '__ID__']) }}'.replace('__ID__',
                            userId);

                        this.showLoading();

                        const response = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();
                        Swal.close();

                        if (data.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: `User ${data.status === 'active' ? 'activated' : 'deactivated'} successfully!`,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            window.location.reload();
                        } else {
                            this.showError(data.message || 'Failed to toggle status');
                        }
                    } catch (error) {
                        Swal.close();
                        console.error('Error:', error);
                        this.showError('Failed to update status. Please try again.');
                    }
                },

                async deleteUser(userId) {
                    try {
                        const url = '{{ route('admin.users.destroy', ['user' => '__ID__']) }}'.replace('__ID__',
                            userId);

                        this.showLoading();

                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();
                        Swal.close();

                        if (data.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'User has been deleted successfully.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            window.location.reload();
                        } else {
                            this.showError(data.message || 'Failed to delete user');
                        }
                    } catch (error) {
                        Swal.close();
                        console.error('Error:', error);
                        this.showError('Failed to delete user. Please try again.');
                    }
                }
            }
        }
    </script>


    {{-- <script>
        function userManagement() {
            return {
                modalOpen: false,
                modalMode: 'create',
                modalTitle: 'Create New User',
                availablePages: @json($availablePages),
                form: {
                    id: null,
                    name: '',
                    email: '',
                    username: '',
                    password: '',
                    role: 'office_head',
                    status: 'active',
                    access_permissions: []
                },

                onRoleChange() {
                    // Reset access permissions when role changes to super_admin
                    if (this.form.role === 'super_admin') {
                        this.form.access_permissions = [];
                    }
                },

                openModal(mode, userData = null) {
                    this.modalMode = mode;
                    this.modalTitle = mode === 'create' ? 'Create New User' : 'Edit User';

                    if (mode === 'edit' && userData) {
                        // Parse access_permissions if it's a string
                        let permissions = userData.access_permissions;
                        if (typeof permissions === 'string') {
                            try {
                                permissions = JSON.parse(permissions);
                            } catch (e) {
                                permissions = [];
                            }
                        }

                        this.form = {
                            id: userData.id,
                            name: userData.name,
                            email: userData.email,
                            username: userData.username,
                            password: '',
                            role: userData.role,
                            status: userData.status,
                            access_permissions: permissions || []
                        };
                    } else {
                        this.form = {
                            id: null,
                            name: '',
                            email: '',
                            username: '',
                            password: '',
                            role: 'office_head',
                            status: 'active',
                            access_permissions: []
                        };
                    }

                    this.modalOpen = true;
                },

                async saveUser() {
                    const url = this.modalMode === 'create' ?
                        '{{ route('admin.users.store') }}' :
                        '{{ route('admin.users.update', ['user' => '__ID__']) }}'.replace('__ID__', this.form.id);

                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                    if (!csrfToken) {
                        console.error('CSRF token not found');
                        alert('Security token not found. Please refresh the page.');
                        return;
                    }

                    // Prepare form data
                    const formData = {
                        ...this.form
                    };

                    // Remove empty password for edit mode
                    if (this.modalMode === 'edit' && !formData.password) {
                        delete formData.password;
                    }

                    // Set method for Laravel
                    if (this.modalMode === 'edit') {
                        formData._method = 'PUT';
                    }

                    console.log('Sending data:', formData); // Debug log

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(formData)
                        });

                        const data = await response.json();

                        console.log('Response:', data); // Debug log

                        if (!response.ok) {
                            if (data.errors) {
                                const errorMessages = Object.values(data.errors).flat().join('\n');
                                alert('Validation errors:\n' + errorMessages);
                            } else {
                                alert(data.message || 'An error occurred');
                            }
                            return;
                        }

                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'An error occurred');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Failed to save user. Check console for details.');
                    }
                },

                async toggleStatus(userId) {
                    try {
                        const url = '{{ route('admin.users.toggle-status', ['user' => '__ID__']) }}'.replace('__ID__',
                            userId);

                        const response = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'An error occurred');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Failed to toggle status');
                    }
                },

                async deleteUser(userId) {
                    if (!confirm('Are you sure you want to delete this user?')) return;

                    try {
                        const url = '{{ route('admin.users.destroy', ['user' => '__ID__']) }}'.replace('__ID__',
                            userId);

                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'An error occurred');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Failed to delete user');
                    }
                }
            }
        }
    </script> --}}
@endsection
