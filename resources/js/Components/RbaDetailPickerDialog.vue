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
    CornerDownRight
} from 'lucide-vue-next';

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
const rbaSearchQuery = ref('');
const activeView = ref('list'); // 'list' or 'tree'
const loading = ref(false);
const errorMessage = ref('');
const treeData = ref([]);
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

// Filtered Account Codes (Left Panel)
const filteredAccountCodes = computed(() => {
    if (!props.accountCodes || !props.accountCodes.length) return [];
    if (!accountSearchQuery.value.trim()) return props.accountCodes;

    const q = accountSearchQuery.value.toLowerCase().trim();
    return props.accountCodes.filter(acc => 
        (acc.code && acc.code.toLowerCase().includes(q)) ||
        (acc.name && acc.name.toLowerCase().includes(q))
    );
});

// Currently Active Account Object
const activeAccount = computed(() => {
    if (!selectedAccountId.value) return null;
    return props.accountCodes.find(a => a.id.toString() === selectedAccountId.value.toString()) || null;
});

// Fetch RBA Tree Data
const fetchRbaTree = async (accountId) => {
    if (!accountId) {
        treeData.value = [];
        leafHeaders.value = [];
        rbaDoc.value = null;
        errorMessage.value = '';
        return;
    }

    if (cache.value[accountId]) {
        const cached = cache.value[accountId];
        treeData.value = cached.tree || [];
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
            leafHeaders.value = [];
            rbaDoc.value = data.rba_document || null;
        } else {
            treeData.value = data.tree || [];
            leafHeaders.value = data.leaf_headers || [];
            rbaDoc.value = data.rba_document || null;
        }

        cache.value[accountId] = {
            tree: treeData.value,
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
    const newExpanded = new Set();
    const traverse = (nodes) => {
        nodes.forEach(node => {
            if (node.type === 'header') {
                newExpanded.add(node.id);
                if (node.children?.length) {
                    traverse(node.children);
                }
            }
        });
    };
    traverse(treeData.value);
    expandedNodes.value = newExpanded;
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
        rbaSearchQuery.value = '';
        activeView.value = 'list';
        
        if (props.initialAccountCodeId) {
            selectedAccountId.value = props.initialAccountCodeId.toString();
            selectedDetailId.value = props.initialDetailId || null;
            fetchRbaTree(props.initialAccountCodeId);
        } else {
            selectedAccountId.value = null;
            selectedDetailId.value = null;
            treeData.value = [];
            leafHeaders.value = [];
            errorMessage.value = '';
        }
    }
});

// Filtered Leaf Headers for List View (Right Panel)
const filteredLeafHeaders = computed(() => {
    if (!rbaSearchQuery.value.trim()) {
        return leafHeaders.value;
    }
    const q = rbaSearchQuery.value.toLowerCase().trim();
    return leafHeaders.value.filter(item => 
        (item.uraian && item.uraian.toLowerCase().includes(q)) ||
        (item.breadcrumb && item.breadcrumb.toLowerCase().includes(q))
    );
});

// Select Leaf Header (with rincian)
const selectDetail = (item) => {
    selectedDetailId.value = item.id;
    emit('select', {
        account_code_id: selectedAccountId.value,
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
        account_code_id: selectedAccountId.value,
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
                                    v-model="rbaSearchQuery"
                                    placeholder="Cari uraian kegiatan rincian..."
                                    class="pl-8 h-8 text-xs focus-visible:ring-primary"
                                    :disabled="loading || leafHeaders.length === 0"
                                />
                            </div>
                            <div v-if="leafHeaders.length > 0" class="flex items-center gap-1 bg-muted/60 p-0.5 rounded-lg border text-xs">
                                <button 
                                    type="button"
                                    @click="activeView = 'list'"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-[11px] font-medium transition-all flex items-center gap-1',
                                        activeView === 'list' 
                                            ? 'bg-background text-foreground shadow-xs' 
                                            : 'text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    <ListFilter class="w-3 h-3" />
                                    Daftar ({{ leafHeaders.length }})
                                </button>
                                <button 
                                    type="button"
                                    @click="activeView = 'tree'"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-[11px] font-medium transition-all flex items-center gap-1',
                                        activeView === 'tree' 
                                            ? 'bg-background text-foreground shadow-xs' 
                                            : 'text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    <Layers class="w-3 h-3" />
                                    Hirarki
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
                            <div v-else-if="errorMessage || (!treeData.length && !leafHeaders.length)" class="h-64 flex flex-col items-center justify-center gap-3 text-center px-6">
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

                            <!-- Mode 1: List View (Daftar Leaf Headers) -->
                            <div v-else-if="activeView === 'list'" class="space-y-2">
                                <div v-if="filteredLeafHeaders.length === 0" class="text-center py-12 text-muted-foreground text-xs">
                                    Tidak ada uraian rincian yang cocok dengan pencarian "{{ rbaSearchQuery }}".
                                </div>

                                <div 
                                    v-for="item in filteredLeafHeaders" 
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
                                        <!-- Uraian Title -->
                                        <div class="font-semibold text-xs text-foreground flex items-center gap-2">
                                            <Badge variant="outline" class="text-[9px] py-0 px-1 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30 flex-shrink-0">
                                                Rincian
                                            </Badge>
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
                                            {{ selectedDetailId === item.id ? 'Terpilih' : 'Pilih Rincian' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Mode 2: Tree View (Hirarki Pohon) -->
                            <div v-else class="space-y-1 text-xs">
                                <template v-for="node in treeData" :key="node.id">
                                    <TreeNode 
                                        :node="node" 
                                        :level="0"
                                        :expanded-nodes="expandedNodes"
                                        :selected-id="selectedDetailId"
                                        @toggle="toggleExpand"
                                        @select="selectDetail"
                                    />
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
                        Pilih tombol <strong class="text-foreground font-semibold">Pilih Rincian</strong> untuk rincian spesifik, atau klik <strong class="text-foreground font-semibold">Gunakan Rekening Ini Saja</strong> di kanan atas untuk pos belanja umum.
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

<!-- Recursive TreeNode Component defined locally -->
<script>
import { defineComponent, computed } from 'vue';

const TreeNode = defineComponent({
    name: 'TreeNode',
    props: {
        node: { type: Object, required: true },
        level: { type: Number, default: 0 },
        expandedNodes: { type: Object, required: true },
        selectedId: { type: [Number, String, null], default: null }
    },
    emits: ['toggle', 'select'],
    setup(props, { emit }) {
        const isExpanded = computed(() => props.expandedNodes.has(props.node.id));
        const hasChildren = computed(() => props.node.children && props.node.children.length > 0);
        const isLeaf = computed(() => props.node.is_leaf_header);
        const isItem = computed(() => props.node.type === 'item');
        const isSelected = computed(() => props.selectedId === props.node.id);

        const formatCurrency = (val) => {
            if (val == null || isNaN(val)) return 'Rp 0';
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(val);
        };

        const formatNumber = (val) => {
            if (val == null) return '';
            return new Intl.NumberFormat('id-ID').format(val);
        };

        const formatVolumes = (item) => {
            if (item.type !== 'item') return '';
            const parts = [];
            if (item.vol_1) parts.push(`${formatNumber(item.vol_1)} ${item.satuan_1 || ''}`.trim());
            if (item.vol_2) parts.push(`${formatNumber(item.vol_2)} ${item.satuan_2 || ''}`.trim());
            if (item.vol_3) parts.push(`${formatNumber(item.vol_3)} ${item.satuan_3 || ''}`.trim());
            if (item.vol_4) parts.push(`${formatNumber(item.vol_4)} ${item.satuan_4 || ''}`.trim());
            return parts.join(' x ');
        };

        return {
            isExpanded,
            hasChildren,
            isLeaf,
            isItem,
            isSelected,
            formatCurrency,
            formatVolumes
        };
    },
    template: `
        <div class="select-none">
            <!-- Node Row -->
            <div 
                :style="{ paddingLeft: (level * 18 + 6) + 'px' }"
                :class="[
                    'py-1.5 px-2 rounded-md flex items-center justify-between gap-2 transition-all my-0.5',
                    isLeaf 
                        ? (isSelected ? 'bg-primary/10 border border-primary/40 font-semibold' : 'hover:bg-muted/50 border border-transparent') 
                        : (isItem ? 'text-muted-foreground hover:bg-muted/20 text-[11px]' : 'hover:bg-muted/40 font-medium')
                ]"
            >
                <div class="flex items-center gap-1.5 min-w-0 flex-1">
                    <!-- Expand/Collapse Chevron for non-items -->
                    <button 
                        v-if="!isItem && hasChildren" 
                        type="button"
                        @click.stop="$emit('toggle', node.id)"
                        class="w-4 h-4 flex items-center justify-center text-muted-foreground hover:text-foreground rounded cursor-pointer"
                    >
                        <svg v-if="isExpanded" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                    <span v-else class="w-4 h-4 inline-block"></span>

                    <!-- Node Icon -->
                    <span v-if="isItem" class="text-muted-foreground/60">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"></circle></svg>
                    </span>
                    <span v-else-if="isLeaf" class="text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </span>
                    <span v-else class="text-muted-foreground">
                        <svg v-if="isExpanded" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    </span>

                    <!-- Node Title & Badges -->
                    <span class="truncate" :class="{ 'font-semibold text-foreground': isLeaf, 'text-muted-foreground': isItem }">
                        {{ node.uraian }}
                    </span>

                    <span v-if="isLeaf" class="text-[9px] px-1 py-0.2 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                        Rincian
                    </span>
                    <span v-else-if="!isItem" class="text-[9px] px-1 py-0.2 rounded bg-muted text-muted-foreground">
                        Induk
                    </span>
                    
                    <!-- Volume / Satuan for item -->
                    <span v-if="isItem && formatVolumes(node)" class="text-[10px] text-muted-foreground/80 font-mono">
                        ({{ formatVolumes(node) }})
                    </span>
                </div>

                <!-- Right Side: Nominal & Button -->
                <div class="flex items-center gap-2.5 flex-shrink-0">
                    <span v-if="node.jumlah > 0" class="font-mono text-xs" :class="isLeaf ? 'font-bold text-foreground' : 'text-muted-foreground'">
                        {{ formatCurrency(node.jumlah) }}
                    </span>

                    <!-- Button: Only active for Leaf Headers -->
                    <button 
                        v-if="isLeaf"
                        type="button"
                        @click="$emit('select', node)"
                        :class="[
                            'px-2 py-0.5 rounded text-xs font-medium transition-all cursor-pointer',
                            isSelected 
                                ? 'bg-primary text-primary-foreground shadow-xs' 
                                : 'bg-muted/80 hover:bg-primary hover:text-primary-foreground text-foreground'
                        ]"
                    >
                        {{ isSelected ? 'Terpilih' : 'Pilih' }}
                    </button>
                    <!-- Spacer for items & parents to maintain alignment -->
                    <span v-else class="w-12 text-center text-[10px] text-muted-foreground/40">
                        {{ isItem ? 'Sub' : '' }}
                    </span>
                </div>
            </div>

            <!-- Children Recursive Container -->
            <div v-if="hasChildren && isExpanded">
                <TreeNode 
                    v-for="child in node.children" 
                    :key="child.id"
                    :node="child"
                    :level="level + 1"
                    :expanded-nodes="expandedNodes"
                    :selected-id="selectedId"
                    @toggle="$emit('toggle', $event)"
                    @select="$emit('select', $event)"
                />
            </div>
        </div>
    `
});
</script>
