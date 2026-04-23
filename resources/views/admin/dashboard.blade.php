@extends('layouts.admin')
<style>
    [x-cloak] {
        display: none !important;
    }

    canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .dashboard-bg {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .stat-card {
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -1px rgb(0 0 0 / 0.03);
    }

    .stat-card:hover {
        transform: scale(1.02) translateY(-2px);
        box-shadow: 0 10px 15px -3px rgb(16 185 129 / 0.1);
    }

    .modern-card {
        border: none;
        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.03), 0 1px 2px -1px rgb(0 0 0 / 0.03);
        transition: all 0.2s ease;
    }

    .modern-card:hover {
        box-shadow: 0 4px 6px -1px rgb(16 185 129 / 0.05);
        transform: translateY(-1px);
    }

    .glass-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        box-shadow: 0 2px 8px -4px rgb(16 185 129 / 0.1);
    }

    .keyword-pill {
        transition: all 0.2s ease;
    }

    .keyword-pill:hover {
        transform: scale(1.05) translateY(-1px);
    }

    .feed-item {
        transition: all 0.2s ease;
    }

    .feed-item:hover {
        background: #fafafa;
        transform: translateX(4px);
    }

    @keyframes pulse-modern {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .live-dot {
        animation: pulse-modern 2s infinite;
    }
</style>

@section('content')
    <div x-data="dashboard()" x-init="init()" x-cloak class="dashboard-bg min-h-screen p-3 sm:p-4 md:p-5 lg:p-6">

        <div x-show="hasNewData" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0" class="fixed top-3 right-3 z-50">
            <div class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow-md flex items-center space-x-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-text="'New feedback! (' + newDataCount + ')'"></span>
                <button @click="refreshData()"
                    class="bg-white text-blue-500 px-3 py-0.5 rounded-md text-xs font-medium hover:bg-blue-50 transition">
                    Refresh
                </button>
                <button @click="hasNewData = false" class="text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        </div>

        <div
            class="glass-header sticky top-0 z-40 -mx-3 -mt-3 mb-5 px-4 sm:px-5 py-3 sm:py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-gray-100">
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-gray-800 flex items-center gap-2">
                    LISTEN
                    <span class="text-sm font-normal text-gray-400">Feedback Intelligence</span>
                </h1>
                <p class="text-emerald-600 text-xs font-medium mt-0.5 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full" :class="{ 'animate-pulse': !isLoading }"></span>
                    Llama 3 • Real-time • <span x-text="lastUpdated"></span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <div class="flex bg-white/90 border border-gray-200 shadow-sm rounded-2xl p-0.5 text-xs font-medium">
                    <button @click="setQuickRange('today')"
                        :class="quickRange === 'today' ? 'bg-emerald-500 text-white shadow-sm' : 'hover:bg-gray-100'"
                        class="px-3 py-1.5 rounded-2xl transition-all">Today</button>
                    <button @click="setQuickRange('week')"
                        :class="quickRange === 'week' ? 'bg-emerald-500 text-white shadow-sm' : 'hover:bg-gray-100'"
                        class="px-3 py-1.5 rounded-2xl transition-all">7d</button>
                    <button @click="setQuickRange('month')"
                        :class="quickRange === 'month' ? 'bg-emerald-500 text-white shadow-sm' : 'hover:bg-gray-100'"
                        class="px-3 py-1.5 rounded-2xl transition-all">30d</button>
                </div>

                <div class="flex items-center bg-white/90 border border-gray-200 rounded-2xl px-3 py-1 shadow-sm gap-2">
                    <input type="date" x-model="dateFrom"
                        class="bg-transparent text-gray-600 focus:outline-none text-xs w-24">
                    <span class="text-emerald-400 text-sm">→</span>
                    <input type="date" x-model="dateTo"
                        class="bg-transparent text-gray-600 focus:outline-none text-xs w-24">
                    <button @click="applyCustomRange"
                        class="bg-emerald-600 text-white px-3 py-1 rounded-2xl text-xs font-medium hover:bg-emerald-700 transition">Go</button>
                    <button @click="clearDateRange" class="text-gray-400 hover:text-gray-600 text-xs">Clear</button>
                </div>

                <div @click="manualRefresh"
                    class="flex items-center gap-1.5 bg-white/95 border border-emerald-200 text-emerald-700 px-3 h-8 rounded-2xl cursor-pointer hover:border-emerald-400 transition text-xs">
                    <div class="relative flex h-2 w-2">
                        <span class="live-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </div>
                    <span class="font-mono text-xs uppercase tracking-wide">LIVE</span>
                    <i class="fa-solid fa-rotate-right text-xs"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card bg-white rounded-2xl p-4">
                <div class="flex justify-between items-start">
                    <div class="space-y-0.5">
                        <p class="uppercase text-xs font-medium text-gray-400 tracking-wide">Total Feedback</p>
                        <h2 class="text-3xl sm:text-4xl font-semibold text-gray-800 tabular-nums" x-text="totalFeedback">0
                        </h2>
                    </div>
                    <div class="text-3xl opacity-70"> </div>
                </div>
                <div class="mt-4 flex items-baseline gap-1.5">
                    <span :class="totalGrowth >= 0 ? 'text-emerald-500' : 'text-red-500'" class="text-xl font-light"
                        x-text="totalGrowth >= 0 ? '↑' : '↓'"></span>
                    <span class="text-base font-semibold tabular-nums" x-text="Math.abs(totalGrowth) + '%'"></span>
                    <span class="text-gray-400 text-xs">vs last</span>
                </div>
            </div>
            <div class="stat-card bg-white rounded-2xl p-4">
                <div class="flex justify-between items-start">
                    <div class="space-y-0.5">
                        <p class="uppercase text-xs font-medium text-gray-400 tracking-wide">Avg. Rating</p>
                        <h2 class="text-3xl sm:text-4xl font-semibold text-gray-800 tabular-nums" x-text="avgRating + '/5'">
                            0/5</h2>
                    </div>
                    <div class="text-3xl opacity-70"> </div>
                </div>
                <div class="mt-4 flex items-baseline gap-1.5">
                    <span :class="avgChange >= 0 ? 'text-emerald-500' : 'text-red-500'" class="text-xl font-light"
                        x-text="avgChange >= 0 ? '↑' : '↓'"></span>
                    <span class="text-base font-semibold tabular-nums" x-text="Math.abs(avgChange).toFixed(1)"></span>
                    <span class="text-gray-400 text-xs">vs last</span>
                </div>
            </div>
            <div class="stat-card bg-white rounded-2xl p-4">
                <div class="flex justify-between items-start">
                    <div class="space-y-0.5">
                        <p class="uppercase text-xs font-medium text-red-400 tracking-wide">Flagged</p>
                        <h2 class="text-3xl sm:text-4xl font-semibold text-red-500 tabular-nums" x-text="flaggedCount">0
                        </h2>
                    </div>
                    <div class="text-3xl opacity-70"> </div>
                </div>
                <p class="mt-4 text-red-500 text-xs flex items-center gap-1">
                    <span x-text="flaggedChange"></span> need review
                </p>
            </div>
            <div class="stat-card bg-white rounded-2xl p-4">
                <div class="flex justify-between items-start">
                    <div class="space-y-0.5">
                        <p class="uppercase text-xs font-medium text-emerald-400 tracking-wide">Positive</p>
                        <h2 class="text-3xl sm:text-4xl font-semibold text-emerald-600 tabular-nums"
                            x-text="sentiment.Positive + '%'">0%</h2>
                    </div>
                    <div class="text-3xl opacity-70"> </div>
                </div>
                <div class="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-400 to-teal-400"
                        :style="`width: ${sentiment.Positive || 0}%`"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mt-4">
            <div class="xl:col-span-7 modern-card bg-white rounded-2xl p-4">
                <div class="flex justify-between mb-3">
                    <h6 class="text-base font-semibold text-gray-700">Submission Trend</h6>
                    <div
                        class="text-xs bg-emerald-100 text-emerald-700 px-2.5 h-5 rounded-full flex items-center font-medium">
                        LIVE</div>
                </div>
                <div class="h-64 sm:h-72 chart-container">
                    <canvas x-ref="trendChart"></canvas>
                </div>
                <h6 class="text-base font-semibold mt-6 pt-4 border-t border-gray-100 flex items-center gap-2">
                    Outstanding Office
                    <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">CURRENT</span>
                </h6>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center mt-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">Office</p>
                        <p class="text-sm font-semibold text-gray-800" x-text="outstandingOffice || 'No data'"></p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs uppercase tracking-wide text-emerald-500">Positive</p>
                        <p class="text-3xl font-light text-emerald-600 mt-0.5" x-text="outstandingOfficeScore">0</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Avg Rating</p>
                        <p class="text-3xl font-semibold text-gray-800 mt-0.5" x-text="avgRating + ' / 5'"></p>
                    </div>
                </div>
            </div>
            <div class="xl:col-span-5 space-y-4">
                <div class="modern-card bg-white rounded-2xl p-4">
                    <h6 class="text-base font-semibold mb-3">Sentiment Breakdown</h6>
                    <div class="flex items-center justify-center min-h-[160px]">
                        <div class="w-full">
                            <div class="h-5 bg-gray-100 rounded-full flex overflow-hidden">
                                <div class="bg-emerald-500 h-full" :style="`width:${sentiment.Positive || 0}%`"></div>
                                <div class="bg-amber-400 h-full" :style="`width:${sentiment.Neutral || 0}%`"></div>
                                <div class="bg-red-500 h-full" :style="`width:${sentiment.Negative || 0}%`"></div>
                            </div>
                            <div class="flex justify-between mt-3 text-xs font-medium">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-emerald-500 rounded"></div>
                                    <span>Positive <span x-text="sentiment.Positive"></span>%</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-amber-400 rounded"></div>
                                    <span>Neutral <span x-text="sentiment.Neutral"></span>%</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-red-500 rounded"></div>
                                    <span>Negative <span x-text="sentiment.Negative"></span>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modern-card bg-white rounded-2xl p-4">
                    <h6 class="text-base font-semibold mb-3">Feedback Types</h6>
                    <div class="h-44 chart-container">
                        <canvas x-ref="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
            <div class="modern-card bg-white rounded-2xl p-4">
                <h6 class="text-base font-semibold mb-3">By Role</h6>
                <div class="h-80 chart-container">
                    <canvas x-ref="roleChart"></canvas>
                </div>
            </div>
            <div class="modern-card bg-white rounded-2xl p-4">
                <h6 class="text-base font-semibold mb-3">By Department</h6>
                <div class="h-80 chart-container">
                    <canvas x-ref="deptChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mt-4">
            <div class="lg:col-span-4 modern-card bg-white rounded-2xl p-4">
                <h6 class="text-base font-semibold mb-3">AI Keywords</h6>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="term in recurringTerms" :key="term.term">
                        <span @click="filterByKeyword(term.term)"
                            class="keyword-pill px-3 py-1.5 text-xs rounded-full cursor-pointer font-medium shadow-sm"
                            :style="getKeywordStyle(term.term)" x-text="term.term"></span>
                    </template>
                    <template x-if="recurringTerms.length === 0">
                        <p class="text-gray-400 text-xs">No terms yet</p>
                    </template>
                </div>
            </div>
            <div class="lg:col-span-4 modern-card bg-white rounded-2xl p-4">
                <h6 class="text-base font-semibold mb-3">Rating Distribution</h6>
                <div class="h-52 chart-container">
                    <canvas x-ref="ratingChart"></canvas>
                </div>
            </div>
            <div class="lg:col-span-4 modern-card bg-white rounded-2xl p-4">
                <h6 class="text-base font-semibold mb-3 text-center">Concerning Offices</h6>
                <div class="space-y-2">
                    <template x-for="(dept, i) in negativeOffices" :key="dept.department">
                        <div class="flex items-center justify-between px-3 py-2 rounded-xl cursor-pointer"
                            :class="i === 0 ? 'bg-gradient-to-r from-red-50 to-white border border-red-200' :
                                'hover:bg-slate-50'">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xl font-bold text-red-200" x-text="i+1"></span>
                                <span class="text-sm font-medium" x-text="dept.department"></span>
                            </div>
                            <span class="font-bold text-red-500 text-base" x-text="dept.total"></span>
                        </div>
                    </template>
                    <template x-if="negativeOffices.length === 0">
                        <p class="text-gray-400 text-xs text-center py-4">No concerning offices</p>
                    </template>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
            <div class="modern-card bg-white rounded-2xl p-4">
                <h6 class="text-base font-semibold mb-3">Top Concerns</h6>
                <div class="space-y-4">
                    <template x-for="item in aiInsights" :key="item.issue || item.title">
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <p class="text-sm font-medium" x-text="item.issue || item.title"></p>
                                <div class="mt-2 h-1.5 bg-slate-100 rounded-full">
                                    <div :class="progressColor(item.priority)" class="h-1.5 rounded-full transition-all"
                                        :style="`width: ${Math.min((item.count || 1) * 20, 100)}%`"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xl font-light text-slate-300" x-text="item.count || 1"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="aiInsights.length === 0">
                        <p class="text-gray-400 text-xs text-center py-4">No insights available</p>
                    </template>
                </div>
            </div>
            <div class="modern-card bg-white rounded-2xl p-2">
                <h6 class="text-base font-semibold mb-3">AI Narrative</h6>
                <div class="space-y-3 max-h-100 overflow-y-auto pr-1">
                    <template x-for="n in aiNarrative" :key="n.title">
                        <div class="rounded-xl p-3 border" :class="calloutClass(n.priority)">
                            <div class="flex justify-between items-start mb-1.5">
                                <p class="font-medium text-sm" x-text="n.title"></p>
                                <span :class="badgeColor(n.priority)"
                                    class="px-2 py-0.5 text-xs rounded-full font-medium uppercase"
                                    x-text="n.priority"></span>
                            </div>
                            <p class="text-xs text-gray-400 mb-1" x-text="n.department || 'Multiple Departments'"></p>
                            <p class="text-slate-600 text-xs leading-relaxed" x-text="n.description"></p>
                        </div>
                    </template>
                    <template x-if="!aiNarrative || aiNarrative.length === 0">
                        <p class="text-gray-400 text-xs text-center py-4">Generating insights...</p>
                    </template>
                </div>
            </div>
        </div>

        <div class="modern-card bg-white rounded-2xl p-4 mt-4">
            <h6 class="text-base font-semibold mb-3">AI Recommendations <span class="text-xs text-gray-400 ml-1">Generated
                    from trends</span></h6>
            <div class="space-y-3">
                <template x-for="rec in aiRecommendations" :key="rec.title">
                    <div class="p-3 rounded-xl border border-gray-100 bg-gradient-to-r from-slate-50 to-white">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-sm font-semibold" x-text="rec.title"></h3>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium uppercase"
                                :class="rec.term === 'short-term' ? 'bg-emerald-100 text-emerald-700' : rec
                                    .term === 'medium-term' ? 'bg-amber-100 text-amber-700' :
                                    'bg-blue-100 text-blue-700'"
                                x-text="rec.term"></span>
                        </div>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs uppercase text-gray-400 mb-1 font-medium">Evidence</p>
                                <p class="text-gray-600 text-xs leading-relaxed" x-text="rec.evidence"></p>
                            </div>
                            <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                                <p class="text-xs uppercase text-emerald-600 mb-1 font-medium">Action</p>
                                <p class="text-gray-700 text-xs leading-relaxed" x-text="rec.action"></p>
                            </div>
                            <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                                <p class="text-xs uppercase text-blue-600 mb-1 font-medium">Impact</p>
                                <p class="text-gray-700 text-xs leading-relaxed" x-text="rec.impact"></p>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="aiRecommendations.length === 0">
                    <p class="text-gray-400 text-xs text-center py-4">Generating recommendations...</p>
                </template>
            </div>
        </div>

        <div class="modern-card bg-white rounded-2xl p-4 mt-4">
            <div class="flex items-center justify-between mb-3">
                <h6 class="text-base font-semibold">Recent Submissions</h6>
                <span class="flex items-center gap-1.5 text-emerald-600 text-xs">
                    <span class="relative flex h-2 w-2"><span
                            class="live-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span></span>
                    LIVE
                </span>
            </div>
            <div class="max-h-96 overflow-auto space-y-2 pr-1 custom-scroll">
                <template x-for="item in filteredFeedback" :key="item.id">
                    <div class="feed-item border border-transparent bg-white rounded-xl p-3 flex gap-3">
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-sm" x-text="item.role"></span>
                                    <span class="text-emerald-600 text-xs" x-text="item.department"></span>
                                </div>
                                <span class="text-xs text-gray-400 font-mono"
                                    x-text="new Date(item.created_at).toLocaleString()"></span>
                            </div>
                            <p class="mt-1.5 text-gray-600 text-sm leading-relaxed" x-text="item.feedback"></p>
                        </div>
                        <div class="text-xl text-amber-400 flex items-center"
                            x-text="'★'.repeat(item.rating || 0) + ''.repeat(5 - (item.rating || 0))"></div>
                    </div>
                </template>
                <template x-if="filteredFeedback.length === 0">
                    <p class="text-gray-400 text-xs text-center py-4">No feedback yet</p>
                </template>
            </div>
        </div>
    </div>


    <script>
    function dashboard() {
        return {
            // State variables
            totalFeedback: 0,
            avgRating: 0,
            flaggedCount: 0,
            flaggedChange: 0,
            outstandingOffice: '',
            outstandingOfficeScore: 0,
            totalGrowth: 0,
            avgChange: 0,
            sentiment: {
                Positive: 0,
                Neutral: 0,
                Negative: 0
            },
            recurringTerms: [],
            aiInsights: [],
            aiNarrative: [],
            recentFeedback: [],
            negativeOffices: [],
            filteredFeedback: [],
            quickRange: 'month',
            lastUpdated: 'just now',
            activeRange: null,
            activeKeywordFilter: null,
            aiRecommendations: [],
            trendChart: null,
            typeChart: null,
            roleChart: null,
            deptChart: null,
            ratingChart: null,
            lastId: 0,
            pollingInterval: null,
            isLoading: false,
            hasNewData: false,
            newDataCount: 0,
            dateFrom: '',
            dateTo: '',
            
            // Dynamic base path that works on both local and online
            basePath: (function() {
                const path = window.location.pathname;
                // Check if we're in a subdirectory like /listen/public
                if (path.includes('/listen/public')) {
                    return window.location.origin + '/listen/public';
                }
                // Otherwise just use origin (for direct access)
                return window.location.origin;
            })(),

            init() {
                console.log('Base path:', this.basePath);
                this.load();
                this.startPolling();
            },

            startPolling() {
                this.stopPolling();
                this.pollingInterval = setInterval(() => this.checkForUpdates(), 3000);
            },

            stopPolling() {
                if (this.pollingInterval) clearInterval(this.pollingInterval);
            },

            async checkForUpdates() {
                if (this.isLoading) return;
                try {
                    const url = `${this.basePath}/admin/dashboard/poll?last_id=${this.lastId}`;
                    console.log('Polling URL:', url);
                    const res = await fetch(url);
                    
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    
                    const data = await res.json();
                    if (data.has_new) {
                        this.hasNewData = true;
                        this.newDataCount = data.new_count;
                        this.lastId = data.latest_id;
                        if (data.latest_feedback && data.new_count === 1) this.addNewFeedback(data.latest_feedback);
                        this.refreshDashboardData();
                    }
                } catch (e) {
                    console.error('Poll error:', e);
                }
            },

            addNewFeedback(feedback) {
                const exists = this.recentFeedback.some(f => f.id === feedback.id);
                if (!exists) {
                    this.recentFeedback.unshift(feedback);
                    if (this.recentFeedback.length > 10) this.recentFeedback.pop();
                }
                this.filteredFeedback = this.activeKeywordFilter ?
                    this.recentFeedback.filter(f => f.feedback.toLowerCase().includes(this.activeKeywordFilter.toLowerCase())) : [...this.recentFeedback];
                this.lastUpdated = 'just now';
            },

            refreshDashboardData() {
                const activeRange = this.activeRange;
                let url = `${this.basePath}/admin/dashboard/data?fresh=1`;
                if (activeRange?.start && activeRange?.end) url += `&start=${activeRange.start}&end=${activeRange.end}`;
                
                console.log('Data URL:', url);
                
                fetch(url).then(res => {
                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                    return res.json();
                }).then(data => {
                    this.totalFeedback = data.totalFeedback ?? 0;
                    this.avgRating = parseFloat(data.avgRating ?? 0).toFixed(1);
                    this.flaggedCount = data.flaggedCount ?? 0;
                    this.totalGrowth = data.totalGrowth ?? 0;
                    this.avgChange = data.avgChange ?? 0;
                    this.flaggedChange = data.flaggedChange ?? 0;
                    this.sentiment = data.sentimentBreakdown || {
                        Positive: 0,
                        Neutral: 0,
                        Negative: 0
                    };
                    this.outstandingOffice = data.outstandingOffice || '';
                    this.outstandingOfficeScore = data.outstandingOfficeScore || 0;
                    this.recurringTerms = data.recurringTerms || [];
                    this.recentFeedback = data.recentFeedback || [];
                    this.negativeOffices = data.negativeByDepartment || [];
                    this.aiInsights = (data.aiInsights || []).map(item => ({
                        issue: item.issue || item.title || 'Unknown',
                        title: item.title || item.issue || 'Unknown',
                        count: item.count || 1,
                        priority: item.priority || 'low',
                        department: item.department || 'Multiple Departments'
                    }));
                    this.aiNarrative = (data.aiNarrative || []).map(item => ({
                        title: item.title || 'Insight',
                        description: item.description || 'No description available',
                        priority: item.priority || 'positive',
                        department: item.department || 'Multiple Departments'
                    }));
                    this.aiRecommendations = data.aiRecommendations || [];
                    this.filteredFeedback = this.activeKeywordFilter ?
                        this.recentFeedback.filter(f => f.feedback.toLowerCase().includes(this.activeKeywordFilter.toLowerCase())) : [...this.recentFeedback];
                    if (this.recentFeedback.length > 0) this.lastId = Math.max(...this.recentFeedback.map(f => f.id));
                    this.updateCharts(data);
                    this.hasNewData = false;
                    this.lastUpdated = 'just now';
                }).catch(e => console.error('Refresh error:', e));
            },

            refreshData() {
                this.hasNewData = false;
                this.load(true);
            },

            setQuickRange(period) {
                const today = new Date();
                let from = new Date(today);
                let to = new Date(today);
                if (period === 'today') from = to;
                else if (period === 'week') from.setDate(today.getDate() - 7);
                else if (period === 'month') from.setMonth(today.getMonth() - 1);
                this.quickRange = period;
                this.activeRange = {
                    start: from.toISOString().split('T')[0],
                    end: to.toISOString().split('T')[0]
                };
                this.dateFrom = this.activeRange.start;
                this.dateTo = this.activeRange.end;
                this.load();
            },

            applyCustomRange() {
                if (!this.dateFrom || !this.dateTo) return;
                this.quickRange = null;
                this.activeRange = {
                    start: this.dateFrom,
                    end: this.dateTo
                };
                this.load();
            },

            clearDateRange() {
                this.dateFrom = '';
                this.dateTo = '';
                this.activeRange = null;
                this.quickRange = 'month';
                this.load();
            },

            manualRefresh() {
                this.load(true);
            },

            filterByKeyword(k) {
                this.activeKeywordFilter = this.activeKeywordFilter === k ? null : k;
                this.filteredFeedback = this.activeKeywordFilter ?
                    this.recentFeedback.filter(f => f.feedback.toLowerCase().includes(this.activeKeywordFilter.toLowerCase())) : [...this.recentFeedback];
            },

            async load(forceFresh = false) {
                if (this.isLoading) return;
                this.isLoading = true;
                this.lastUpdated = 'updating...';
                try {
                    let url = forceFresh ? `${this.basePath}/admin/dashboard/data?fresh=1` : `${this.basePath}/admin/dashboard/data`;
                    if (this.activeRange?.start && this.activeRange?.end) url += (url.includes('?') ? '&' : '?') +
                        `start=${this.activeRange.start}&end=${this.activeRange.end}`;
                    
                    console.log('Load URL:', url);
                    
                    const res = await fetch(url);
                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                    
                    const data = await res.json();
                    this.totalFeedback = data.totalFeedback ?? 0;
                    this.avgRating = parseFloat(data.avgRating ?? 0).toFixed(1);
                    this.flaggedCount = data.flaggedCount ?? 0;
                    this.totalGrowth = data.totalGrowth ?? 0;
                    this.avgChange = data.avgChange ?? 0;
                    this.flaggedChange = data.flaggedChange ?? 0;
                    this.sentiment = data.sentimentBreakdown || {
                        Positive: 0,
                        Neutral: 0,
                        Negative: 0
                    };
                    this.aiRecommendations = data.aiRecommendations || [];
                    this.outstandingOffice = data.outstandingOffice || '';
                    this.outstandingOfficeScore = data.outstandingOfficeScore || 0;
                    this.recurringTerms = data.recurringTerms || [];
                    this.recentFeedback = data.recentFeedback || [];
                    this.negativeOffices = data.negativeByDepartment || [];
                    this.aiInsights = (data.aiInsights || []).map(item => ({
                        issue: item.issue || item.title || 'Unknown',
                        title: item.title || item.issue || 'Unknown',
                        count: item.count || 1,
                        priority: item.priority || 'low',
                        department: item.department || 'Multiple Departments'
                    }));
                    this.aiNarrative = (data.aiNarrative || []).map(item => ({
                        title: item.title || 'Insight',
                        description: item.description || 'No description available',
                        priority: item.priority || 'positive',
                        department: item.department || 'Multiple Departments'
                    }));
                    this.filteredFeedback = [...this.recentFeedback];
                    if (this.recentFeedback.length > 0) this.lastId = Math.max(...this.recentFeedback.map(f => f.id));
                    this.updateCharts(data);
                    this.lastUpdated = 'just now';
                } catch (e) {
                    console.error('Load error:', e);
                    this.lastUpdated = 'error';
                } finally {
                    this.isLoading = false;
                }
            },

            updateCharts(data) {
                if (!data) return;
                if (!this.trendChart) {
                    this.trendChart = new Chart(this.$refs.trendChart.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: data.submissionTrend?.labels ?? [],
                            datasets: [{
                                label: 'Submissions',
                                data: data.submissionTrend?.values ?? [],
                                borderColor: '#10b981',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                                backgroundColor: 'rgba(16,185,129,0.08)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    grid: {
                                        color: '#f1f5f9'
                                    },
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                } else {
                    this.trendChart.data.labels = data.submissionTrend?.labels ?? [];
                    this.trendChart.data.datasets[0].data = data.submissionTrend?.values ?? [];
                    this.trendChart.update();
                }
                const ratingValues = Object.values(data.ratingDistribution || {});
                if (!this.ratingChart) {
                    this.ratingChart = new Chart(this.$refs.ratingChart.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: ['1★', '2★', '3★', '4★', '5★'],
                            datasets: [{
                                data: ratingValues,
                                backgroundColor: '#10b981',
                                borderRadius: 6
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                } else {
                    this.ratingChart.data.datasets[0].data = ratingValues;
                    this.ratingChart.update();
                }
                const typeLabels = Object.keys(data.typeCounts || {});
                const typeValues = Object.values(data.typeCounts || {});
                if (!this.typeChart) {
                    this.typeChart = new Chart(this.$refs.typeChart.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: typeLabels,
                            datasets: [{
                                data: typeValues,
                                backgroundColor: ['#10b981', '#3b82f6', '#eab308', '#ef4444', '#8b5cf6',
                                    '#14b8a6'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 8,
                                        padding: 10,
                                        font: {
                                            size: 10
                                        },
                                        generateLabels: (chart) => {
                                            const dataset = chart.data.datasets[0];
                                            const total = dataset.data.reduce((a, b) => a + b, 0) || 1;
                                            return chart.data.labels.map((label, i) => {
                                                const value = dataset.data[i];
                                                const percent = ((value / total) * 100).toFixed(1);
                                                return {
                                                    text: `${label} (${value}) - ${percent}%`,
                                                    fillStyle: dataset.backgroundColor[i],
                                                    strokeStyle: dataset.backgroundColor[i],
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    this.typeChart.data.labels = typeLabels;
                    this.typeChart.data.datasets[0].data = typeValues;
                    this.typeChart.update();
                }
                const deptLabels = Object.keys(data.departmentCounts || {});
                const deptValues = Object.values(data.departmentCounts || {});

                if (!this.deptChart) {
                    this.deptChart = new Chart(this.$refs.deptChart.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: deptLabels,
                            datasets: [{
                                label: 'Number of Feedbacks',
                                data: deptValues,
                                backgroundColor: '#10b981',
                                borderRadius: 6
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            const value = context.parsed.x || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percent = ((value / total) * 100).toFixed(1);
                                            return `${value} feedbacks (${percent}%)`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: '#f1f5f9'
                                    },
                                    ticks: {
                                        precision: 0,
                                        stepSize: 1
                                    }
                                },
                                y: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            },
                            layout: {
                                padding: {
                                    left: 5,
                                    right: 5,
                                    top: 5,
                                    bottom: 5
                                }
                            }
                        }
                    });
                } else {
                    this.deptChart.data.labels = deptLabels;
                    this.deptChart.data.datasets[0].data = deptValues;
                    this.deptChart.update();
                }
                if (!this.roleChart) {
                    this.roleChart = new Chart(this.$refs.roleChart.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: Object.keys(data.roleCounts || {}),
                            datasets: [{
                                data: Object.values(data.roleCounts || {}),
                                backgroundColor: '#10b981',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                } else {
                    this.roleChart.data.labels = Object.keys(data.roleCounts || {});
                    this.roleChart.data.datasets[0].data = Object.values(data.roleCounts || {});
                    this.roleChart.update();
                }
            },

            calloutClass(p) {
                return p === 'critical' ? 'bg-red-50 border-red-200' : p === 'warning' ?
                    'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200';
            },

            badgeColor(p) {
                return p === 'critical' ? 'bg-red-500 text-white' : p === 'warning' ? 'bg-amber-500 text-white' :
                    'bg-emerald-500 text-white';
            },

            progressColor(p) {
                return p === 'high' ? 'bg-red-500' : p === 'medium' ? 'bg-amber-400' : 'bg-emerald-500';
            },

            getKeywordStyle(term) {
                const t = term.toLowerCase();
                if (['slow', 'delay', 'problem', 'error', 'issue', 'poor', 'bad', 'terrible', 'not'].some(w => t.includes(w))) 
                    return 'background:#fef2f2;color:#b91c1c';
                if (['good', 'fast', 'excellent', 'love', 'great', 'nice', 'wonderful', 'amazing', 'lovely'].some(w => t.includes(w))) 
                    return 'background:#ecfdf5;color:#0f766e';
                return 'background:#f8fafc;color:#334155';
            },

            destroy() {
                this.stopPolling();
                if (this.trendChart) this.trendChart.destroy();
                if (this.typeChart) this.typeChart.destroy();
                if (this.roleChart) this.roleChart.destroy();
                if (this.deptChart) this.deptChart.destroy();
                if (this.ratingChart) this.ratingChart.destroy();
            }
        }
    }
</script>

    {{-- <script>
        function dashboard() {
            return {
                // State variables
                totalFeedback: 0,
                avgRating: 0,
                flaggedCount: 0,
                flaggedChange: 0,
                outstandingOffice: '',
                outstandingOfficeScore: 0,
                totalGrowth: 0,
                avgChange: 0,
                sentiment: {
                    Positive: 0,
                    Neutral: 0,
                    Negative: 0
                },
                recurringTerms: [],
                aiInsights: [],
                aiNarrative: [],
                recentFeedback: [],
                negativeOffices: [],
                filteredFeedback: [],
                quickRange: 'month',
                lastUpdated: 'just now',
                activeRange: null,
                activeKeywordFilter: null,
                aiRecommendations: [],
                trendChart: null,
                typeChart: null,
                roleChart: null,
                deptChart: null,
                ratingChart: null,
                lastId: 0,
                pollingInterval: null,
                isLoading: false,
                hasNewData: false,
                newDataCount: 0,
                dateFrom: '',
                dateTo: '',

                // Use route names or direct paths without duplication
                // basePath: window.location.origin + '/listen/public',

                // init() {
                //     this.load();
                //     this.startPolling();
                // },

                basePath: (function() {
                    const path = window.location.pathname;
                    // Check if we're in a subdirectory like /listen/public
                    if (path.includes('/listen/public')) {
                        return window.location.origin + '/listen/public';
                    }
                    // Otherwise just use origin (for direct access)
                    return window.location.origin;
                })(),

                init() {
                    this.load();
                    this.startPolling();
                },

                startPolling() {
                    this.stopPolling();
                    this.pollingInterval = setInterval(() => this.checkForUpdates(), 3000);
                },

                stopPolling() {
                    if (this.pollingInterval) clearInterval(this.pollingInterval);
                },

                async checkForUpdates() {
                    if (this.isLoading) return;
                    try {
                        const res = await fetch(`${this.basePath}/admin/dashboard/poll?last_id=${this.lastId}`);
                        const data = await res.json();
                        if (data.has_new) {
                            this.hasNewData = true;
                            this.newDataCount = data.new_count;
                            this.lastId = data.latest_id;
                            if (data.latest_feedback && data.new_count === 1) this.addNewFeedback(data.latest_feedback);
                            this.refreshDashboardData();
                        }
                    } catch (e) {
                        console.error('Poll error:', e);
                    }
                },

                addNewFeedback(feedback) {
                    const exists = this.recentFeedback.some(f => f.id === feedback.id);
                    if (!exists) {
                        this.recentFeedback.unshift(feedback);
                        if (this.recentFeedback.length > 10) this.recentFeedback.pop();
                    }
                    this.filteredFeedback = this.activeKeywordFilter ?
                        this.recentFeedback.filter(f => f.feedback.toLowerCase().includes(this.activeKeywordFilter
                            .toLowerCase())) : [...this.recentFeedback];
                    this.lastUpdated = 'just now';
                },

                refreshDashboardData() {
                    const activeRange = this.activeRange;
                    let url = `${this.basePath}/admin/dashboard/data?fresh=1`;
                    if (activeRange?.start && activeRange?.end) url += `&start=${activeRange.start}&end=${activeRange.end}`;
                    fetch(url).then(res => res.json()).then(data => {
                        this.totalFeedback = data.totalFeedback ?? 0;
                        this.avgRating = parseFloat(data.avgRating ?? 0).toFixed(1);
                        this.flaggedCount = data.flaggedCount ?? 0;
                        this.totalGrowth = data.totalGrowth ?? 0;
                        this.avgChange = data.avgChange ?? 0;
                        this.flaggedChange = data.flaggedChange ?? 0;
                        this.sentiment = data.sentimentBreakdown || {
                            Positive: 0,
                            Neutral: 0,
                            Negative: 0
                        };
                        this.outstandingOffice = data.outstandingOffice || '';
                        this.outstandingOfficeScore = data.outstandingOfficeScore || 0;
                        this.recurringTerms = data.recurringTerms || [];
                        this.recentFeedback = data.recentFeedback || [];
                        this.negativeOffices = data.negativeByDepartment || [];
                        this.aiInsights = (data.aiInsights || []).map(item => ({
                            issue: item.issue || item.title || 'Unknown',
                            title: item.title || item.issue || 'Unknown',
                            count: item.count || 1,
                            priority: item.priority || 'low',
                            department: item.department || 'Multiple Departments'
                        }));
                        this.aiNarrative = (data.aiNarrative || []).map(item => ({
                            title: item.title || 'Insight',
                            description: item.description || 'No description available',
                            priority: item.priority || 'positive',
                            department: item.department || 'Multiple Departments'
                        }));
                        this.aiRecommendations = data.aiRecommendations || [];
                        this.filteredFeedback = this.activeKeywordFilter ?
                            this.recentFeedback.filter(f => f.feedback.toLowerCase().includes(this
                                .activeKeywordFilter.toLowerCase())) : [...this.recentFeedback];
                        if (this.recentFeedback.length > 0) this.lastId = Math.max(...this.recentFeedback.map(f => f
                            .id));
                        this.updateCharts(data);
                        this.hasNewData = false;
                        this.lastUpdated = 'just now';
                    }).catch(e => console.error('Refresh error:', e));
                },

                refreshData() {
                    this.hasNewData = false;
                    this.load(true);
                },

                setQuickRange(period) {
                    const today = new Date();
                    let from = new Date(today);
                    let to = new Date(today);
                    if (period === 'today') from = to;
                    else if (period === 'week') from.setDate(today.getDate() - 7);
                    else if (period === 'month') from.setMonth(today.getMonth() - 1);
                    this.quickRange = period;
                    this.activeRange = {
                        start: from.toISOString().split('T')[0],
                        end: to.toISOString().split('T')[0]
                    };
                    this.dateFrom = this.activeRange.start;
                    this.dateTo = this.activeRange.end;
                    this.load();
                },

                applyCustomRange() {
                    if (!this.dateFrom || !this.dateTo) return;
                    this.quickRange = null;
                    this.activeRange = {
                        start: this.dateFrom,
                        end: this.dateTo
                    };
                    this.load();
                },

                clearDateRange() {
                    this.dateFrom = '';
                    this.dateTo = '';
                    this.activeRange = null;
                    this.quickRange = 'month';
                    this.load();
                },

                manualRefresh() {
                    this.load(true);
                },

                filterByKeyword(k) {
                    this.activeKeywordFilter = this.activeKeywordFilter === k ? null : k;
                    this.filteredFeedback = this.activeKeywordFilter ?
                        this.recentFeedback.filter(f => f.feedback.toLowerCase().includes(this.activeKeywordFilter
                            .toLowerCase())) : [...this.recentFeedback];
                },

                async load(forceFresh = false) {
                    if (this.isLoading) return;
                    this.isLoading = true;
                    this.lastUpdated = 'updating...';
                    try {
                        let url = forceFresh ? `${this.basePath}/admin/dashboard/data?fresh=1` :
                            `${this.basePath}/admin/dashboard/data`;
                        if (this.activeRange?.start && this.activeRange?.end) url += (url.includes('?') ? '&' : '?') +
                            `start=${this.activeRange.start}&end=${this.activeRange.end}`;
                        const res = await fetch(url);
                        const data = await res.json();
                        this.totalFeedback = data.totalFeedback ?? 0;
                        this.avgRating = parseFloat(data.avgRating ?? 0).toFixed(1);
                        this.flaggedCount = data.flaggedCount ?? 0;
                        this.totalGrowth = data.totalGrowth ?? 0;
                        this.avgChange = data.avgChange ?? 0;
                        this.flaggedChange = data.flaggedChange ?? 0;
                        this.sentiment = data.sentimentBreakdown || {
                            Positive: 0,
                            Neutral: 0,
                            Negative: 0
                        };
                        this.aiRecommendations = data.aiRecommendations || [];
                        this.outstandingOffice = data.outstandingOffice || '';
                        this.outstandingOfficeScore = data.outstandingOfficeScore || 0;
                        this.recurringTerms = data.recurringTerms || [];
                        this.recentFeedback = data.recentFeedback || [];
                        this.negativeOffices = data.negativeByDepartment || [];
                        this.aiInsights = (data.aiInsights || []).map(item => ({
                            issue: item.issue || item.title || 'Unknown',
                            title: item.title || item.issue || 'Unknown',
                            count: item.count || 1,
                            priority: item.priority || 'low',
                            department: item.department || 'Multiple Departments'
                        }));
                        this.aiNarrative = (data.aiNarrative || []).map(item => ({
                            title: item.title || 'Insight',
                            description: item.description || 'No description available',
                            priority: item.priority || 'positive',
                            department: item.department || 'Multiple Departments'
                        }));
                        this.filteredFeedback = [...this.recentFeedback];
                        if (this.recentFeedback.length > 0) this.lastId = Math.max(...this.recentFeedback.map(f => f
                            .id));
                        this.updateCharts(data);
                        this.lastUpdated = 'just now';
                    } catch (e) {
                        console.error('Load error:', e);
                        this.lastUpdated = 'error';
                    } finally {
                        this.isLoading = false;
                    }
                },

                updateCharts(data) {
                    if (!data) return;
                    if (!this.trendChart) {
                        this.trendChart = new Chart(this.$refs.trendChart.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: data.submissionTrend?.labels ?? [],
                                datasets: [{
                                    label: 'Submissions',
                                    data: data.submissionTrend?.values ?? [],
                                    borderColor: '#10b981',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: true,
                                    backgroundColor: 'rgba(16,185,129,0.08)'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        grid: {
                                            color: '#f1f5f9'
                                        },
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        this.trendChart.data.labels = data.submissionTrend?.labels ?? [];
                        this.trendChart.data.datasets[0].data = data.submissionTrend?.values ?? [];
                        this.trendChart.update();
                    }
                    const ratingValues = Object.values(data.ratingDistribution || {});
                    if (!this.ratingChart) {
                        this.ratingChart = new Chart(this.$refs.ratingChart.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: ['1★', '2★', '3★', '4★', '5★'],
                                datasets: [{
                                    data: ratingValues,
                                    backgroundColor: '#10b981',
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    } else {
                        this.ratingChart.data.datasets[0].data = ratingValues;
                        this.ratingChart.update();
                    }
                    const typeLabels = Object.keys(data.typeCounts || {});
                    const typeValues = Object.values(data.typeCounts || {});
                    if (!this.typeChart) {
                        this.typeChart = new Chart(this.$refs.typeChart.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: typeLabels,
                                datasets: [{
                                    data: typeValues,
                                    backgroundColor: ['#10b981', '#3b82f6', '#eab308', '#ef4444', '#8b5cf6',
                                        '#14b8a6'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 8,
                                            padding: 10,
                                            font: {
                                                size: 10
                                            },
                                            generateLabels: (chart) => {
                                                const dataset = chart.data.datasets[0];
                                                const total = dataset.data.reduce((a, b) => a + b, 0) || 1;
                                                return chart.data.labels.map((label, i) => {
                                                    const value = dataset.data[i];
                                                    const percent = ((value / total) * 100).toFixed(1);
                                                    return {
                                                        text: `${label} (${value}) - ${percent}%`,
                                                        fillStyle: dataset.backgroundColor[i],
                                                        strokeStyle: dataset.backgroundColor[i],
                                                        hidden: false,
                                                        index: i
                                                    };
                                                });
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        this.typeChart.data.labels = typeLabels;
                        this.typeChart.data.datasets[0].data = typeValues;
                        this.typeChart.update();
                    }
                    const deptLabels = Object.keys(data.departmentCounts || {});
                    const deptValues = Object.values(data.departmentCounts || {});

                    if (!this.deptChart) {
                        this.deptChart = new Chart(this.$refs.deptChart.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: deptLabels,
                                datasets: [{
                                    label: 'Number of Feedbacks',
                                    data: deptValues,
                                    backgroundColor: '#10b981',
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: (context) => {
                                                const value = context.parsed.x || 0;
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percent = ((value / total) * 100).toFixed(1);
                                                return `${value} feedbacks (${percent}%)`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            color: '#f1f5f9'
                                        },
                                        ticks: {
                                            precision: 0,
                                            stepSize: 1
                                        }
                                    },
                                    y: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            font: {
                                                size: 10
                                            }
                                        }
                                    }
                                },
                                layout: {
                                    padding: {
                                        left: 5,
                                        right: 5,
                                        top: 5,
                                        bottom: 5
                                    }
                                }
                            }
                        });
                    } else {
                        this.deptChart.data.labels = deptLabels;
                        this.deptChart.data.datasets[0].data = deptValues;
                        this.deptChart.update();
                    }
                    if (!this.roleChart) {
                        this.roleChart = new Chart(this.$refs.roleChart.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: Object.keys(data.roleCounts || {}),
                                datasets: [{
                                    data: Object.values(data.roleCounts || {}),
                                    backgroundColor: '#10b981',
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    } else {
                        this.roleChart.data.labels = Object.keys(data.roleCounts || {});
                        this.roleChart.data.datasets[0].data = Object.values(data.roleCounts || {});
                        this.roleChart.update();
                    }
                },

                calloutClass(p) {
                    return p === 'critical' ? 'bg-red-50 border-red-200' : p === 'warning' ?
                        'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200';
                },

                badgeColor(p) {
                    return p === 'critical' ? 'bg-red-500 text-white' : p === 'warning' ? 'bg-amber-500 text-white' :
                        'bg-emerald-500 text-white';
                },

                progressColor(p) {
                    return p === 'high' ? 'bg-red-500' : p === 'medium' ? 'bg-amber-400' : 'bg-emerald-500';
                },

                getKeywordStyle(term) {
                    const t = term.toLowerCase();
                    if (['slow', 'delay', 'problem', 'error', 'issue', 'poor', 'bad', 'terrible', 'not'].some(w => t
                            .includes(w)))
                        return 'background:#fef2f2;color:#b91c1c';
                    if (['good', 'fast', 'excellent', 'love', 'great', 'nice', 'wonderful', 'amazing', 'lovely'].some(w => t
                            .includes(w)))
                        return 'background:#ecfdf5;color:#0f766e';
                    return 'background:#f8fafc;color:#334155';
                },

                destroy() {
                    this.stopPolling();
                    if (this.trendChart) this.trendChart.destroy();
                    if (this.typeChart) this.typeChart.destroy();
                    if (this.roleChart) this.roleChart.destroy();
                    if (this.deptChart) this.deptChart.destroy();
                    if (this.ratingChart) this.ratingChart.destroy();
                }
            }
        }
    </script> --}}
@endsection
