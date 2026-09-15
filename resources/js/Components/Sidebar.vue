<script setup>
import SidebarContent from '@/Components/SidebarContent.vue';
import { useSidebar } from '@/Composables/useSidebar';

const { isCollapsed, isMobileOpen, closeMobile } = useSidebar();
</script>

<template>
    <!-- Desktop Collapsible Sidebar -->
    <aside
        class="bg-card border-r border-border/80 hidden md:flex flex-col h-full shadow-sm shrink-0 transition-all duration-300 ease-in-out relative overflow-hidden select-none"
        :class="isCollapsed ? 'w-0 border-r-0 opacity-0 pointer-events-none' : 'w-64 opacity-100'"
        aria-label="Sidebar Navigasi"
    >
        <!-- Fixed width inner container to ensure content slides smoothly without jittering/squishing -->
        <div class="w-64 h-full flex flex-col overflow-hidden">
            <SidebarContent />
        </div>
    </aside>

    <!-- Mobile Drawer (Slide-over with Backdrop) -->
    <Teleport to="body">
        <!-- Backdrop Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isMobileOpen"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 md:hidden"
                @click="closeMobile"
                aria-hidden="true"
            />
        </Transition>

        <!-- Slide-over Panel -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div
                v-if="isMobileOpen"
                class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-card border-r border-border/80 flex flex-col shadow-2xl md:hidden overflow-hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Menu Navigasi Mobile"
            >
                <SidebarContent :is-mobile="true" @close="closeMobile" />
            </div>
        </Transition>
    </Teleport>
</template>
