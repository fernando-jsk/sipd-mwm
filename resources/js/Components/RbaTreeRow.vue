<script setup>
import { ref, computed } from 'vue';
import { TableRow, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/Components/ui/dropdown-menu';
import { ChevronRight, ChevronDown, MoreVertical, Plus, Edit, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    level: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['addHeader', 'addItem', 'edit', 'delete']);

const isExpanded = ref(false);

const hasChildren = computed(() => props.row.children && props.row.children.length > 0);

const toggleExpand = () => {
    if (hasChildren.value) {
        isExpanded.value = !isExpanded.value;
    }
};

const formatCurrency = (value) => {
    if (value == null) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(value);
};

const formatNumber = (value) => {
    if (value == null) return '';
    return new Intl.NumberFormat('id-ID').format(value);
};

const formatVolumes = (row) => {
    if (row.type !== 'item') return '';
    const parts = [];
    if (row.vol_1) parts.push(`${formatNumber(row.vol_1)} ${row.satuan_1 || ''}`.trim());
    if (row.vol_2) parts.push(`${formatNumber(row.vol_2)} ${row.satuan_2 || ''}`.trim());
    if (row.vol_3) parts.push(`${formatNumber(row.vol_3)} ${row.satuan_3 || ''}`.trim());
    if (row.vol_4) parts.push(`${formatNumber(row.vol_4)} ${row.satuan_4 || ''}`.trim());
    
    return parts.join(' x ');
};

// Calculate dynamic padding based on hierarchy level
const paddingLeft = computed(() => {
    // base padding is 0.5rem (pl-2), add 1.5rem (24px) for each level
    return `padding-left: calc(0.5rem + ${props.level * 24}px)`;
});
</script>

<template>
    <!-- Render Current Row -->
    <TableRow :class="[row.type === 'header' ? 'bg-muted/30 font-medium' : '']">
        <!-- Uraian -->
        <TableCell :style="paddingLeft" class="align-top py-3 max-w-[500px]">
            <div class="flex items-start gap-1">
                <button
                    v-if="row.type === 'header'"
                    @click="toggleExpand"
                    class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded hover:bg-muted text-muted-foreground focus:outline-none mt-0.5"
                    :class="{ 'invisible': !hasChildren }"
                >
                    <ChevronDown v-if="isExpanded" class="w-4 h-4" />
                    <ChevronRight v-else class="w-4 h-4" />
                </button>
                <div v-else class="w-5 h-5 flex-shrink-0"></div> <!-- Spacer for items -->
                
                <span class="whitespace-normal break-words leading-tight pt-1">{{ row.uraian }}</span>
            </div>
        </TableCell>
        
        <!-- Volume Keseluruhan -->
        <TableCell>
            <span v-if="row.type === 'item'" class="text-muted-foreground text-sm">
                {{ formatVolumes(row) }}
            </span>
        </TableCell>
        
        <!-- Harga -->
        <TableCell class="text-right">
            <span v-if="row.type === 'item' && row.harga != null">
                {{ formatCurrency(row.harga) }}
            </span>
        </TableCell>
        
        <!-- Jumlah (Total) -->
        <TableCell class="text-right font-semibold">
            <span v-if="row.jumlah != null">
                {{ formatCurrency(row.jumlah) }}
            </span>
        </TableCell>
        
        <!-- Aksi -->
        <TableCell class="text-right w-[80px]">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                        <MoreVertical class="h-4 w-4" />
                        <span class="sr-only">Buka menu</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <template v-if="row.type === 'header'">
                        <DropdownMenuItem @click="$emit('addHeader', row.id)">
                            <Plus class="mr-2 h-4 w-4" />
                            <span>Tambah Rincian (Grup)</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="$emit('addItem', row.id)">
                            <Plus class="mr-2 h-4 w-4" />
                            <span>Tambah Sub Rincian</span>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                    </template>
                    
                    <DropdownMenuItem @click="$emit('edit', row)">
                        <Edit class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                    </DropdownMenuItem>
                    
                    <DropdownMenuItem @click="$emit('delete', row)" class="text-destructive focus:text-destructive">
                        <Trash2 class="mr-2 h-4 w-4" />
                        <span>Hapus</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </TableCell>
    </TableRow>

    <!-- Render Children Recursively -->
    <template v-if="isExpanded && hasChildren">
        <RbaTreeRow
            v-for="child in row.children"
            :key="child.id"
            :row="child"
            :level="level + 1"
            @addHeader="$emit('addHeader', $event)"
            @addItem="$emit('addItem', $event)"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
        />
    </template>
</template>
