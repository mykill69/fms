@extends('layouts.admin')

<style>
    [x-cloak] { display: none !important; }

    :root {
        --tw-color-emerald: #10b981;
    }

    canvas { width: 100% !important; height: 100% !important; }

    .dashboard-bg {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .stat-card {
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 10px 15px -3px rgb(16 185 129 / 0.1),
                    25px 25px 50px -12px rgb(16 185 129 / 0.15);
    }
    .stat-card:hover {
        transform: scale(1.04) translateY(-4px);
        box-shadow: 0 25px 50px -12px rgb(16 185 129 / 0.25);
    }

    .modern-card {
        border: none;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05),
                    0 10px 15px -3px rgb(0 0 0 / 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modern-card:hover {
        box-shadow: 25px 25px 50px -12px rgb(16 185 129 / 0.15);
        transform: translateY(-3px);
    }

    .glass-header {
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(20px);
        box-shadow: 0 8px 32px -12px rgb(16 185 129 / 0.3);
    }

    .chart-container {
        transition: all 0.4s ease;
    }

    .keyword-pill {
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .keyword-pill:hover {
        transform: scale(1.1) translateY(-2px);
    }

    .feed-item {
        transition: all 0.3s ease;
    }
    .feed-item:hover {
        background: #f8fafc;
        transform: translateX(8px);
    }

    @keyframes pulse-modern {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    .live-dot {
        animation: pulse-modern 2s infinite;
    }

    @media (max-width: 640px) {
        .glass-header { padding: 16px !important; }
        .modern-card { padding: 20px !important; }
        .stat-card { padding: 24px 20px !important; }
        .h-96 { height: 320px !important; }
        .h-80, .h-72 { height: 300px !important; }
        .h-[280px], .h-[260px] { height: 260px !important; }
    }
</style>

@section('content')
<div x-data="dashboard()" x-init="init()" x-cloak class="dashboard-bg min-h-screen p-4 sm:p-6 md:p-8 lg:p-10">

    <!-- MODERN HEADER -->
    <div class="glass-header sticky top-0 z-50 -mx-4 -mt-4 mb-8 sm:mb-10 px-5 sm:px-8 py-5 sm:py-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sm:gap-6 border-b border-white/60">
        <div class="flex items-center gap-4">
            <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center text-white text-2xl shadow-inner">📡</div>
            <div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tighter text-gray-900">Feedback OS</h1>
                <p class="text-emerald-600 text-sm font-medium -mt-1">Llama 3 • Real-time Intelligence</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 sm:gap-8">
            <!-- FUNCTIONAL QUICK RANGE BUTTONS -->
            <div class="flex bg-white/80 border border-white shadow-inner rounded-3xl p-1 text-sm font-semibold">
                <button @click="setQuickRange('today')" 
                        :class="quickRange === 'today' ? 'bg-emerald-500 text-white shadow-md' : 'hover:bg-white/60'"
                        class="px-5 sm:px-6 py-3 rounded-3xl transition-all">Today</button>
                <button @click="setQuickRange('week')" 
                        :class="quickRange === 'week' ? 'bg-emerald-500 text-white shadow-md' : 'hover:bg-white/60'"
                        class="px-5 sm:px-6 py-3 rounded-3xl transition-all">7 Days</button>
                <button @click="setQuickRange('month')" 
                        :class="quickRange === 'month' ? 'bg-emerald-500 text-white shadow-md' : 'hover:bg-white/60'"
                        class="px-5 sm:px-6 py-3 rounded-3xl transition-all">30 Days</button>
            </div>

            <!-- Date Picker -->
            <div x-data="dateRangeFilter()" class="flex items-center bg-white/80 border border-white rounded-3xl px-4 sm:px-6 py-2 shadow-inner gap-3 sm:gap-4">
                <input type="date" x-model="from" class="bg-transparent text-gray-700 focus:outline-none text-sm w-28">
                <div class="text-emerald-400 text-xl">→</div>
                <input type="date" x-model="to" class="bg-transparent text-gray-700 focus:outline-none text-sm w-28">
                <button @click="applyRange" 
                        class="bg-emerald-600 text-white px-6 sm:px-7 py-3 rounded-3xl text-sm font-bold tracking-wider hover:bg-emerald-700 transition">GO</button>
            </div>

            <!-- Live Refresh -->
            <div @click="manualRefresh" 
                 class="flex items-center gap-3 bg-white/90 border border-emerald-200 text-emerald-700 px-5 h-12 rounded-3xl cursor-pointer hover:border-emerald-400 transition">
                <div class="relative flex h-3 w-3">
                    <span class="live-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </div>
                <span class="font-mono text-xs uppercase tracking-[1px]" x-text="lastUpdated"></span>
                <i class="fa-solid fa-rotate-right"></i>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
        <div @click="highlightSection('total')" class="stat-card bg-white rounded-3xl p-6 sm:p-8 cursor-pointer">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <p class="uppercase text-xs font-medium text-gray-400 tracking-widest">Total Feedback</p>
                    <h2 class="text-5xl sm:text-6xl font-semibold text-gray-900 tabular-nums" x-text="totalFeedback"></h2>
                </div>
                <div class="text-5xl opacity-80">📨</div>
            </div>
            <div class="mt-8 flex items-baseline gap-2">
                <span :class="totalGrowth >= 0 ? 'text-emerald-500' : 'text-red-500'" class="text-3xl font-light" x-text="totalGrowth >= 0 ? '↑' : '↓'"></span>
                <span class="text-2xl font-semibold tabular-nums" x-text="Math.abs(totalGrowth) + '%'"></span>
                <span class="text-gray-400 text-sm">vs last period</span>
            </div>
        </div>

        <div @click="highlightSection('satisfaction')" class="stat-card bg-white rounded-3xl p-6 sm:p-8 cursor-pointer">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <p class="uppercase text-xs font-medium text-gray-400 tracking-widest">Avg. Satisfaction</p>
                    <h2 class="text-5xl sm:text-6xl font-semibold text-gray-900 tabular-nums" x-text="avgRating + '/5'"></h2>
                </div>
                <div class="text-5xl opacity-80">⭐</div>
            </div>
            <div class="mt-8 flex items-baseline gap-2">
                <span :class="avgChange >= 0 ? 'text-emerald-500' : 'text-red-500'" class="text-3xl font-light" x-text="avgChange >= 0 ? '↑' : '↓'"></span>
                <span class="text-2xl font-semibold tabular-nums" x-text="Math.abs(avgChange) + '%'"></span>
                <span class="text-gray-400 text-sm">vs last period</span>
            </div>
        </div>

        <div @click="highlightSection('flagged')" class="stat-card bg-white rounded-3xl p-6 sm:p-8 cursor-pointer">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <p class="uppercase text-xs font-medium text-red-400 tracking-widest">Flagged</p>
                    <h2 class="text-5xl sm:text-6xl font-semibold text-red-600 tabular-nums" x-text="flaggedCount"></h2>
                </div>
                <div class="text-5xl opacity-80">🚩</div>
            </div>
            <p class="mt-8 text-red-500 text-sm flex items-center gap-1">
                <span x-text="flaggedChange"></span> items need review
            </p>
        </div>

        <div @click="highlightSection('sentiment')" class="stat-card bg-white rounded-3xl p-6 sm:p-8 cursor-pointer">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <p class="uppercase text-xs font-medium text-emerald-400 tracking-widest">Positive Sentiment</p>
                    <h2 class="text-5xl sm:text-6xl font-semibold text-emerald-600 tabular-nums" x-text="sentiment.Positive + '%'"></h2>
                </div>
                <div class="text-5xl opacity-80">🌟</div>
            </div>
            <div class="mt-6 h-2 bg-gray-100 rounded-3xl overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-400 to-teal-400" :style="`width: ${sentiment.Positive || 0}%`"></div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 1 -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 md:gap-8 mt-10 sm:mt-12">
        <div class="xl:col-span-7 modern-card bg-white rounded-3xl p-6 sm:p-8">
            <div class="flex justify-between mb-6">
                <h6 class="text-xl sm:text-2xl font-semibold text-gray-800">Submission Trend</h6>
                <div class="text-xs bg-emerald-100 text-emerald-700 px-4 h-7 rounded-3xl flex items-center font-medium">LIVE • LAST 30 DAYS</div>
            </div>
            <div class="h-80 sm:h-96 chart-container">
                <canvas x-ref="trendChart"></canvas>
            </div>
        </div>

        <div class="xl:col-span-5 space-y-6">
            <div class="modern-card bg-white rounded-3xl p-6 sm:p-8">
                <h6 class="text-xl font-semibold mb-4">Sentiment Breakdown</h6>
                <div class="flex items-center justify-center min-h-[240px] sm:min-h-[260px]">
                    <div class="w-full">
                        <div class="h-6 bg-gray-100 rounded-3xl flex overflow-hidden">
                            <div class="bg-emerald-500 h-full" :style="`width:${sentiment.Positive || 0}%`"></div>
                            <div class="bg-amber-400 h-full" :style="`width:${sentiment.Neutral || 0}%`"></div>
                            <div class="bg-red-500 h-full" :style="`width:${sentiment.Negative || 0}%`"></div>
                        </div>
                        <div class="flex justify-between mt-4 text-sm font-medium">
                            <div class="flex items-center gap-2"><div class="w-3 h-3 bg-emerald-500 rounded"></div> Positive <span x-text="sentiment.Positive" class="font-semibold"></span>%</div>
                            <div class="flex items-center gap-2"><div class="w-3 h-3 bg-amber-400 rounded"></div> Neutral <span x-text="sentiment.Neutral" class="font-semibold"></span>%</div>
                            <div class="flex items-center gap-2"><div class="w-3 h-3 bg-red-500 rounded"></div> Negative <span x-text="sentiment.Negative" class="font-semibold"></span>%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modern-card bg-white rounded-3xl p-6 sm:p-8">
                <h6 class="text-xl font-semibold mb-6">Feedback Types</h6>
                <div class="h-[260px] sm:h-[280px] chart-container">
                    <canvas x-ref="typeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Role & Department -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 mt-8">
        <div class="modern-card bg-white rounded-3xl p-6 sm:p-8">
            <h6 class="text-xl sm:text-2xl font-semibold mb-6">By Role</h6>
            <div class="h-72 sm:h-80 chart-container">
                <canvas x-ref="roleChart"></canvas>
            </div>
        </div>
        <div class="modern-card bg-white rounded-3xl p-6 sm:p-8">
            <h6 class="text-xl sm:text-2xl font-semibold mb-6">By Department</h6>
            <div class="h-72 sm:h-80 chart-container">
                <canvas x-ref="deptChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Three Column Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8 mt-12">
        <div class="lg:col-span-4 modern-card bg-white rounded-3xl p-6 sm:p-8">
            <h6 class="text-xl sm:text-2xl font-semibold mb-8">AI Keywords</h6>
            <div class="flex flex-wrap gap-3">
                <template x-for="term in recurringTerms" :key="term.term">
                    <span @click="filterByKeyword(term.term)"
                          class="keyword-pill px-6 py-4 text-base rounded-3xl cursor-pointer font-medium shadow-sm"
                          :style="getKeywordStyle(term.term)"
                          x-text="term.term"></span>
                </template>
            </div>
        </div>

        <div class="lg:col-span-4 modern-card bg-white rounded-3xl p-6 sm:p-8">
            <h6 class="text-xl sm:text-2xl font-semibold mb-8">Rating Distribution</h6>
            <div class="h-72 chart-container">
                <canvas x-ref="ratingChart"></canvas>
            </div>
        </div>

        <div class="lg:col-span-4 modern-card bg-white rounded-3xl p-6 sm:p-8">
            <h6 class="text-xl sm:text-2xl font-semibold mb-8 text-center">Most Concerning Offices</h6>
            <div class="space-y-4">
                <template x-for="(dept, i) in negativeOffices" :key="dept.department">
                    <div @click="highlightDepartment(dept.department)"
                         class="flex items-center justify-between px-6 py-5 rounded-3xl cursor-pointer"
                         :class="i===0 ? 'bg-gradient-to-r from-red-50 to-white border border-red-200' : 'hover:bg-slate-50'">
                        <div class="flex items-center gap-5">
                            <span class="font-mono text-4xl font-black text-red-200" x-text="i+1"></span>
                            <span class="text-xl font-medium" x-text="dept.department"></span>
                        </div>
                        <span class="font-bold text-red-600 text-2xl" x-text="dept.total"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Insights + AI Narrative -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 mt-12">
        <div class="modern-card bg-white rounded-3xl p-6 sm:p-8">
            <h6 class="text-xl sm:text-2xl font-semibold mb-8">Top Concerns</h6>
            <div class="space-y-8">
                <template x-for="item in aiInsights" :key="item.issue">
                    <div class="flex gap-6">
                        <div class="flex-1">
                            <p class="text-xl font-medium" x-text="item.issue"></p>
                            <div class="mt-4 h-3 bg-slate-100 rounded-3xl">
                                <div :class="progressColor(item.priority)" class="h-3 rounded-3xl transition-all" :style="`width: ${Math.min(item.count * 25, 100)}%`"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-4xl font-light text-slate-300" x-text="item.count"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="modern-card bg-white rounded-3xl p-6 sm:p-8">
            <h6 class="text-xl sm:text-2xl font-semibold mb-8">AI Narrative</h6>
            <div class="space-y-6">
                <template x-for="n in aiNarrative" :key="n.title">
                    <div class="rounded-3xl p-7 border border-transparent" :class="calloutClass(n.priority)">
                        <div class="flex justify-between">
                            <p class="font-semibold text-xl" x-text="n.title"></p>
                            <span :class="badgeColor(n.priority)" class="px-5 text-xs h-7 flex items-center rounded-3xl font-bold uppercase tracking-widest text-[10px]"><span x-text="n.priority"></span></span>
                        </div>
                        <p class="mt-4 text-slate-600" x-text="n.description"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Recent Submissions -->
    <div class="modern-card bg-white rounded-3xl p-6 sm:p-8 mt-12">
        <div class="flex items-center justify-between mb-8">
            <h6 class="text-xl sm:text-2xl font-semibold">Live Submissions</h6>
            <span class="flex items-center gap-2 text-emerald-600">
                <span class="relative flex h-3 w-3"><span class="live-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span></span>
                LIVE
            </span>
        </div>
        <div class="max-h-[520px] overflow-auto space-y-4 pr-2 custom-scroll">
            <template x-for="item in filteredFeedback" :key="item.id">
                <div class="feed-item border border-transparent bg-white rounded-3xl p-7 flex gap-6">
                    <div class="flex-1">
                        <div class="flex justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-semibold" x-text="item.role"></span>
                                <span class="text-teal-600 text-sm" x-text="item.department"></span>
                            </div>
                            <span class="text-xs text-gray-400 font-mono" x-text="new Date(item.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})"></span>
                        </div>
                        <p class="mt-4 text-gray-700 leading-relaxed" x-text="item.feedback"></p>
                    </div>
                    <div class="text-4xl text-amber-400 flex items-center" x-text="'★'.repeat(item.rating || 4)"></div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

<script>
function dateRangeFilter() {
    return {
        from: '',
        to: '',
        applyRange() {
            if (!this.from || !this.to) return;
            window.dispatchEvent(new CustomEvent('date-range', { detail: { start: this.from, end: this.to } }));
        },
        resetRange() {
            this.from = this.to = '';
            window.dispatchEvent(new CustomEvent('date-range', { detail: { start: null, end: null } }));
        }
    }
}

function dashboard() {
    return {
        totalFeedback: 0,
        avgRating: 0,
        flaggedCount: 0,
        flaggedChange: 0,
        totalGrowth: 0,
        avgChange: 0,
        sentiment: { Positive: 0, Neutral: 0, Negative: 0 },
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

        trendChart: null,
        typeChart: null,
        roleChart: null,
        deptChart: null,
        ratingChart: null,

        init() {
            this.load();
            setInterval(() => this.load(), 30000);
            window.addEventListener('date-range', e => { this.activeRange = e.detail; this.load(); });
        },

        setQuickRange(period) {
            const today = new Date();
            let fromDate = new Date(today);
            let toDate = new Date(today);

            if (period === 'today') {
                fromDate = toDate = today;
            } else if (period === 'week') {
                fromDate.setDate(today.getDate() - 7);
            } else if (period === 'month') {
                fromDate.setMonth(today.getMonth() - 1);
            }

            const from = fromDate.toISOString().split('T')[0];
            const to = toDate.toISOString().split('T')[0];

            this.quickRange = period;
            this.activeRange = { start: from, end: to };
            this.load();
        },

        manualRefresh() {
            this.load();
            this.lastUpdated = 'REFRESHED';
            setTimeout(() => this.lastUpdated = 'just now', 1800);
        },

        filterByKeyword(k) {
            this.activeKeywordFilter = this.activeKeywordFilter === k ? null : k;
            this.filteredFeedback = this.activeKeywordFilter 
                ? this.recentFeedback.filter(f => f.feedback.toLowerCase().includes(this.activeKeywordFilter.toLowerCase()))
                : [...this.recentFeedback];
        },

        highlightDepartment(d) {},
        highlightSection(s) {},

        async load() {
            let url = '/admin/dashboard/data';
            if (this.activeRange?.start && this.activeRange?.end) {
                url += `?start=${this.activeRange.start}&end=${this.activeRange.end}`;
            }
            const res = await fetch(url);
            const data = await res.json();

            this.totalFeedback = data.totalFeedback ?? 0;
            this.avgRating = parseFloat(data.avgRating ?? 0).toFixed(1);
            this.flaggedCount = data.flaggedCount ?? 0;
            this.flaggedChange = data.flaggedChange ?? 0;
            this.totalGrowth = data.totalGrowth ?? 0;
            this.avgChange = data.avgChange ?? 0;
            this.sentiment = data.sentimentBreakdown || { Positive: 0, Neutral: 0, Negative: 0 };
            this.recurringTerms = data.recurringTerms || [];
            this.recentFeedback = data.recentFeedback || [];
            this.negativeOffices = data.negativeByDepartment || [];
            this.aiInsights = data.aiInsights || [];
            this.aiNarrative = data.aiNarrative || [];
            this.filteredFeedback = [...this.recentFeedback];

            this.updateCharts(data);
        },

        updateCharts(data) {
            if (!data) return;

            // Trend
            if (!this.trendChart) {
                this.trendChart = new Chart(this.$refs.trendChart.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.submissionTrend?.labels ?? [],
                        datasets: [{
                            label: 'Submissions',
                            data: data.submissionTrend?.values ?? [],
                            borderColor: '#10b981',
                            borderWidth: 4,
                            tension: 0.35,
                            fill: true,
                            backgroundColor: 'rgba(16,185,129,0.12)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { grid: { color: '#f1f5f9' }, ticks: { precision: 0 } } }
                    }
                });
            } else {
                this.trendChart.data.labels = data.submissionTrend?.labels ?? [];
                this.trendChart.data.datasets[0].data = data.submissionTrend?.values ?? [];
                this.trendChart.update('active');
            }

            // Rating
            const ratingLabels = ['1★','2★','3★','4★','5★'];
            const ratingValues = Object.values(data.ratingDistribution || {});
            if (!this.ratingChart) {
                this.ratingChart = new Chart(this.$refs.ratingChart.getContext('2d'), {
                    type: 'bar',
                    data: { labels: ratingLabels, datasets: [{ data: ratingValues, backgroundColor: '#10b981', borderRadius: 999 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            } else {
                this.ratingChart.data.datasets[0].data = ratingValues;
                this.ratingChart.update();
            }

            // Feedback Types
            const typeLabels = Object.keys(data.typeCounts || {});
            const typeValues = Object.values(data.typeCounts || {});
            if (!this.typeChart) {
                this.typeChart = new Chart(this.$refs.typeChart.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: typeLabels,
                        datasets: [{
                            data: typeValues,
                            backgroundColor: ['#10b981','#3b82f6','#eab308','#ef4444','#8b5cf6','#14b8a6']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    generateLabels: (chart) => {
                                        const dataset = chart.data.datasets[0];
                                        const total = dataset.data.reduce((a, b) => a + b, 0) || 1;
                                        return chart.data.labels.map((label, i) => {
                                            const value = dataset.data[i];
                                            const percent = ((value / total) * 100).toFixed(1);
                                            return {
                                                text: `${label} (${percent}%)`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    },
                                    padding: 20,
                                    usePointStyle: true,
                                    boxWidth: 10
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

            // By Department
            const deptLabels = Object.keys(data.departmentCounts || {});
            const deptValues = Object.values(data.departmentCounts || {});
            if (!this.deptChart) {
                this.deptChart = new Chart(this.$refs.deptChart.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: deptLabels,
                        datasets: [{
                            data: deptValues,
                            backgroundColor: ['#10b981','#3b82f6','#eab308','#ef4444','#8b5cf6']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                align: 'center',
                                labels: { padding: 20, boxWidth: 12, usePointStyle: true }
                            }
                        }
                    }
                });
            } else {
                this.deptChart.data.labels = deptLabels;
                this.deptChart.data.datasets[0].data = deptValues;
                this.deptChart.update();
            }

            // Role
            if (!this.roleChart) {
                this.roleChart = new Chart(this.$refs.roleChart.getContext('2d'), {
                    type: 'bar',
                    data: { labels: Object.keys(data.roleCounts || {}), datasets: [{ data: Object.values(data.roleCounts || {}), backgroundColor: '#10b981' }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            } else {
                this.roleChart.data.labels = Object.keys(data.roleCounts || {});
                this.roleChart.data.datasets[0].data = Object.values(data.roleCounts || {});
                this.roleChart.update();
            }
        },

        calloutClass(p) {
            return p === 'critical' ? 'bg-red-50 border-red-300' : 
                   p === 'warning' ? 'bg-amber-50 border-amber-300' : 'bg-emerald-50 border-emerald-300';
        },

        badgeColor(p) {
            return p === 'critical' ? 'bg-red-500 text-white' : 
                   p === 'warning' ? 'bg-amber-400 text-black' : 'bg-emerald-500 text-white';
        },

        progressColor(p) {
            return p === 'high' ? 'bg-red-500' : p === 'medium' ? 'bg-amber-400' : 'bg-emerald-500';
        },

        getKeywordStyle(term) {
            const t = term.toLowerCase();
            if (['slow','delay','problem','error'].some(w => t.includes(w))) return 'background:#fef2f2;color:#b91c1c';
            if (['good','fast','excellent','love'].some(w => t.includes(w))) return 'background:#ecfdf5;color:#0f766e';
            return 'background:#f8fafc;color:#334155';
        }
    }
}
</script>