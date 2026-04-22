@extends('layouts.admin')
<style>
    [x-cloak] {
        display: none;
    }

    canvas {
        width: 100% !important;
        height: 100% !important;
    }

    @keyframes heartbeatSlow {

        0%,
        100% {
            transform: scale(1);
        }

        10% {
            transform: scale(1.03);
        }

        20% {
            transform: scale(0.99);
        }

        30% {
            transform: scale(1.06);
        }

        40% {
            transform: scale(1);
        }
    }

    .animate-heartbeat-slow {
        animation: heartbeatSlow 5.5s ease-in-out infinite;
        transform-origin: center;
    }

    animation: {
        duration: 700,
            easing: 'easeInOutQuart'
    }
</style>
@section('content')
    <div x-data="dashboard()" x-init="init()" x-cloak class="space-y-6 p-4 md:p-6">

        <!-- HEADER -->
        <!-- HEADER -->
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">

            <!-- LEFT -->
            <div class="space-y-1">
                <h3 class="text-xl sm:text-2xl font-bold leading-tight">
                    AI Feedback Analysis Dashboard
                </h3>

                <p class="text-gray-500 text-sm">
                    Real-time insights powered by Llama 3 -
                    <span class="font-semibold text-emerald-400"
                        x-text="currentMonthSubmissions + ' total responses this month'">
                    </span>
                </p>
            </div>

            <!-- RIGHT -->
            <div class="flex flex-col lg:flex-row lg:items-end gap-3 w-full xl:w-auto">

                <!-- AI LIVE BADGE -->
                <span
                    class="inline-flex items-center gap-2 bg-emerald-400 text-white px-3 py-2 rounded-full text-sm shadow-md w-fit">

                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full rounded-full bg-white opacity-60 animate-ping"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                    </span>

                    AI Live Analytics
                </span>

                <!-- DATE FILTER WRAPPER -->
                <div x-data="dateRangeFilter()" class="flex flex-col sm:flex-row lg:items-end gap-2 w-full xl:w-auto">

                    <!-- FROM -->
                    <div class="flex flex-col w-full sm:w-auto">
                        <label class="text-xs text-gray-400 mb-1">From</label>
                        <input type="date" x-model="from"
                            class="h-[38px] w-full sm:w-[150px] border border-gray-300 rounded-lg px-2 text-sm focus:ring-2 focus:ring-emerald-400">
                    </div>

                    <!-- TO -->
                    <div class="flex flex-col w-full sm:w-auto">
                        <label class="text-xs text-gray-400 mb-1">To</label>
                        <input type="date" x-model="to"
                            class="h-[38px] w-full sm:w-[150px] border border-gray-300 rounded-lg px-2 text-sm focus:ring-2 focus:ring-emerald-400">
                    </div>

                    <!-- BUTTONS -->
                    <div class="flex gap-2 w-full sm:w-auto">

                        <button @click="applyRange"
                            class="h-[38px] flex-1 sm:flex-none bg-emerald-500 text-white px-4 rounded-lg text-sm hover:bg-emerald-600 transition">
                            Apply
                        </button>

                        <button @click="resetRange"
                            class="h-[38px] flex-1 sm:flex-none text-gray-500 text-sm hover:text-red-500">
                            Reset
                        </button>

                    </div>

                </div>
            </div>

        </div>

        <!-- STATS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- TOTAL SUBMISSIONS -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border">
                <p class="text-gray-500 text-sm">Total Submissions</p>

                <h2 class="text-2xl font-bold" x-text="totalFeedback"></h2>

                <p class="text-xs mt-1" :class="totalGrowth >= 0 ? 'text-green-500' : 'text-red-500'">
                    <span x-text="totalGrowth >= 0 ? '+' + totalGrowth : totalGrowth"></span>%
                    vs last month
                </p>
            </div>

            <!-- AVG SATISFACTION -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border">
                <p class="text-gray-500 text-sm">Avg Satisfaction</p>

                <h2 class="text-2xl font-bold" x-text="avgRating + ' / 5'"></h2>

                <p class="text-xs mt-1" :class="avgChange >= 0 ? 'text-green-500' : 'text-red-500'">
                    <span x-text="avgChange >= 0 ? '+' + avgChange : avgChange"></span>
                    vs last month
                </p>
            </div>

            <!-- FLAGGED ITEMS -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border">
                <p class="text-gray-500 text-sm">Flagged Items</p>

                <h2 class="text-2xl font-bold" x-text="flaggedCount"></h2>

                <p class="text-xs mt-1 text-red-500">
                    <span x-text="flaggedChange >= 0 ? '+' + flaggedChange : flaggedChange"></span>
                    needs action
                </p>
            </div>

            <!-- POSITIVE SENTIMENT -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border">
                <p class="text-gray-500 text-sm">Positive Sentiment</p>

                <h2 class="text-2xl font-bold" x-text="sentiment.Positive + '%'"></h2>

                <p class="text-xs mt-1 text-green-500">
                    +3% vs last month
                </p>
            </div>

        </div>
        <!-- CHARTS for Submission trend and feedback by type -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- Submission Trend -->
            <div class="bg-white p-4 rounded-xl shadow h-[420px] flex flex-col">

                <!-- TITLE -->
                <h6 class="font-semibold mb-2 flex-shrink-0">
                    Submission Trend
                </h6>

                <!-- LINE CHART -->
                <div class="flex-1 relative">
                    <canvas x-ref="trendChart" class="absolute inset-0 w-full h-full"></canvas>
                </div>

                <!-- SENTIMENT BREAKDOWN (SINGLE 100% STACKED BAR) -->
                <div class="mt-4">

                    <div class="w-full h-4 bg-gray-200 rounded-full overflow-hidden flex">

                        <!-- Positive -->
                        <div class="h-full bg-green-500" :style="`width:${sentiment.Positive || 0}%`"></div>

                        <!-- Neutral -->
                        <div class="h-full bg-yellow-400" :style="`width:${sentiment.Neutral || 0}%`"></div>

                        <!-- Negative -->
                        <div class="h-full bg-red-500" :style="`width:${sentiment.Negative || 0}%`"></div>

                    </div>

                    <!-- LABELS -->
                    <div class="flex justify-between text-sm mt-2 text-gray-600">

                        <span>
                            Positive: <span x-text="sentiment.Positive || 0"></span>%
                        </span>

                        <span>
                            Neutral: <span x-text="sentiment.Neutral || 0"></span>%
                        </span>

                        <span>
                            Negative: <span x-text="sentiment.Negative || 0"></span>%
                        </span>

                    </div>

                </div>

            </div>

            <!-- Feedback Type -->
            <div class="bg-white p-4 rounded-xl shadow min-h-[300px] flex flex-col">

                <h6 class="mb-2 font-semibold">Feedback Type</h6>

                <div class="relative flex-1">
                    <canvas x-ref="typeChart" class="absolute inset-0"></canvas>
                </div>

            </div>

        </div>

        <!-- CHARTS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- ROLE CHART -->
            <div class="bg-white p-4 rounded-xl shadow h-80 flex flex-col">
                <h6 class="mb-2 font-semibold">Feedback by Role</h6>

                <div class="relative flex-1 w-full">
                    <canvas x-ref="roleChart" class="absolute inset-0 w-full h-full"></canvas>
                </div>
            </div>

            <!-- DEPARTMENT CHART -->
            <div class="bg-white p-4 rounded-xl shadow h-80 flex flex-col">
                <h6 class="mb-2 font-semibold">Feedback by Department</h6>

                <div class="relative flex-1 w-full">
                    <canvas x-ref="deptChart" class="absolute inset-0 w-full h-full"></canvas>
                </div>
            </div>

        </div>

        {{-- 
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- Keywords -->
            <div class="bg-white p-4 rounded-xl shadow border">
                <h6 class="font-semibold mb-3">AI Keyword Analysis</h6>

                <div class="flex flex-wrap gap-2">
                    <template x-for="term in recurringTerms" :key="term.term">
                        <span class="px-3 py-1 rounded-full text-sm" :style="getKeywordStyle(term.term)"
                            x-text="term.term"></span>
                    </template>
                </div>
            </div>

            <!-- Rating Distribution -->
            <div class="bg-white p-4 rounded-xl shadow border">
                <h6 class="font-semibold mb-3">Rating Distribution</h6>

                <div class="relative w-full h-48 sm:h-52 md:h-56 lg:h-64">
                    <canvas x-ref="ratingChart" class="absolute inset-0 w-full h-full"></canvas>
                </div>

            </div>

        </div> --}}

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- LEFT: Keywords -->
            <div class="bg-white p-4 rounded-xl shadow border">
                <h6 class="font-semibold mb-3">AI Keyword Analysis</h6>

                <div class="flex flex-wrap gap-2">
                    <template x-for="term in recurringTerms" :key="term.term">
                        <span class="px-3 py-1 rounded-full text-sm" :style="getKeywordStyle(term.term)" x-text="term.term">
                        </span>
                    </template>
                </div>
            </div>

            <!-- RIGHT: Rating Distribution -->
            <div class="bg-white p-4 rounded-xl shadow border">
                <h6 class="font-semibold mb-3">Rating Distribution</h6>

                <div class="relative w-full h-48 sm:h-52 md:h-56 lg:h-64">
                    <canvas x-ref="ratingChart" class="absolute inset-0 w-full h-full"></canvas>
                </div>

            </div>

            <!-- MIDDLE: FLAGGED INSIGHT -->
            <div class="bg-white p-4 rounded-xl shadow border">

                <h6 class="font-semibold mb-3 text-center">
                    Most Concerning Offices
                </h6>

                <template x-for="(dept, index) in negativeOffices" :key="dept.department">

                    <div class="flex justify-between items-center p-2 rounded mb-2"
                        :class="index === 0 ?
                            'bg-red-100 border-l-4 border-red-500' :
                            'bg-gray-50'">

                        <!-- Rank + Name -->
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-600" x-text="index + 1 + '.'"></span>

                            <span class="font-medium" :class="index === 0 ? 'text-red-700' : 'text-gray-700'"
                                x-text="dept.department">
                            </span>
                        </div>

                        <!-- Score -->
                        <div class="text-sm font-semibold" :class="index === 0 ? 'text-red-600' : 'text-gray-500'">

                            <span x-text="dept.total + ' negative'"></span>
                        </div>

                    </div>

                </template>

                <div x-show="negativeOffices.length === 0" class="text-center text-gray-500 text-sm mt-2">
                    No negative feedback found
                </div>

            </div>

        </div>

        <!-- INSIGHTS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <div class="bg-white p-4 rounded-xl shadow">
                <h6 class="font-semibold mb-2">Top Recurring Concerns</h6>

                <template x-for="item in aiInsights" :key="item.issue">
                    <div class="border p-2 rounded mb-2">

                        <p class="font-semibold mt-1" x-text="item.issue"></p>

                        <div class="w-full bg-gray-200 h-2 rounded mt-1">
                            <div class="h-2 rounded" :class="progressColor(item.priority)"
                                :style="`width:${Math.min(item.count * 25, 100)}%`">
                            </div>
                        </div>

                        <small x-text="item.count + ' reports'"></small>
                    </div>
                </template>
            </div>

            <div class="bg-white p-4 rounded-xl shadow">
                <h6 class="font-semibold mb-3">AI-generated insights</h6>

                <template x-for="n in aiNarrative" :key="n.title">
                    <div class="relative p-4 mb-3 rounded-xl border-l-4 shadow-sm transition hover:shadow-md"
                        :class="calloutClass(n.priority)">

                        <!-- HEADER -->
                        <div class="flex items-center justify-between mb-1">
                            <h6 class="font-semibold text-base"
                                :class="{
                                    critical: 'text-red-700',
                                    warning: 'text-yellow-700',
                                    positive: 'text-green-700'
                                } [n.priority] || 'text-gray-800'"
                                x-text="n.title">
                            </h6>

                            <span class="text-xs px-2 py-1 rounded-full font-medium" :class="badgeColor(n.priority)"
                                x-text="n.priority">
                            </span>
                        </div>

                        <!-- DESCRIPTION -->
                        <p class="text-sm text-gray-600" x-text="n.description"></p>

                    </div>
                </template>
            </div>

        </div>

        <!-- FEEDBACK STREAM -->
        <div class="bg-white p-4 rounded-xl shadow">
            <h6 class="font-semibold mb-2">Recent Submissions</h6>

            <template x-for="item in recentFeedback" :key="item.id">
                <div class="border p-3 rounded mb-2">

                    <div class="flex justify-between text-sm">
                        <div>
                            <span class="font-semibold" x-text="item.role"></span>
                            <span class="text-gray-400" x-text="'· ' + (item.department || 'General')"></span>
                        </div>

                        <small class="text-gray-400" x-text="new Date(item.created_at).toLocaleTimeString()"></small>
                    </div>

                    <p class="mt-2" x-text="item.feedback"></p>

                    <div class="text-yellow-500 mt-1">
                        <span x-text="'★'.repeat(item.rating || 3)"></span>
                    </div>

                </div>
            </template>
        </div>

    </div>



    <script>
        function dateRangeFilter() {
            return {
                from: '',
                to: '',

                applyRange() {
                    if (!this.from || !this.to) return;

                    window.dispatchEvent(new CustomEvent('date-range', {
                        detail: {
                            start: this.from,
                            end: this.to
                        }
                    }));
                },

                resetRange() {
                    // ✅ clear inputs
                    this.from = '';
                    this.to = '';

                    // ✅ reset dashboard filter state
                    window.dispatchEvent(new CustomEvent('date-range', {
                        detail: {
                            start: null,
                            end: null
                        }
                    }));

                    // 🔥 OPTIONAL BUT IMPORTANT: force immediate refresh
                    window.dispatchEvent(new CustomEvent('dashboard-reset'));

                }
            }
        }
    </script>


    <script>
        function dashboard() {
            return {


                flaggedOffice: '',
                flaggedOfficeScore: 0,
                negativeOffices: [],
                negativeTypes: [],
                negativeTypeChart: null,

                totalFeedback: 0,
                avgRating: 0,
                departmentCount: 0,

                recurringTerms: [],
                aiInsights: [],
                aiNarrative: [],
                recentFeedback: [],

                sentiment: {
                    Positive: 0,
                    Neutral: 0,
                    Negative: 0
                },

                flaggedCount: 0,
                flaggedChange: 0,
                totalGrowth: 0,
                avgChange: 0,
                currentMonthSubmissions: 0,
                ratingChart: null,

                activeRange: null,

                roleChart: null,
                deptChart: null,
                lastTrendHash: null,

                init() {
                    this.load();

                    setInterval(() => this.load(), 1000);

                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) this.load();
                    });
                    window.addEventListener('date-range', (e) => {
                        this.activeRange = e.detail;
                        this.load(this.activeRange, true);
                    });
                    window.addEventListener('dashboard-reset', () => {
                        this.activeRange = null;
                        this.load();
                    });
                },
                calloutClass(p) {
                    return {
                        critical: 'border-red-500 bg-red-50',
                        warning: 'border-yellow-400 bg-yellow-50',
                        positive: 'border-green-500 bg-green-50'
                    } [p] || 'border-gray-300 bg-gray-50';
                },
                async load(range = null) {
                    if (this.loading) return;
                    this.loading = true;

                    try {

                        let url = '/admin/dashboard/data';

                        // always use latest range
                        const r = range || this.activeRange;

                        if (r?.start && r?.end) {
                            url += `?start=${r.start}&end=${r.end}`;
                        }

                        const res = await fetch(url);
                        const data = await res.json();

                        this.totalFeedback = data.totalFeedback ?? 0;
                        this.avgRating = parseFloat(data.avgRating ?? 0).toFixed(1);
                        this.departmentCount = Object.keys(data.departmentCounts || {}).length;

                        this.currentMonthSubmissions = data.currentMonthSubmissions ?? 0;

                        this.recurringTerms = data.recurringTerms || [];
                        this.recentFeedback = data.recentFeedback || [];
                        this.sentiment = data.sentimentBreakdown || {
                            Positive: 0,
                            Neutral: 0,
                            Negative: 0
                        };

                        this.flaggedOffice = data.flaggedOffice ?? '';
                        this.flaggedOfficeScore = data.flaggedOfficeScore ?? 0;
                        this.negativeOffices = data.negativeByDepartment ?? [];



                        this.flaggedCount = data.flaggedCount ?? 0;
                        this.flaggedChange = data.flaggedChange ?? 0;
                        this.totalGrowth = data.totalGrowth ?? 0;
                        this.avgChange = data.avgChange ?? 0;

                        if (this.lastNarrativeVersion !== data.latestFeedbackTime) {
                            this.aiNarrative = data.aiNarrative || [];
                            this.aiInsights = data.aiInsights || [];
                            this.lastNarrativeVersion = data.latestFeedbackTime;
                        }

                        this.updateCharts(data);

                    } finally {
                        this.loading = false;
                    }
                },

                updateCharts(data) {

                    if (!data) return;


                    // =========================
                    // SUBMISSION TREND (STABLE FIX)
                    // =========================
                    const trendLabels = data.submissionTrend?.labels ?? [];
                    const trendValues = data.submissionTrend?.values ?? [];

                    // 🔥 create simple hash to detect change
                    const newHash = JSON.stringify(trendLabels) + JSON.stringify(trendValues);

                    // 🚫 DO NOTHING if no change
                    if (!this.activeRange && this.lastTrendHash === newHash) {
                        return; // only skip when NOT filtering
                    }

                    this.lastTrendHash = newHash;

                    if (!this.trendChart) {
                        this.trendChart = new Chart(this.$refs.trendChart.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: trendLabels,
                                datasets: [{
                                    label: 'Submissions',
                                    data: trendValues,
                                    borderColor: '#3b82f6',
                                    backgroundColor: 'rgba(59,130,246,0.2)',
                                    tension: 0.35,
                                    fill: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 7,
                                    pointHitRadius: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },

                                plugins: {
                                    legend: {
                                        display: true
                                    },

                                    tooltip: {
                                        enabled: true,
                                        callbacks: {
                                            label: (ctx) =>
                                                `Submissions: ${ctx.parsed?.y ?? 0}`
                                        }
                                    }
                                },

                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        // ONLY update data (NO destroy)
                        this.trendChart.data.labels = trendLabels;
                        this.trendChart.data.datasets[0].data = trendValues;
                        this.trendChart.update();
                    }

                    // =========================
                    // RATING DISTRIBUTION (BAR) - RESPONSIVE FIX
                    // =========================
                    const ratingData = data.ratingDistribution ?? {
                        1: 0,
                        2: 0,
                        3: 0,
                        4: 0,
                        5: 0
                    };

                    const ratingLabels = ['1', '2', '3', '4', '5'];
                    const ratingValues = ratingLabels.map(r => Number(ratingData[r] ?? 0));

                    const ctx = this.$refs.ratingChart?.getContext('2d');

                    if (!ctx) return;

                    if (!this.ratingChart) {
                        this.ratingChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ratingLabels,
                                datasets: [{
                                    label: 'Rating Count',
                                    data: ratingValues,
                                    backgroundColor: [
                                        '#ef4444',
                                        '#f97316',
                                        '#eab308',
                                        '#22c55e',
                                        '#16a34a'
                                    ],
                                    borderRadius: 6,

                                    // 🔥 IMPORTANT: remove fixed size for responsiveness
                                    barThickness: undefined,
                                    maxBarThickness: 40,
                                    categoryPercentage: 0.6,
                                    barPercentage: 0.8
                                }]
                            },
                            options: {
                                indexAxis: 'y', // ✅ THIS MAKES IT HORIZONTAL

                                responsive: true,
                                maintainAspectRatio: false,

                                resizeDelay: 0,

                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => `Count: ${ctx.raw}`
                                        }
                                    }
                                },

                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    },
                                    y: {
                                        ticks: {
                                            maxRotation: 0,
                                            minRotation: 0,
                                            autoSkip: true,

                                            callback: function(value, index) {
                                                const stars = ['⭐', '⭐', '⭐', '⭐', '⭐'];
                                                return `${stars[index]} ${ratingLabels[index]}`;
                                            }
                                        },
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        this.ratingChart.data.datasets[0].data = ratingValues;
                        this.ratingChart.update('none');
                    }

                    // =========================
                    // FEEDBACK TYPE (DOUGHNUT)
                    // =========================
                    const typeLabels = Object.keys(data.typeCounts || {});
                    const typeValues = Object.values(data.typeCounts || {});

                    if (!this.typeChart) {
                        this.typeChart = new Chart(this.$refs.typeChart.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: typeLabels,
                                datasets: [{
                                    data: typeValues,
                                    backgroundColor: [
                                        '#0d6efd',
                                        '#198754',
                                        '#ffc107',
                                        '#dc3545',
                                        '#6610f2',
                                        '#14b8a6'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            generateLabels: (chart) => {
                                                const data = chart.data;
                                                const dataset = data.datasets[0];

                                                const total = dataset.data.reduce((a, b) => a + b, 0) || 1;

                                                return data.labels.map((label, i) => {
                                                    const value = dataset.data[i];
                                                    const percent = ((value / total) * 100).toFixed(
                                                        1);

                                                    return {
                                                        text: `${label} (${percent}%)`,
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
                        this.typeChart.update('none');
                    }

                    // =========================
                    // EXISTING CHARTS (KEEP)
                    // =========================
                    const roleLabels = Object.keys(data.roleCounts || {});
                    const roleValues = Object.values(data.roleCounts || {});

                    const deptLabels = Object.keys(data.departmentCounts || {});
                    const deptValues = Object.values(data.departmentCounts || {});

                    if (!this.roleChart) {
                        this.roleChart = new Chart(this.$refs.roleChart.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: roleLabels,
                                datasets: [{
                                    label: 'Feedback Count',
                                    data: roleValues
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    } else {
                        this.roleChart.data.labels = roleLabels;
                        this.roleChart.data.datasets[0].data = roleValues;
                        this.roleChart.update('none');
                    }

                    if (!this.deptChart) {
                        this.deptChart = new Chart(this.$refs.deptChart.getContext('2d'), {
                            type: 'pie',
                            data: {
                                labels: deptLabels,
                                datasets: [{
                                    data: deptValues
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    } else {
                        this.deptChart.data.labels = deptLabels;
                        this.deptChart.data.datasets[0].data = deptValues;
                        this.deptChart.update('none');
                    }
                },

                badgeColor(p) {
                    return {
                        critical: 'bg-red-500 text-white',
                        warning: 'bg-yellow-400 text-black',
                        positive: 'bg-green-500 text-white'
                    } [p] || 'bg-gray-300';
                },

                progressColor(p) {
                    return {
                        high: 'bg-red-500',
                        medium: 'bg-yellow-400',
                        low: 'bg-green-500'
                    } [p] || 'bg-gray-300';
                },

                getKeywordStyle(term) {
                    const t = term.toLowerCase();

                    if (['slow', 'problem', 'delay', 'error'].some(w => t.includes(w)))
                        return "background:#fecaca;color:#7f1d1d";

                    if (['good', 'fast', 'excellent', 'nice'].some(w => t.includes(w)))
                        return "background:#bbf7d0;color:#14532d";

                    return "background:#e5e7eb;color:#374151";
                }
            }
        }
    </script>
@endsection
