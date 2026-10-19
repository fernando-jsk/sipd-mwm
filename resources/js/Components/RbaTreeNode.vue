<script setup>
import { computed } from 'vue';

const props = defineProps({
    node: {
        type: Object,
        required: true
    },
    level: {
        type: Number,
        default: 0
    },
    expandedNodes: {
        type: Object,
        required: true
    },
    selectedId: {
        type: [Number, String, null],
        default: null
    },
    searchQuery: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['toggle', 'select']);

const isExpanded = computed(() => props.expandedNodes.has(props.node.id));
const hasChildren = computed(() => props.node.children && props.node.children.length > 0);
const isLeaf = computed(() => props.node.is_leaf_header);
const isItem = computed(() => props.node.type === 'item');
const isSelected = computed(() => props.selectedId != null && props.selectedId.toString() === props.node.id.toString());

const parentBreadcrumb = computed(() => {
    if (!props.node.breadcrumb) return '';
    const parts = props.node.breadcrumb.split(' ❯ ');
    if (parts.length <= 1) return '';
    return parts.slice(0, -1).join(' ❯ ');
});

const tooltipText = computed(() => {
    const parts = [];
    if (props.node?.uraian) parts.push(props.node.uraian);
    if (parentBreadcrumb.value) parts.push(`Jalur Hirarki: ${parentBreadcrumb.value}`);
    if (isItem.value && formatVolumes(props.node)) parts.push(`Volume: ${formatVolumes(props.node)}`);
    return parts.join('\n');
});

const highlightedParts = computed(() => {
    const text = props.node.uraian || '';
    const q = (props.searchQuery || '').trim();
    if (!q || !text.toLowerCase().includes(q.toLowerCase())) {
        return [{ text, match: false }];
    }

    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');
    const parts = text.split(regex);
    return parts.map(part => ({
        text: part,
        match: part.toLowerCase() === q.toLowerCase()
    }));
});

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
</script>

<template>
    <div class="select-none">
        <!-- Node Row -->
        <div 
            :style="{ paddingLeft: (level * 18 + 6) + 'px' }"
            :class="[
                'py-1.5 px-2 rounded-md flex items-center justify-between gap-2 transition-all my-0.5',
                isSelected 
                    ? 'bg-primary/10 border border-primary/40 font-semibold' 
                    : (!isItem 
                        ? 'hover:bg-muted/40 font-semibold text-foreground border border-transparent' 
                        : 'text-muted-foreground hover:bg-muted/20 text-[11px] border border-transparent')
            ]"
        >
            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                <!-- Expand/Collapse Chevron for non-items -->
                <button 
                    v-if="!isItem && hasChildren" 
                    type="button"
                    @click.stop="emit('toggle', node.id)"
                    class="w-4 h-4 flex items-center justify-center text-muted-foreground hover:text-foreground rounded cursor-pointer"
                >
                    <svg v-if="isExpanded" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <span v-else class="w-4 h-4 inline-block"></span>

                <!-- Node Icon -->
                <span v-if="isItem" class="text-muted-foreground/60 flex-shrink-0">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="2"></circle>
                    </svg>
                </span>
                <span v-else class="text-sky-600 dark:text-sky-400 flex-shrink-0">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>

                <!-- Node Title (with Browser Native Tooltip via title attribute) -->
                <div 
                    class="min-w-0 flex-1 flex items-center gap-1.5"
                    :title="tooltipText"
                >
                    <span 
                        class="truncate" 
                        :class="{ 'font-semibold text-foreground': !isItem, 'text-muted-foreground': isItem }"
                    >
                        <template v-if="searchQuery && searchQuery.trim()">
                            <template v-for="(part, idx) in highlightedParts" :key="idx">
                                <mark v-if="part.match" class="bg-amber-400/30 text-amber-950 dark:text-amber-200 font-bold px-0.5 rounded">
                                    {{ part.text }}
                                </mark>
                                <span v-else>{{ part.text }}</span>
                            </template>
                        </template>
                        <template v-else>
                            {{ node.uraian }}
                        </template>
                    </span>
                    
                    <!-- Volume / Satuan for item -->
                    <span v-if="isItem && formatVolumes(node)" class="text-[10px] text-muted-foreground/80 font-mono truncate flex-shrink-0">
                        ({{ formatVolumes(node) }})
                    </span>
                </div>
            </div>

            <!-- Right Side: Nominal & Button -->
            <div class="flex items-center gap-2.5 flex-shrink-0">
                <span v-if="node.jumlah > 0" class="font-mono text-xs" :class="!isItem ? 'font-bold text-foreground' : 'text-muted-foreground'">
                    {{ formatCurrency(node.jumlah) }}
                </span>

                <!-- Button: Active for all headers (!isItem) -->
                <button 
                    v-if="!isItem"
                    type="button"
                    @click="emit('select', node)"
                    :class="[
                        'px-2 py-0.5 rounded text-xs font-medium transition-all cursor-pointer',
                        isSelected 
                            ? 'bg-primary text-primary-foreground shadow-xs' 
                            : 'bg-muted/80 hover:bg-primary hover:text-primary-foreground text-foreground'
                    ]"
                >
                    {{ isSelected ? 'Terpilih' : 'Pilih' }}
                </button>
                <!-- Spacer for items to maintain alignment -->
                <span v-else class="w-12 text-center text-[10px] text-muted-foreground/40">
                    Sub
                </span>
            </div>
        </div>

        <!-- Children Recursive Container -->
        <div v-if="hasChildren && isExpanded">
            <RbaTreeNode 
                v-for="child in node.children" 
                :key="child.id" 
                :node="child" 
                :level="level + 1" 
                :expanded-nodes="expandedNodes" 
                :selected-id="selectedId" 
                :search-query="searchQuery"
                @toggle="emit('toggle', $event)" 
                @select="emit('select', $event)" 
            />
        </div>
    </div>
</template>
