<script setup>
import { useSlots } from 'vue';

const slots = useSlots();
</script>

<template>
    <nav class="bg-card border-b border-border/80 flex items-center min-h-16 py-3 px-4 sm:px-6 lg:px-8 shadow-sm relative z-10">
        <!-- Mobile Menu Button -->
        <div class="flex items-center md:hidden mr-4 shrink-0">
            <button class="text-muted-foreground focus:outline-none hover:text-foreground transition-colors">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Header Content (Breadcrumbs & Title passed from layout) -->
        <div class="flex-1 w-full flex items-center">
            <slot />
        </div>
    </nav>
</template>
