<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CashFlowSection from '@/Pages/Dashboard/CashFlowSection.vue';
import AgingPiutangSection from '@/Pages/Dashboard/AgingPiutangSection.vue';
import VarianceBudgetSection from '@/Pages/Dashboard/VarianceBudgetSection.vue';
import CustomizeDashboardModal from '@/Pages/Dashboard/CustomizeDashboardModal.vue';
import { Button } from '@/Components/ui/button';
import { SlidersHorizontal, LayoutDashboard } from 'lucide-vue-next';

const props = defineProps({
    cashFlowData: Object,
    varianceData: Object,
    dashboardWidgets: Object,
});

const page = usePage();

const isCustomizeModalOpen = ref(false);

const activeWidgets = computed(() => {
    return props.dashboardWidgets || page.props.auth?.user?.dashboard_widgets || {};
});

const allWidgetsHidden = computed(() => {
    const w = activeWidgets.value;
    if (!w || Object.keys(w).length === 0) return false;
    return Object.values(w).every(val => val === false);
});
</script>

<template>
    <Head title="Dashboard Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Dashboard Keuangan
                    </h2>
                    <span class="text-xs bg-primary/10 text-primary px-2.5 py-0.5 rounded-full font-semibold">
                        RSUD MWM
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Button 
                        variant="outline" 
                        size="icon" 
                        @click="isCustomizeModalOpen = true"
                        class="border-border/80 hover:bg-muted/80 shadow-xs cursor-pointer text-secondary"
                        title="Atur Tampilan Dashboard"
                    >
                        <SlidersHorizontal class="w-4 h-4 text-primary" />
                        <span class="sr-only">Atur Tampilan Dashboard</span>
                    </Button>
                </div>
            </div>
        </template>

        <!-- Section 1: Cash Flow -->
        <CashFlowSection :data="cashFlowData" :widgets="activeWidgets" />

        <!-- Section 2: Aging Piutang (hidden) -->
        <!-- <AgingPiutangSection /> -->

        <!-- Section 3: Realisasi (Anggaran vs Realisasi) -->
        <VarianceBudgetSection :data="cashFlowData" :varianceData="varianceData" :widgets="activeWidgets" />

        <!-- Empty state jika semua kartu disembunyikan -->
        <div v-if="allWidgetsHidden" class="bg-card border border-dashed border-border/80 rounded-2xl p-10 text-center max-w-md mx-auto my-12 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center mx-auto mb-3">
                <LayoutDashboard class="w-6 h-6" />
            </div>
            <h3 class="text-base font-bold text-secondary">Semua Kartu Disembunyikan</h3>
            <p class="text-xs text-muted-foreground mt-1 mb-5 leading-relaxed">
                Anda telah menonaktifkan tampilan seluruh widget dashboard. Klik tombol di bawah untuk memilih kartu yang ingin ditampilkan kembali.
            </p>
            <Button @click="isCustomizeModalOpen = true" size="sm" class="bg-primary hover:bg-primary/90 text-primary-foreground text-xs shadow-xs cursor-pointer">
                <SlidersHorizontal class="w-3.5 h-3.5 mr-1.5" />
                Atur Tampilan Dashboard
            </Button>
        </div>

        <!-- Customize Modal -->
        <CustomizeDashboardModal 
            :open="isCustomizeModalOpen" 
            :initial-widgets="activeWidgets"
            @update:open="isCustomizeModalOpen = $event" 
        />
    </AuthenticatedLayout>
</template>
