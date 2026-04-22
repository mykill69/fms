<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CPSU Feedback Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="{{ asset('js/tailwind.js') }}"></script>
    <script defer src="{{ asset('js/alpine.js') }}"></script>
</head>

<body class="bg-gray-100 min-h-screen py-6 sm:py-10 px-4">

    <!-- HEADER -->
    <div class="text-center mb-6 sm:mb-8">
        <img src="/img/cpsu_logo.png" class="mx-auto w-16 sm:w-20 md:w-24 mb-3">

        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-wide text-[#0b6036]"
            style="font-family: 'Arial Black', Arial, sans-serif;">
            LISTEN
        </h1>

        <p class="text-xs sm:text-sm font-bold text-gray-800 mt-2 px-2">
            The Voice of the CPSU Community, where every voice becomes insight.
        </p>
    </div>
    @if (session('success'))
        <div
            class="max-w-2xl mx-auto mb-4 px-4 py-3 rounded-lg bg-green-100 border border-green-300 text-green-800 text-sm sm:text-base">
            {{ session('success') }}
        </div>
    @endif
    <!-- INTRO CARD -->
    <div class="max-w-2xl mx-auto bg-white p-5 sm:p-6 md:p-8 rounded-2xl shadow-lg mb-6">

        <div class="flex items-start gap-3 sm:gap-4">

            <!-- ICON -->
            <div class="relative w-10 h-10 flex items-center justify-center">

                <!-- Pulse -->
                <span
                    class="absolute inline-flex h-full w-full rounded-full bg-[#0b6036] opacity-20 animate-ping"></span>

                <!-- Icon -->
                <svg width="58" height="58" viewBox="0 0 24 24" fill="none" stroke="#0b6036" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">

                    <!-- Ear (clean FA-style outline) -->
                    <path d="M12 3c-3.6 0-6.5 3-6.5 6.5v3c0 2.5 1.3 4.5 3.5 5.5" />
                    <path d="M9 12c0-1.8 1.4-3.2 3-3.2s3 1.4 3 3.2c0 2.3-1.5 3.2-2.2 3.8" />

                    <!-- Sound waves (FontAwesome-like simplicity) -->
                    <path d="M17.5 9c1 1 1 3 0 4" />
                    <path d="M19.5 7c2 2 2 6 0 8" />

                </svg>
            </div>

            <!-- TEXT -->
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
    <div class="max-w-2xl mx-auto mb-6 px-4 sm:px-0">

        <div class="flex items-center justify-center gap-2 text-xs sm:text-sm text-gray-500 text-center">

            <!-- ICON -->
            <svg class="w-4 h-4 text-[#0b6036] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-1a4 4 0 00-3-3.87M9 20H4v-1a4 4 0 013-3.87m10-4.13a4 4 0 10-8 0 4 4 0 008 0zM16 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>

            <!-- TEXT -->
            <p class="leading-relaxed">
                More than <span class="font-semibold">5,000 Cenphilians</span> share this university.
                Your voice helps shape its future.
            </p>

        </div>

    </div>

    <!-- FORM -->
    <div class="max-w-2xl mx-auto bg-white p-5 sm:p-6 md:p-8 rounded-2xl shadow-lg" x-data="feedbackForm()">

        <form method="POST" action="/feedback">
            @csrf

            <!-- ROLE -->
            <h6 class="font-semibold text-sm sm:text-base mb-2">
                Who are you? <span class="text-red-500">*</span>
            </h6>

            <input type="hidden" name="role" :value="role">

            <div class="flex flex-wrap gap-2 mb-4">
                <template x-for="item in roles" :key="item">
                    <button type="button" @click="role = item" :class="role === item ? activeClass : inactiveClass"
                        class="px-3 sm:px-4 py-2 rounded-full border text-xs sm:text-sm">
                        <span x-text="item"></span>
                    </button>
                </template>
            </div>

            <hr class="my-5">

            <!-- OFFICE -->
            <h6 class="font-semibold text-sm sm:text-base mb-2">
                Which office is this about? <span class="text-red-500">*</span>
            </h6>

            <select name="department" required
                class="w-full border rounded-lg px-3 py-2 text-sm sm:text-base mb-4 focus:ring focus:ring-blue-200">

                <option value="">Select Office</option>

                @foreach ($offices as $office)
                    <option value="{{ $office->office_name }}">
                        {{ $office->office_name }}
                    </option>
                @endforeach

            </select>

            <hr class="my-5">

            <!-- TYPE -->
            <h6 class="font-semibold text-sm sm:text-base mb-2">
                What is this about? <span class="text-red-500">*</span>
            </h6>

            <input type="hidden" name="type" :value="type">

            <div class="flex flex-wrap gap-2 mb-4">
                <template x-for="item in types" :key="item">
                    <button type="button" @click="type = item" :class="type === item ? activeClass : inactiveClass"
                        class="px-3 sm:px-4 py-2 rounded-full border text-xs sm:text-sm">
                        <span x-text="item"></span>
                    </button>
                </template>
            </div>

            <hr class="my-5">

            <!-- RATING -->
            <h6 class="font-semibold text-sm sm:text-base mb-2 text-center">
                Rate your experience <span class="text-red-500">*</span>
            </h6>

            <input type="hidden" name="rating" :value="rating">

            <div class="flex justify-center gap-3 sm:gap-4 text-4xl sm:text-5xl mb-6">
                <template x-for="i in 5">
                    <span @click="rating = i" :class="i <= rating ? 'text-yellow-400 scale-110' : 'text-gray-300'"
                        class="cursor-pointer transition transform hover:scale-125">
                        ★
                    </span>
                </template>
            </div>

            <hr class="my-5">

            <!-- FEEDBACK -->
            <h6 class="font-semibold text-sm sm:text-base mb-2">
                What is one thing you would change to improve CPSU? <span class="text-red-500">*</span>
            </h6>

            <textarea name="feedback" rows="4" required
                class="w-full border rounded-lg px-3 py-2 text-sm sm:text-base focus:ring focus:ring-blue-200 placeholder-gray-400"
                placeholder="Please be as specific as possible"></textarea>

            <br><br>

            <!-- SUBMIT -->
            <button
                class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2 text-sm sm:text-base">

                Submit Your Voice
            </button>

            <!-- ANONYMITY NOTE -->
            <div class="mt-5 flex items-start sm:items-center gap-2 text-[11px] sm:text-[11px] text-gray-500">

                <!-- SHIELD ICON -->
                <svg class="w-4 h-4 text-[#0b6036] flex-shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>

                <!-- TEXT -->
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
                    'External Stakeholder'
                ],

                types: [
                    'Suggestion',
                    'Complaint',
                    'Compliment',
                    'Information Request',
                    'Service Issue',
                    'Facility Concern',
                    'Staff Feedback',
                    'Process Concern'
                ],

                activeClass: 'bg-blue-600 text-white border-blue-600',
                inactiveClass: 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
            }
        }
    </script>


    <!-- SCRIPT -->
    <script>
        /* ROLE SELECT */
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('role').value = this.innerText;
            });
        });

        /* TYPE SELECT */
        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('type').value = this.innerText;
            });
        });

        /* STAR RATING */
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                let value = this.getAttribute('data-value');
                document.getElementById('rating').value = value;

                document.querySelectorAll('.star').forEach(s => {
                    s.classList.remove('selected');
                });

                for (let i = 0; i < value; i++) {
                    document.querySelectorAll('.star')[i].classList.add('selected');
                }
            });
        });
    </script>

</body>

</html>
