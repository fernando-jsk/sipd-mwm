<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription
} from '@/Components/ui/dialog';
import { Switch } from '@/Components/ui/switch';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    SlidersHorizontal,
    RotateCcw,
    Check,
    TrendingUp,
    ArrowUpRight,
    ArrowDownRight,
    PieChart,
    BarChart3,
    LineChart,
    CheckCircle,
    Flame,
    Receipt,
    Target,
    Table,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    initialWidgets: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:open']);

// Definitions of all 14 available widgets — names matching the exact dashboard card/section titles
const WIDGET_DEFINITIONS = [
    // --- Kategori: Arus Kas ---
    {
        id: 'kpi_cash_in',
        category: 'cash_flow',
        title: 'Pemasukan',
        description: 'Ringkasan total penerimaan kas riil dan persentase kenaikan vs bulan lalu',
        icon: ArrowUpRight,
        color: 'text-emerald-500 bg-emerald-500/10'
    },
    {
        id: 'kpi_cash_out',
        category: 'cash_flow',
        title: 'Pengeluaran',
        description: 'Ringkasan total pengeluaran belanja riil dan persentase vs bulan lalu',
        icon: ArrowDownRight,
        color: 'text-rose-500 bg-rose-500/10'
    },
    {
        id: 'kpi_net_cash_flow',
        category: 'cash_flow',
        title: 'Arus Kas Bersih',
        description: 'Status surplus atau defisit kas riil (Pemasukan dikurangi Pengeluaran)',
        icon: TrendingUp,
        color: 'text-blue-500 bg-blue-500/10'
    },
    {
        id: 'chart_receipt_breakdown',
        category: 'cash_flow',
        title: 'Rincian Penerimaan',
        description: 'Distribusi donat proporsi penerimaan per kategori dan jenis rekening',
        icon: PieChart,
        color: 'text-emerald-500 bg-emerald-500/10'
    },
    {
        id: 'chart_expense_breakdown',
        category: 'cash_flow',
        title: 'Rincian Pengeluaran',
        description: 'Distribusi donat proporsi belanja riil berdasarkan kode akun belanja',
        icon: PieChart,
        color: 'text-rose-500 bg-rose-500/10'
    },
    {
        id: 'chart_monthly_cashflow',
        category: 'cash_flow',
        title: 'Tren Pemasukan vs Pengeluaran',
        description: 'Bar chart perbandingan nominal pemasukan vs pengeluaran setiap bulan (Jan – Des)',
        icon: BarChart3,
        color: 'text-indigo-500 bg-indigo-500/10'
    },
    {
        id: 'chart_cumulative_cashflow',
        category: 'cash_flow',
        title: 'Akumulasi Pemasukan & Pengeluaran',
        description: 'Line chart tren kumulatif kas masuk vs kas keluar dari awal tahun berjalan',
        icon: LineChart,
        color: 'text-teal-500 bg-teal-500/10'
    },

    // --- Kategori: Realisasi Anggaran ---
    {
        id: 'kpi_revenue_variance',
        category: 'budget_variance',
        title: 'Selisih Pendapatan',
        description: 'Persentase dan nominal deviasi realisasi pendapatan terhadap target anggaran',
        icon: CheckCircle,
        color: 'text-emerald-500 bg-emerald-500/10'
    },
    {
        id: 'kpi_expense_variance',
        category: 'budget_variance',
        title: 'Selisih Pengeluaran',
        description: 'Persentase selisih pengeluaran serta visual bar sisa pagu anggaran tahunan',
        icon: Flame,
        color: 'text-amber-500 bg-amber-500/10'
    },
    {
        id: 'kpi_revenue_realization',
        category: 'budget_variance',
        title: 'Realisasi Pendapatan',
        description: 'Nominal realisasi kas masuk vs pagu anggaran dengan progress bar pencapaian',
        icon: Receipt,
        color: 'text-emerald-500 bg-emerald-500/10'
    },
    {
        id: 'kpi_net_budget_variance',
        category: 'budget_variance',
        title: 'Selisih Bersih (Laba)',
        description: 'Perbandingan surplus/defisit aktual terhadap proyeksi anggaran rencana',
        icon: Target,
        color: 'text-blue-500 bg-blue-500/10'
    },
    {
        id: 'chart_monthly_variance',
        category: 'budget_variance',
        title: 'Tren Anggaran vs Realisasi (Bulanan)',
        description: 'Line chart perbandingan bulanan dengan toggle Pendapatan dan Pengeluaran',
        icon: LineChart,
        color: 'text-purple-500 bg-purple-500/10'
    },
    {
        id: 'chart_cumulative_variance',
        category: 'budget_variance',
        title: 'Akumulasi Anggaran vs Realisasi (Kumulatif YTD)',
        description: 'Line chart kumulatif tahun berjalan untuk evaluasi capaian target tahunan',
        icon: LineChart,
        color: 'text-sky-500 bg-sky-500/10'
    },
    {
        id: 'table_budget_summary',
        category: 'budget_variance',
        title: 'Tabel Ringkasan Realisasi',
        description: 'Tabel perbandingan detail pos pendapatan, belanja operasi, modal, dan surplus',
        icon: Table,
        color: 'text-slate-600 bg-slate-500/10'
    }
];

const categories = [
    {
        id: 'cash_flow',
        label: 'Arus Kas (Cash Flow)',
        description: 'Widget indikator kas riil, aliran masuk, keluar, dan proporsi transaksi.'
    },
    {
        id: 'budget_variance',
        label: 'Realisasi Anggaran (Budget vs Actual)',
        description: 'Widget evaluasi anggaran RBA, capaian target pendapatan, dan sisa pagu belanja.'
    }
];

// Local state of toggles
const localWidgets = ref({});
const isSaving = ref(false);

const initLocalState = () => {
    const nextState = {};
    WIDGET_DEFINITIONS.forEach(w => {
        // Default to true if not explicitly set to false
        nextState[w.id] = props.initialWidgets?.[w.id] !== false;
    });
    localWidgets.value = nextState;
};

watch(() => props.open, (val) => {
    if (val) {
        initLocalState();
    }
}, { immediate: true });

watch(() => props.initialWidgets, () => {
    if (!props.open) {
        initLocalState();
    }
}, { deep: true });

const activeCount = computed(() => {
    return Object.values(localWidgets.value).filter(Boolean).length;
});

const isCategoryAllActive = (catId) => {
    const catWidgets = WIDGET_DEFINITIONS.filter(w => w.category === catId);
    return catWidgets.every(w => localWidgets.value[w.id] === true);
};

const toggleCategory = (catId) => {
    const targetState = !isCategoryAllActive(catId);
    WIDGET_DEFINITIONS.filter(w => w.category === catId).forEach(w => {
        localWidgets.value[w.id] = targetState;
    });
};

const applyPreset = (presetType) => {
    if (presetType === 'all') {
        WIDGET_DEFINITIONS.forEach(w => { localWidgets.value[w.id] = true; });
    } else if (presetType === 'none') {
        WIDGET_DEFINITIONS.forEach(w => { localWidgets.value[w.id] = false; });
    } else if (presetType === 'kpi_only') {
        WIDGET_DEFINITIONS.forEach(w => {
            localWidgets.value[w.id] = w.id.startsWith('kpi_');
        });
    } else if (presetType === 'cash_flow') {
        WIDGET_DEFINITIONS.forEach(w => {
            localWidgets.value[w.id] = w.category === 'cash_flow';
        });
    } else if (presetType === 'budget_variance') {
        WIDGET_DEFINITIONS.forEach(w => {
            localWidgets.value[w.id] = w.category === 'budget_variance';
        });
    }
};

const savePreferences = () => {
    isSaving.value = true;
    router.post('/dashboard/preferences', {
        widgets: localWidgets.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('update:open', false);
        },
        onFinish: () => {
            isSaving.value = false;
        }
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="w-[96vw] sm:max-w-3xl max-h-[90vh] flex flex-col p-0 gap-0 overflow-hidden shadow-2xl rounded-2xl border-border/80">
            <!-- Modal Header -->
            <DialogHeader class="px-6 py-4 border-b border-border/60 bg-muted/20 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <SlidersHorizontal class="w-5 h-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold text-secondary">
                            Atur Tampilan Dashboard
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Pilih kartu dan widget mana saja yang ingin ditampilkan atau disembunyikan pada dashboard akun Anda.
                        </DialogDescription>
                    </div>
                </div>

                <!-- Presets Bar -->
                <div class="flex items-center flex-wrap gap-1.5 pt-3 mt-1 border-t border-border/40">
                    <span class="text-[11px] font-semibold text-muted-foreground mr-1">Preset:</span>
                    <button 
                        type="button"
                        @click="applyPreset('all')"
                        class="px-2.5 py-1 text-xs rounded-md border border-border/80 bg-card hover:bg-muted text-secondary transition-colors cursor-pointer"
                    >
                        Tampilkan Semua
                    </button>
                    <button 
                        type="button"
                        @click="applyPreset('kpi_only')"
                        class="px-2.5 py-1 text-xs rounded-md border border-border/80 bg-card hover:bg-muted text-secondary transition-colors cursor-pointer"
                    >
                        Hanya KPI
                    </button>
                    <button 
                        type="button"
                        @click="applyPreset('cash_flow')"
                        class="px-2.5 py-1 text-xs rounded-md border border-border/80 bg-card hover:bg-muted text-secondary transition-colors cursor-pointer"
                    >
                        Hanya Arus Kas
                    </button>
                    <button 
                        type="button"
                        @click="applyPreset('budget_variance')"
                        class="px-2.5 py-1 text-xs rounded-md border border-border/80 bg-card hover:bg-muted text-secondary transition-colors cursor-pointer"
                    >
                        Hanya Realisasi
                    </button>
                    <button 
                        type="button"
                        @click="applyPreset('none')"
                        class="px-2.5 py-1 text-xs rounded-md border border-border/80 bg-card hover:bg-destructive/10 hover:text-destructive text-muted-foreground transition-colors ml-auto cursor-pointer"
                    >
                        Sembunyikan Semua
                    </button>
                </div>
            </DialogHeader>

            <!-- Scrollable Body with Cards -->
            <div class="overflow-y-auto px-6 py-5 space-y-6 flex-1 min-h-[300px]">
                <div v-for="cat in categories" :key="cat.id" class="space-y-3">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between pb-1 border-b border-border/50">
                        <div>
                            <h4 class="text-sm font-bold text-secondary flex items-center gap-2">
                                <span>{{ cat.label }}</span>
                                <Badge variant="secondary" class="text-[10px] py-0 px-2 font-normal">
                                    {{ WIDGET_DEFINITIONS.filter(w => w.category === cat.id && localWidgets[w.id]).length }} / {{ WIDGET_DEFINITIONS.filter(w => w.category === cat.id).length }} Aktif
                                </Badge>
                            </h4>
                            <p class="text-[11px] text-muted-foreground mt-0.5">{{ cat.description }}</p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="toggleCategory(cat.id)"
                            class="text-xs text-primary hover:text-primary/80 h-7 px-2 cursor-pointer"
                        >
                            {{ isCategoryAllActive(cat.id) ? 'Sembunyikan Bagian' : 'Pilih Semua Bagian' }}
                        </Button>
                    </div>

                    <!-- Widgets in this category -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div
                            v-for="widget in WIDGET_DEFINITIONS.filter(w => w.category === cat.id)"
                            :key="widget.id"
                            @click="localWidgets[widget.id] = !localWidgets[widget.id]"
                            :class="[
                                'p-3.5 rounded-xl border transition-all duration-200 cursor-pointer flex items-start gap-3 select-none',
                                localWidgets[widget.id]
                                    ? 'bg-card border-border/90 shadow-xs hover:border-primary/50'
                                    : 'bg-muted/30 border-dashed border-border/60 opacity-60 hover:opacity-90'
                            ]"
                        >
                            <!-- Widget Icon -->
                            <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5 transition-colors', widget.color]">
                                <component :is="widget.icon" class="w-4 h-4" />
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0 pr-2">
                                <div class="flex items-center gap-1.5">
                                    <h5 class="text-xs font-semibold text-secondary truncate">
                                        {{ widget.title }}
                                    </h5>
                                    <span v-if="!localWidgets[widget.id]" class="text-[10px] text-muted-foreground italic flex-shrink-0">
                                        (disembunyikan)
                                    </span>
                                </div>
                                <p class="text-[11px] text-muted-foreground line-clamp-2 mt-0.5 leading-snug">
                                    {{ widget.description }}
                                </p>
                            </div>

                            <!-- Switch Toggle -->
                            <div class="shrink-0 pt-0.5" @click.stop>
                                <Switch 
                                    :model-value="localWidgets[widget.id]" 
                                    @update:model-value="localWidgets[widget.id] = $event"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Clean Custom Container) -->
            <div class="px-6 py-3.5 border-t border-border/80 bg-muted/40 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                <!-- Left: Status counter & Reset -->
                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-between sm:justify-start">
                    <span class="text-xs text-muted-foreground">
                        <strong class="text-secondary font-semibold">{{ activeCount }}</strong> dari {{ WIDGET_DEFINITIONS.length }} kartu aktif
                    </span>
                    <Button 
                        type="button" 
                        variant="ghost" 
                        size="sm" 
                        @click="initLocalState" 
                        class="text-xs text-muted-foreground hover:text-secondary hover:bg-muted/80 h-7 px-2 cursor-pointer"
                        title="Kembalikan ke pengaturan terakhir yang tersimpan"
                    >
                        <RotateCcw class="w-3.5 h-3.5 mr-1 text-muted-foreground" />
                        Reset
                    </Button>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <Button 
                        type="button" 
                        variant="outline" 
                        size="sm" 
                        @click="$emit('update:open', false)"
                        :disabled="isSaving"
                        class="text-xs h-8 px-4 cursor-pointer border-border/80 hover:bg-card"
                    >
                        Batal
                    </Button>
                    <Button 
                        type="button" 
                        size="sm" 
                        @click="savePreferences"
                        :disabled="isSaving"
                        class="bg-primary hover:bg-primary/90 text-primary-foreground text-xs font-medium h-8 px-4 cursor-pointer shadow-xs"
                    >
                        <Loader2 v-if="isSaving" class="w-3.5 h-3.5 mr-1.5 animate-spin" />
                        <Check v-else class="w-3.5 h-3.5 mr-1.5" />
                        Simpan Tampilan
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
