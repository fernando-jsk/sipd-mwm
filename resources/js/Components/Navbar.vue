<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav class="bg-white border-b border-gray-100 flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center">
            <!-- Mobile menu button, could be implemented later -->
            <button class="text-gray-500 focus:outline-none focus:text-gray-700 md:hidden">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div class="flex items-center">
            <!-- Dropdown for User -->
            <div class="relative flex items-center gap-4">
                <div class="text-sm font-medium text-gray-500">{{ $page.props.auth?.user?.name }}</div>
                
                <Link href="/logout" method="post" as="button" class="text-sm text-gray-700 underline hover:text-gray-900 focus:outline-none">
                    Log Out
                </Link>
            </div>
        </div>
    </nav>
</template>
