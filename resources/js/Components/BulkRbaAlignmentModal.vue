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
import { Checkbox } from '@/Components/ui/checkbox';
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
    X,
    ChevronDown,
    ExternalLink,
    Eye,
    FileText
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

// Accordion & Transaction Drilldown State
const expandedGroups = ref({});
const loadingTransactions = ref({});
const groupTransactions = ref({});
const selectedDetailIds = ref({});
const itemSearchQueries = ref({});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
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

// Toggle accordion and lazy-load individual transaction rows
const toggleGroupTransactions = async (group) => {
    const key = getGroupKey(group);
    const currentlyExpanded = !!expandedGroups.value[key];

    if (currentlyExpanded) {
        expandedGroups.value[key] = false;
        return;
    }

    expandedGroups.value[key] = true;

    // Fetch if not yet loaded
    if (!groupTransactions.value[key]) {
        loadingTransactions.value[key] = true;
        try {
            const response = await axios.get('/expenditures/unmapped-rba-details/items', {
                params: {
                    account_code_id: group.account_code_id,
                    uraian: group.uraian
                }
            });
            if (response.data.status === 'success') {
                const items = response.data.items || [];
                groupTransactions.value[key] = items;
                selectedDetailIds.value[key] = items.map(i => i.id);
            }
        } catch (err) {
            console.error('Failed to load transaction items:', err);
            notification.value = {
                type: 'error',
                message: err.response?.data?.message || 'Gagal memuat rincian transaksi belanja.'
            };
        } finally {
            loadingTransactions.value[key] = false;
        }
    }
};

const getFilteredItems = (group) => {
    const key = getGroupKey(group);
    const items = groupTransactions.value[key] || [];
    const q = (itemSearchQueries.value[key] || '').toLowerCase().trim();
    if (!q) return items;
    return items.filter(item => 
        (item.expenditure?.document_number && item.expenditure.document_number.toLowerCase().includes(q)) ||
        (item.expenditure?.description && item.expenditure.description.toLowerCase().includes(q)) ||
        (item.expenditure?.vendor?.name && item.expenditure.vendor.name.toLowerCase().includes(q))
    );
};

const isItemSelected = (group, itemId) => {
    const key = getGroupKey(group);
    const list = selectedDetailIds.value[key];
    return list ? list.includes(itemId) : true;
};

const toggleItemSelection = (group, itemId, checked) => {
    const key = getGroupKey(group);
    if (!selectedDetailIds.value[key]) {
        selectedDetailIds.value[key] = (groupTransactions.value[key] || []).map(i => i.id);
    }
    const current = new Set(selectedDetailIds.value[key]);
    if (checked) {
        current.add(itemId);
    } else {
        current.delete(itemId);
    }
    selectedDetailIds.value[key] = Array.from(current);
};

const isAllSelected = (group) => {
    const key = getGroupKey(group);
    const items = groupTransactions.value[key] || [];
    if (!items.length) return false;
    const selected = selectedDetailIds.value[key] || [];
    return selected.length === items.length;
};

const toggleSelectAll = (group, checked) => {
    const key = getGroupKey(group);
    const items = groupTransactions.value[key] || [];
    if (checked) {
        selectedDetailIds.value[key] = items.map(i => i.id);
    } else {
        selectedDetailIds.value[key] = [];
    }
};

const getSelectedCount = (group) => {
    const key = getGroupKey(group);
    if (!expandedGroups.value[key] || !selectedDetailIds.value[key]) {
        return Number(group.count || 0);
    }
    return selectedDetailIds.value[key].length;
};

const getSelectedAmount = (group) => {
    const key = getGroupKey(group);
    const items = groupTransactions.value[key] || [];
    const selected = new Set(selectedDetailIds.value[key] || []);
    return items
        .filter(i => selected.has(i.id))
        .reduce((sum, i) => sum + Number(i.amount || 0), 0);
};

const isSelective = (group) => {
    const key = getGroupKey(group);
    if (!expandedGroups.value[key] || !selectedDetailIds.value[key]) return false;
    const items = groupTransactions.value[key] || [];
    return items.length > 0 && selectedDetailIds.value[key].length < items.length;
};

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        searchQuery.value = '';
        selectedTargets.value = {};
        expandedGroups.value = {};
        loadingTransactions.value = {};
        groupTransactions.value = {};
        selectedDetailIds.value = {};
        itemSearchQueries.value = {};
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

    // Check if user has customized transaction selection
    let detailIds = null;
    if (expandedGroups.value[key] && selectedDetailIds.value[key]) {
        detailIds = selectedDetailIds.value[key];
        if (detailIds.length === 0) return;
    }

    applyingKey.value = key;
    notification.value = null;

    try {
        const payload = {
            account_code_id: group.account_code_id,
            raw_uraian: group.uraian,
            rba_detail_id: target.id
        };
        if (detailIds && detailIds.length > 0) {
            payload.selected_detail_ids = detailIds;
        }

        const response = await axios.post('/expenditures/bulk-map-rba-detail', payload);

        if (response.data.status === 'success') {
            notification.value = {
                type: 'success',
                message: response.data.message
            };
            
            const remainingCount = response.data.remaining_count ?? 0;
            if (remainingCount <= 0) {
                // Remove group completely from unmapped list
                unmappedGroups.value = unmappedGroups.value.filter(g => getGroupKey(g) !== key);
                delete selectedTargets.value[key];
                delete expandedGroups.value[key];
                delete groupTransactions.value[key];
                delete selectedDetailIds.value[key];
                delete itemSearchQueries.value[key];
            } else {
                // Partial update
                group.count = remainingCount;
                if (response.data.remaining_amount !== undefined) {
                    group.total_amount = response.data.remaining_amount;
                }
                if (groupTransactions.value[key]) {
                    const appliedSet = new Set(detailIds || []);
                    groupTransactions.value[key] = groupTransactions.value[key].filter(i => !appliedSet.has(i.id));
                    selectedDetailIds.value[key] = groupTransactions.value[key].map(i => i.id);
                }
            }

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
                    class="bg-card border border-border/80 rounded-xl shadow-2xs hover:border-primary/40 transition-all overflow-hidden flex flex-col"
                    :class="{ 'ring-1 ring-primary/20 border-primary/50': expandedGroups[getGroupKey(group)] }"
                >
                    <!-- Main Card Row -->
                    <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
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

                            <div class="flex flex-wrap items-center gap-2.5 mt-2 text-[11px] text-muted-foreground">
                                <!-- Interactive Transactions Toggle Badge -->
                                <button 
                                    type="button" 
                                    @click="toggleGroupTransactions(group)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold border transition-all cursor-pointer select-none"
                                    :class="expandedGroups[getGroupKey(group)] 
                                        ? 'bg-primary/10 text-primary border-primary/30 shadow-xs' 
                                        : 'bg-muted/80 hover:bg-muted text-foreground border-border/70 hover:border-border'"
                                    :title="expandedGroups[getGroupKey(group)] ? 'Tutup rincian transaksi' : 'Klik untuk melihat daftar transaksi'"
                                >
                                    <Eye v-if="expandedGroups[getGroupKey(group)]" class="w-3.5 h-3.5 text-primary" />
                                    <FileText v-else class="w-3.5 h-3.5 text-muted-foreground" />
                                    <span>{{ group.count }} Transaksi</span>
                                    <Loader2 v-if="loadingTransactions[getGroupKey(group)]" class="w-3 h-3 animate-spin text-primary ml-0.5" />
                                    <ChevronDown 
                                        v-else 
                                        class="w-3.5 h-3.5 text-muted-foreground transition-transform duration-200" 
                                        :class="{ 'rotate-180': expandedGroups[getGroupKey(group)] }" 
                                    />
                                </button>

                                <span class="text-muted-foreground/50">•</span>

                                <span>
                                    Total Belanja: <strong class="text-foreground font-mono">{{ formatCurrency(group.total_amount) }}</strong>
                                </span>

                                <span v-if="isSelective(group)" class="text-primary font-medium bg-primary/10 px-2 py-0.5 rounded border border-primary/20">
                                    {{ getSelectedCount(group) }} terpilih ({{ formatCurrency(getSelectedAmount(group)) }})
                                </span>
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
                                    :disabled="!selectedTargets[getGroupKey(group)] || applyingKey === getGroupKey(group) || (isSelective(group) && getSelectedCount(group) === 0)"
                                    @click="applySingleMapping(group)"
                                    class="text-xs h-8 gap-1.5 bg-primary text-primary-foreground font-medium"
                                >
                                    <Loader2 v-if="applyingKey === getGroupKey(group)" class="w-3.5 h-3.5 animate-spin" />
                                    <Check v-else class="w-3.5 h-3.5" />
                                    <span v-if="isSelective(group)">
                                        Terapkan ({{ getSelectedCount(group) }})
                                    </span>
                                    <span v-else>
                                        Terapkan Massal ({{ group.count }})
                                    </span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion: Detailed Transactions List -->
                    <div 
                        v-if="expandedGroups[getGroupKey(group)]" 
                        class="border-t border-border/80 bg-muted/20 px-4 py-3 transition-all"
                    >
                        <!-- Loading State -->
                        <div v-if="loadingTransactions[getGroupKey(group)]" class="py-6 text-center text-xs text-muted-foreground flex items-center justify-center gap-2">
                            <Loader2 class="w-4 h-4 animate-spin text-primary" />
                            <span>Memuat rincian transaksi belanja...</span>
                        </div>

                        <!-- Content State -->
                        <div v-else class="space-y-2">
                            <!-- Accordion Header Bar -->
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold text-foreground">
                                        Daftar Transaksi Hasil Import
                                    </span>
                                    <span class="text-[11px] text-muted-foreground">
                                        ({{ (groupTransactions[getGroupKey(group)] || []).length }} baris pengeluaran)
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <div class="relative w-full sm:w-56">
                                        <Search class="w-3.5 h-3.5 absolute left-2.5 top-2 text-muted-foreground" />
                                        <Input 
                                            :model-value="itemSearchQueries[getGroupKey(group)] || ''"
                                            @update:model-value="itemSearchQueries[getGroupKey(group)] = $event"
                                            placeholder="Saring no. SPPD / rekanan..."
                                            class="pl-8 h-7 text-[11px] bg-background"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Table of Transactions -->
                            <div class="rounded-lg border border-border/80 overflow-hidden bg-background max-h-56 overflow-y-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-muted/60 text-muted-foreground font-semibold uppercase text-[10px] tracking-wider sticky top-0 z-10 border-b border-border/80 backdrop-blur-xs">
                                        <tr>
                                            <th class="py-2 px-3 w-10 text-center">
                                                <Checkbox 
                                                    :model-value="isAllSelected(group)"
                                                    @update:model-value="toggleSelectAll(group, $event)"
                                                    title="Pilih semua transaksi pada kelompok ini"
                                                />
                                            </th>
                                            <th class="py-2 px-3">No. Dokumen / SPPD</th>
                                            <th class="py-2 px-3">Tanggal</th>
                                            <th class="py-2 px-3">Keperluan / Rekanan</th>
                                            <th class="py-2 px-3 text-right">Nominal</th>
                                            <th class="py-2 px-3 w-16 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/60">
                                        <tr 
                                            v-if="getFilteredItems(group).length === 0"
                                            class="text-center text-muted-foreground"
                                        >
                                            <td colspan="6" class="py-6 text-xs">
                                                Tidak ada transaksi yang cocok dengan pencarian.
                                            </td>
                                        </tr>
                                        <tr 
                                            v-for="item in getFilteredItems(group)" 
                                            :key="item.id"
                                            class="hover:bg-muted/40 transition-colors"
                                            :class="{ 'opacity-50 bg-muted/15': !isItemSelected(group, item.id) }"
                                        >
                                            <td class="py-2 px-3 text-center">
                                                <Checkbox 
                                                    :model-value="isItemSelected(group, item.id)"
                                                    @update:model-value="toggleItemSelection(group, item.id, $event)"
                                                />
                                            </td>
                                            <td class="py-2 px-3 font-mono font-medium text-foreground whitespace-nowrap">
                                                {{ item.expenditure?.document_number || '-' }}
                                            </td>
                                            <td class="py-2 px-3 text-muted-foreground whitespace-nowrap">
                                                {{ formatDate(item.expenditure?.date) }}
                                            </td>
                                            <td class="py-2 px-3 max-w-[260px] truncate" :title="item.expenditure?.description || '-'">
                                                <div class="truncate font-medium text-foreground">
                                                    {{ item.expenditure?.description || '-' }}
                                                </div>
                                                <div v-if="item.expenditure?.vendor?.name" class="text-[10px] text-muted-foreground truncate">
                                                    Rekanan: {{ item.expenditure.vendor.name }}
                                                </div>
                                            </td>
                                            <td class="py-2 px-3 text-right font-mono font-semibold text-foreground whitespace-nowrap">
                                                {{ formatCurrency(item.amount) }}
                                            </td>
                                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                                <a 
                                                    :href="`/expenditures/${item.expenditure_id}`" 
                                                    target="_blank" 
                                                    class="inline-flex items-center gap-1 text-[11px] text-primary hover:underline hover:text-primary/80 font-medium"
                                                    title="Buka rincian dokumen pengeluaran di tab baru"
                                                >
                                                    <span>Buka</span>
                                                    <ExternalLink class="w-3 h-3" />
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Footer note on accordion -->
                            <div class="flex items-center justify-between text-[11px] text-muted-foreground pt-1">
                                <span>
                                    * Centang transaksi yang ingin diselaraskan. Transaksi yang tidak dicentang akan tetap berada di kelompok rincian belum terpetakan.
                                </span>
                                <span class="font-mono font-medium text-foreground shrink-0">
                                    {{ getSelectedCount(group) }} dari {{ (groupTransactions[getGroupKey(group)] || []).length }} dipilih
                                </span>
                            </div>
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
