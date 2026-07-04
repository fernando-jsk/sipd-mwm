<script setup>
import { ref, computed } from 'vue';
import { ChevronRight, ChevronDown, FileEdit } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { TableRow, TableCell } from '@/Components/ui/table';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    level: {
        type: Number,
        default: 0,
    }
});

const isExpanded = ref(true);

const hasChildren = computed(() => {
    return props.row.children && props.row.children.length > 0;
});

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

const formatCurrency = (value) => {
    if (value == null || value === 0) return 'Rp 0,00';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(value);
};

const paddingLeft = computed(() => {
    return `padding-left: calc(0.5rem + ${props.level * 24}px)`;
});
</script>

<template>
    <TableRow :class="[hasChildren ? 'bg-muted/10 font-medium' : '']">
        <TableCell :style="paddingLeft" class="font-medium">
            <div class="flex items-center gap-1">
                <button
                    v-if="hasChildren"
                    @click="toggleExpand"
                    class="w-5 h-5 flex items-center justify-center rounded hover:bg-muted text-muted-foreground focus:outline-none"
                >
                    <ChevronDown v-if="isExpanded" class="w-4 h-4" />
                    <ChevronRight v-else class="w-4 h-4" />
                </button>
                <div v-else class="w-5 h-5 flex items-center justify-center text-muted-foreground/30">
                    <div class="w-1 h-1 rounded-full bg-current"></div>
                </div>
                <span>{{ row.code }}</span>
            </div>
        </TableCell>
        
        <TableCell>{{ row.name }}</TableCell>
        
        <TableCell class="text-right font-semibold text-primary">
            {{ formatCurrency(row.tree_jumlah) }}
        </TableCell>
        
        <TableCell class="text-right">
            <Button v-if="!hasChildren" as-child variant="default" size="sm">
                <Link :href="`/rba/${row.rba_document_id}`">
                    <FileEdit class="w-4 h-4 mr-1" />
                    Susun RBA
                </Link>
            </Button>
        </TableCell>
    </TableRow>
    
    <template v-if="hasChildren && isExpanded">
        <AccountTreeRow
            v-for="child in row.children"
            :key="child.id"
            :row="child"
            :level="level + 1"
        />
    </template>
</template>
