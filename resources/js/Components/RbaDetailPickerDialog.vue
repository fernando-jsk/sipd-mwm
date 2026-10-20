<script setup>
import { ref, computed, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { 
    Search, 
    Folder, 
    Check, 
    Layers, 
    ListFilter, 
    FileText,
    Loader2,
    AlertCircle,
    CheckCircle2,
    ArrowRight,
    HelpCircle,
    Info,
    CornerDownRight,
    X
} from 'lucide-vue-next';
import RbaTreeNode from '@/Components/RbaTreeNode.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    accountCodes: {
        type: Array,
        default: () => []
    },
    initialAccountCodeId: {
        type: [Number, String, null],
        default: null
    },
    initialDetailId: {
        type: [Number, String, null],
        default: null
    }
});

const emit = defineEmits(['update:open', 'select']);

// State
const selectedAccountId = ref(null);
const selectedDetailId = ref(null);
const accountSearchQuery = ref('');
const searchInput = ref('');
const rbaSearchQuery = ref('');
const activeView = ref('tree'); // 'tree' as default or 'list'
const headerFilterType = ref('all'); // 'all', 'parent', 'leaf'
const loading = ref(false);
const errorMessage = ref('');
const treeData = ref([]);
const allHeaders = ref([]);
const leafHeaders = ref([]);
const rbaDoc = ref(null);
const expandedNodes = ref(new Set());
const cache = ref({});

// Formatter
const formatCurrency = (value) => {
    if (value == null || isNaN(value)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value);
};

// Base budgetary account codes from RBA documents only (excluding Kas di Bendahara Pengeluaran)
const rbaAccountCodes = computed(() => {
    if (!props.accountCodes || !props.accountCodes.length) return [];
    return props.accountCodes.filter(acc => {
        if (acc.is_non_budgetary) return false;
        if (acc.code && acc.code.startsWith('1.')) return false;
        if (acc.name && acc.name.toLowerCase().includes('kas di bendahara')) return false;
        return true;
    });
});

// Filtered Account Codes (Left Panel)
const filteredAccountCodes = computed(() => {
    const list = rbaAccountCodes.value;
    if (!list.length) return [];
    if (!accountSearchQuery.value.trim()) return list;

    const q = accountSearchQuery.value.toLowerCase().trim();
    return list.filter(acc => 
        (acc.code && acc.code.toLowerCase().includes(q)) ||
        (acc.name && acc.name.toLowerCase().includes(q))
    );
});

// Currently Active Account Object
const activeAccount = computed(() => {
    if (!selectedAccountId.value) return null;
    const found = props.accountCodes.find(a => a.id.toString() === selectedAccountId.value.toString());
    if (found) return found;
    if (rbaDoc.value?.account_code) {
        return rbaDoc.value.account_code;
    }
    return null;
});

// Fetch RBA Tree Data
const fetchRbaTree = async (accountId) => {
    if (!accountId) {
        treeData.value = [];
        allHeaders.value = [];
        leafHeaders.value = [];
        rbaDoc.value = null;
        errorMessage.value = '';
        return;
    }

    if (cache.value[accountId]) {
        const cached = cache.value[accountId];
        treeData.value = cached.tree || [];
        allHeaders.value = cached.all_headers || cached.leaf_headers || [];
        leafHeaders.value = cached.leaf_headers || [];
        rbaDoc.value = cached.rba_document || null;
        errorMessage.value = cached.message || '';
        initializeExpanded();
        return;
    }

    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch(`/expenditures/rba-tree/${accountId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Gagal memuat data (HTTP ${response.status})`);
        }

        const data = await response.json();
        
        if (data.status === 'not_found' || data.status === 'empty') {
            errorMessage.value = data.message || 'Tidak ada data rincian.';
            treeData.value = [];
            allHeaders.value = [];
            leafHeaders.value = [];
            rbaDoc.value = data.rba_document || null;
        } else {
            treeData.value = data.tree || [];
            allHeaders.value = data.all_headers || data.leaf_headers || [];
            leafHeaders.value = data.leaf_headers || [];
            rbaDoc.value = data.rba_document || null;
        }

        cache.value[accountId] = {
            tree: treeData.value,
            all_headers: allHeaders.value,
            leaf_headers: leafHeaders.value,
            rba_document: rbaDoc.value,
            message: errorMessage.value
        };

        initializeExpanded();
    } catch (err) {
        console.error('Error fetching RBA tree:', err);
        errorMessage.value = 'Terjadi kesalahan saat memuat rincian RBA.';
    } finally {
        loading.value = false;
    }
};

const initializeExpanded = () => {
    if (rbaSearchQuery.value.trim()) {
        expandedNodes.value = new Set(filteredTreeResult.value.expandIds);
    } else {
        // Default: Semua collapsable ditutup secara default
        expandedNodes.value = new Set();
    }
};

const expandAll = () => {
    const all = new Set();
    const traverse = (nodes) => {
        nodes.forEach(node => {
            if (node.type === 'header') {
                all.add(node.id);
                if (node.children?.length) {
                    traverse(node.children);
                }
            }
        });
    };
    traverse(filteredTreeData.value);
    expandedNodes.value = all;
};

const collapseAll = () => {
    expandedNodes.value = new Set();
};

const toggleExpand = (nodeId) => {
    if (expandedNodes.value.has(nodeId)) {
        expandedNodes.value.delete(nodeId);
    } else {
        expandedNodes.value.add(nodeId);
    }
};

// Select an account from the left panel
const handleSelectAccount = (accId) => {
    selectedAccountId.value = accId.toString();
    if (props.initialAccountCodeId && props.initialAccountCodeId.toString() === accId.toString()) {
        selectedDetailId.value = props.initialDetailId || null;
    } else {
        selectedDetailId.value = null;
    }
    fetchRbaTree(accId);
};

// Watchers
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        accountSearchQuery.value = '';
        clearSearch();
        headerFilterType.value = 'all';
        activeView.value = 'tree';
        
        if (props.initialAccountCodeId) {
            selectedAccountId.value = props.initialAccountCodeId.toString();
            selectedDetailId.value = props.initialDetailId || null;
            fetchRbaTree(props.initialAccountCodeId);
        } else {
            selectedAccountId.value = null;
            selectedDetailId.value = null;
            treeData.value = [];
            allHeaders.value = [];
            leafHeaders.value = [];
            errorMessage.value = '';
        }
    }
});

// Debounced Search Handling untuk UI super responsif tanpa lag
let searchDebounceTimer = null;
watch(searchInput, (newVal) => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    // Jika input dihapus sampai kosong, langsung reset seketika tanpa delay
    if (!newVal.trim()) {
        rbaSearchQuery.value = '';
        return;
    }
    // Debounce ringan 150ms agar main thread tidak freeze saat mengetik cepat
    searchDebounceTimer = setTimeout(() => {
        rbaSearchQuery.value = newVal;
    }, 150);
});

const clearSearch = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    searchInput.value = '';
    rbaSearchQuery.value = '';
};

// Counts for List View Tabs
const parentHeadersCount = computed(() => allHeaders.value.filter(h => !h.is_leaf_header).length);
const leafHeadersCount = computed(() => allHeaders.value.filter(h => h.is_leaf_header).length);

// Filtered Headers for List View (Right Panel)
const filteredHeaders = computed(() => {
    let list = allHeaders.value;
    if (headerFilterType.value === 'parent') {
        list = list.filter(item => !item.is_leaf_header);
    } else if (headerFilterType.value === 'leaf') {
        list = list.filter(item => item.is_leaf_header);
    }

    if (!rbaSearchQuery.value.trim()) {
        return list;
    }
    const q = rbaSearchQuery.value.toLowerCase().trim();
    return list.filter(item => 
        (item.uraian && item.uraian.toLowerCase().includes(q)) ||
        (item.breadcrumb && item.breadcrumb.toLowerCase().includes(q))
    );
});

// Single-Pass Tree Filter & Auto-Expand Calculation (High Performance)
const filteredTreeResult = computed(() => {
    if (!rbaSearchQuery.value.trim()) {
        return {
            tree: treeData.value,
            matchCount: 0,
            expandIds: new Set()
        };
    }
    const q = rbaSearchQuery.value.toLowerCase().trim();
    let matchCount = 0;
    const expandIds = new Set();

    const filterNode = (node) => {
        const selfMatch = Boolean(node.uraian && node.uraian.toLowerCase().includes(q));
        if (selfMatch) {
            matchCount++;
        }

        let matchingChildren = [];
        if (node.children && node.children.length > 0) {
            matchingChildren = node.children
                .map(child => filterNode(child))
                .filter(Boolean);
        }

        if (matchingChildren.length > 0) {
            expandIds.add(node.id);
            return {
                ...node,
                children: matchingChildren,
                isMatch: selfMatch
            };
        } else if (selfMatch) {
            // Jika node induk cocok, sertakan seluruh anak-anaknya agar pengguna dapat memilih rincian
            if (node.children && node.children.length > 0) {
                expandIds.add(node.id);
            }
            return {
                ...node,
                children: node.children || [],
                isMatch: true
            };
        }

        return null;
    };

    const tree = treeData.value
        .map(rootNode => filterNode(rootNode))
        .filter(Boolean);

    return { tree, matchCount, expandIds };
});

const filteredTreeData = computed(() => filteredTreeResult.value.tree);
const searchMatchCount = computed(() => filteredTreeResult.value.matchCount);

// Auto-expand tree when search query changes (langsung mengambil expandIds dari single-pass)
watch(rbaSearchQuery, (newQuery) => {
    const q = newQuery.trim();
    if (q) {
        expandedNodes.value = new Set(filteredTreeResult.value.expandIds);
    } else {
        // Ketika pencarian dihapus, kembalikan status pohon ke terlipat/tertutup rapi
        expandedNodes.value = new Set();
    }
});

// Select Header (either parent or leaf)
const selectDetail = (item) => {
    selectedDetailId.value = item.id;
    emit('select', {
        account_code_id: rbaDoc.value?.account_code_id || selectedAccountId.value,
        rba_detail_id: item.id,
        rba_detail_uraian: item.uraian,
        rba_detail_breadcrumb: item.breadcrumb,
    });
    emit('update:open', false);
};

// Select Account Only (without rincian)
const selectAccountOnly = () => {
    if (!selectedAccountId.value) return;
    emit('select', {
        account_code_id: rbaDoc.value?.account_code_id || selectedAccountId.value,
        rba_detail_id: null,
        rba_detail_uraian: '',
        rba_detail_breadcrumb: '',
    });
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="w-[96vw] sm:max-w-5xl lg:max-w-6xl h-[88vh] max-h-[850px] flex flex-col p-0 gap-0 overflow-hidden shadow-2xl">
            <!-- Header Dialog -->
            <DialogHeader class="px-5 py-4 border-b bg-muted/20 flex-shrink-0">
                <div class="flex items-center justify-between pr-6">
                    <div>
                        <DialogTitle class="text-base sm:text-lg font-bold flex items-center gap-2 text-foreground">
                            <Layers class="w-5 h-5 text-primary" />
                            Pilih Rekening & Rincian Anggaran (RBA)
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Pilih kode rekening pada panel kiri, lalu tentukan uraian rincian kegiatan pada panel kanan.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <!-- Split View Body Container -->
            <div class="flex-1 grid grid-cols-1 md:grid-cols-12 min-h-0 overflow-hidden bg-background">
                <!-- Panel Kiri: Master Daftar Kode Rekening (Col 4 - 5) -->
                <div class="md:col-span-5 lg:col-span-5 border-r flex flex-col h-full overflow-hidden bg-muted/10">
                    <!-- Search Bar Rekening -->
                    <div class="p-3 border-b bg-background flex-shrink-0">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model="accountSearchQuery"
                                placeholder="Cari kode atau nama rekening..."
                                class="pl-9 h-8 text-xs focus-visible:ring-primary"
                            />
                        </div>
                        <div class="flex items-center justify-between mt-2 px-1 text-[11px] text-muted-foreground">
                            <span>Daftar Rekening Belanja</span>
                            <span class="font-medium font-mono text-[10px] bg-muted px-1.5 py-0.5 rounded">{{ filteredAccountCodes.length }} Akun</span>
                        </div>
                    </div>

                    <!-- List Rekening (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-2 space-y-1.5">
                        <div v-if="filteredAccountCodes.length === 0" class="text-center py-12 text-muted-foreground text-xs px-4">
                            Tidak ditemukan rekening yang cocok dengan "{{ accountSearchQuery }}".
                        </div>

                        <div 
                            v-for="acc in filteredAccountCodes" 
                            :key="acc.id"
                            @click="handleSelectAccount(acc.id)"
                            :class="[
                                'p-3 rounded-lg border text-left cursor-pointer transition-all flex flex-col gap-1.5 select-none',
                                selectedAccountId === acc.id.toString()
                                    ? 'bg-primary/10 border-primary ring-1 ring-primary/40 shadow-xs'
                                    : 'bg-card border-border/70 hover:border-primary/50 hover:bg-muted/40'
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <Badge 
                                    variant="outline" 
                                    :class="[
                                        'font-mono text-[11px] px-2 py-0.5 font-bold flex-shrink-0',
                                        selectedAccountId === acc.id.toString() 
                                            ? 'bg-primary text-primary-foreground border-primary' 
                                            : 'bg-muted/80 text-foreground border-border'
                                    ]"
                                >
                                    {{ acc.code }}
                                </Badge>
                                <span 
                                    v-if="selectedAccountId === acc.id.toString()" 
                                    class="w-4 h-4 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-[10px] flex-shrink-0 font-bold"
                                >
                                    ✓
                                </span>
                            </div>

                            <p class="font-semibold text-xs leading-snug text-foreground line-clamp-2" :title="acc.name">
                                {{ acc.name }}
                            </p>

                            <div class="flex items-center justify-between text-[11px] font-mono pt-1 border-t border-border/40 text-muted-foreground">
                                <span class="truncate">Pagu: {{ formatCurrency(acc.total_budget || 0) }}</span>
                                <span 
                                    class="font-bold ml-2 flex-shrink-0"
                                    :class="(acc.remaining_budget ?? 0) <= 0 ? 'text-destructive' : 'text-emerald-600 dark:text-emerald-400'"
                                >
                                    Sisa: {{ formatCurrency(acc.remaining_budget || 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Kanan: Detail Uraian RBA (Col 7 - 8) -->
                <div class="md:col-span-7 lg:col-span-7 flex flex-col h-full overflow-hidden bg-background">
                    <!-- Status jika belum memilih akun di panel kiri -->
                    <div v-if="!selectedAccountId" class="flex-1 flex flex-col items-center justify-center p-8 text-center gap-3 text-muted-foreground">
                        <div class="w-14 h-14 rounded-2xl bg-muted/60 flex items-center justify-center text-muted-foreground/80">
                            <Layers class="w-7 h-7" />
                        </div>
                        <div class="space-y-1 max-w-sm">
                            <h4 class="font-semibold text-sm text-foreground">Pilih Kode Rekening</h4>
                            <p class="text-xs text-muted-foreground leading-relaxed">
                                Silakan klik salah satu kode rekening di panel kiri untuk melihat rincian kegiatan belanja dan pagu anggarannya.
                            </p>
                        </div>
                    </div>

                    <!-- Panel Kanan Aktif (Akun sudah dipilih) -->
                    <template v-else>
                        <!-- Top Banner Akun Terpilih & Opsi Pemilihan Cepat Rekening Saja -->
                        <div class="p-3.5 border-b bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3 flex-shrink-0">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="font-mono bg-primary/10 text-primary border-primary/30 font-semibold px-2 py-0.5 text-xs">
                                        {{ activeAccount?.code }}
                                    </Badge>
                                    <span class="font-bold text-xs text-foreground truncate" :title="activeAccount?.name">
                                        {{ activeAccount?.name }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-[11px] font-mono mt-1 text-muted-foreground">
                                    <span>Total: <strong class="text-foreground">{{ formatCurrency(activeAccount?.total_budget || 0) }}</strong></span>
                                    <span>•</span>
                                    <span>Sisa: <strong class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(activeAccount?.remaining_budget ?? 0) }}</strong></span>
                                </div>
                            </div>

                            <!-- Tombol Cepat: Gunakan Rekening Saja (Tanpa Rincian) -->
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-8 text-xs font-semibold border-primary/30 text-primary hover:bg-primary/10 hover:border-primary flex-shrink-0 cursor-pointer"
                                @click="selectAccountOnly"
                                title="Pilih rekening ini secara langsung tanpa mengaitkan ke rincian RBA spesifik"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5 mr-1.5 text-primary" />
                                Gunakan Rekening Ini Saja
                            </Button>
                        </div>

                        <!-- Sub-bar: Search & Toggle View -->
                        <div class="px-3.5 py-2.5 border-b bg-background flex items-center justify-between gap-3 flex-shrink-0">
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground" />
                                <Input 
                                    v-model="searchInput"
                                    placeholder="Cari uraian kegiatan (induk atau rincian)..."
                                    class="pl-8 pr-8 h-8 text-xs focus-visible:ring-primary"
                                    :disabled="loading || allHeaders.length === 0"
                                />
                                <button 
                                    v-if="searchInput" 
                                    type="button"
                                    @click="clearSearch"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-0.5 rounded cursor-pointer"
                                    title="Bersihkan pencarian"
                                >
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <div v-if="allHeaders.length > 0" class="flex items-center gap-1 bg-muted/60 p-0.5 rounded-lg border text-xs">
                                <button 
                                    type="button"
                                    @click="activeView = 'tree'"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-[11px] font-medium transition-all flex items-center gap-1 cursor-pointer',
                                        activeView === 'tree' 
                                            ? 'bg-background text-foreground shadow-xs font-semibold' 
                                            : 'text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    <Layers class="w-3 h-3" />
                                    Hirarki
                                </button>
                                <button 
                                    type="button"
                                    @click="activeView = 'list'"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-[11px] font-medium transition-all flex items-center gap-1 cursor-pointer',
                                        activeView === 'list' 
                                            ? 'bg-background text-foreground shadow-xs font-semibold' 
                                             : 'text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    <ListFilter class="w-3 h-3" />
                                    Daftar ({{ allHeaders.length }})
                                </button>
                            </div>
                        </div>

                        <!-- Filter Tabs untuk List View -->
                        <div v-if="activeView === 'list' && allHeaders.length > 0" class="px-3.5 py-1.5 border-b bg-muted/15 flex items-center gap-1.5 flex-shrink-0 text-xs overflow-x-auto">
                            <span class="text-[11px] text-muted-foreground mr-1 flex-shrink-0">Tampilkan:</span>
                            <button 
                                type="button"
                                @click="headerFilterType = 'all'"
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[11px] transition-all cursor-pointer border whitespace-nowrap',
                                    headerFilterType === 'all'
                                        ? 'bg-primary text-primary-foreground border-primary shadow-2xs font-semibold'
                                        : 'bg-background text-muted-foreground hover:text-foreground border-border/70 hover:bg-muted/40 font-medium'
                                ]"
                            >
                                Semua ({{ allHeaders.length }})
                            </button>
                            <button 
                                type="button"
                                @click="headerFilterType = 'parent'"
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[11px] transition-all cursor-pointer border whitespace-nowrap',
                                    headerFilterType === 'parent'
                                        ? 'bg-sky-600 text-white border-sky-600 shadow-2xs font-semibold'
                                        : 'bg-background text-muted-foreground hover:text-foreground border-border/70 hover:bg-muted/40 font-medium'
                                ]"
                            >
                                Induk Kegiatan ({{ parentHeadersCount }})
                            </button>
                            <button 
                                type="button"
                                @click="headerFilterType = 'leaf'"
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[11px] transition-all cursor-pointer border whitespace-nowrap',
                                    headerFilterType === 'leaf'
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs font-semibold'
                                        : 'bg-background text-muted-foreground hover:text-foreground border-border/70 hover:bg-muted/40 font-medium'
                                ]"
                            >
                                Rincian Terbawah ({{ leafHeadersCount }})
                            </button>
                        </div>

                        <!-- Toolbar Khusus Tree View: Kontrol Expand / Collapse -->
                        <div v-if="activeView === 'tree' && treeData.length > 0" class="px-3.5 py-1.5 border-b bg-muted/15 flex items-center justify-between gap-2 flex-shrink-0 text-xs">
                            <div class="flex items-center gap-1.5 text-muted-foreground text-[11px] truncate">
                                <Layers class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                <span class="truncate">Pohon Hirarki RBA (Klik panah untuk membuka)</span>
                                <span v-if="rbaSearchQuery.trim()" class="text-primary font-semibold flex-shrink-0">
                                    • Ditemukan {{ searchMatchCount }} uraian
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button 
                                    type="button" 
                                    @click="expandAll"
                                    class="px-2 py-0.5 rounded text-[11px] font-medium bg-background text-muted-foreground hover:text-foreground border border-border/70 hover:bg-muted/40 transition-all cursor-pointer shadow-2xs"
                                >
                                    Buka Semua
                                </button>
                                <button 
                                    type="button" 
                                    @click="collapseAll"
                                    class="px-2 py-0.5 rounded text-[11px] font-medium bg-background text-muted-foreground hover:text-foreground border border-border/70 hover:bg-muted/40 transition-all cursor-pointer shadow-2xs"
                                >
                                    Tutup Semua
                                </button>
                            </div>
                        </div>

                        <!-- Content Area RBA (Scrollable) -->
                        <div class="flex-1 overflow-y-auto p-3.5 space-y-2">
                            <!-- Loading State -->
                            <div v-if="loading" class="h-64 flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                <Loader2 class="w-7 h-7 animate-spin text-primary" />
                                <p class="text-xs">Memuat rincian kegiatan RBA...</p>
                            </div>

                            <!-- Error / Empty State (Gelondongan) -->
                            <div v-else-if="errorMessage || (!treeData.length && !allHeaders.length)" class="h-64 flex flex-col items-center justify-center gap-3 text-center px-6">
                                <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                    <FileText class="w-6 h-6" />
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-foreground">Rekening Gelondongan / Tanpa Rincian</p>
                                    <p class="text-xs text-muted-foreground max-w-md">
                                        {{ errorMessage || 'Rekening ini tidak memiliki uraian rincian sub-kegiatan di dokumen RBA. Anda dapat langsung memilih rekening ini untuk pencairan dana.' }}
                                    </p>
                                </div>
                                <Button 
                                    type="button" 
                                    size="sm" 
                                    class="h-8 text-xs font-semibold cursor-pointer"
                                    @click="selectAccountOnly"
                                >
                                    <Check class="w-3.5 h-3.5 mr-1" />
                                    Pilih Rekening Ini
                                </Button>
                            </div>

                            <!-- Mode 1: List View (Daftar Headers) -->
                            <div v-else-if="activeView === 'list'" class="space-y-2">
                                <div v-if="filteredHeaders.length === 0" class="text-center py-12 text-muted-foreground text-xs space-y-2">
                                    <p>Tidak ada uraian yang cocok dengan pencarian "{{ searchInput }}".</p>
                                    <button 
                                        type="button" 
                                        @click="clearSearch"
                                        class="text-xs text-primary hover:underline font-medium cursor-pointer"
                                    >
                                        Reset Pencarian
                                    </button>
                                </div>

                                <div 
                                    v-for="item in filteredHeaders" 
                                    :key="item.id"
                                    :class="[
                                        'p-3 rounded-lg border transition-all flex items-center justify-between gap-3 text-left',
                                        selectedDetailId === item.id 
                                            ? 'bg-primary/10 border-primary ring-1 ring-primary/40 shadow-xs' 
                                            : 'bg-card hover:border-primary/50 hover:bg-muted/30'
                                    ]"
                                >
                                    <div class="space-y-1 min-w-0 flex-1">
                                        <!-- Breadcrumb Path -->
                                        <div class="text-[11px] text-muted-foreground flex items-center gap-1 truncate" :title="item.breadcrumb">
                                            <Folder class="w-3 h-3 flex-shrink-0 text-muted-foreground/70" />
                                            <span class="truncate">{{ item.breadcrumb }}</span>
                                        </div>
                                        <!-- Uraian Title (with Browser Native Tooltip via title attribute) -->
                                        <div 
                                            class="font-semibold text-xs text-foreground flex items-center gap-2"
                                            :title="item.breadcrumb && item.breadcrumb !== item.uraian ? `${item.uraian}\nJalur Hirarki: ${item.breadcrumb}` : item.uraian"
                                        >
                                            <span class="truncate font-semibold">{{ item.uraian }}</span>
                                        </div>
                                    </div>

                                    <!-- Right Side: Nominal Pagu & Select Button -->
                                    <div class="flex items-center gap-2.5 flex-shrink-0">
                                        <div v-if="item.jumlah > 0" class="text-right">
                                            <span class="text-[10px] text-muted-foreground block">Pagu:</span>
                                            <span class="font-mono text-xs font-bold text-foreground">{{ formatCurrency(item.jumlah) }}</span>
                                        </div>

                                        <Button 
                                            type="button" 
                                            size="sm" 
                                            :variant="selectedDetailId === item.id ? 'default' : 'outline'"
                                            class="h-7 px-2.5 text-xs font-medium cursor-pointer"
                                            @click="selectDetail(item)"
                                        >
                                            <Check v-if="selectedDetailId === item.id" class="w-3.5 h-3.5 mr-1" />
                                            {{ selectedDetailId === item.id ? 'Terpilih' : 'Pilih' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Mode 2: Tree View (Hirarki Pohon) -->
                            <div v-else class="space-y-1 text-xs">
                                <div v-if="filteredTreeData.length === 0" class="text-center py-12 text-muted-foreground text-xs space-y-2">
                                    <p>Tidak ada uraian hirarki yang cocok dengan pencarian "{{ searchInput }}".</p>
                                    <button 
                                        type="button" 
                                        @click="clearSearch"
                                        class="text-xs text-primary hover:underline font-medium cursor-pointer"
                                    >
                                        Reset Pencarian
                                    </button>
                                </div>
                                <template v-else>
                                    <template v-for="node in filteredTreeData" :key="node.id">
                                        <RbaTreeNode 
                                            :node="node" 
                                            :level="0"
                                            :expanded-nodes="expandedNodes"
                                            :selected-id="selectedDetailId"
                                            :search-query="rbaSearchQuery"
                                            @toggle="toggleExpand"
                                            @select="selectDetail"
                                        />
                                    </template>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer Dialog (Polished & Padded) -->
            <div class="px-5 py-3 border-t bg-muted/25 flex flex-col sm:flex-row items-center justify-between gap-3 w-full flex-shrink-0">
                <div class="flex items-center gap-2.5 text-xs text-muted-foreground min-w-0">
                    <div class="w-6 h-6 rounded-md bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <Info class="w-3.5 h-3.5" />
                    </div>
                    <p class="text-[11.5px] leading-normal text-muted-foreground">
                        Pilih tombol <strong class="text-foreground font-semibold">Pilih</strong> pada induk kegiatan maupun sub-rincian kegiatan RBA, atau klik <strong class="text-foreground font-semibold">Gunakan Rekening Ini Saja</strong> di kanan atas untuk pos belanja umum.
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <Button 
                        type="button" 
                        variant="outline" 
                        size="sm" 
                        class="h-8 px-4 text-xs font-semibold cursor-pointer bg-background hover:bg-muted text-foreground border-border shadow-xs"
                        @click="$emit('update:open', false)"
                    >
                        Tutup
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
