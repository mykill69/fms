<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CPSU Feedback Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="{{ asset('js/tailwind.js') }}"></script>
    <script defer src="{{ asset('js/alpine.js') }}"></script>
    <link rel="shortcut icon" type="" href="{{ asset('img/cpsu_logo.png') }}">
    <style>
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            33% {
                transform: translateY(-15px) rotate(1deg);
            }

            66% {
                transform: translateY(-5px) rotate(-1deg);
            }
        }

        @keyframes float-reverse {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            33% {
                transform: translateY(15px) rotate(-1deg);
            }

            66% {
                transform: translateY(5px) rotate(1deg);
            }
        }

        @keyframes pulse-soft {

            0%,
            100% {
                opacity: 0.4;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        @keyframes gradient-shift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes particle-drift {
            0% {
                transform: translate(0, 0);
            }

            25% {
                transform: translate(100px, -50px);
            }

            50% {
                transform: translate(50px, -100px);
            }

            75% {
                transform: translate(-50px, -50px);
            }

            100% {
                transform: translate(0, 0);
            }
        }

        .animate-float {
            animation: float 8s ease-in-out infinite;
        }

        .animate-float-reverse {
            animation: float-reverse 8s ease-in-out infinite;
        }

        .animate-pulse-soft {
            animation: pulse-soft 4s ease-in-out infinite;
        }

        .animate-gradient-shift {
            animation: gradient-shift 8s ease infinite;
            background-size: 200% 200%;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .bg-grid {
            background-image:
                linear-gradient(rgba(11, 96, 54, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(11, 96, 54, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .particle {
            position: fixed;
            width: 4px;
            height: 4px;
            background: rgba(11, 96, 54, 0.1);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>

<body class="min-h-screen py-6 sm:py-10 px-4 relative">

    <div class="fixed inset-0 bg-gradient-to-br from-emerald-50 via-green-50 to-teal-50 animate-gradient-shift -z-10">
        <div class="absolute inset-0 bg-grid"></div>
    </div>

    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-5">
        <div
            class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-300 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-float">
        </div>
        <div
            class="absolute top-1/2 -left-40 w-96 h-96 bg-green-300 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-float-reverse">
        </div>
        <div
            class="absolute -bottom-40 right-1/4 w-80 h-80 bg-teal-300 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-pulse-soft">
        </div>
    </div>

    <div id="particles-container" class="fixed inset-0 pointer-events-none -z-5"></div>

    <!-- HEADER -->
    <div class="text-center mb-6 sm:mb-8 relative">
        <div
            class="inline-flex items-center justify-center w-28 h-28 sm:w-36 sm:h-36 md:w-44 md:h-44 bg-white rounded-3xl shadow-xl mb-6 transform hover:scale-105 transition-transform duration-300">
            <img src="{{ asset('img/cpsu_logo.png') }}"
                class="w-24 h-24 sm:w-32 sm:h-32 md:w-40 md:h-40 object-contain">
        </div>

        <h1 class="text-4xl sm:text-5xl md:text-6xl font-black tracking-tight text-[#0b6036]"
            style="font-family: 'Arial Black', Arial, sans-serif;">
            LISTEN
        </h1>

        <p class="text-sm sm:text-base font-medium text-gray-700 mt-3 px-2 max-w-lg mx-auto leading-relaxed">
            The Voice of the CPSU Community, where every voice becomes insight.
        </p>
    </div>

    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="max-w-2xl mx-auto mb-4 px-4 py-3 rounded-lg bg-green-100 border border-green-300 text-green-800 text-sm sm:text-base flex items-center justify-between relative">
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="ml-4 text-green-600 hover:text-green-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="max-w-2xl mx-auto mb-4 px-4 py-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-sm sm:text-base flex items-center justify-between relative">
            <span>{{ session('error') }}</span>
            <button @click="show = false" class="ml-4 text-red-600 hover:text-red-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>
    @endif

    <!-- INTRO CARD -->
    <div class="max-w-2xl mx-auto glass-card p-5 sm:p-6 md:p-8 rounded-2xl shadow-xl mb-6 relative">

        <div class="flex items-start gap-3 sm:gap-4">

            <div class="relative w-10 h-10 flex items-center justify-center flex-shrink-0">
                <span
                    class="absolute inline-flex h-full w-full rounded-full bg-[#0b6036] opacity-20 animate-ping"></span>
                <svg width="58" height="58" viewBox="0 0 24 24" fill="none" stroke="#0b6036" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3c-3.6 0-6.5 3-6.5 6.5v3c0 2.5 1.3 4.5 3.5 5.5" />
                    <path d="M9 12c0-1.8 1.4-3.2 3-3.2s3 1.4 3 3.2c0 2.3-1.5 3.2-2.2 3.8" />
                    <path d="M17.5 9c1 1 1 3 0 4" />
                    <path d="M19.5 7c2 2 2 6 0 8" />
                </svg>
            </div>

            <div>
                <h2 class="text-lg sm:text-xl font-bold text-[#0b6036]">
                    We Are Listening
                </h2>
                <p class="text-gray-500 text-sm mt-1 leading-relaxed">
                    At Central Philippines State University, every voice matters.
                    This platform gives students, faculty, and staff a safe and simple way to share feedback,
                    concerns, and suggestions to help improve university services.
                </p>
                <p class="text-xs text-gray-400 mt-3">
                    All responses are anonymous and used only for service improvement.
                </p>
            </div>

        </div>

    </div>

    <!-- INFO SECTION -->
    <div class="max-w-2xl mx-auto mb-6 px-4 sm:px-0 relative">

        <div class="flex items-center justify-center gap-2 text-xs sm:text-sm text-gray-500 text-center">
            <svg class="w-4 h-4 text-[#0b6036] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-1a4 4 0 00-3-3.87M9 20H4v-1a4 4 0 013-3.87m10-4.13a4 4 0 10-8 0 4 4 0 008 0zM16 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <p class="leading-relaxed">
                More than <span class="font-semibold text-[#0b6036]">5,000 Cenphilians</span> share this university.
                Your voice helps shape its future.
            </p>
        </div>

    </div>

    <!-- FORM -->
    <div class="max-w-2xl mx-auto glass-card p-5 sm:p-6 md:p-8 rounded-2xl shadow-xl mb-8 relative"
        x-data="feedbackForm()">

        <form method="POST" action="{{ url('/feedback') }}">
            @csrf

            <h6 class="font-semibold text-sm sm:text-base mb-2 text-gray-800">
                Who are you? <span class="text-red-500">*</span>
            </h6>

            <input type="hidden" name="role" :value="role">

            <div class="flex flex-wrap gap-2 mb-4">
                <template x-for="item in roles" :key="item">
                    <button type="button" @click="role = item" :class="role === item ? activeClass : inactiveClass"
                        class="px-3 sm:px-4 py-2 rounded-full border text-xs sm:text-sm transition-all duration-200">
                        <span x-text="item"></span>
                    </button>
                </template>
            </div>

            <hr class="my-5 border-gray-200">

            <h6 class="font-semibold text-sm sm:text-base mb-2 text-gray-800">
                Which office is this about? <span class="text-red-500">*</span>
            </h6>

            <select name="department" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm sm:text-base mb-4 focus:ring-2 focus:ring-[#0b6036] focus:border-transparent transition-all duration-200 bg-white">

                <option value="">Select Office</option>
                <option value="CPSU - University Wide">CPSU - University Wide (Entire Institution)</option>
                @foreach ($offices as $office)
                    <option value="{{ $office->office_name }}">
                        {{ $office->office_name }}
                    </option>
                @endforeach

            </select>

            <hr class="my-5 border-gray-200">

            <h6 class="font-semibold text-sm sm:text-base mb-2 text-gray-800">
                What is this about? <span class="text-red-500">*</span>
            </h6>

            <input type="hidden" name="type" :value="type">

            <div class="flex flex-wrap gap-2 mb-4">
                <template x-for="item in types" :key="item">
                    <button type="button" @click="type = item" :class="type === item ? activeClass : inactiveClass"
                        class="px-3 sm:px-4 py-2 rounded-full border text-xs sm:text-sm transition-all duration-200">
                        <span x-text="item"></span>
                    </button>
                </template>
            </div>

            <hr class="my-5 border-gray-200">

            <h6 class="font-semibold text-sm sm:text-base mb-2 text-center text-gray-800">
                Rate your experience <span class="text-red-500">*</span>
            </h6>

            <input type="hidden" name="rating" :value="rating">

            <div class="flex justify-center gap-3 sm:gap-4 text-4xl sm:text-5xl mb-3">
                <template x-for="i in 5">
                    <div class="flex flex-col items-center gap-1">
                        <span @click="rating = i" :class="i <= rating ? 'text-yellow-400 scale-110' : 'text-gray-300'"
                            class="cursor-pointer transition-all duration-200 transform hover:scale-125">
                            ★
                        </span>
                        <span class="text-[10px] font-medium"
                            :class="{
                                'text-red-500': i === 1 && i <= rating,
                                'text-orange-500': i === 2 && i <= rating,
                                'text-yellow-500': i === 3 && i <= rating,
                                'text-lime-500': i === 4 && i <= rating,
                                'text-green-500': i === 5 && i <= rating,
                                'text-gray-300': i > rating
                            }"
                            x-text="['','Poor','Fair','Good','Very Good','Excellent'][i]">
                        </span>
                    </div>
                </template>
            </div>

            <hr class="my-5 border-gray-200">

            <h6 class="font-semibold text-sm sm:text-base mb-2 text-gray-800">
                Share your thoughts to help us serve you better <span class="text-red-500">*</span>
            </h6>

            <textarea name="feedback" rows="4" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm sm:text-base focus:ring-2 focus:ring-[#0b6036] focus:border-transparent placeholder-gray-400 transition-all duration-200 bg-white"
                placeholder="Please be as specific as possible"></textarea>

            <br><br>

            <button
                class="w-full bg-gradient-to-r from-[#0b6036] to-green-700 text-white py-3 rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-300 flex items-center justify-center gap-2 text-sm sm:text-base font-semibold shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98]">

                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Submit Your Voice
            </button>

            <div class="mt-5 flex items-start sm:items-center gap-2 text-[11px] sm:text-[11px] text-gray-500">
                <svg class="w-4 h-4 text-[#0b6036] flex-shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
                <p class="leading-relaxed">
                    All responses are anonymous and reviewed only in aggregated form to help improve university policies
                    and services.
                </p>
            </div>

        </form>
    </div>

    <script>
        function feedbackForm() {
            return {
                role: '',
                rating: 0,
                type: '',

                roles: [
                    'Student',
                    'Faculty',
                    'Personnel / Staff',
                    'Alumni',
                    'Community',
                    'Other'
                ],

                types: [
                    'Compliment',
                    'Suggestion',
                    'Information Request',
                    'Facility Concern',
                    'Staff Feedback',
                    'Process Concern',
                    'Service Issue',
                    'Complaint'
                ],

                activeClass: 'bg-[#0b6036] text-white border-[#0b6036] shadow-md',
                inactiveClass: 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-[#0b6036] hover:text-[#0b6036]'
            }
        }

        const container = document.getElementById('particles-container');
        const particleCount = window.innerWidth < 768 ? 10 : 25;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = (Math.random() * 15 + 10) + 's';
            particle.style.animationName = 'particle-drift';
            particle.style.animationTimingFunction = 'ease-in-out';
            particle.style.animationIterationCount = 'infinite';
            container.appendChild(particle);
        }
    </script>

</body>

</html>
