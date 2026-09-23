<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import {
    PopoverRoot,
    PopoverTrigger,
    PopoverContent,
    PopoverPortal,
} from 'reka-ui';
import { Search, ChevronsUpDown, Check, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: 'root',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Pilih Induk (Header)',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    allowRoot: {
        type: Boolean,
        default: true,
    },
    rootLabel: {
        type: String,
        default: '-- Tingkat Utama (Tanpa Induk) --',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const searchInputRef = ref(null);

// Reset search query when popover opens and focus input
watch(isOpen, async (open) => {
    if (open) {
        searchQuery.value = '';
        await nextTick();
        searchInputRef.value?.focus();
    }
});

const isSelected = (val) => {
    const current = props.modelValue == null ? 'root' : props.modelValue.toString();
    return current === val.toString();
};

const selectedLabel = computed(() => {
    const current = props.modelValue == null ? 'root' : props.modelValue.toString();
    if (current === 'root' || current === '') {
        return props.allowRoot ? props.rootLabel : props.placeholder;
    }
    const found = props.options.find(opt => opt.id.toString() === current);
    return found ? found.label : props.placeholder;
});

const filteredOptions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter(opt => {
        const text = (opt.uraian || opt.label || '').toLowerCase();
        return text.includes(q);
    });
});

const showRootOption = computed(() => {
    if (!props.allowRoot) return false;
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return true;
    return props.rootLabel.toLowerCase().includes(q) || 'utama'.includes(q) || 'tanpa'.includes(q);
});

const selectValue = (val) => {
    emit('update:modelValue', val.toString());
    isOpen.value = false;
};
</script>

<template>
    <PopoverRoot v-model:open="isOpen">
        <PopoverTrigger as-child :disabled="disabled">
            <button
                type="button"
                role="combobox"
                :aria-expanded="isOpen"
                class="flex h-9 w-full min-w-0 items-center justify-between rounded-lg border border-input bg-transparent py-2 pr-2.5 pl-3 text-sm transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 hover:bg-accent/40"
            >
                <span class="truncate block min-w-0 text-left" :class="{ 'text-muted-foreground': !modelValue || modelValue === 'root' }">
                    {{ selectedLabel }}
                </span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 text-muted-foreground" />
            </button>
        </PopoverTrigger>

        <PopoverPortal>
            <PopoverContent
                side="bottom"
                align="start"
                :side-offset="4"
                class="z-50 w-[var(--reka-popover-trigger-width)] min-w-[320px] max-w-[480px] rounded-lg border bg-popover p-0 text-popover-foreground shadow-lg outline-none overflow-hidden"
            >
                <!-- Search Input Box -->
                <div class="flex items-center border-b px-3 py-2 bg-muted/20">
                    <Search class="mr-2 h-4 w-4 shrink-0 text-muted-foreground" />
                    <input
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Ketik untuk mencari induk..."
                        class="w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="text-muted-foreground hover:text-foreground ml-1"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Options List -->
                <div class="max-h-[260px] overflow-y-auto p-1 text-sm">
                    <!-- Option Root / Tanpa Induk -->
                    <div
                        v-if="showRootOption"
                        @click="selectValue('root')"
                        class="flex items-center justify-between px-2.5 py-1.5 rounded-md cursor-pointer hover:bg-accent hover:text-accent-foreground select-none transition-colors"
                        :class="{ 'bg-accent font-medium text-accent-foreground': isSelected('root') }"
                    >
                        <span class="truncate">{{ rootLabel }}</span>
                        <Check v-if="isSelected('root')" class="ml-2 h-4 w-4 shrink-0 text-primary" />
                    </div>

                    <!-- Filtered Headers -->
                    <div
                        v-for="opt in filteredOptions"
                        :key="opt.id"
                        @click="selectValue(opt.id)"
                        class="flex items-center justify-between px-2.5 py-2 rounded-md cursor-pointer hover:bg-accent hover:text-accent-foreground select-none transition-colors text-left"
                        :class="{ 'bg-accent font-medium text-accent-foreground': isSelected(opt.id) }"
                    >
                        <span class="break-words whitespace-normal leading-snug flex-1 pr-2">
                            {{ opt.label }}
                        </span>
                        <Check v-if="isSelected(opt.id)" class="ml-2 h-4 w-4 shrink-0 text-primary" />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredOptions.length === 0 && !showRootOption"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Induk tidak ditemukan.
                    </div>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
