<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import {
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    CheckCircle,
    Target,
    Receipt,
    Wallet,
    Flame,
    ChevronUp,
    ChevronDown,
    Minus,
    BarChart3,
} from 'lucide-vue-next';

ChartJS.register(
    CategoryScale, LinearScale,
    BarElement, LineElement, PointElement,
    Title, Tooltip, Legend, Filler,
);

// ============================================================
// DATA DARI PROPS
// ============================================================

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    varianceData: {
        type: Object,
        default: () => ({})
    }
});

const months = computed(() => props.data.months || []);

const startMonth = ref(props.data.startMonth?.toString() || '1');
const endMonth = ref(props.data.endMonth?.toString() || (new Date().getMonth() + 1).toString());

watch(() => props.data.startMonth, (newVal) => {
    if (newVal) startMonth.value = newVal.toString();
});
watch(() => props.data.endMonth, (newVal) => {
    if (newVal) endMonth.value = newVal.toString();
});

const selectedMonthLabel = computed(() => {
    const sIdx = parseInt(startMonth.value) - 1;
    const eIdx = parseInt(endMonth.value) - 1;
    if (sIdx === 0 && eIdx === 11) return 'Seluruh Tahun';
    if (sIdx === eIdx) return months.value?.[sIdx] || '';
    return `${months.value?.[sIdx] || ''} - ${months.value?.[eIdx] || ''}`;
});

const applyFilter = () => {
    if (parseInt(startMonth.value) > parseInt(endMonth.value)) {
        startMonth.value = endMonth.value;
    }

    router.get('/dashboard', { 
        startMonth: startMonth.value, 
        endMonth: endMonth.value 
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const revenueBudget = computed(() => props.varianceData.revenueBudget || []);
const revenueActual = computed(() => props.varianceData.revenueActual || []);
const expenseBudget = computed(() => props.varianceData.expenseBudget || []);
const expenseActual = computed(() => props.varianceData.expenseActual || []);

// Hitung rentang
const startIdx = computed(() => parseInt(startMonth.value || 1) - 1);
const endIdx = computed(() => parseInt(endMonth.value || 1) - 1);

const curRevBudget = computed(() => revenueBudget.value.slice(startIdx.value, endIdx.value + 1).reduce((a, b) => a + b, 0));
const curRevActual = computed(() => revenueActual.value.slice(startIdx.value, endIdx.value + 1).reduce((a, b) => a + b, 0));
const curExpBudget = computed(() => expenseBudget.value.slice(startIdx.value, endIdx.value + 1).reduce((a, b) => a + b, 0));
const curExpActual = computed(() => expenseActual.value.slice(startIdx.value, endIdx.value + 1).reduce((a, b) => a + b, 0));

const revVariance   = computed(() => curRevActual.value - curRevBudget.value);   // positif = bagus
const expVariance   = computed(() => curExpActual.value - curExpBudget.value);   // positif = bahaya

const revVariancePct = computed(() => curRevBudget.value ? +((revVariance.value / curRevBudget.value) * 100).toFixed(1) : 0);
const expVariancePct = computed(() => curExpBudget.value ? +((expVariance.value / curExpBudget.value) * 100).toFixed(1) : 0);

const netBudget = computed(() => curRevBudget.value - curExpBudget.value);
const netActual = computed(() => curRevActual.value - curExpActual.value);
const netVariance = computed(() => netActual.value - netBudget.value);

const totalExpenseBudget = computed(() => props.varianceData.totalExpenseBudget || 0);
const totalExpenseActual = computed(() => expenseActual.value.reduce((a, b) => a + b, 0));
const sisaPaguBelanja = computed(() => totalExpenseBudget.value - totalExpenseActual.value);
const sisaPaguPct = computed(() => totalExpenseBudget.value ? (sisaPaguBelanja.value / totalExpenseBudget.value) * 100 : 0);

// Tab state
const activeTab = ref('revenue'); // 'revenue' | 'expense'

// ── Chart: Revenue Budget vs Actual ─────────────────────────
const filteredRevenueBudget = computed(() => {
    return revenueBudget.value.map((val, i) => (i >= startIdx.value && i <= endIdx.value) ? val : null);
});

const filteredRevenueActual = computed(() => {
    return revenueActual.value.map((val, i) => (i >= startIdx.value && i <= endIdx.value) ? val : null);
});

const filteredExpenseBudget = computed(() => {
    return expenseBudget.value.map((val, i) => (i >= startIdx.value && i <= endIdx.value) ? val : null);
});

const filteredExpenseActual = computed(() => {
    return expenseActual.value.map((val, i) => (i >= startIdx.value && i <= endIdx.value) ? val : null);
});

const revenueChartData = computed(() => ({
    labels: months.value,
    datasets: [
        {
            label: 'Anggaran',
            data: filteredRevenueBudget.value,
            borderColor: '#94a3b8',
            backgroundColor: 'rgba(148,163,184,0.08)',
            borderWidth: 2,
            borderDash: [5, 4],
            pointRadius: 4,
            pointBackgroundColor: '#94a3b8',
            tension: 0.35,
            fill: false,
            order: 2,
            spanGaps: false,
        },
        {
            label: 'Realisasi',
            data: filteredRevenueActual.value,
            borderColor: '#4ADE80',
            backgroundColor: 'rgba(74,222,128,0.10)',
            borderWidth: 2.5,
            pointRadius: 5,
            pointBackgroundColor: '#4ADE80',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.35,
            fill: true,
            order: 1,
            spanGaps: false,
        },
    ],
}));

// ── Chart: Expense Budget vs Actual ─────────────────────────
const expenseChartData = computed(() => ({
    labels: months.value,
    datasets: [
        {
            label: 'Anggaran',
            data: filteredExpenseBudget.value,
            borderColor: '#94a3b8',
            backgroundColor: 'rgba(148,163,184,0.08)',
            borderWidth: 2,
            borderDash: [5, 4],
            pointRadius: 4,
            pointBackgroundColor: '#94a3b8',
            tension: 0.35,
            fill: false,
            order: 2,
            spanGaps: false,
        },
        {
            label: 'Realisasi',
            data: filteredExpenseActual.value,
            borderColor: '#FF8781',
            backgroundColor: 'rgba(255,135,129,0.10)',
            borderWidth: 2.5,
            pointRadius: 5,
            pointBackgroundColor: '#FF8781',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.35,
            fill: true,
            order: 1,
            spanGaps: false,
        },
    ],
}));

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'top',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20,
                font: { size: 11, family: 'inherit' },
                color: '#64748b',
            },
        },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    if (ctx.raw === null || ctx.raw === undefined) return null;
                    return ` ${ctx.dataset.label}: ${formatRupiah(ctx.raw)}`;
                },
                afterBody: (items) => {
                    const validItems = items.filter(i => i.raw !== null && i.raw !== undefined);
                    if (validItems.length < 2) return [];
                    const budget = validItems.find(i => i.dataset.label === 'Anggaran')?.raw ?? 0;
                    const actual = validItems.find(i => i.dataset.label === 'Realisasi')?.raw ?? 0;
                    const diff   = actual - budget;
                    const pct    = budget ? ((diff / budget) * 100).toFixed(1) : 0;
                    return ['', `Selisih: ${diff >= 0 ? '+' : ''}${formatRupiahShort(diff)} (${diff >= 0 ? '+' : ''}${pct}%)`];
                },
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 11 } },
        },
        y: {
            beginAtZero: true,
            min: 0,
            grid: { color: 'rgba(100,116,139,0.08)' },
            border: { dash: [4, 4] },
            ticks: {
                color: '#64748b',
                font: { size: 10 },
                callback: (v) => formatRupiahShort(v),
            },
        },
    },
};

// ── Helpers ──────────────────────────────────────────────────
function formatRupiah(val) {
    if (val === 0) return 'Rp 0';
    const abs = Math.abs(val);
    const formatted = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(abs);
    return val < 0 ? `−${formatted}` : formatted;
}

function formatRupiahShort(val) {
    const abs = Math.abs(val);
    let str = '';
    if (abs >= 1_000_000_000) str = `${(abs / 1_000_000_000).toFixed(1)} M`;
    else if (abs >= 1_000_000) str = `${(abs / 1_000_000).toFixed(0)} Jt`;
    else str = String(abs);
    return val < 0 ? `−${str}` : str;
}

function varianceSeverity(pct, isExpense = true) {
    if (!isExpense) {
        // Revenue: positif = bagus
        if (pct >= 5)   return 'great';
        if (pct >= 0)   return 'ok';
        if (pct >= -5)  return 'warn';
        return 'danger';
    }
    // Expense: positif = bahaya
    if (pct > 15) return 'danger';
    if (pct > 0)  return 'warn';
    if (pct >= -5) return 'ok';
    return 'great';
}

const severityClass = {
    great:  { text: 'text-emerald-600', bg: 'bg-emerald-100', border: 'border-emerald-200', badge: 'bg-emerald-100 text-emerald-700 border-emerald-200', bar: 'bg-emerald-500' },
    ok:     { text: 'text-blue-600',    bg: 'bg-blue-100',    border: 'border-blue-200',    badge: 'bg-blue-100 text-blue-700 border-blue-200',           bar: 'bg-blue-400' },
    warn:   { text: 'text-amber-600',   bg: 'bg-amber-100',   border: 'border-amber-200',   badge: 'bg-amber-100 text-amber-700 border-amber-200',         bar: 'bg-amber-500' },
    danger: { text: 'text-rose-600',    bg: 'bg-rose-100',    border: 'border-rose-200',    badge: 'bg-rose-100 text-rose-700 border-rose-200',            bar: 'bg-rose-500' },
};

</script>

<template>
    <!-- ============================================================
         SECTION 3: DASHBOARD REALISASI (ANGGARAN VS REALISASI)
    ============================================================ -->
    <section class="space-y-5">

        <!-- ── Section Header ──────────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-1 h-8 rounded-full bg-blue-500"></div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-secondary">Realisasi</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Anggaran vs Realisasi · Periode: {{ selectedMonthLabel }}</p>
                </div>
            </div>

            <!-- Month Filter -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground font-medium">Dari</span>
                <select 
                    v-model="startMonth" 
                    @change="applyFilter"
                    class="text-sm border-border/80 rounded-lg bg-card text-secondary shadow-sm focus:ring-primary focus:border-primary px-3 py-1.5 cursor-pointer"
                >
                    <option v-for="(m, i) in months" :key="i" :value="(i + 1).toString()">
                        {{ m }}
                    </option>
                </select>
                <span class="text-xs text-muted-foreground font-medium">sampai</span>
                <select 
                    v-model="endMonth" 
                    @change="applyFilter"
                    class="text-sm border-border/80 rounded-lg bg-card text-secondary shadow-sm focus:ring-primary focus:border-primary px-3 py-1.5 cursor-pointer"
                >
                    <option v-for="(m, i) in months" :key="i" :value="(i + 1).toString()">
                        {{ m }}
                    </option>
                </select>
            </div>
        </div>

        <!-- ── ROW 1: KPI Cards ─────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Revenue Variance -->
            <div :class="[
                'border rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group',
                revVariance >= 0 ? 'bg-emerald-50/80 border-emerald-200' : 'bg-rose-50/80 border-rose-200'
            ]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Selisih Pendapatan</span>
                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200', revVariance >= 0 ? 'bg-emerald-500/15' : 'bg-rose-500/15']">
                        <TrendingUp v-if="revVariance >= 0" class="w-4 h-4 text-emerald-600" />
                        <TrendingDown v-else class="w-4 h-4 text-rose-500" />
                    </div>
                </div>
                <p :class="['text-2xl font-bold tracking-tight leading-none', revVariance >= 0 ? 'text-emerald-700' : 'text-rose-600']">
                    {{ revVariance >= 0 ? '+' : '' }}{{ revVariancePct }}%
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">
                    {{ revVariance >= 0 ? '+' : '' }}{{ formatRupiahShort(revVariance) }} vs anggaran
                </p>
                <div :class="['mt-3 flex items-center gap-1 text-[11px] font-semibold', revVariance >= 0 ? 'text-emerald-600' : 'text-rose-500']">
                    <CheckCircle v-if="revVariance >= 0" class="w-3.5 h-3.5 flex-shrink-0" />
                    <AlertTriangle v-else class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>{{ revVariance >= 0 ? 'Melebihi target' : 'Di bawah target' }}</span>
                </div>
            </div>

            <!-- Expense Variance & Sisa Pagu Belanja -->
            <div :class="[
                'border rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group',
                expVariance <= 0 ? 'bg-emerald-50/80 border-emerald-200' : expVariancePct <= 15 ? 'bg-amber-50/80 border-amber-300' : 'bg-rose-50/80 border-rose-300'
            ]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Selisih Pengeluaran</span>
                    <div :class="[
                        'w-8 h-8 rounded-lg flex items-center justify-center',
                        expVariance <= 0 ? 'bg-emerald-500/15' : expVariancePct <= 15 ? 'bg-amber-500/15' : 'bg-rose-500/15'
                    ]">
                        <Flame :class="['w-4 h-4', expVariance <= 0 ? 'text-emerald-600' : expVariancePct <= 15 ? 'text-amber-600' : 'text-rose-500']" />
                    </div>
                </div>
                <p :class="[
                    'text-2xl font-bold tracking-tight leading-none',
                    expVariance <= 0 ? 'text-emerald-700' : expVariancePct <= 15 ? 'text-amber-700' : 'text-rose-600'
                ]">
                    {{ expVariance >= 0 ? '+' : '' }}{{ expVariancePct }}%
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">
                    {{ expVariance >= 0 ? '+' : '' }}{{ formatRupiahShort(expVariance) }} dari anggaran periode ini
                </p>
                
                <!-- Sisa Pagu Tahunan -->
                <div class="mt-3 pt-3 border-t border-border/50">
                    <div class="flex justify-between items-center text-[10px] mb-1">
                        <span class="font-semibold text-muted-foreground">Sisa Pagu Tahunan</span>
                        <span class="font-bold text-secondary">{{ formatRupiahShort(sisaPaguBelanja) }}</span>
                    </div>
                    <div class="h-1.5 bg-muted rounded-full overflow-hidden">
                        <div :class="['h-full rounded-full transition-all duration-700', sisaPaguPct > 30 ? 'bg-emerald-500' : sisaPaguPct > 10 ? 'bg-amber-500' : 'bg-rose-500']"
                            :style="{ width: Math.min(100, Math.max(0, sisaPaguPct)) + '%' }">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Realisasi Pendapatan -->
            <div class="bg-card border border-border/80 rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Realisasi Pendapatan</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <Receipt class="w-4 h-4 text-emerald-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-secondary tracking-tight leading-none">{{ formatRupiahShort(curRevActual) }}</p>
                <p class="text-[11px] text-muted-foreground mt-1.5">Anggaran: {{ formatRupiahShort(curRevBudget) }}</p>
                <div class="mt-3">
                    <div class="h-1.5 bg-muted rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-700"
                            :style="{ width: curRevBudget ? Math.min(100, (curRevActual / curRevBudget) * 100) + '%' : '0%' }">
                        </div>
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">{{ curRevBudget ? ((curRevActual / curRevBudget) * 100).toFixed(1) : 0 }}% dari target</p>
                </div>
            </div>

            <!-- Net Budget vs Actual -->
            <div :class="[
                'border rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group',
                netVariance >= 0 ? 'bg-card border-border/80' : 'bg-rose-50/80 border-rose-200'
            ]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Selisih Bersih (Laba)</span>
                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', netVariance >= 0 ? 'bg-blue-500/10' : 'bg-rose-500/15']">
                        <Target :class="['w-4 h-4', netVariance >= 0 ? 'text-blue-600' : 'text-rose-500']" />
                    </div>
                </div>
                <p :class="['text-2xl font-bold tracking-tight leading-none', netVariance >= 0 ? 'text-secondary' : 'text-rose-600']">
                    {{ netVariance >= 0 ? '+' : '' }}{{ formatRupiahShort(netVariance) }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">
                    Aktual {{ formatRupiahShort(netActual) }} vs rencana {{ formatRupiahShort(netBudget) }}
                </p>
                <div :class="['mt-3 flex items-center gap-1 text-[11px] font-semibold', netVariance >= 0 ? 'text-blue-600' : 'text-rose-500']">
                    <TrendingUp v-if="netVariance >= 0" class="w-3.5 h-3.5 flex-shrink-0" />
                    <TrendingDown v-else class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>{{ netVariance >= 0 ? 'Surplus vs rencana' : 'Defisit vs rencana' }}</span>
                </div>
            </div>

        </div>

        <!-- ── ROW 2: Chart Budget vs Actual ───────────────────── -->
        <div class="bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">

            <!-- Tab Header -->
            <div class="px-5 py-4 border-b border-border/60 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-secondary">Tren Anggaran vs Realisasi</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Garis putus-putus = anggaran · Garis solid = realisasi</p>
                </div>
                <!-- Tab toggle -->
                <div class="flex items-center bg-muted rounded-lg p-0.5 gap-0.5">
                    <button
                        @click="activeTab = 'revenue'"
                        :class="[
                            'px-3 py-1.5 rounded-md text-[11px] font-semibold transition-all duration-200',
                            activeTab === 'revenue'
                                ? 'bg-card text-secondary shadow-sm'
                                : 'text-muted-foreground hover:text-secondary'
                        ]"
                    >
                        📈 Pendapatan
                    </button>
                    <button
                        @click="activeTab = 'expense'"
                        :class="[
                            'px-3 py-1.5 rounded-md text-[11px] font-semibold transition-all duration-200',
                            activeTab === 'expense'
                                ? 'bg-card text-secondary shadow-sm'
                                : 'text-muted-foreground hover:text-secondary'
                        ]"
                    >
                        📉 Pengeluaran
                    </button>
                </div>
            </div>

            <!-- Chart Area -->
            <div class="p-5" style="height: 300px;">
                <Line
                    v-if="activeTab === 'revenue'"
                    :data="revenueChartData"
                    :options="lineChartOptions"
                />
                <Line
                    v-else
                    :data="expenseChartData"
                    :options="lineChartOptions"
                />
            </div>

            <!-- Variance Summary Bar per Month -->
            <div class="px-5 pb-5">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground mb-3">Selisih per Bulan</p>
                <div class="grid grid-cols-6 sm:grid-cols-12 gap-1.5">
                    <div
                        v-for="(m, i) in months"
                        :key="m"
                        :class="[
                            'flex flex-col items-center gap-1 transition-all duration-200 rounded-md py-1',
                            (i >= startIdx && i <= endIdx) ? 'opacity-100 bg-muted/40 font-medium' : 'opacity-25'
                        ]"
                    >
                        <template v-if="activeTab === 'revenue'">
                            <span :class="[
                                'text-[9px] font-bold',
                                revenueActual[i] >= revenueBudget[i] ? 'text-emerald-600' : 'text-rose-500'
                            ]">
                                {{ revenueActual[i] >= revenueBudget[i] ? '+' : '' }}{{ revenueBudget[i] ? (((revenueActual[i] - revenueBudget[i]) / revenueBudget[i]) * 100).toFixed(1) : 0 }}%
                            </span>
                        </template>
                        <template v-else>
                            <span :class="[
                                'text-[9px] font-bold',
                                expenseActual[i] <= expenseBudget[i] ? 'text-emerald-600' : 'text-rose-500'
                            ]">
                                {{ expenseActual[i] >= expenseBudget[i] ? '+' : '' }}{{ expenseBudget[i] ? (((expenseActual[i] - expenseBudget[i]) / expenseBudget[i]) * 100).toFixed(1) : 0 }}%
                            </span>
                        </template>
                        <span class="text-[9px] text-muted-foreground">{{ m }}</span>
                    </div>
                </div>
            </div>
        </div>



        <!-- ── ROW 4: Variance Table Summary ───────────────────── -->
        <div class="bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-border/60 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-secondary">Tabel Ringkasan Realisasi</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Pendapatan: selisih (+) = melebihi target · Pengeluaran: selisih (+) = melebihi anggaran</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/40 border-b border-border/60">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Pos</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Anggaran</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Realisasi</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Selisih (Rp)</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Selisih %</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <!-- Revenue row -->
                        <tr class="hover:bg-muted/30 transition-colors duration-150">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></div>
                                    <span class="text-sm font-semibold text-secondary">Total Pendapatan</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-muted-foreground">{{ formatRupiah(curRevBudget) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-secondary">{{ formatRupiah(curRevActual) }}</td>
                            <td :class="['px-4 py-3 text-right text-sm font-semibold', revVariance >= 0 ? 'text-emerald-600' : 'text-rose-500']">
                                {{ revVariance >= 0 ? '+' : '' }}{{ formatRupiah(revVariance) }}
                            </td>
                            <td :class="['px-4 py-3 text-right text-sm font-bold', revVariance >= 0 ? 'text-emerald-600' : 'text-rose-500']">
                                {{ revVariance >= 0 ? '+' : '' }}{{ revVariancePct }}%
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="[
                                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border',
                                    revVariance >= 0 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200'
                                ]">
                                    {{ revVariance >= 0 ? '✅ Bagus' : '⚠️ Under' }}
                                </span>
                            </td>
                        </tr>

                        <!-- Expense row -->
                        <tr class="hover:bg-muted/30 transition-colors duration-150">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div :class="['w-2 h-2 rounded-full flex-shrink-0', expVariance <= 0 ? 'bg-emerald-500' : expVariancePct <= 15 ? 'bg-amber-500' : 'bg-rose-500']"></div>
                                    <span class="text-sm font-semibold text-secondary">Total Pengeluaran</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-muted-foreground">{{ formatRupiah(curExpBudget) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-secondary">{{ formatRupiah(curExpActual) }}</td>
                            <td :class="['px-4 py-3 text-right text-sm font-semibold', expVariance <= 0 ? 'text-emerald-600' : 'text-rose-500']">
                                {{ expVariance >= 0 ? '+' : '' }}{{ formatRupiah(expVariance) }}
                            </td>
                            <td :class="['px-4 py-3 text-right text-sm font-bold', expVariance <= 0 ? 'text-emerald-600' : expVariancePct <= 15 ? 'text-amber-600' : 'text-rose-600']">
                                {{ expVariance >= 0 ? '+' : '' }}{{ expVariancePct }}%
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="[
                                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border',
                                    expVariance <= 0 ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                                    : expVariancePct <= 15 ? 'bg-amber-100 text-amber-700 border-amber-200'
                                    : 'bg-rose-100 text-rose-700 border-rose-200'
                                ]">
                                    {{ expVariance <= 0 ? '✅ Efisien' : expVariancePct <= 15 ? '⚠️ Melebihi' : '🔴 Bahaya' }}
                                </span>
                            </td>
                        </tr>

                        <!-- Net / Laba row -->
                        <tr class="bg-muted/20 hover:bg-muted/40 transition-colors duration-150 font-bold">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div :class="['w-2 h-2 rounded-full flex-shrink-0', netVariance >= 0 ? 'bg-blue-500' : 'bg-rose-500']"></div>
                                    <span class="text-sm font-bold text-secondary">Surplus / Defisit Bersih</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-secondary">{{ formatRupiah(netBudget) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-secondary">{{ formatRupiah(netActual) }}</td>
                            <td :class="['px-4 py-3 text-right text-sm font-bold', netVariance >= 0 ? 'text-blue-600' : 'text-rose-600']">
                                {{ netVariance >= 0 ? '+' : '' }}{{ formatRupiah(netVariance) }}
                            </td>
                            <td :class="['px-4 py-3 text-right text-sm font-black', netVariance >= 0 ? 'text-blue-600' : 'text-rose-600']">
                                {{ netVariance >= 0 ? '+' : '' }}{{ netBudget ? (((netVariance) / Math.abs(netBudget)) * 100).toFixed(1) : 0 }}%
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="[
                                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border',
                                    netVariance >= 0 ? 'bg-blue-100 text-blue-700 border-blue-200' : 'bg-rose-100 text-rose-700 border-rose-200'
                                ]">
                                    {{ netVariance >= 0 ? '✅ Surplus' : '🔴 Defisit' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </section>
</template>
