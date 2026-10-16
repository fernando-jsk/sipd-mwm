<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    Calendar,
    Lock,
    Unlock,
    Sparkles,
    RotateCcw,
    CheckCircle2,
    AlertTriangle,
    Check,
} from 'lucide-vue-next';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    rbaDocument: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:open', 'saved']);

const monthNames = [
    { num: 1, name: 'Januari', quarter: 1 },
    { num: 2, name: 'Februari', quarter: 1 },
    { num: 3, name: 'Maret', quarter: 1 },
    { num: 4, name: 'April', quarter: 2 },
    { num: 5, name: 'Mei', quarter: 2 },
    { num: 6, name: 'Juni', quarter: 2 },
    { num: 7, name: 'Juli', quarter: 3 },
    { num: 8, name: 'Agustus', quarter: 3 },
    { num: 9, name: 'September', quarter: 3 },
    { num: 10, name: 'Oktober', quarter: 4 },
    { num: 11, name: 'November', quarter: 4 },
    { num: 12, name: 'Desember', quarter: 4 },
];

const quarters = [
    { num: 1, label: 'Triwulan I (Jan - Mar)' },
    { num: 2, label: 'Triwulan II (Apr - Jun)' },
    { num: 3, label: 'Triwulan III (Jul - Sep)' },
    { num: 4, label: 'Triwulan IV (Okt - Des)' },
];

const months = ref({});
const locked = ref({});
const isSubmitting = ref(false);
const isLoading = ref(false);
const docDetails = ref(null);

const initForm = async () => {
    docDetails.value = props.rbaDocument;
    const initialMonths = {};
    const initialLocked = {};
    for (let m = 1; m <= 12; m++) {
        initialMonths[m] = props.rbaDocument?.months?.[m] ?? 0;
        initialLocked[m] = false;
    }
    months.value = initialMonths;
    locked.value = initialLocked;

    // Jika data bulan belum dioper (misal dipanggil dari pohon RBA), ambil dari API
    const docId = props.rbaDocument?.id || props.rbaDocument?.rba_document_id;
    if (docId && (!props.rbaDocument?.months || Object.keys(props.rbaDocument.months).length === 0)) {
        isLoading.value = true;
        try {
            const res = await fetch(`/rba/${docId}/cash-allocations`);
            const data = await res.json();
            if (data.status === 'success' && data.rba_document) {
                docDetails.value = {
                    ...props.rbaDocument,
                    ...data.rba_document,
                };
                if (data.rba_document.months) {
                    months.value = { ...data.rba_document.months };
                }
            }
        } catch (e) {
            console.error('Gagal mengambil alokasi kas:', e);
        } finally {
            isLoading.value = false;
        }
    }
};

watch(() => props.open, (newVal) => {
    if (newVal && props.rbaDocument) {
        initForm();
    }
}, { immediate: true });

const targetBudget = computed(() => {
    return Number(docDetails.value?.total_budget ?? props.rbaDocument?.total_budget ?? props.rbaDocument?.tree_jumlah ?? 0);
});

const totalAllocated = computed(() => {
    return Object.values(months.value).reduce((sum, val) => sum + (Number(val) || 0), 0);
});

const difference = computed(() => {
    return Math.round((targetBudget.value - totalAllocated.value) * 100) / 100;
});

const isBalanced = computed(() => {
    return Math.abs(difference.value) <= 0.05 && targetBudget.value > 0;
});

const quarterTotals = computed(() => {
    const totals = { 1: 0, 2: 0, 3: 0, 4: 0 };
    monthNames.forEach(m => {
        totals[m.quarter] += Number(months.value[m.num] || 0);
    });
    return totals;
});

const formatCurrency = (value) => {
    if (value == null || isNaN(value)) return 'Rp 0';
    const num = Number(value);
    const hasDecimals = num % 1 !== 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: hasDecimals ? 2 : 0,
        maximumFractionDigits: 2,
    }).format(num);
};

const toggleLock = (monthNum) => {
    locked.value[monthNum] = !locked.value[monthNum];
};

// Smart Helper: Bagi Rata 12 Bulan (memperhitungkan bulan yang dikunci)
const distributeEqual = () => {
    let lockedSum = 0;
    const unlockedMonths = [];

    for (let m = 1; m <= 12; m++) {
        if (locked.value[m]) {
            lockedSum += Number(months.value[m] || 0);
        } else {
            unlockedMonths.push(m);
        }
    }

    const remainingBudget = Math.max(0, targetBudget.value - lockedSum);

    if (unlockedMonths.length > 0) {
        const perMonth = Math.floor((remainingBudget / unlockedMonths.length) * 100) / 100;
        let accumulated = 0;

        unlockedMonths.forEach((m, idx) => {
            if (idx === unlockedMonths.length - 1) {
                // Bulan terakhir mengambil sisa presisi sen
                months.value[m] = Math.round((remainingBudget - accumulated) * 100) / 100;
            } else {
                months.value[m] = perMonth;
                accumulated += perMonth;
            }
        });
    }
};

// Smart Helper: Pola Triwulan (25% per triwulan)
const distributeQuarterly = () => {
    const quarterTarget = Math.round((targetBudget.value / 4) * 100) / 100;
    let accumulatedQuarters = 0;

    for (let q = 1; q <= 4; q++) {
        const currentQTarget = (q === 4) 
            ? Math.round((targetBudget.value - accumulatedQuarters) * 100) / 100
            : quarterTarget;
        accumulatedQuarters += currentQTarget;

        const qMonths = monthNames.filter(m => m.quarter === q);
        const perMonth = Math.floor((currentQTarget / 3) * 100) / 100;
        let qAccum = 0;

        qMonths.forEach((m, idx) => {
            if (!locked.value[m.num]) {
                if (idx === 2) {
                    months.value[m.num] = Math.round((currentQTarget - qAccum) * 100) / 100;
                } else {
                    months.value[m.num] = perMonth;
                    qAccum += perMonth;
                }
            }
        });
    }
};

// Smart Helper: Pola Progresif (TW I: 20%, TW II: 30%, TW III: 30%, TW IV: 20%)
const distributeProgressive = () => {
    const percentages = { 1: 0.20, 2: 0.30, 3: 0.30, 4: 0.20 };
    let accumulatedQuarters = 0;

    for (let q = 1; q <= 4; q++) {
        const currentQTarget = (q === 4)
            ? Math.round((targetBudget.value - accumulatedQuarters) * 100) / 100
            : Math.round((targetBudget.value * percentages[q]) * 100) / 100;
        accumulatedQuarters += currentQTarget;

        const qMonths = monthNames.filter(m => m.quarter === q);
        const perMonth = Math.floor((currentQTarget / 3) * 100) / 100;
        let qAccum = 0;

        qMonths.forEach((m, idx) => {
            if (!locked.value[m.num]) {
                if (idx === 2) {
                    months.value[m.num] = Math.round((currentQTarget - qAccum) * 100) / 100;
                } else {
                    months.value[m.num] = perMonth;
                    qAccum += perMonth;
                }
            }
        });
    }
};

// Reset alokasi bulan yang tidak dikunci
const resetUnlocked = () => {
    for (let m = 1; m <= 12; m++) {
        if (!locked.value[m]) {
            months.value[m] = 0;
        }
    }
};

const handleSave = () => {
    const docId = props.rbaDocument?.id || props.rbaDocument?.rba_document_id;
    if (!docId) return;

    if (!isBalanced.value) {
        return;
    }

    isSubmitting.value = true;
    router.post(
        `/rba/${docId}/cash-allocations`,
        { months: months.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                emit('update:open', false);
                emit('saved');
            },
            onError: () => {
                isSubmitting.value = false;
            },
        }
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="w-[96vw] sm:max-w-5xl lg:max-w-6xl max-h-[92vh] flex flex-col p-0 gap-0 overflow-hidden shadow-2xl rounded-2xl">
            <!-- Modal Header -->
            <DialogHeader class="p-5 md:p-6 pb-4 border-b border-border/80 bg-muted/20 shrink-0">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <DialogTitle class="text-lg font-bold tracking-tight text-secondary dark:text-foreground flex items-center gap-2">
                            <Calendar class="size-5 text-primary" />
                            Penyusunan Anggaran Kas Belanja (RAK)
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-1 pt-1">
                            <span class="font-semibold text-foreground font-mono">{{ docDetails?.account_code || rbaDocument?.account_code || rbaDocument?.code }}</span>
                            <span>&bull;</span>
                            <span class="text-foreground font-medium">{{ docDetails?.account_name || rbaDocument?.account_name || rbaDocument?.name }}</span>
                            <span v-if="docDetails?.pptk_name || rbaDocument?.pptk_name" class="text-muted-foreground">&bull; PPTK: {{ docDetails?.pptk_name || rbaDocument?.pptk_name }}</span>
                        </DialogDescription>
                    </div>
                </div>

                <!-- Summary & Status Card -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4">
                    <div class="bg-card rounded-xl p-3 border border-border/80 shadow-2xs">
                        <div class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Pagu Tahunan RBA</div>
                        <div class="text-base font-bold text-foreground mt-0.5 truncate" :title="formatCurrency(targetBudget)">
                            {{ formatCurrency(targetBudget) }}
                        </div>
                    </div>

                    <div class="bg-card rounded-xl p-3 border border-border/80 shadow-2xs">
                        <div class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Total Kas Teralokasi</div>
                        <div class="text-base font-bold text-primary mt-0.5 truncate" :title="formatCurrency(totalAllocated)">
                            {{ formatCurrency(totalAllocated) }}
                        </div>
                    </div>

                    <div class="bg-card rounded-xl p-3 border border-border/80 shadow-2xs flex flex-col justify-between">
                        <div class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Status Keseimbangan</div>
                        <div class="mt-1 flex items-center">
                            <Badge v-if="isBalanced" variant="outline" class="bg-emerald-500/10 text-emerald-600 border-emerald-500/20 font-semibold gap-1 py-1 text-xs">
                                <CheckCircle2 class="size-3.5" />
                                Seimbang (100% Sesuai Pagu)
                            </Badge>
                            <Badge v-else-if="difference > 0" variant="outline" class="bg-amber-500/10 text-amber-600 border-amber-500/20 font-semibold gap-1 py-1 text-xs">
                                <AlertTriangle class="size-3.5" />
                                Kurang {{ formatCurrency(difference) }}
                            </Badge>
                            <Badge v-else variant="destructive" class="gap-1 py-1 text-xs font-semibold">
                                <AlertTriangle class="size-3.5" />
                                Lebih {{ formatCurrency(Math.abs(difference)) }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Toolbar -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-muted-foreground mr-1">Bagi Cepat:</span>
                        <Button type="button" variant="outline" size="sm" @click="distributeEqual" class="h-8 text-xs font-medium cursor-pointer hover:bg-primary/10 hover:text-primary transition-colors">
                            <Sparkles class="size-3.5 mr-1 text-primary" />
                            Bagi Rata (12 Bulan)
                        </Button>
                        <Button type="button" variant="outline" size="sm" @click="distributeQuarterly" class="h-8 text-xs font-medium cursor-pointer hover:bg-primary/10 hover:text-primary transition-colors">
                            Pola Triwulan (25% Rata)
                        </Button>
                        <Button type="button" variant="outline" size="sm" @click="distributeProgressive" class="h-8 text-xs font-medium cursor-pointer hover:bg-primary/10 hover:text-primary transition-colors">
                            Pola Progresif (20-30-30-20%)
                        </Button>
                    </div>
                    <Button type="button" variant="ghost" size="sm" @click="resetUnlocked" class="h-8 text-xs text-muted-foreground hover:text-destructive cursor-pointer">
                        <RotateCcw class="size-3.5 mr-1" />
                        Reset
                    </Button>
                </div>
            </DialogHeader>

            <!-- Modal Body (12 Months in 4 Quarter Columns) -->
            <div class="flex-1 overflow-y-auto p-5 md:p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="q in quarters" 
                        :key="q.num"
                        class="rounded-xl border border-border/80 bg-card p-3.5 space-y-3 shadow-2xs flex flex-col justify-between"
                    >
                        <!-- Quarter Header -->
                        <div class="pb-2 border-b border-border/60">
                            <div class="text-[11px] font-bold text-secondary dark:text-foreground uppercase tracking-wider">
                                {{ q.label }}
                            </div>
                            <div class="text-xs font-semibold text-primary mt-0.5">
                                Subtotal: {{ formatCurrency(quarterTotals[q.num]) }}
                            </div>
                        </div>

                        <!-- Months in Quarter -->
                        <div class="space-y-3 flex-1">
                            <div 
                                v-for="m in monthNames.filter(item => item.quarter === q.num)" 
                                :key="m.num"
                                class="space-y-1"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <Label :for="`month-${m.num}`" class="font-medium text-muted-foreground">
                                        {{ m.name }}
                                    </Label>
                                    <span v-if="locked[m.num]" class="text-[10px] text-amber-600 font-semibold flex items-center gap-0.5">
                                        <Lock class="size-2.5" /> Terkunci
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-1.5">
                                    <div class="relative flex-1">
                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-medium text-muted-foreground pointer-events-none select-none">
                                            Rp
                                        </span>
                                        <Input
                                            :id="`month-${m.num}`"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            v-model.number="months[m.num]"
                                            class="pl-8 pr-2.5 h-8 text-xs text-right font-mono focus-visible:ring-primary [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                            :class="{ 'bg-amber-500/10 border-amber-500/30 font-semibold text-amber-900 dark:text-amber-200': locked[m.num] }"
                                            placeholder="0"
                                        />
                                    </div>

                                    <!-- Lock Toggle Button -->
                                    <button
                                        type="button"
                                        @click="toggleLock(m.num)"
                                        class="size-8 rounded-lg flex items-center justify-center border transition-colors cursor-pointer shrink-0"
                                        :class="locked[m.num] 
                                            ? 'bg-amber-500/10 border-amber-500/30 text-amber-600 hover:bg-amber-500/20' 
                                            : 'bg-muted/30 border-border/60 text-muted-foreground/60 hover:text-foreground hover:bg-muted'"
                                        :title="locked[m.num] ? 'Terkunci dari pembagian otomatis (Klik untuk buka)' : 'Kunci bulan ini saat bagi otomatis'"
                                    >
                                        <Lock v-if="locked[m.num]" class="size-3.5" />
                                        <Unlock v-else class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-border/80 bg-muted/40 dark:bg-muted/20 flex flex-col sm:flex-row items-center justify-between gap-4 shrink-0 rounded-b-2xl">
                <!-- Status notice pill -->
                <div class="w-full sm:w-auto flex items-center">
                    <div
                        v-if="!isBalanced"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/25 text-amber-800 dark:text-amber-300 text-xs font-medium"
                    >
                        <AlertTriangle class="size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span>Alokasi belum seimbang.</span>
                            <span v-if="difference > 0">
                                Kurang: <strong class="font-bold font-mono text-amber-900 dark:text-amber-200">{{ formatCurrency(difference) }}</strong>
                            </span>
                            <span v-else>
                                Lebih: <strong class="font-bold font-mono text-destructive dark:text-red-400">{{ formatCurrency(Math.abs(difference)) }}</strong>
                            </span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/25 text-emerald-800 dark:text-emerald-300 text-xs font-medium"
                    >
                        <CheckCircle2 class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>
                            <strong class="font-semibold text-emerald-900 dark:text-emerald-200">Alokasi Seimbang 100%</strong> &bull; Total 12 bulan sesuai pagu tahunan.
                        </span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end shrink-0">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-9 px-4 text-xs font-medium cursor-pointer border-border/80 hover:bg-muted text-foreground transition-colors"
                        @click="$emit('update:open', false)"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :disabled="!isBalanced || isSubmitting"
                        @click="handleSave"
                        class="h-9 px-5 text-xs font-semibold bg-primary hover:bg-primary/90 text-primary-foreground shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <span v-if="isSubmitting" class="flex items-center gap-1.5">
                            <span class="size-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            Menyimpan...
                        </span>
                        <span v-else class="flex items-center gap-1.5">
                            <Check class="size-3.5" />
                            Simpan Anggaran Kas
                        </span>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
