<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>LISTEN - CPSU Feedback System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="{{ asset('js/tailwind.js') }}"></script>
    <script defer src="{{ asset('js/alpine.js') }}"></script>
    <style>
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-15px) rotate(1deg); }
            66% { transform: translateY(-5px) rotate(-1deg); }
        }
        
        @keyframes float-slow-reverse {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(15px) rotate(-1deg); }
            66% { transform: translateY(5px) rotate(1deg); }
        }
        
        @keyframes pulse-soft {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes slide-in-up {
            0% { transform: translateY(30px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .animate-float-slow { animation: float-slow 8s ease-in-out infinite; }
        .animate-float-slow-reverse { animation: float-slow-reverse 8s ease-in-out infinite; }
        .animate-pulse-soft { animation: pulse-soft 4s ease-in-out infinite; }
        .animate-gradient-shift { animation: gradient-shift 3s ease infinite; background-size: 200% 200%; }
        .animate-slide-in-up { animation: slide-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .animate-shake { animation: shake 0.5s ease-in-out; }
        
        .glass-morphism {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .glass-morphism-dark {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .input-focus-effect {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .input-focus-effect:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.2), 0 8px 10px -6px rgba(16, 185, 129, 0.1);
        }
        
        .stagger-animation > * {
            opacity: 0;
            animation: slide-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        
        .stagger-animation > *:nth-child(1) { animation-delay: 0.05s; }
        .stagger-animation > *:nth-child(2) { animation-delay: 0.1s; }
        .stagger-animation > *:nth-child(3) { animation-delay: 0.15s; }
        .stagger-animation > *:nth-child(4) { animation-delay: 0.2s; }
        .stagger-animation > *:nth-child(5) { animation-delay: 0.25s; }
        .stagger-animation > *:nth-child(6) { animation-delay: 0.3s; }
        
        .toggle-password-btn {
            transition: all 0.2s ease;
        }
        
        .toggle-password-btn:hover {
            transform: scale(1.1);
        }
        
        .remember-checkbox {
            transition: all 0.2s ease;
        }
        
        .remember-checkbox:checked {
            background-color: #10b981;
            border-color: #10b981;
            animation: pulse-soft 0.3s ease;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(16, 185, 129, 0.3);
            border-radius: 50%;
            pointer-events: none;
        }
    </style>
</head>
<body class="relative min-h-screen overflow-x-hidden" x-data="loginForm()" x-init="initParticles()">
    
    <div class="fixed inset-0 bg-gradient-to-br from-emerald-50 via-blue-50 to-indigo-50 animate-gradient-shift">
        <div id="particles-container" class="absolute inset-0 pointer-events-none"></div>
    </div>
    
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float-slow"></div>
        <div class="absolute top-1/2 -left-40 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float-slow-reverse"></div>
        <div class="absolute -bottom-40 right-1/4 w-80 h-80 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse-soft"></div>
        <div class="absolute top-1/3 right-1/3 w-64 h-64 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-float-slow" style="animation-delay: 2s;"></div>
    </div>
    
    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-6xl mx-auto">
            <div class="flex flex-col lg:flex-row items-center justify-center gap-8 lg:gap-16">
                
                <div class="w-full lg:w-1/2 text-center lg:text-left stagger-animation">
                    <div class="inline-flex items-center justify-center w-28 h-28 sm:w-32 sm:h-32 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-3xl shadow-2xl mb-6 transform hover:scale-105 transition-transform duration-300">
                        <img src="{{ asset('img/cpsu_logo.png') }}" alt="CPSU Logo" class="w-20 h-20 sm:w-24 sm:h-24 object-contain">
                    </div>
                    
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black mb-4">
                        <span class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 bg-clip-text text-transparent animate-gradient-shift">
                            LISTEN
                        </span>
                    </h1>
                    
                    <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                        The Voice of the CPSU Community,<br>
                        <span class="font-semibold text-emerald-700">where every voice becomes insight.</span>
                    </p>
                    
                    <div class="hidden lg:block mt-8 space-y-4 text-gray-500">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span>Secure & Encrypted Access</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <span>Role-Based Dashboard</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span>Real-Time Analytics</span>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-[480px]">
                    <div class="glass-morphism rounded-3xl shadow-2xl p-6 sm:p-8 border border-white/40 stagger-animation">
                        
                        <div class="text-center mb-8">
                            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Welcome Back</h2>
                            <p class="text-gray-500 text-sm sm:text-base">Sign in with your email to continue</p>
                        </div>
                        
                        @if($errors->any())
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl animate-slide-in-up" 
                             x-show="showError" 
                             x-transition:leave="transition ease-in duration-300"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-5 h-5 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-red-800">{{ $errors->first() }}</p>
                                </div>
                                <button @click="showError = false" class="flex-shrink-0 text-red-400 hover:text-red-600 transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        @endif
                        
                        @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl animate-slide-in-up"
                             x-show="showSuccess"
                             x-init="setTimeout(() => showSuccess = false, 5000)">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-5 h-5 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                </div>
                                <button @click="showSuccess = false" class="flex-shrink-0 text-green-400 hover:text-green-600 transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        @endif
                        
                        <form method="POST" action="{{ route('login') }}" @submit="handleSubmit">
                            @csrf
                            
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2 text-emerald-500"></i>Email Address
                                    </label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-envelope text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                        </div>
                                        <input type="email" 
                                               name="email" 
                                               value="{{ old('email') }}" 
                                               required 
                                               autofocus
                                               x-model="email"
                                               @focus="fieldFocus = 'email'"
                                               @blur="fieldFocus = null"
                                               class="input-focus-effect w-full pl-12 pr-4 py-3.5 sm:py-4 glass-morphism-dark border-0 rounded-xl text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:bg-white/80 transition-all duration-300"
                                               placeholder="your.email@cpsu.edu.ph">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-lock mr-2 text-emerald-500"></i>Password
                                    </label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-key text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                        </div>
                                        <input :type="showPassword ? 'text' : 'password'" 
                                               name="password" 
                                               required
                                               x-model="password"
                                               @focus="fieldFocus = 'password'"
                                               @blur="fieldFocus = null"
                                               class="input-focus-effect w-full pl-12 pr-12 py-3.5 sm:py-4 glass-morphism-dark border-0 rounded-xl text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:bg-white/80 transition-all duration-300"
                                               placeholder="••••••••">
                                        <button type="button" 
                                                @click="showPassword = !showPassword" 
                                                class="toggle-password-btn absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                                            <i class="fas text-lg" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center cursor-pointer group">
                                        <input type="checkbox" 
                                               name="remember" 
                                               class="remember-checkbox w-4 h-4 text-emerald-500 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 focus:ring-2">
                                        <span class="ml-2.5 text-sm text-gray-600 group-hover:text-gray-800 transition">Remember me</span>
                                    </label>
                                    
                                    <a href="#" 
                                       class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors hover:underline">
                                        Forgot password?
                                    </a>
                                </div>
                                
                                <div class="pt-2">
                                    <button type="submit" 
                                            :disabled="loading"
                                            class="relative w-full py-3.5 sm:py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-semibold text-base hover:from-emerald-600 hover:to-emerald-700 transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 overflow-hidden group">
                                        <span x-show="!loading" class="flex items-center justify-center gap-2">
                                            <i class="fas fa-sign-in-alt"></i>
                                            Sign In
                                        </span>
                                        <span x-show="loading" class="flex items-center justify-center gap-2">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            Authenticating...
                                        </span>
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="mt-8 pt-6 border-t border-gray-200/50">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="flex items-center gap-1.5">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    System Online
                                </span>
                                <span>CPSU Feedback v2.1.0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 text-center lg:hidden">
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-lock text-emerald-500 mr-1"></i>
                            Secure & Encrypted Connection
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function loginForm() {
            return {
                email: '{{ old('email') }}',
                password: '',
                showPassword: false,
                loading: false,
                showError: true,
                showSuccess: true,
                fieldFocus: null,
                particles: [],
                
                initParticles() {
                    const container = document.getElementById('particles-container');
                    const particleCount = window.innerWidth < 768 ? 15 : 30;
                    
                    for (let i = 0; i < particleCount; i++) {
                        const particle = document.createElement('div');
                        particle.className = 'particle';
                        particle.style.left = Math.random() * 100 + '%';
                        particle.style.top = Math.random() * 100 + '%';
                        particle.style.animationDelay = Math.random() * 5 + 's';
                        particle.style.animationDuration = (Math.random() * 5 + 5) + 's';
                        container.appendChild(particle);
                    }
                },
                
                handleSubmit(e) {
                    if (this.email && this.password) {
                        this.loading = true;
                    }
                },
                
                togglePassword() {
                    this.showPassword = !this.showPassword;
                }
            }
        }
        
        window.addEventListener('resize', () => {
            const container = document.getElementById('particles-container');
            if (container) {
                container.innerHTML = '';
                const particleCount = window.innerWidth < 768 ? 15 : 30;
                
                for (let i = 0; i < particleCount; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.top = Math.random() * 100 + '%';
                    particle.style.animationDelay = Math.random() * 5 + 's';
                    particle.style.animationDuration = (Math.random() * 5 + 5) + 's';
                    container.appendChild(particle);
                }
            }
        });
    </script>
</body>
</html>