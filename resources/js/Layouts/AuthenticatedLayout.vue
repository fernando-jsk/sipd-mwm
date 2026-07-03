<script setup>
import Navbar from '@/Components/Navbar.vue';
import Sidebar from '@/Components/Sidebar.vue';
</script>

<template>
    <div class="h-screen bg-background text-foreground flex overflow-hidden">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Navbar / Header Area -->
            <Navbar>
                <template v-if="$slots.header">
                    <slot name="header" />
                </template>
            </Navbar>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
                <!-- Outer container based on STYLE_GUIDE -->
                <div class="max-w-7xl mx-auto space-y-6">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
