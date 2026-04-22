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
</head>

<body class="bg-gray-100 overflow-x-hidden">

    <div 
        x-data="{ 
            open: false, 
            active: '{{ request()->path() }}' 
        }" 
        class="flex min-h-screen">

        <!-- Mobile Overlay -->
        <div x-show="open" 
             x-transition.opacity 
             @click="open = false" 
             class="fixed inset-0 bg-black/50 z-40 md:hidden">
        </div>

        <!-- Mobile Menu Button -->
        <button @click="open = !open" 
                class="md:hidden fixed top-3 left-3 z-50 text-gray-900 text-3xl leading-none">
            ☰
        </button>

        <!-- SIDEBAR -->
        <aside class="fixed md:fixed top-0 left-0 z-50 h-full w-64 bg-gray-900 text-white
                     transform transition-transform duration-300 md:translate-x-0"
               :class="open ? 'translate-x-0' : '-translate-x-full'">

            <div class="flex items-center gap-3 py-4 px-4 text-lg font-semibold border-b border-gray-800">
                <img src="{{ asset('img/cpsu_logo.png') }}" 
                     alt="CPSU Logo"
                     class="w-10 h-10 object-contain flex-shrink-0">
                <span class="leading-tight">
                    AI-Powered Feedback Management System
                </span>
            </div>

            <nav class="space-y-1 px-2 py-3 text-sm">
                <!-- DASHBOARD -->
                <a href="/admin/dashboard"
                   :class="active === 'admin/dashboard' ? 'bg-gray-800 text-white shadow' : 'hover:bg-gray-800 text-gray-300'"
                   class="flex items-center gap-3 px-4 py-2 rounded transition">
                    <i class="fa-solid fa-gauge-high"
                       :class="active === 'admin/dashboard' ? 'text-emerald-400' : 'text-gray-400'"></i>
                    <span>Dashboard</span>
                </a>

                <!-- FEEDBACKS -->
                <a href="/admin/feedbacks"
                   :class="active === 'admin/feedbacks' ? 'bg-gray-800 text-white shadow' : 'hover:bg-gray-800 text-gray-300'"
                   class="flex items-center gap-3 px-4 py-2 rounded transition">
                    <i class="fa-solid fa-comments"
                       :class="active === 'admin/feedbacks' ? 'text-emerald-400' : 'text-gray-400'"></i>
                    <span>Feedbacks</span>
                </a>

                <!-- AI ANALYSIS -->
                <a href="/admin/analysis"
                   :class="active === 'admin/analysis' ? 'bg-gray-800 text-white shadow' : 'hover:bg-gray-800 text-gray-300'"
                   class="flex items-center gap-3 px-4 py-2 rounded transition">
                    <i class="fa-solid fa-brain"
                       :class="active === 'admin/analysis' ? 'text-emerald-400' : 'text-gray-400'"></i>
                    <span>AI Analysis</span>
                </a>

                <!-- OFFICES -->
                <a href="/admin/offices"
                   :class="active === 'admin/offices' ? 'bg-gray-800 text-white shadow' : 'hover:bg-gray-800 text-gray-300'"
                   class="flex items-center gap-3 px-4 py-2 rounded transition">
                    <i class="fa-solid fa-building"
                       :class="active === 'admin/offices' ? 'text-emerald-400' : 'text-gray-400'"></i>
                    <span>All Offices</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 w-full p-0 md:p-0 transition-all duration-300 md:ml-64">
            @yield('content')
        </main>

    </div>

</body>
</html>