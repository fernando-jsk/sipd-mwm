<script setup>
import { ref, computed, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription
} from '@/Components/ui/dialog';
import axios from 'axios';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { 
    Layers, 
    Search, 
    Check, 
    Loader2, 
    AlertCircle, 
    CheckCircle2, 
    ArrowRight,
    CornerDownRight,
    RefreshCw,
    Sparkles,
    SlidersHorizontal,
    X
} from 'lucide-vue-next';
import RbaDetailPickerDialog from '@/Components/RbaDetailPickerDialog.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    accountCodes: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:open', 'resolved']);

// State
const loading = ref(false);
const applyingKey = ref(null);
const unmappedGroups = ref([]);
const internalAccountCodes = ref([]);
const searchQuery = ref('');
const notification = ref(null);

const effectiveAccountCodes = computed(() => {
    return props.accountCodes && props.accountCodes.length 
        ? props.accountCodes 
        : internalAccountCodes.value;
});

// RBA Detail Picker State
const isPickerOpen = ref(false);
const activeGroup = ref(null);

// Selected target mappings: key = `${group.account_code_id}_${group.uraian}`
const selectedTargets = ref({});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const getGroupKey = (group) => {
    return `${group.account_code_id}_${group.uraian}`;
};

// Fetch unmapped data from backend
const fetchUnmappedData = async () => {
    loading.value = true;
    notification.value = null;
    try {
        const response = await axios.get('/expenditures/unmapped-rba-details');
        if (response.data.status === 'success') {
            unmappedGroups.value = response.data.items || [];
            if (response.data.account_codes && response.data.account_codes.length) {
                internalAccountCodes.value = response.data.account_codes;
            }
        }
    } catch (err) {
        console.error('Failed to fetch unmapped details:', err);
        notification.value = {
            type: 'error',
            message: err.response?.data?.message || 'Gagal memuat daftar rincian belum terpetakan.'
        };
    } finally {
        loading.value = false;
    }
};

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        searchQuery.value = '';
        selectedTargets.value = {};
        fetchUnmappedData();
    }
});

// Filtered groups by search
const filteredGroups = computed(() => {
    if (!searchQuery.value.trim()) return unmappedGroups.value;
    const q = searchQuery.value.toLowerCase().trim();
    return unmappedGroups.value.filter(g => 
        g.uraian.toLowerCase().includes(q) ||
        (g.account_code?.code && g.account_code.code.toLowerCase().includes(q)) ||
        (g.account_code?.name && g.account_code.name.toLowerCase().includes(q))
    );
});

const totalPendingRows = computed(() => {
    return unmappedGroups.value.reduce((sum, g) => sum + Number(g.count || 0), 0);
});

// Open Rba Detail Picker for specific group
const openPickerForGroup = (group) => {
    activeGroup.value = group;
    isPickerOpen.value = true;
};

// Handle target selection from RbaDetailPickerDialog
const handleTargetSelected = (selection) => {
    if (!activeGroup.value) return;
    const key = getGroupKey(activeGroup.value);
    
    if (selection.rba_detail_id) {
        selectedTargets.value[key] = {
            id: selection.rba_detail_id,
            uraian: selection.rba_detail_uraian,
            breadcrumb: selection.rba_detail_breadcrumb
        };
    }
    activeGroup.value = null;
};

// Apply bulk mapping for a single group
const applySingleMapping = async (group) => {
    const key = getGroupKey(group);
    const target = selectedTargets.value[key];
    if (!target) return;

    applyingKey.value = key;
    notification.value = null;

    try {
        const response = await axios.post('/expenditures/bulk-map-rba-detail', {
            account_code_id: group.account_code_id,
            raw_uraian: group.uraian,
            rba_detail_id: target.id
        });

        if (response.data.status === 'success') {
            notification.value = {
                type: 'success',
                message: response.data.message
            };
            
            // Remove group from unmapped list
            unmappedGroups.value = unmappedGroups.value.filter(g => getGroupKey(g) !== key);
            delete selectedTargets.value[key];

            emit('resolved', {
                updatedCount: response.data.updated_count,
                remainingCount: totalPendingRows.value
            });
        } else {
            notification.value = {
                type: 'error',
                message: response.data.message || 'Gagal menyimpan penyelarasan.'
            };
        }
    } catch (err) {
        console.error('Error applying mapping:', err);
        notification.value = {
            type: 'error',
            message: err.response?.data?.message || 'Terjadi kesalahan sistem saat menghubungi server.'
        };
    } finally {
        applyingKey.value = null;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="w-[96vw] sm:max-w-4xl lg:max-w-5xl h-[88vh] max-h-[820px] flex flex-col p-0 gap-0 overflow-hidden shadow-2xl">
            <!-- Modal Header -->
            <DialogHeader class="px-6 py-4 border-b bg-muted/20 flex-shrink-0">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <SlidersHorizontal class="w-5 h-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-lg font-bold text-foreground">
                                Penyelarasan Rincian Belanja RBA Massal
                            </DialogTitle>
                            <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                                Petakan teks rincian belanja hasil import Excel ke rincian resmi RBA aktif untuk puluhan transaksi sekaligus.
                            </DialogDescription>
                        </div>
                    </div>
                    <Badge variant="outline" class="font-mono text-xs px-2.5 py-1 bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30 shrink-0">
                        {{ totalPendingRows }} Baris Menunggu
                    </Badge>
                </div>
            </DialogHeader>

            <!-- Notification Banner -->
            <div v-if="notification" class="px-6 py-2.5 border-b text-xs flex items-center justify-between gap-2 transition-all" :class="notification.type === 'success' ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/20' : 'bg-destructive/10 text-destructive border-destructive/20'">
                <div class="flex items-center gap-2 flex-1">
                    <CheckCircle2 v-if="notification.type === 'success'" class="w-4 h-4 shrink-0 text-emerald-600" />
                    <AlertCircle v-else class="w-4 h-4 shrink-0 text-destructive" />
                    <span class="font-medium">{{ notification.message }}</span>
                </div>
                <button type="button" @click="notification = null" class="p-1 rounded hover:bg-black/5 dark:hover:bg-white/10 transition-colors cursor-pointer" title="Tutup pemberitahuan">
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>

            <!-- Toolbar & Search -->
            <div class="px-6 py-3 border-b bg-background flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                <div class="relative w-full sm:w-80">
                    <Search class="w-4 h-4 absolute left-3 top-2.5 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery"
                        placeholder="Cari teks uraian atau rekening..."
                        class="pl-9 h-9 text-xs"
                    />
                </div>
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Button variant="ghost" size="sm" @click="fetchUnmappedData" :disabled="loading" class="h-8 text-xs gap-1.5">
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" /> Refresh Data
                    </Button>
                </div>
            </div>

            <!-- Content Body: List of Unmapped Groups -->
            <div class="flex-1 overflow-y-auto p-6 space-y-3 bg-muted/5">
                <div v-if="loading" class="py-16 text-center text-muted-foreground">
                    <Loader2 class="w-8 h-8 mx-auto animate-spin text-primary opacity-60 mb-2" />
                    <p class="text-xs">Memuat daftar rincian yang belum terpetakan...</p>
                </div>

                <div v-else-if="filteredGroups.length === 0" class="py-16 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                    <h4 class="font-bold text-sm text-foreground">Semua Rincian Belanja Sudah Selaras!</h4>
                    <p class="text-xs text-muted-foreground max-w-md mx-auto mt-1">
                        Tidak ada transaksi belanja yang belum terpetakan ke master RBA. Semua baris pengeluaran telah memiliki uraian dan ID resmi.
                    </p>
                </div>

                <!-- Card per Group -->
                <div 
                    v-else
                    v-for="group in filteredGroups" 
                    :key="getGroupKey(group)"
                    class="bg-card border border-border/80 rounded-xl p-4 shadow-2xs hover:border-primary/40 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4"
                >
                    <!-- Left: Source Info (Raw Text from Excel) -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="font-mono text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20">
                                {{ group.account_code?.code }}
                            </span>
                            <span class="text-xs text-muted-foreground truncate" :title="group.account_code?.name">
                                {{ group.account_code?.name }}
                            </span>
                        </div>

                        <div class="flex items-baseline gap-2">
                            <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider shrink-0">
                                Teks Excel:
                            </span>
                            <span class="text-xs font-bold text-foreground font-sans break-words">
                                "{{ group.uraian }}"
                            </span>
                        </div>

                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-muted-foreground">
                            <Badge variant="secondary" class="text-[10px] py-0 px-1.5 font-bold">
                                {{ group.count }} Transaksi
                            </Badge>
                            <span>Total Belanja: <strong class="text-foreground font-mono">{{ formatCurrency(group.total_amount) }}</strong></span>
                        </div>
                    </div>

                    <!-- Right: Target RBA Selection & Action -->
                    <div class="md:w-96 flex flex-col justify-center gap-2 shrink-0 border-t md:border-t-0 md:border-l md:pl-4 pt-3 md:pt-0">
                        <div v-if="selectedTargets[getGroupKey(group)]" class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400 flex items-center gap-1">
                                <Sparkles class="w-3 h-3" /> Target RBA Terpilih:
                            </div>
                            <div class="font-bold text-emerald-950 dark:text-emerald-200 mt-0.5 leading-snug">
                                {{ selectedTargets[getGroupKey(group)].uraian }}
                            </div>
                            <div v-if="selectedTargets[getGroupKey(group)].breadcrumb" class="text-[10px] text-emerald-700/80 truncate mt-0.5">
                                {{ selectedTargets[getGroupKey(group)].breadcrumb }}
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                @click="openPickerForGroup(group)"
                                class="flex-1 text-xs h-8 border-dashed"
                            >
                                <span v-if="selectedTargets[getGroupKey(group)]">Ubah Target RBA</span>
                                <span v-else class="flex items-center gap-1.5 text-primary">
                                    <Layers class="w-3.5 h-3.5" /> Pilih Rincian RBA
                                </span>
                            </Button>

                            <Button 
                                type="button" 
                                size="sm" 
                                :disabled="!selectedTargets[getGroupKey(group)] || applyingKey === getGroupKey(group)"
                                @click="applySingleMapping(group)"
                                class="text-xs h-8 gap-1.5 bg-primary text-primary-foreground font-medium"
                            >
                                <Loader2 v-if="applyingKey === getGroupKey(group)" class="w-3.5 h-3.5 animate-spin" />
                                <Check v-else class="w-3.5 h-3.5" />
                                Terapkan Massal ({{ group.count }})
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Dialog (Polished, Padded & Non-Clipping) -->
            <div class="px-6 py-3.5 border-t border-border/80 bg-muted/40 flex-shrink-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary/10 text-primary font-bold text-[11px]">
                        i
                    </span>
                    <span>Menampilkan <strong>{{ filteredGroups.length }}</strong> kelompok rincian</span>
                    <span v-if="filteredGroups.length !== unmappedGroups.length" class="text-muted-foreground/70">
                        (dari {{ unmappedGroups.length }})
                    </span>
                    <span class="text-muted-foreground/40">•</span>
                    <span class="font-mono text-muted-foreground font-medium">{{ totalPendingRows }} total transaksi</span>
                </div>
                <Button 
                    type="button"
                    variant="outline" 
                    size="sm" 
                    class="h-8 px-4 text-xs font-semibold shadow-xs hover:bg-muted cursor-pointer"
                    @click="$emit('update:open', false)"
                >
                    Tutup
                </Button>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Dialog Pemilih RBA -->
    <RbaDetailPickerDialog
        v-if="activeGroup"
        :open="isPickerOpen"
        @update:open="isPickerOpen = $event"
        :account-codes="effectiveAccountCodes"
        :initial-account-code-id="activeGroup.account_code_id"
        @select="handleTargetSelected"
    />
</template>
