<script setup>
import { computed, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Tooltip,
    Legend,
} from 'chart.js';
import {
    Users,
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    CheckCircle,
    ChevronUp,
    ChevronDown,
    Minus,
    Clock,
    BadgeAlert,
    CircleDollarSign,
    PercentCircle,
} from 'lucide-vue-next';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

// ============================================================
// DATA DUMMY — akan diganti dengan data dari API/props nantinya
// ============================================================

// Bucket Aging Summary
const agingBuckets = [
    {
        label: 'Current',
        sublabel: 'Belum jatuh tempo',
        value: 4_250_000_000,
        color: 'emerald',
        icon: CheckCircle,
        risk: 'aman',
    },
    {
        label: '1 – 30 Hari',
        sublabel: 'Terlambat ringan',
        value: 1_850_000_000,
        color: 'blue',
        icon: Clock,
        risk: 'ringan',
    },
    {
        label: '31 – 60 Hari',
        sublabel: 'Perlu follow-up intens',
        value: 980_000_000,
        color: 'amber',
        icon: AlertTriangle,
        risk: 'sedang',
    },
    {
        label: '61 – 90 Hari',
        sublabel: 'Status kritis',
        value: 540_000_000,
        color: 'orange',
        icon: BadgeAlert,
        risk: 'kritis',
    },
    {
        label: '> 90 Hari',
        sublabel: 'Risiko macet / bad debt',
        value: 320_000_000,
        color: 'rose',
        icon: TrendingDown,
        risk: 'bahaya',
    },
];

const totalPiutang = computed(() => agingBuckets.reduce((s, b) => s + b.value, 0));

const collectionRate     = 87.5;  // % piutang tertagih tepat waktu bulan ini
const collectionRateEom  = 84.2;  // bulan lalu
const collectionDelta    = +(collectionRate - collectionRateEom).toFixed(1);

// ── Top 5 Debitur Menunggak ──────────────────────────────────
const debtors = ref([
    {
        id: 1,
        name: 'PT Suka Maju',
        type: 'Perusahaan',
        total: 150_000_000,
        current: 100_000_000,
        d30: 50_000_000,
        d60: 0,
        d90: 0,
        d90plus: 0,
    },
    {
        id: 2,
        name: 'CV Sumber Rejeki',
        type: 'CV',
        total: 85_000_000,
        current: 0,
        d30: 0,
        d60: 60_000_000,
        d90: 25_000_000,
        d90plus: 0,
    },
    {
        id: 3,
        name: 'PT Harapan Baru',
        type: 'Perusahaan',
        total: 210_000_000,
        current: 80_000_000,
        d30: 70_000_000,
        d60: 40_000_000,
        d90: 20_000_000,
        d90plus: 0,
    },
    {
        id: 4,
        name: 'UD Makmur Jaya',
        type: 'Usaha Dagang',
        total: 95_000_000,
        current: 0,
        d30: 0,
        d60: 0,
        d90: 55_000_000,
        d90plus: 40_000_000,
    },
    {
        id: 5,
        name: 'PT Citra Mandiri',
        type: 'Perusahaan',
        total: 175_000_000,
        current: 0,
        d30: 75_000_000,
        d60: 60_000_000,
        d90: 25_000_000,
        d90plus: 15_000_000,
    },
]);

// Sort key untuk tabel
const sortKey = ref('total');
const sortDir = ref('desc');

const sortedDebtors = computed(() => {
    const arr = [...debtors.value];
    arr.sort((a, b) => {
        const mul = sortDir.value === 'asc' ? 1 : -1;
        return (a[sortKey.value] - b[sortKey.value]) * mul;
    });
    return arr;
});

function toggleSort(key) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'desc';
    }
}

// ── Bar Chart: Aging Bucket ──────────────────────────────────
const barColors = [
    'rgba(74,222,128,0.82)',
    'rgba(96,165,250,0.82)',
    'rgba(251,191,36,0.82)',
    'rgba(249,115,22,0.82)',
    'rgba(251,113,133,0.82)',
];
const barBorders = ['#22c55e', '#3b82f6', '#F59E0B', '#ea580c', '#f43f5e'];

const barChartData = computed(() => ({
    labels: agingBuckets.map(b => b.label),
    datasets: [{
        label: 'Total Piutang',
        data: agingBuckets.map(b => b.value),
        backgroundColor: barColors,
        borderColor: barBorders,
        borderWidth: 1.5,
        borderRadius: 8,
        borderSkipped: false,
    }],
}));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => ` ${formatRupiah(ctx.raw)} (${((ctx.raw / totalPiutang.value) * 100).toFixed(1)}%)`,
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

// ── Helpers ──────────────────────────────────────────────────
function formatRupiah(val) {
    if (val === 0) return '—';
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

function pct(val) {
    return ((val / totalPiutang.value) * 100).toFixed(1);
}

function riskBadgeClass(debtor) {
    const overdueTotal = debtor.d60 + debtor.d90 + debtor.d90plus;
    if (debtor.d90plus > 0) return 'bg-rose-100 text-rose-700 border-rose-200';
    if (overdueTotal > 0 && debtor.d90 > 0) return 'bg-orange-100 text-orange-700 border-orange-200';
    if (debtor.d60 > 0) return 'bg-amber-100 text-amber-700 border-amber-200';
    if (debtor.d30 > 0) return 'bg-blue-100 text-blue-700 border-blue-200';
    return 'bg-emerald-100 text-emerald-700 border-emerald-200';
}

function riskLabel(debtor) {
    if (debtor.d90plus > 0) return 'Bahaya';
    if (debtor.d90 > 0)     return 'Kritis';
    if (debtor.d60 > 0)     return 'Sedang';
    if (debtor.d30 > 0)     return 'Ringan';
    return 'Aman';
}

const bucketColorMap = {
    emerald: { bg: 'bg-emerald-500/10', text: 'text-emerald-600', border: 'border-emerald-200', pill: 'bg-emerald-500', track: 'bg-emerald-500/15' },
    blue:    { bg: 'bg-blue-500/10',    text: 'text-blue-600',    border: 'border-blue-200',    pill: 'bg-blue-500',    track: 'bg-blue-500/15' },
    amber:   { bg: 'bg-amber-500/10',   text: 'text-amber-600',   border: 'border-amber-200',   pill: 'bg-amber-500',   track: 'bg-amber-500/15' },
    orange:  { bg: 'bg-orange-500/10',  text: 'text-orange-600',  border: 'border-orange-200',  pill: 'bg-orange-500',  track: 'bg-orange-500/15' },
    rose:    { bg: 'bg-rose-500/10',    text: 'text-rose-600',    border: 'border-rose-200',    pill: 'bg-rose-500',    track: 'bg-rose-500/15' },
};

const tableColumns = [
    { key: 'name',    label: 'Nama Pelanggan',      sortable: false },
    { key: 'total',   label: 'Total Piutang',        sortable: true  },
    { key: 'current', label: 'Current',              sortable: true  },
    { key: 'd30',     label: '1–30 Hari',            sortable: true  },
    { key: 'd60',     label: '31–60 Hari',           sortable: true  },
    { key: 'd90',     label: '61–90 Hari',           sortable: true  },
    { key: 'd90plus', label: '> 90 Hari',            sortable: true  },
    { key: 'risk',    label: 'Status',               sortable: false },
];
</script>

<template>
    <!-- ============================================================
         SECTION 2: DASHBOARD AGING PIUTANG (AR AGING)
    ============================================================ -->
    <section class="space-y-5">

        <!-- ── Section Header ──────────────────────────────────── -->
        <div class="flex items-center gap-3">
            <div class="w-1 h-8 rounded-full bg-amber-500"></div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-secondary">Dashboard Aging Piutang</h2>
                <p class="text-xs text-muted-foreground mt-0.5">Account Receivable Aging · Periode: Juli 2025</p>
            </div>
        </div>

        <!-- ── ROW 1: KPI Summary Cards ────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Total Piutang -->
            <div class="bg-card border border-border/80 rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group lg:col-span-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Total Piutang</span>
                    <div class="w-8 h-8 rounded-lg bg-secondary/8 flex items-center justify-center group-hover:bg-secondary/15 transition-colors duration-200">
                        <CircleDollarSign class="w-4 h-4 text-secondary" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-secondary tracking-tight leading-none">
                    {{ formatRupiahShort(totalPiutang) }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">{{ formatRupiah(totalPiutang) }}</p>
                <div class="mt-3 text-[11px] text-muted-foreground font-medium">
                    5 debitur aktif
                </div>
            </div>

            <!-- Overdue > 30 Hari -->
            <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Overdue > 30 Hari</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/15 flex items-center justify-center group-hover:bg-amber-500/25 transition-colors duration-200">
                        <AlertTriangle class="w-4 h-4 text-amber-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-amber-700 tracking-tight leading-none">
                    {{ formatRupiahShort(agingBuckets[2].value + agingBuckets[3].value + agingBuckets[4].value) }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">
                    {{ pct(agingBuckets[2].value + agingBuckets[3].value + agingBuckets[4].value) }}% dari total piutang
                </p>
                <div class="mt-3 flex items-center gap-1 text-[11px] text-amber-600 font-semibold">
                    <AlertTriangle class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>Perlu tindak lanjut</span>
                </div>
            </div>

            <!-- Bad Debt Risk -->
            <div class="bg-rose-50/80 border border-rose-200 rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Risiko Bad Debt</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-500/15 flex items-center justify-center group-hover:bg-rose-500/25 transition-colors duration-200">
                        <BadgeAlert class="w-4 h-4 text-rose-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-rose-600 tracking-tight leading-none">
                    {{ formatRupiahShort(agingBuckets[4].value) }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1.5">
                    {{ pct(agingBuckets[4].value) }}% dari total piutang
                </p>
                <div class="mt-3 flex items-center gap-1 text-[11px] text-rose-600 font-semibold">
                    <TrendingDown class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>Risiko macet &gt; 90 hari</span>
                </div>
            </div>

            <!-- Collection Rate -->
            <div class="bg-card border border-border/80 rounded-xl p-5 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Collection Rate</span>
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors duration-200">
                        <PercentCircle class="w-4 h-4 text-primary" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-secondary tracking-tight leading-none">{{ collectionRate }}%</p>
                <p class="text-[11px] text-muted-foreground mt-1.5">Tertagih tepat waktu bulan ini</p>
                <div :class="['mt-3 flex items-center gap-1 text-[11px] font-semibold', collectionDelta >= 0 ? 'text-emerald-600' : 'text-rose-500']">
                    <TrendingUp v-if="collectionDelta >= 0" class="w-3.5 h-3.5 flex-shrink-0" />
                    <TrendingDown v-else class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>{{ collectionDelta >= 0 ? '+' : '' }}{{ collectionDelta }}% vs bulan lalu</span>
                </div>
            </div>

        </div>

        <!-- ── ROW 2: Aging Bucket Visual ──────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            <!-- Bar Chart Aging Bucket -->
            <div class="lg:col-span-3 bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border/60 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-secondary">Distribusi Bucket Aging</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Total piutang per kategori umur</p>
                    </div>
                    <span class="text-[10px] bg-muted text-muted-foreground rounded-full px-2.5 py-1 font-semibold uppercase tracking-wide">Juli 2025</span>
                </div>
                <div class="p-5" style="height: 260px;">
                    <Bar :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Aging Bucket Cards Stack -->
            <div class="lg:col-span-2 flex flex-col gap-2.5">
                <div
                    v-for="(bucket, i) in agingBuckets"
                    :key="i"
                    :class="[
                        'bg-card border rounded-xl px-4 py-3 flex items-center gap-3 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200',
                        bucketColorMap[bucket.color].border
                    ]"
                >
                    <!-- Icon -->
                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', bucketColorMap[bucket.color].bg]">
                        <component :is="bucket.icon" :class="['w-4 h-4', bucketColorMap[bucket.color].text]" />
                    </div>

                    <!-- Label & Progress -->
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline mb-1">
                            <span class="text-[11px] font-semibold text-secondary">{{ bucket.label }}</span>
                            <span :class="['text-[11px] font-bold', bucketColorMap[bucket.color].text]">{{ pct(bucket.value) }}%</span>
                        </div>
                        <div :class="['h-1.5 rounded-full', bucketColorMap[bucket.color].track]">
                            <div
                                :class="['h-full rounded-full transition-all duration-700', bucketColorMap[bucket.color].pill]"
                                :style="{ width: pct(bucket.value) + '%' }"
                            ></div>
                        </div>
                        <p class="text-[10px] text-muted-foreground mt-1">{{ formatRupiahShort(bucket.value) }} · {{ bucket.sublabel }}</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── ROW 3: Tabel Aging Detail ───────────────────────── -->
        <div class="bg-card border border-border/80 rounded-xl shadow-sm overflow-hidden">

            <!-- Table Header -->
            <div class="px-5 py-4 border-b border-border/60 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-secondary">Top Debitur — Rincian Aging</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Klik header kolom untuk mengurutkan</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="flex items-center gap-1.5 text-[10px] font-medium text-muted-foreground">
                        <Users class="w-3.5 h-3.5" />
                        {{ debtors.length }} Debitur
                    </span>
                </div>
            </div>

            <!-- Scrollable Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/40 border-b border-border/60">
                            <th
                                v-for="col in tableColumns"
                                :key="col.key"
                                :class="[
                                    'px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-muted-foreground whitespace-nowrap',
                                    col.sortable ? 'cursor-pointer hover:text-secondary select-none transition-colors duration-150' : ''
                                ]"
                                @click="col.sortable ? toggleSort(col.key) : null"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{ col.label }}</span>
                                    <template v-if="col.sortable">
                                        <ChevronUp
                                            v-if="sortKey === col.key && sortDir === 'asc'"
                                            class="w-3 h-3 text-primary"
                                        />
                                        <ChevronDown
                                            v-else-if="sortKey === col.key && sortDir === 'desc'"
                                            class="w-3 h-3 text-primary"
                                        />
                                        <Minus v-else class="w-3 h-3 text-muted-foreground/40" />
                                    </template>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr
                            v-for="(debtor, idx) in sortedDebtors"
                            :key="debtor.id"
                            class="hover:bg-muted/30 transition-colors duration-150 group"
                        >
                            <!-- Nama -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-secondary/8 flex items-center justify-center text-[10px] font-bold text-secondary flex-shrink-0">
                                        {{ idx + 1 }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-secondary">{{ debtor.name }}</p>
                                        <p class="text-[10px] text-muted-foreground">{{ debtor.type }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Total -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm font-bold text-secondary">{{ formatRupiah(debtor.total) }}</span>
                            </td>

                            <!-- Current -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['text-sm', debtor.current > 0 ? 'text-emerald-600 font-semibold' : 'text-muted-foreground/50']">
                                    {{ formatRupiah(debtor.current) }}
                                </span>
                            </td>

                            <!-- 1-30 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['text-sm', debtor.d30 > 0 ? 'text-blue-600 font-semibold' : 'text-muted-foreground/50']">
                                    {{ formatRupiah(debtor.d30) }}
                                </span>
                            </td>

                            <!-- 31-60 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['text-sm', debtor.d60 > 0 ? 'text-amber-600 font-semibold' : 'text-muted-foreground/50']">
                                    {{ formatRupiah(debtor.d60) }}
                                </span>
                            </td>

                            <!-- 61-90 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['text-sm', debtor.d90 > 0 ? 'text-orange-600 font-semibold' : 'text-muted-foreground/50']">
                                    {{ formatRupiah(debtor.d90) }}
                                </span>
                            </td>

                            <!-- >90 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['text-sm', debtor.d90plus > 0 ? 'text-rose-600 font-bold' : 'text-muted-foreground/50']">
                                    {{ formatRupiah(debtor.d90plus) }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border', riskBadgeClass(debtor)]">
                                    {{ riskLabel(debtor) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Footer: Total row -->
                    <tfoot>
                        <tr class="bg-muted/40 border-t border-border/60">
                            <td class="px-4 py-3 text-[11px] font-bold text-secondary uppercase tracking-wider">TOTAL</td>
                            <td class="px-4 py-3 text-[11px] font-bold text-secondary">
                                {{ formatRupiah(debtors.reduce((s, d) => s + d.total, 0)) }}
                            </td>
                            <td class="px-4 py-3 text-[11px] font-semibold text-emerald-600">
                                {{ formatRupiah(debtors.reduce((s, d) => s + d.current, 0)) }}
                            </td>
                            <td class="px-4 py-3 text-[11px] font-semibold text-blue-600">
                                {{ formatRupiah(debtors.reduce((s, d) => s + d.d30, 0)) }}
                            </td>
                            <td class="px-4 py-3 text-[11px] font-semibold text-amber-600">
                                {{ formatRupiah(debtors.reduce((s, d) => s + d.d60, 0)) }}
                            </td>
                            <td class="px-4 py-3 text-[11px] font-semibold text-orange-600">
                                {{ formatRupiah(debtors.reduce((s, d) => s + d.d90, 0)) }}
                            </td>
                            <td class="px-4 py-3 text-[11px] font-semibold text-rose-600">
                                {{ formatRupiah(debtors.reduce((s, d) => s + d.d90plus, 0)) }}
                            </td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ── ROW 4: Collection Rate Meter ────────────────────── -->
        <div class="bg-card border border-border/80 rounded-xl px-5 py-4 flex flex-wrap sm:flex-nowrap items-center gap-5 shadow-sm">
            <!-- Label -->
            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                    <PercentCircle class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <p class="text-sm font-bold text-secondary">Collection Rate Bulan Ini</p>
                    <p class="text-[11px] text-muted-foreground">% piutang berhasil ditagih tepat waktu</p>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="flex-1 min-w-0">
                <div class="flex justify-between text-[10px] text-muted-foreground mb-1.5">
                    <span>0%</span>
                    <span class="font-semibold text-secondary">Target: 90%</span>
                    <span>100%</span>
                </div>
                <div class="relative h-3 bg-muted rounded-full overflow-hidden">
                    <!-- Target line -->
                    <div class="absolute top-0 bottom-0 w-px bg-secondary/30 z-10" style="left: 90%"></div>
                    <!-- Actual bar -->
                    <div
                        :class="[
                            'h-full rounded-full transition-all duration-700 ease-out',
                            collectionRate >= 90 ? 'bg-emerald-500' : collectionRate >= 75 ? 'bg-amber-500' : 'bg-rose-500'
                        ]"
                        :style="{ width: collectionRate + '%' }"
                    ></div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <span :class="['text-xs font-bold', collectionRate >= 90 ? 'text-emerald-600' : collectionRate >= 75 ? 'text-amber-600' : 'text-rose-600']">
                        {{ collectionRate }}% tercapai
                    </span>
                    <span :class="['text-[10px] font-semibold flex items-center gap-0.5', collectionDelta >= 0 ? 'text-emerald-600' : 'text-rose-500']">
                        <TrendingUp v-if="collectionDelta >= 0" class="w-3 h-3" />
                        <TrendingDown v-else class="w-3 h-3" />
                        {{ collectionDelta >= 0 ? '+' : '' }}{{ collectionDelta }}% vs bulan lalu ({{ collectionRateEom }}%)
                    </span>
                </div>
            </div>

            <!-- Big number -->
            <div class="text-right flex-shrink-0">
                <p :class="['text-3xl font-black tracking-tight', collectionRate >= 90 ? 'text-emerald-600' : collectionRate >= 75 ? 'text-amber-600' : 'text-rose-600']">
                    {{ collectionRate }}%
                </p>
                <p class="text-[10px] text-muted-foreground mt-0.5">
                    {{ collectionRate >= 90 ? '✅ Target tercapai' : collectionRate >= 75 ? '⚠️ Di bawah target' : '🔴 Perlu perhatian' }}
                </p>
            </div>
        </div>

    </section>
</template>
