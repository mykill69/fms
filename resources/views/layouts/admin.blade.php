<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CPSU Feedback System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- LOCAL SCRIPTS -->
    <script src="{{ asset('js/tailwind.js') }}"></script>
    <script defer src="{{ asset('js/alpine.js') }}"></script>
    <script src="{{ asset('js/chart.js') }}"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<style>
    .swal2-popup {
        border-radius: 16px !important;
        padding: 2rem !important;
    }

    .swal2-title {
        font-size: 1.5rem !important;
        font-weight: 600 !important;
    }

    .swal2-confirm {
        border-radius: 8px !important;
        padding: 0.75rem 2rem !important;
        font-weight: 500 !important;
    }

    .swal2-cancel {
        border-radius: 8px !important;
        padding: 0.75rem 2rem !important;
        font-weight: 500 !important;
    }
</style>

<body class="bg-gray-50 overflow-x-hidden">

    <div x-data="{
        open: false,
        active: '{{ request()->path() }}',
        userMenuOpen: false
    }" class="flex min-h-screen">

        <!-- Mobile Menu Button -->
        <button @click="open = !open"
            class="lg:hidden fixed top-4 left-4 z-50 w-12 h-12 bg-white rounded-xl shadow-lg flex items-center justify-center text-gray-700 hover:bg-gray-50 transition-all">
            <i class="fas fa-bars text-xl"></i>
        </button>

        <!-- Mobile Overlay -->
        <div x-show="open" x-transition.opacity @click="open = false"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden">
        </div>

        <!-- SIDEBAR -->
        <aside
            class="fixed lg:sticky top-0 left-0 z-40 h-screen w-72 lg:w-80 bg-gradient-to-b from-gray-900 to-gray-800 text-white
                     transform transition-transform duration-300 ease-in-out lg:translate-x-0
                     shadow-2xl lg:shadow-none flex flex-col"
            :class="open ? 'translate-x-0' : '-translate-x-full'">

            <!-- Logo Section - Fixed at top -->
            <div class="flex-shrink-0 flex items-center gap-3 py-5 px-5 border-b border-gray-700/50">
                <div
                    class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                    <img src="{{ asset('img/cpsu_logo.png') }}" alt="CPSU Logo" class="w-8 h-8 object-contain">
                </div>
                <div class="flex-1">
                    <h1
                        class="text-sm lg:text-base font-bold leading-tight bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                        CPSU Feedback
                    </h1>
                    <p class="text-xs text-gray-400 hidden sm:block">Management System</p>
                </div>
                <button @click="open = false" class="lg:hidden text-gray-400 hover:text-white transition p-2">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Navigation Menu - Scrollable -->
            <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto scrollbar-thin">
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Main Menu</p>

                <!-- Dashboard -->
                <a href="/admin/dashboard"
                    :class="active === 'admin/dashboard'
                        ?
                        'bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 text-white border-l-4 border-emerald-500' :
                        'text-gray-300 hover:bg-gray-800/50 hover:text-white border-l-4 border-transparent'"
                    class="flex items-center gap-3 px-4 py-3 lg:py-3.5 rounded-r-lg transition-all duration-200 group">
                    <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="active === 'admin/dashboard' ? 'bg-emerald-500/20 text-emerald-400' :
                            'bg-gray-800 text-gray-400 group-hover:bg-gray-700 group-hover:text-gray-300'">
                        <i class="fas fa-gauge-high text-base lg:text-lg"></i>
                    </div>
                    <span class="font-medium text-sm sm:text-base">Dashboard</span>
                    <i class="fas fa-chevron-right ml-auto text-xs opacity-50"></i>
                </a>

                <!-- Feedbacks -->
                <a href="/admin/feedbacks"
                    :class="active === 'admin/feedbacks'
                        ?
                        'bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 text-white border-l-4 border-emerald-500' :
                        'text-gray-300 hover:bg-gray-800/50 hover:text-white border-l-4 border-transparent'"
                    class="flex items-center gap-3 px-4 py-3 lg:py-3.5 rounded-r-lg transition-all duration-200 group">
                    <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="active === 'admin/feedbacks' ? 'bg-emerald-500/20 text-emerald-400' :
                            'bg-gray-800 text-gray-400 group-hover:bg-gray-700 group-hover:text-gray-300'">
                        <i class="fas fa-comments text-base lg:text-lg"></i>
                    </div>
                    <span class="font-medium text-sm lg:text-base">Feedbacks</span>
                    <i class="fas fa-chevron-right ml-auto text-xs opacity-50"></i>
                </a>

                <!-- Reports -->
                <a href="/admin/reports"
                    :class="active === 'admin/reports'
                        ?
                        'bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 text-white border-l-4 border-emerald-500' :
                        'text-gray-300 hover:bg-gray-800/50 hover:text-white border-l-4 border-transparent'"
                    class="flex items-center gap-3 px-4 py-3 lg:py-3.5 rounded-r-lg transition-all duration-200 group">
                    <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="active === 'admin/reports' ? 'bg-emerald-500/20 text-emerald-400' :
                            'bg-gray-800 text-gray-400 group-hover:bg-gray-700 group-hover:text-gray-300'">
                        <i class="fas fa-chart-simple text-base lg:text-lg"></i>
                    </div>
                    <span class="font-medium text-sm lg:text-base">Reports</span>
                    <i class="fas fa-chevron-right ml-auto text-xs opacity-50"></i>
                </a>

                <!-- AI Analysis -->
                <a href="/admin/analysis"
                    :class="active === 'admin/analysis'
                        ?
                        'bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 text-white border-l-4 border-emerald-500' :
                        'text-gray-300 hover:bg-gray-800/50 hover:text-white border-l-4 border-transparent'"
                    class="flex items-center gap-3 px-4 py-3 lg:py-3.5 rounded-r-lg transition-all duration-200 group">
                    <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="active === 'admin/analysis' ? 'bg-emerald-500/20 text-emerald-400' :
                            'bg-gray-800 text-gray-400 group-hover:bg-gray-700 group-hover:text-gray-300'">
                        <i class="fas fa-brain text-base lg:text-lg"></i>
                    </div>
                    <span class="font-medium text-sm lg:text-base">AI Analysis</span>
                    <span
                        class="ml-auto px-2 py-0.5 text-[10px] font-bold bg-purple-500/20 text-purple-400 rounded-full">NEW</span>
                </a>

                <!-- Offices -->
                <a href="/admin/offices"
                    :class="active === 'admin/offices'
                        ?
                        'bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 text-white border-l-4 border-emerald-500' :
                        'text-gray-300 hover:bg-gray-800/50 hover:text-white border-l-4 border-transparent'"
                    class="flex items-center gap-3 px-4 py-3 lg:py-3.5 rounded-r-lg transition-all duration-200 group">
                    <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="active === 'admin/offices' ? 'bg-emerald-500/20 text-emerald-400' :
                            'bg-gray-800 text-gray-400 group-hover:bg-gray-700 group-hover:text-gray-300'">
                        <i class="fas fa-building text-base lg:text-lg"></i>
                    </div>
                    <span class="font-medium text-sm lg:text-base">All Offices</span>
                    <i class="fas fa-chevron-right ml-auto text-xs opacity-50"></i>
                </a>

                <!-- User Management -->
                @if (auth()->user() && auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}"
                        :class="active === 'admin/users'
                            ?
                            'bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 text-white border-l-4 border-emerald-500' :
                            'text-gray-300 hover:bg-gray-800/50 hover:text-white border-l-4 border-transparent'"
                        class="flex items-center gap-3 px-4 py-3 lg:py-3.5 rounded-r-lg transition-all duration-200 group">
                        <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                            :class="active === 'admin/users' ? 'bg-emerald-500/20 text-emerald-400' :
                                'bg-gray-800 text-gray-400 group-hover:bg-gray-700 group-hover:text-gray-300'">
                            <i class="fas fa-users-cog text-base lg:text-lg"></i>
                        </div>
                        <span class="font-medium text-sm lg:text-base">User Management</span>
                        <i class="fas fa-chevron-right ml-auto text-xs opacity-50"></i>
                    </a>
                @endif

                <!-- Extra padding at bottom for scroll -->
                <div class="h-4"></div>
            </nav>

            <!-- User Profile Section - Fixed at bottom -->
            <div
                class="flex-shrink-0 p-4 border-t border-gray-700/50 bg-gradient-to-t from-gray-900 via-gray-900 to-transparent">
                <div class="relative" x-data="{ userMenuOpen: false }">
                    <!-- User Info Button -->
                    <button @click="userMenuOpen = !userMenuOpen"
                        class="w-full flex items-center gap-3 p-2 lg:p-3 rounded-xl hover:bg-gray-800/50 transition group">
                        <div class="relative flex-shrink-0">
                            <div
                                class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center text-white font-semibold text-sm lg:text-base shadow-md">
                                A
                            </div>
                            <span
                                class="absolute bottom-0 right-0 w-3 h-3 lg:w-3.5 lg:h-3.5 bg-emerald-500 border-2 border-gray-900 rounded-full"></span>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <p class="text-sm lg:text-base font-medium text-white truncate">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ auth()->user()->role }}
                            </p>
                        </div>
                        <i class="fas fa-chevron-up text-gray-400 text-xs transition-transform flex-shrink-0"
                            :class="{ 'rotate-180': userMenuOpen }"></i>
                    </button>

                    <!-- User Dropdown Menu -->
                    <div x-show="userMenuOpen" @click.away="userMenuOpen = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-2"
                        class="absolute bottom-full left-0 right-0 mb-2 bg-gray-800 rounded-xl shadow-xl border border-gray-700 overflow-hidden z-50">
                        <div class="p-2">
                            <a href="#"
                                class="flex items-center gap-3 px-3 py-2.5 lg:py-3 rounded-lg text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition">
                                <i class="fas fa-user-circle w-5"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="#"
                                class="flex items-center gap-3 px-3 py-2.5 lg:py-3 rounded-lg text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition">
                                <i class="fas fa-cog w-5"></i>
                                <span>Settings</span>
                            </a>
                            <a href="#"
                                class="flex items-center gap-3 px-3 py-2.5 lg:py-3 rounded-lg text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition">
                                <i class="fas fa-bell w-5"></i>
                                <span>Notifications</span>
                                <span
                                    class="ml-auto w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">3</span>
                            </a>
                            <div class="border-t border-gray-700 my-2"></div>
                            <a href="{{ route('logout') }}"
                                class="flex items-center gap-3 px-3 py-2.5 lg:py-3 rounded-lg text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 transition">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-gray-400">System Online</span>
                    </span>
                    <span class="text-gray-500">v2.1.0</span>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 w-full min-h-screen transition-all duration-300">
            <!-- Top Header for Mobile -->
            <div class="lg:hidden h-14 bg-white shadow-sm flex items-center justify-between px-4 sticky top-0 z-30">
                <div class="w-10"></div>
                <h1 class="text-base font-semibold text-gray-800 truncate px-2">CPSU Feedback System</h1>
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-user text-gray-600 text-sm"></i>
                </div>
            </div>

            <!-- Page Content -->
            <div class="h-full">
                @yield('content')
            </div>
        </main>

    </div>

    <!-- Custom Scrollbar Styles -->
    <style>
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 5px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Safe area padding for mobile devices */
        @supports (padding: max(0px)) {
            aside {
                padding-bottom: max(0px, env(safe-area-inset-bottom));
            }
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            aside {
                width: 280px !important;
            }
        }

        @media (min-width: 1024px) {
            aside {
                width: 300px !important;
            }
        }

        @media (min-width: 1280px) {
            aside {
                width: 320px !important;
            }
        }
    </style>

</body>

</html>
