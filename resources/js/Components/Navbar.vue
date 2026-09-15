<script setup>
import { useSlots } from 'vue';
import { useSidebar } from '@/Composables/useSidebar';
import { PanelLeft, PanelLeftClose, Menu } from 'lucide-vue-next';

const slots = useSlots();
const { isCollapsed, toggleSidebar, toggleMobile } = useSidebar();
</script>

<template>
    <nav class="bg-card border-b border-border/80 flex items-center min-h-16 py-3 px-4 sm:px-6 lg:px-8 shadow-sm relative z-10">
        <!-- Desktop Sidebar Toggle Button -->
        <button
            type="button"
            @click="toggleSidebar"
            class="hidden md:inline-flex items-center justify-center size-9 mr-3 rounded-lg border border-border/80 bg-background/50 text-muted-foreground hover:text-foreground hover:bg-muted transition-all duration-200 cursor-pointer shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
            :title="isCollapsed ? 'Buka Sidebar (Ctrl+B)' : 'Tutup Sidebar (Ctrl+B)'"
            aria-label="Toggle Sidebar"
        >
            <PanelLeftClose v-if="!isCollapsed" class="size-4.5" />
            <PanelLeft v-else class="size-4.5 text-primary" />
        </button>

        <!-- Mobile Menu Button -->
        <div class="flex items-center md:hidden mr-3 shrink-0">
            <button
                type="button"
                @click="toggleMobile"
                class="inline-flex items-center justify-center size-9 rounded-lg border border-border/80 bg-background/50 text-muted-foreground hover:text-foreground hover:bg-muted transition-all duration-200 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                aria-label="Buka Menu Navigasi"
            >
                <Menu class="size-5" />
            </button>
        </div>

        <!-- Header Content (Breadcrumbs & Title passed from layout) -->
        <div class="flex-1 w-full flex items-center min-w-0">
            <slot />
        </div>
    </nav>
</template>
