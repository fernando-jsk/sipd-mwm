<script setup>
import { ref, computed, watch } from 'vue';
import { ChevronRight, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    data: {
        type: Array,
        required: true
    },
    title: {
        type: String,
        default: ''
    }
});

// Set of expanded row IDs
const expandedRows = ref(new Set());

// Initialize by expanding level 1, 2, 3
const initExpanded = (nodes, maxLevel = 3) => {
    for (const node of nodes) {
        if (node.level <= maxLevel) {
            expandedRows.value.add(node.id);
        }
        if (node.children && node.children.length > 0) {
            initExpanded(node.children, maxLevel);
        }
    }
};

// Flatten the tree into an array of visible rows based on expanded state
const flattenedRows = computed(() => {
    const rows = [];
    
    const traverse = (nodes, isParentExpanded) => {
        for (const node of nodes) {
            if (isParentExpanded) {
                rows.push(node);
                if (node.children && node.children.length > 0) {
                    const isExpanded = expandedRows.value.has(node.id);
                    traverse(node.children, isExpanded);
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
        initExpanded(newData, 3);
    }
}, { immediate: true });
</script>

<template>
    <div class="rounded-md border border-border bg-card overflow-hidden h-full flex flex-col">
        <div v-if="title" class="bg-muted/50 px-4 py-3 border-b border-border font-semibold text-primary">
            {{ title }}
        </div>
        <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted-foreground uppercase bg-muted/30 border-b border-border">
                    <tr>
                        <th class="px-4 py-3 font-medium w-1/3">Kode Rekening</th>
                        <th class="px-4 py-3 font-medium w-auto">Uraian Akun</th>
                        <th class="px-4 py-3 font-medium text-right w-1/4">Saldo (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!flattenedRows.length" class="border-b border-border/50">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Tidak ada data.
                        </td>
                    </tr>
                    <template v-else>
                        <tr 
                            v-for="row in flattenedRows" 
                            :key="row.id"
                            class="border-b border-border/50 hover:bg-muted/20 transition-colors"
                            :class="{
                                'bg-muted/10 font-medium': row.level <= 3,
                                'text-foreground': row.level <= 3,
                                'text-muted-foreground': row.level > 3
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
                                    <span>{{ row.code }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 max-w-sm truncate" :title="row.name">
                                {{ row.name }}
                            </td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap" :class="{'text-destructive': row.balance < 0}">
                                {{ formatCurrency(row.balance) }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>
