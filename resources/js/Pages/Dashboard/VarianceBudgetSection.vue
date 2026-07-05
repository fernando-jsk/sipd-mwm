<script setup>
import { computed, ref } from 'vue';
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
// DATA DUMMY — akan diganti dengan data dari API/props nantinya
// ============================================================

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'];

// ── Revenue: Budget vs Actual ────────────────────────────────
const revenueBudget = [2_000_000_000, 2_100_000_000, 2_050_000_000, 2_200_000_000, 2_150_000_000, 2_300_000_000, 2_250_000_000];
const revenueActual = [1_850_000_000, 2_100_000_000, 1_950_000_000, 2_300_000_000, 2_150_000_000, 2_450_000_000, 2_200_000_000];

// ── Expense: Budget vs Actual ────────────────────────────────
const expenseBudget = [1_700_000_000, 1_800_000_000, 1_850_000_000, 1_920_000_000, 1_980_000_000, 2_050_000_000, 2_000_000_000];
const expenseActual = [1_600_000_000, 1_800_000_000, 2_050_000_000, 1_900_000_000, 2_000_000_000, 2_100_000_000, 2_380_000_000];

// KPI summary bulan ini (Juli)
const curRevBudget  = revenueBudget.at(-1);
const curRevActual  = revenueActual.at(-1);
const curExpBudget  = expenseBudget.at(-1);
const curExpActual  = expenseActual.at(-1);

const revVariance   = curRevActual - curRevBudget;   // positif = bagus
const expVariance   = curExpActual - curExpBudget;   // positif = bahaya

const revVariancePct = +((revVariance / curRevBudget) * 100).toFixed(1);
const expVariancePct = +((expVariance / curExpBudget) * 100).toFixed(1);

const netBudget = curRevBudget - curExpBudget;
const netActual = curRevActual - curExpActual;
const netVariance = netActual - netBudget;

// ── Departments Red Flag ─────────────────────────────────────
const departments = ref([
    { id: 1, name: 'Instalasi Farmasi',     category: 'Pengadaan',   budget: 450_000_000, actual: 612_000_000, icon: '💊' },
    { id: 2, name: 'Bagian SDM & Diklat',   category: 'Pegawai',     budget: 320_000_000, actual: 401_000_000, icon: '👥' },
    { id: 3, name: 'Instalasi Gizi',        category: 'Operasional', budget: 180_000_000, actual: 231_000_000, icon: '🍱' },
    { id: 4, name: 'Pemeliharaan Sarana',   category: 'Aset',        budget: 275_000_000, actual: 330_000_000, icon: '🔧' },
    { id: 5, name: 'Instalasi Radiologi',   category: 'Pengadaan',   budget: 220_000_000, actual: 251_000_000, icon: '🩻' },
    { id: 6, name: 'Bagian Keuangan',       category: 'Operasional', budget: 95_000_000,  actual: 88_000_000,  icon: '💰' },
    { id: 7, name: 'Instalasi Laboratorium',category: 'Pengadaan',   budget: 310_000_000, actual: 298_000_000, icon: '🔬' },
    { id: 8, name: 'Bagian Humas & Pemasaran', category: 'Operasional', budget: 120_000_000, actual: 104_000_000, icon: '📣' },
]);

// Hitung variance per dept
const deptWithVariance = computed(() =>
    departments.value.map(d => ({
        ...d,
        variance: d.actual - d.budget,
        variancePct: +(((d.actual - d.budget) / d.budget) * 100).toFixed(1),
    })).sort((a, b) => b.variancePct - a.variancePct)
);

const redFlagDepts = computed(() => deptWithVariance.value.filter(d => d.variancePct > 0));
const onTargetDepts = computed(() => deptWithVariance.value.filter(d => d.variancePct <= 0));

// Tab state
const activeTab = ref('revenue'); // 'revenue' | 'expense'

// ── Chart: Revenue Budget vs Actual ─────────────────────────
const revenueChartData = computed(() => ({
    labels: months,
    datasets: [
        {
            label: 'Anggaran',
            data: revenueBudget,
            borderColor: '#94a3b8',
            backgroundColor: 'rgba(148,163,184,0.08)',
            borderWidth: 2,
            borderDash: [5, 4],
            pointRadius: 4,
            pointBackgroundColor: '#94a3b8',
            tension: 0.35,
            fill: false,
            order: 2,
        },
        {
            label: 'Realisasi',
            data: revenueActual,
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
        },
    ],
}));

// ── Chart: Expense Budget vs Actual ─────────────────────────
const expenseChartData = computed(() => ({
    labels: months,
    datasets: [
        {
            label: 'Anggaran',
            data: expenseBudget,
            borderColor: '#94a3b8',
            backgroundColor: 'rgba(148,163,184,0.08)',
            borderWidth: 2,
            borderDash: [5, 4],
            pointRadius: 4,
            pointBackgroundColor: '#94a3b8',
            tension: 0.35,
            fill: false,
            order: 2,
        },
        {
            label: 'Realisasi',
            data: expenseActual,
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
                label: (ctx) => ` ${ctx.dataset.label}: ${formatRupiah(ctx.raw)}`,
                afterBody: (items) => {
                    if (items.length < 2) return [];
                    const budget = items.find(i => i.dataset.label === 'Anggaran')?.raw ?? 0;
                    const actual = items.find(i => i.dataset.label === 'Realisasi')?.raw ?? 0;
                    const diff   = actual - budget;
                    const pct    = ((diff / budget) * 100).toFixed(1);
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

// ── Chart: Variance Bar (dept) ───────────────────────────────
const deptBarData = computed(() => ({
    labels: deptWithVariance.value.map(d => d.name.split(' ').slice(-2).join(' ')), // shorten label
    datasets: [{
        label: 'Variance (%)',
        data: deptWithVariance.value.map(d => d.variancePct),
        backgroundColor: deptWithVariance.value.map(d =>
            d.variancePct > 15  ? 'rgba(251,113,133,0.85)'
            : d.variancePct > 0  ? 'rgba(251,191,36,0.85)'
            : 'rgba(74,222,128,0.82)'
        ),
        borderColor: deptWithVariance.value.map(d =>
            d.variancePct > 15  ? '#f43f5e'
            : d.variancePct > 0  ? '#F59E0B'
            : '#22c55e'
        ),
        borderWidth: 1.5,
        borderRadius: 6,
        borderSkipped: false,
    }],
}));

const deptBarOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    const d = deptWithVariance.value[ctx.dataIndex];
                    return [
                        ` Variance: ${ctx.raw >= 0 ? '+' : ''}${ctx.raw}%`,
                        ` Selisih: ${formatRupiah(d.variance)}`,
                    ];
                },
            },
        },
    },
    scales: {
        x: {
            grid: { color: 'rgba(100,116,139,0.08)' },
            border: { dash: [4, 4] },
            ticks: {
                color: '#64748b',
                font: { size: 10 },
                callback: (v) => `${v}%`,
            },
        },
        y: {
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 10 } },
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

function deptSeverity(pct) {
    if (pct > 15) return 'danger';
    if (pct > 0)  return 'warn';
    if (pct >= -5) return 'ok';
    return 'great';
}
</script>

<template>
    <!-- ============================================================
         SECTION 3: DASHBOARD VARIANCE BUDGET (ANGGARAN VS REALISASI)
    ============================================================ -->
    <section class="space-y-5">

        <!-- ── Section Header ──────────────────────────────────── -->
        <div class="flex items-center gap-3">
            <div class="w-1 h-8 rounded-full bg-blue-500"></div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-secondary">Dashboard Variance Budget</h2>
                <p class="text-xs text-muted-foreground mt-0.5">Anggaran vs Realisasi · Periode: Juli 2025</p>
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
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Variance Pendapatan</span>
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

            <!-- Expense Variance -->
            <div :class="[
                'border rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group',
                expVariance <= 0 ? 'bg-emerald-50/80 border-emerald-200' : expVariancePct <= 15 ? 'bg-amber-50/80 border-amber-300' : 'bg-rose-50/80 border-rose-300'
            ]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Variance Pengeluaran</span>
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
                    {{ expVariance >= 0 ? '+' : '' }}{{ formatRupiahShort(expVariance) }} dari anggaran
                </p>
                <div :class="[
                    'mt-3 flex items-center gap-1 text-[11px] font-semibold',
                    expVariance <= 0 ? 'text-emerald-600' : expVariancePct <= 15 ? 'text-amber-600' : 'text-rose-600'
                ]">
                    <CheckCircle v-if="expVariance <= 0" class="w-3.5 h-3.5 flex-shrink-0" />
                    <AlertTriangle v-else class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>{{ expVariance <= 0 ? 'Efisien — Di bawah anggaran' : expVariancePct <= 15 ? 'Over budget (ringan)' : 'OVER BUDGET — Perhatian!' }}</span>
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
                            :style="{ width: Math.min(100, (curRevActual / curRevBudget) * 100) + '%' }">
                        </div>
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">{{ ((curRevActual / curRevBudget) * 100).toFixed(1) }}% dari target</p>
                </div>
            </div>

            <!-- Net Budget vs Actual -->
            <div :class="[
                'border rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group',
                netVariance >= 0 ? 'bg-card border-border/80' : 'bg-rose-50/80 border-rose-200'
            ]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Net Variance (Laba)</span>
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
                <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground mb-3">Variance per Bulan</p>
                <div class="grid grid-cols-7 gap-1.5">
                    <div
                        v-for="(m, i) in months"
                        :key="m"
                        class="flex flex-col items-center gap-1"
                    >
                        <template v-if="activeTab === 'revenue'">
                            <span :class="[
                                'text-[9px] font-bold',
                                revenueActual[i] >= revenueBudget[i] ? 'text-emerald-600' : 'text-rose-500'
                            ]">
                                {{ revenueActual[i] >= revenueBudget[i] ? '+' : '' }}{{ (((revenueActual[i] - revenueBudget[i]) / revenueBudget[i]) * 100).toFixed(1) }}%
                            </span>
                        </template>
                        <template v-else>
                            <span :class="[
                                'text-[9px] font-bold',
                                expenseActual[i] <= expenseBudget[i] ? 'text-emerald-600' : 'text-rose-500'
                            ]">
                                {{ expenseActual[i] >= expenseBudget[i] ? '+' : '' }}{{ (((expenseActual[i] - expenseBudget[i]) / expenseBudget[i]) * 100).toFixed(1) }}%
                            </span>
                        </template>
                        <span class="text-[9px] text-muted-foreground">{{ m }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── ROW 3: Red Flags + Variance Chart ───────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            <!-- Red Flag Departments List (3/5) -->
            <div class="lg:col-span-3 bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border/60 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-secondary flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse inline-block"></span>
                            Red Flag Departments
                        </h3>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            {{ redFlagDepts.length }} departemen over budget bulan ini
                        </p>
                    </div>
                    <span class="text-[10px] bg-rose-100 text-rose-700 border border-rose-200 rounded-full px-2.5 py-1 font-semibold uppercase tracking-wide">
                        {{ redFlagDepts.length }} Over Budget
                    </span>
                </div>

                <div class="divide-y divide-border/50">
                    <!-- Over Budget rows -->
                    <div
                        v-for="(dept, i) in redFlagDepts"
                        :key="dept.id"
                        class="px-5 py-3.5 hover:bg-muted/30 transition-colors duration-150 flex items-center gap-3"
                    >
                        <!-- Rank & Icon -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-[10px] font-bold text-muted-foreground/60 w-4 text-right">{{ i + 1 }}</span>
                            <span class="text-base">{{ dept.icon }}</span>
                        </div>

                        <!-- Name & Category -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                <p class="text-[13px] font-semibold text-secondary truncate">{{ dept.name }}</p>
                                <span class="text-[9px] bg-muted text-muted-foreground rounded px-1.5 py-0.5 font-medium flex-shrink-0">{{ dept.category }}</span>
                            </div>
                            <!-- Progress bar: actual vs budget -->
                            <div class="relative h-1.5 bg-muted rounded-full overflow-hidden">
                                <!-- Budget line at 100% -->
                                <div class="absolute top-0 right-0 bottom-0 w-px bg-secondary/30 z-10" style="left: calc(100% * (1/1))"></div>
                                <div
                                    :class="[
                                        'h-full rounded-full transition-all duration-700',
                                        severityClass[deptSeverity(dept.variancePct)].bar
                                    ]"
                                    :style="{ width: Math.min(130, (dept.actual / dept.budget) * 100) + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-[10px] text-muted-foreground">
                                    Aktual: <strong class="text-foreground">{{ formatRupiahShort(dept.actual) }}</strong>
                                </span>
                                <span class="text-[10px] text-muted-foreground">
                                    Anggaran: {{ formatRupiahShort(dept.budget) }}
                                </span>
                            </div>
                        </div>

                        <!-- Variance Badge -->
                        <div class="flex-shrink-0 text-right">
                            <span :class="[
                                'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border',
                                severityClass[deptSeverity(dept.variancePct)].badge
                            ]">
                                +{{ dept.variancePct }}%
                            </span>
                            <p class="text-[10px] text-rose-500 font-semibold mt-0.5">+{{ formatRupiahShort(dept.variance) }}</p>
                        </div>
                    </div>

                    <!-- On-target rows (collapsed style) -->
                    <div v-if="onTargetDepts.length > 0" class="px-5 py-2.5 bg-muted/20">
                        <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-2">✅ Sesuai / Efisien</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="dept in onTargetDepts"
                                :key="dept.id"
                                class="inline-flex items-center gap-1 text-[10px] font-medium bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-md px-2 py-0.5"
                            >
                                {{ dept.icon }} {{ dept.name.split(' ').slice(-1)[0] }}
                                <span class="text-[9px] opacity-70">{{ dept.variancePct }}%</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variance Horizontal Bar Chart (2/5) -->
            <div class="lg:col-span-2 bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border/60">
                    <h3 class="text-sm font-semibold text-secondary">Variance % per Departemen</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Merah = over budget · Hijau = efisien</p>
                </div>
                <div class="p-4" style="height: 360px;">
                    <Bar :data="deptBarData" :options="deptBarOptions" />
                </div>
            </div>

        </div>

        <!-- ── ROW 4: Variance Table Summary ───────────────────── -->
        <div class="bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-border/60 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-secondary">Tabel Ringkasan Variance</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Pendapatan: variance (+) = bagus · Pengeluaran: variance (+) = bahaya</p>
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
                            <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Variance %</th>
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
                                    {{ expVariance <= 0 ? '✅ Efisien' : expVariancePct <= 15 ? '⚠️ Over' : '🔴 Bahaya' }}
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
                                {{ netVariance >= 0 ? '+' : '' }}{{ (((netVariance) / Math.abs(netBudget)) * 100).toFixed(1) }}%
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
