<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CashFlowSection from '@/Pages/Dashboard/CashFlowSection.vue';
import AgingPiutangSection from '@/Pages/Dashboard/AgingPiutangSection.vue';
import VarianceBudgetSection from '@/Pages/Dashboard/VarianceBudgetSection.vue';
</script>

<template>
    <Head title="Dashboard Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-xl text-secondary leading-tight">
                    Dashboard Keuangan
                </h2>
                <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-semibold">
                    RSUD MWM
                </span>
            </div>
        </template>

        <!-- Section 1: Cash Flow -->
        <CashFlowSection />

        <!-- Section 2: Aging Piutang (hidden) -->
        <!-- <AgingPiutangSection /> -->

        <!-- Section 3: Variance Budget -->
        <VarianceBudgetSection />
    </AuthenticatedLayout>
</template>
