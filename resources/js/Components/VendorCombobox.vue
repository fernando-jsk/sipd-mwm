<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import {
    PopoverRoot,
    PopoverTrigger,
    PopoverContent,
    PopoverPortal,
} from 'reka-ui';
import { Search, ChevronsUpDown, Check, X, Building2, CreditCard } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: '',
    },
    vendors: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Pilih Rekanan',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: 'vendor_id',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'select']);

const isOpen = ref(false);
const searchQuery = ref('');
const searchInputRef = ref(null);
const listRef = ref(null);
const highlightedIndex = ref(0);

const selectedVendor = computed(() => {
    if (!props.modelValue && props.modelValue !== 0) return null;
    return props.vendors.find(v => v.id?.toString() === props.modelValue?.toString()) || null;
});

const isSelected = (id) => {
    if (!props.modelValue && props.modelValue !== 0) return false;
    return props.modelValue?.toString() === id?.toString();
};

const filteredVendors = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.vendors;

    return props.vendors.filter(vendor => {
        const name = (vendor.name || '').toLowerCase();
        const bankName = (vendor.bank_name || '').toLowerCase();
        const bankAccount = (vendor.bank_account_number || '').toLowerCase();
        const type = (vendor.type || '').toLowerCase();
        const director = (vendor.director_name || '').toLowerCase();

        return name.includes(q) 
            || bankName.includes(q) 
            || bankAccount.includes(q) 
            || type.includes(q) 
            || director.includes(q);
    });
});

const scrollToHighlighted = () => {
    nextTick(() => {
        if (!listRef.value) return;
        const item = listRef.value.querySelector(`[data-index="${highlightedIndex.value}"]`);
        if (item) {
            item.scrollIntoView({ block: 'nearest' });
        }
    });
};

watch(isOpen, async (open) => {
    if (open) {
        searchQuery.value = '';
        const currentIndex = filteredVendors.value.findIndex(v => isSelected(v.id));
        highlightedIndex.value = currentIndex >= 0 ? currentIndex : 0;
        await nextTick();
        searchInputRef.value?.focus();
        scrollToHighlighted();
    }
});

watch(searchQuery, () => {
    highlightedIndex.value = 0;
    if (listRef.value) {
        listRef.value.scrollTop = 0;
    }
});

const onKeyDown = () => {
    if (filteredVendors.value.length === 0) return;
    highlightedIndex.value = (highlightedIndex.value + 1) % filteredVendors.value.length;
    scrollToHighlighted();
};

const onKeyUp = () => {
    if (filteredVendors.value.length === 0) return;
    highlightedIndex.value = (highlightedIndex.value - 1 + filteredVendors.value.length) % filteredVendors.value.length;
    scrollToHighlighted();
};

const onKeyEnter = () => {
    if (filteredVendors.value.length > 0 && filteredVendors.value[highlightedIndex.value]) {
        selectVendor(filteredVendors.value[highlightedIndex.value]);
    }
};

const selectVendor = (vendor) => {
    const val = vendor ? vendor.id.toString() : '';
    emit('update:modelValue', val);
    emit('select', vendor);
    isOpen.value = false;
};

const clearSelection = (e) => {
    e?.stopPropagation?.();
    emit('update:modelValue', '');
    emit('select', null);
};
</script>

<template>
    <PopoverRoot v-model:open="isOpen">
        <PopoverTrigger as-child :disabled="disabled">
            <button
                :id="id"
                type="button"
                role="combobox"
                :aria-expanded="isOpen"
                :disabled="disabled"
                class="flex h-9 w-full min-w-0 items-center justify-between rounded-lg border border-input bg-transparent py-2 pr-2.5 pl-3 text-sm transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 hover:bg-accent/40"
                :class="{ 'border-destructive focus-visible:ring-destructive/20': !!error }"
            >
                <div class="flex items-center gap-2 min-w-0 flex-1 text-left">
                    <Building2 class="w-4 h-4 text-muted-foreground shrink-0" />
                    <span v-if="selectedVendor" class="truncate font-medium text-foreground">
                        {{ selectedVendor.name }}
                        <span v-if="selectedVendor.type" class="ml-1 text-[11px] font-normal text-muted-foreground">
                            ({{ selectedVendor.type }})
                        </span>
                    </span>
                    <span v-else class="text-muted-foreground truncate">
                        {{ placeholder }}
                    </span>
                </div>

                <div class="flex items-center gap-1 shrink-0 ml-2">
                    <span
                        v-if="selectedVendor && !disabled"
                        role="button"
                        title="Hapus pilihan"
                        tabindex="0"
                        @click.stop="clearSelection"
                        @keydown.enter.stop="clearSelection"
                        class="p-0.5 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                    >
                        <X class="w-3.5 h-3.5" />
                    </span>
                    <ChevronsUpDown class="h-4 w-4 shrink-0 text-muted-foreground" />
                </div>
            </button>
        </PopoverTrigger>

        <PopoverPortal>
            <PopoverContent
                side="bottom"
                align="start"
                :side-offset="4"
                class="z-50 w-[var(--reka-popover-trigger-width)] min-w-[320px] max-w-[550px] rounded-xl border border-border/80 bg-popover p-0 text-popover-foreground shadow-xl outline-none overflow-hidden"
            >
                <!-- Search Input Header -->
                <div class="flex items-center border-b border-border/60 px-3 py-2 bg-muted/20 gap-2">
                    <Search class="h-4 w-4 shrink-0 text-muted-foreground" />
                    <input
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Ketik nama rekanan, bank, atau rekening..."
                        class="w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                        @keydown.down.prevent="onKeyDown"
                        @keydown.up.prevent="onKeyUp"
                        @keydown.enter.prevent="onKeyEnter"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="text-muted-foreground hover:text-foreground p-0.5 rounded transition-colors"
                        title="Hapus kata pencarian"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                    <span class="text-[11px] text-muted-foreground font-medium shrink-0 pl-1">
                        {{ filteredVendors.length }}
                    </span>
                </div>

                <!-- Vendors List -->
                <div ref="listRef" class="max-h-[280px] overflow-y-auto p-1.5 space-y-0.5 text-sm">
                    <div
                        v-for="(vendor, index) in filteredVendors"
                        :key="vendor.id"
                        :data-index="index"
                        @click="selectVendor(vendor)"
                        @mouseenter="highlightedIndex = index"
                        class="flex items-center justify-between px-2.5 py-2 rounded-lg cursor-pointer transition-colors duration-150 text-left select-none"
                        :class="[
                            isSelected(vendor.id)
                                ? 'bg-primary/10 text-primary font-medium'
                                : (highlightedIndex === index ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60 text-foreground')
                        ]"
                    >
                        <div class="flex items-start gap-2.5 min-w-0 flex-1">
                            <div
                                class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 mt-0.5 transition-colors"
                                :class="isSelected(vendor.id) ? 'bg-primary/20 text-primary' : 'bg-muted text-muted-foreground'"
                            >
                                <Building2 class="w-3.5 h-3.5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-sm font-semibold truncate leading-tight">
                                        {{ vendor.name }}
                                    </span>
                                    <span v-if="vendor.type" class="text-[10px] px-1.5 py-0.2 rounded bg-muted text-muted-foreground font-medium shrink-0 leading-none">
                                        {{ vendor.type }}
                                    </span>
                                </div>
                                <div v-if="vendor.bank_name || vendor.bank_account_number || vendor.director_name" class="text-[11px] text-muted-foreground flex items-center gap-2 mt-1 truncate">
                                    <span v-if="vendor.bank_name || vendor.bank_account_number" class="flex items-center gap-1 truncate">
                                        <CreditCard class="w-3 h-3 shrink-0 opacity-70" />
                                        <span class="truncate">{{ [vendor.bank_name, vendor.bank_account_number].filter(Boolean).join(' • ') }}</span>
                                    </span>
                                    <span v-if="vendor.director_name" class="truncate border-l border-border/80 pl-2">
                                        {{ vendor.director_name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <Check v-if="isSelected(vendor.id)" class="ml-2 h-4 w-4 shrink-0 text-primary" />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredVendors.length === 0"
                        class="py-8 px-4 text-center select-none"
                    >
                        <Building2 class="w-8 h-8 mx-auto text-muted-foreground/30 mb-2" />
                        <p class="text-sm font-medium text-foreground">Rekanan tidak ditemukan</p>
                        <p class="text-xs text-muted-foreground mt-0.5 max-w-[280px] mx-auto">
                            Tidak ada rekanan yang sesuai dengan pencarian "<span class="font-semibold text-foreground">{{ searchQuery }}</span>".
                        </p>
                    </div>
                </div>

                <!-- Footer Summary -->
                <div class="px-3 py-1.5 bg-muted/20 border-t border-border/60 text-[11px] text-muted-foreground flex items-center justify-between">
                    <span>Total Rekanan: {{ vendors.length }}</span>
                    <span v-if="searchQuery" class="text-primary font-medium">Hasil: {{ filteredVendors.length }}</span>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
