<script setup>
import Navbar from '@/Components/Navbar.vue';
import Sidebar from '@/Components/Sidebar.vue';
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Navbar -->
            <Navbar />

            <!-- Page Header -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <slot />
            </main>
        </div>
    </div>
</template>
