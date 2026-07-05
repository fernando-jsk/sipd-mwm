<script setup>
import { computed } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
} from 'chart.js';
import {
    TrendingUp,
    TrendingDown,
    Minus,
    ArrowUpRight,
    ArrowDownRight,
} from 'lucide-vue-next';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement);

// ============================================================
// DATA DUMMY — akan diganti dengan data dari API/props nantinya
// ============================================================
const months      = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'];
const cashInData  = [1_850_000_000, 2_100_000_000, 1_950_000_000, 2_300_000_000, 2_150_000_000, 2_450_000_000, 2_200_000_000];
const cashOutData = [1_600_000_000, 1_800_000_000, 2_050_000_000, 1_900_000_000, 2_000_000_000, 2_100_000_000, 2_380_000_000];

const currentMonthIn    = cashInData.at(-1);
const currentMonthOut   = cashOutData.at(-1);
const netCashFlow       = currentMonthIn - currentMonthOut;

const endingBalance      = 1_420_000_000;   // Saldo akhir kas bulan ini
const minimumSafeBalance = 500_000_000;     // Batas aman minimal operasional
const dailyBurnRate      = currentMonthOut / 30;
const runway             = Math.floor(endingBalance / dailyBurnRate); // Hari bisa bertahan

// Threshold alarm
const isBalanceDanger   = endingBalance < minimumSafeBalance;
const isBalanceCritical = !isBalanceDanger && endingBalance < minimumSafeBalance * 1.5;

// ============================================================
// CHART 1 — Bar: Cash In vs Cash Out bulanan
// ============================================================
const barChartData = computed(() => ({
    labels: months,
    datasets: [
        {
            label: 'Cash In',
            data: cashInData,
            backgroundColor: 'rgba(74,222,128,0.82)',
            borderColor:     'rgba(74,222,128,1)',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false,
        },
        {
            label: 'Cash Out',
            data: cashOutData,
            backgroundColor: 'rgba(255,135,129,0.82)',
            borderColor:     'rgba(255,135,129,1)',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false,
        },
    ],
}));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
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
                font: { size: 11 },
                callback: (val) => formatRupiahShort(val),
            },
        },
    },
};

// ============================================================
// CHART 2 — Doughnut: Breakdown pengeluaran
// ============================================================
const breakdownLabels = ['Gaji & SDM', 'Supplier', 'Operasional', 'Pajak', 'Lainnya'];
const breakdownValues = [980_000_000, 620_000_000, 480_000_000, 180_000_000, 120_000_000];
const breakdownBg     = ['rgba(255,135,129,0.80)', 'rgba(74,222,128,0.80)', 'rgba(96,165,250,0.80)', 'rgba(251,191,36,0.80)', 'rgba(167,139,250,0.80)'];
const breakdownBorder = ['#E64E47', '#22c55e', '#3b82f6', '#F59E0B', '#8B5CF6'];

const doughnutData = computed(() => ({
    labels: breakdownLabels,
    datasets: [{
        data: breakdownValues,
        backgroundColor: breakdownBg,
        borderColor:     breakdownBorder,
        borderWidth: 2,
        hoverOffset: 8,
    }],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '66%',
    plugins: {
        legend: {
            position: 'right',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 12,
                font: { size: 11, family: 'inherit' },
                color: '#64748b',
            },
        },
        tooltip: {
            callbacks: {
                label: (ctx) => ` ${ctx.label}: ${formatRupiah(ctx.raw)} (${((ctx.raw / currentMonthOut) * 100).toFixed(1)}%)`,
            },
        },
    },
};

// ============================================================
// HELPERS
// ============================================================
function formatRupiah(val) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}

function formatRupiahShort(val) {
    if (val >= 1_000_000_000) return `${(val / 1_000_000_000).toFixed(1)} M`;
    if (val >= 1_000_000)     return `${(val / 1_000_000).toFixed(0)} Jt`;
    return String(val);
}

const balancePct = Math.min(100, Math.round((endingBalance / (minimumSafeBalance * 3)) * 100));
</script>

<template>
    <!-- ============================================================
         SECTION 1: DASHBOARD CASH FLOW (ARUS KAS)
    ============================================================ -->
    <section class="space-y-5">

        <!-- ── Section Header ──────────────────────────────────── -->
        <div class="flex items-center gap-3">
            <div class="w-1 h-8 rounded-full bg-primary"></div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-secondary">Dashboard Cash Flow</h2>
                <p class="text-xs text-muted-foreground mt-0.5">Arus kas riil · Periode: Juli 2025</p>
            </div>
        </div>

        <!-- ── ROW 1: KPI Cards ─────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">

            <!-- KPI: Cash In -->
            <div class="bg-card border border-border/80 rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Cash In</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-colors duration-200">
                        <ArrowUpRight class="w-4 h-4 text-emerald-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-secondary tracking-tight leading-none">
                    {{ formatRupiahShort(currentMonthIn) }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">
                    {{ formatRupiah(currentMonthIn) }}
                </p>
                <div class="mt-3 flex items-center gap-1 text-[11px] text-emerald-600 font-semibold">
                    <TrendingUp class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>+6.2% vs bulan lalu</span>
                </div>
            </div>

            <!-- KPI: Cash Out -->
            <div class="bg-card border border-border/80 rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Cash Out</span>
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors duration-200">
                        <ArrowDownRight class="w-4 h-4 text-primary" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-secondary tracking-tight leading-none">
                    {{ formatRupiahShort(currentMonthOut) }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">
                    {{ formatRupiah(currentMonthOut) }}
                </p>
                <div class="mt-3 flex items-center gap-1 text-[11px] text-rose-500 font-semibold">
                    <TrendingUp class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>+19.0% vs bulan lalu</span>
                </div>
            </div>

            <!-- KPI: Net Cash Flow -->
            <div :class="[
                'border rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group',
                netCashFlow >= 0
                    ? 'bg-emerald-50/80 border-emerald-200'
                    : 'bg-rose-50/80 border-rose-200'
            ]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Net Cash Flow</span>
                    <div :class="[
                        'w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200',
                        netCashFlow >= 0 ? 'bg-emerald-500/15' : 'bg-rose-500/15'
                    ]">
                        <TrendingUp v-if="netCashFlow >= 0" class="w-4 h-4 text-emerald-600" />
                        <TrendingDown v-else class="w-4 h-4 text-rose-500" />
                    </div>
                </div>
                <p :class="['text-2xl font-bold tracking-tight leading-none', netCashFlow >= 0 ? 'text-emerald-700' : 'text-rose-600']">
                    {{ netCashFlow >= 0 ? '+' : '' }}{{ formatRupiahShort(netCashFlow) }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">Selisih bulan berjalan</p>
                <div :class="['mt-3 flex items-center gap-1 text-[11px] font-semibold', netCashFlow >= 0 ? 'text-emerald-600' : 'text-rose-500']">
                    <Minus class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>In − Out</span>
                </div>
            </div>


        </div>

        <!-- ── ROW 2: Charts ────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            <!-- Bar Chart: Tren Cash In vs Cash Out -->
            <div class="lg:col-span-3 bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border/60 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-secondary">Tren Cash In vs Cash Out</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">7 bulan terakhir (Jan – Jul 2025)</p>
                    </div>
                    <span class="text-[10px] bg-muted text-muted-foreground rounded-full px-2.5 py-1 font-semibold uppercase tracking-wide">Bulanan</span>
                </div>
                <div class="p-5" style="height: 288px;">
                    <Bar :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Doughnut: Cash Breakdown -->
            <div class="lg:col-span-2 bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border/60 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-secondary">Breakdown Cash Out</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Distribusi pengeluaran Juli 2025</p>
                    </div>
                </div>
                <div class="p-4 flex items-center justify-center" style="height: 288px;">
                    <Doughnut :data="doughnutData" :options="doughnutOptions" />
                </div>
            </div>

        </div>

        <!-- ── ROW 3: Ending Cash Balance Alert Bar ─────────────── -->
        <div :class="[
            'rounded-xl border px-5 py-4 flex flex-wrap sm:flex-nowrap items-center gap-4 transition-all duration-300',
            isBalanceDanger   ? 'bg-rose-50 border-rose-300'
            : isBalanceCritical ? 'bg-amber-50 border-amber-300'
            : 'bg-emerald-50/70 border-emerald-200'
        ]">
            <!-- Icon -->
            <div :class="[
                'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0',
                isBalanceDanger ? 'bg-rose-500/15' : isBalanceCritical ? 'bg-amber-500/15' : 'bg-emerald-500/15'
            ]">
                <AlertTriangle :class="['w-5 h-5', isBalanceDanger ? 'text-rose-600' : isBalanceCritical ? 'text-amber-600' : 'text-emerald-600']" />
            </div>

            <!-- Text -->
            <div class="flex-1 min-w-0">
                <p :class="['text-sm font-bold', isBalanceDanger ? 'text-rose-700' : isBalanceCritical ? 'text-amber-800' : 'text-emerald-800']">
                    {{
                        isBalanceDanger
                            ? '⚠️ ALARM: Saldo Kas Di Bawah Batas Aman Minimum!'
                            : isBalanceCritical
                                ? 'Perhatian: Saldo Kas Mendekati Batas Aman'
                                : '✅ Saldo Akhir Kas Dalam Kondisi Aman'
                    }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-0.5 leading-relaxed">
                    Saldo saat ini <strong class="text-foreground">{{ formatRupiah(endingBalance) }}</strong>
                    &nbsp;·&nbsp; Batas aman min. <strong class="text-foreground">{{ formatRupiah(minimumSafeBalance) }}</strong>
                    &nbsp;·&nbsp; Burn rate harian ≈ <strong class="text-foreground">{{ formatRupiah(Math.round(dailyBurnRate)) }}</strong>
                </p>
            </div>

            <!-- Progress Bar Saldo -->
            <div class="hidden sm:block w-44 flex-shrink-0">
                <div class="flex justify-between text-[10px] text-muted-foreground mb-1.5">
                    <span>Batas aman</span>
                    <span class="font-semibold">{{ balancePct }}%</span>
                </div>
                <div class="h-2 bg-black/10 rounded-full overflow-hidden">
                    <div
                        :class="[
                            'h-full rounded-full transition-all duration-700 ease-out',
                            isBalanceDanger ? 'bg-rose-500' : isBalanceCritical ? 'bg-amber-500' : 'bg-emerald-500'
                        ]"
                        :style="{ width: balancePct + '%' }"
                    ></div>
                </div>
                <p :class="['text-[10px] mt-1 font-semibold', isBalanceDanger ? 'text-rose-600' : isBalanceCritical ? 'text-amber-600' : 'text-emerald-600']">
                    {{ formatRupiahShort(endingBalance) }} / {{ formatRupiahShort(minimumSafeBalance * 3) }}
                </p>
            </div>
        </div>

    </section>
</template>
