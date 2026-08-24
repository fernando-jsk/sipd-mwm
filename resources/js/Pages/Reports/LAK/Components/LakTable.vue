<script setup>
import { ref, computed, watch } from 'vue';
import { ChevronRight, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    data: {
        type: Array,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    }
});

// Set of expanded row IDs
const expandedRows = ref(new Set());

// Initialize by expanding level 1 and 2
const initExpanded = (nodes) => {
    for (const node of nodes) {
        expandedRows.value.add(node.id);
        if (node.children && node.children.length > 0) {
            initExpanded(node.children);
        }
    }
};

// Flatten the tree into an array of visible rows based on expanded state
const flattenedRows = computed(() => {
    const rows = [];
    
    const traverse = (nodes, isParentExpanded, level = 1) => {
        for (const node of nodes) {
            if (isParentExpanded) {
                // Decorate with level
                rows.push({ ...node, level });
                if (node.children && node.children.length > 0) {
                    const isExpanded = expandedRows.value.has(node.id);
                    traverse(node.children, isExpanded, level + 1);
                }
            }
        }
    };
    
    traverse(props.data, true);
    
    return rows;
});

const toggleRow = (id) => {
    const newExpanded = new Set(expandedRows.value);
    if (newExpanded.has(id)) {
        newExpanded.delete(id);
    } else {
        newExpanded.add(id);
    }
    expandedRows.value = newExpanded;
};

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

// Initialize once when data is first loaded
watch(() => props.data, (newData) => {
    if (newData && newData.length > 0 && expandedRows.value.size === 0) {
        initExpanded(newData);
    }
}, { immediate: true });
</script>

<template>
    <div class="rounded-md border border-border bg-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted-foreground uppercase bg-muted/50 border-b border-border">
                    <tr>
                        <th class="px-4 py-3 font-medium w-1/2">Uraian / Aktivitas Arus Kas</th>
                        <th class="px-4 py-3 font-medium text-right">Rincian (Rp)</th>
                        <th class="px-4 py-3 font-medium text-right">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading" class="border-b border-border/50">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Memuat data laporan arus kas...
                        </td>
                    </tr>
                    <tr v-else-if="!flattenedRows.length" class="border-b border-border/50">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Tidak ada data LAK yang ditemukan.
                        </td>
                    </tr>
                    <template v-else>
                        <tr 
                            v-for="row in flattenedRows" 
                            :key="row.id"
                            class="border-b border-border/50 hover:bg-muted/20 transition-colors"
                            :class="{
                                'bg-muted/10 font-semibold text-secondary': row.is_header,
                                'bg-muted/5 font-medium text-foreground': row.is_subheader,
                                'text-muted-foreground': !row.is_header && !row.is_subheader
                            }"
                        >
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <div class="flex items-center" :style="{ paddingLeft: `${(row.level - 1) * 1.5}rem` }">
                                    <button 
                                        v-if="row.children && row.children.length > 0"
                                        @click="toggleRow(row.id)"
                                        class="mr-1.5 p-0.5 rounded-sm hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        <ChevronDown v-if="expandedRows.has(row.id)" class="w-3.5 h-3.5" />
                                        <ChevronRight v-else class="w-3.5 h-3.5" />
                                    </button>
                                    <span v-else class="w-5 mr-1.5 inline-block"></span>
                                    <span>{{ row.code ? `${row.code} - ${row.name}` : row.name }}</span>
                                </div>
                            </td>
                            <!-- Rincian (hanya untuk detail/level 3) -->
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <span v-if="!row.is_header && !row.is_subheader">
                                    {{ formatCurrency(row.amount) }}
                                </span>
                            </td>
                            <!-- Jumlah (hanya untuk header/subheader) -->
                            <td class="px-4 py-2.5 text-right whitespace-nowrap font-medium" :class="{'text-secondary': row.is_header}">
                                <span v-if="row.is_header || row.is_subheader">
                                    {{ formatCurrency(row.amount) }}
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>
