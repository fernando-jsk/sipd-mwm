<script setup>
</script>

<template>
    <div>
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    <slot name="header" />
                </h2>
            </div>
        </header>
        <main>
            <slot />
        </main>
    </div>
</template>
