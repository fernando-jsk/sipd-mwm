<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BalanceSheetTable from './Components/BalanceSheetTable.vue';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { RefreshCw, Download, Search, AlertTriangle, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    years: {
        type: Array,
        required: true,
        default: () => []
    },
    defaultYear: {
        type: [String, Number],
        required: true
    }
});

const filters = ref({
    date: new Date().toISOString().split('T')[0] // Default to today
});

const asetData = ref([]);
const kewajibanData = ref([]);
const ekuitasData = ref([]);

const summary = ref({
    total_aset: 0,
    total_kewajiban: 0,
    total_ekuitas: 0,
    total_pasiva: 0,
    is_balanced: true
});
const loading = ref(true);

const fetchBalanceSheetData = async () => {
    loading.value = true;
    try {
        const params = {
            date: filters.value.date
        };
        
        const response = await axios.get('/reports/balance-sheet/data', { params });
        asetData.value = response.data.aset;
        kewajibanData.value = response.data.kewajiban;
        ekuitasData.value = response.data.ekuitas;
        summary.value = response.data.summary;
    } catch (error) {
        console.error("Gagal mengambil data Neraca:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchBalanceSheetData();
});

watch(filters, () => {
    fetchBalanceSheetData();
}, { deep: true });

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <Head title="Laporan Neraca (Balance Sheet)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Laporan Neraca
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Posisi kekayaan, kewajiban, dan ekuitas entitas.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="fetchBalanceSheetData" :disabled="loading">
                        <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                        Segarkan
                    </Button>
                    <Button variant="default" size="sm" class="hidden sm:flex">
                        <Download class="w-4 h-4 mr-2" />
                        Unduh
                    </Button>
                </div>
            </div>
        </template>

        <!-- Filter Section -->
        <Card class="mb-4">
            <CardHeader class="border-b border-border/50">
                <CardTitle class="text-sm font-semibold flex items-center gap-2">
                    <Search class="w-4 h-4 text-muted-foreground" />
                    Parameter Laporan
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="space-y-1.5 flex flex-col">
                        <label class="text-xs font-medium text-muted-foreground">Per Tanggal</label>
                        <input 
                            type="date" 
                            v-model="filters.date"
                            class="flex h-10 w-full md:w-64 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Balance Indicator -->
        <Card class="mb-4" :class="summary.is_balanced ? 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200' : 'bg-rose-50 dark:bg-rose-950/20 border-rose-200'">
            <CardContent class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div :class="summary.is_balanced ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'" class="p-2 rounded-full">
                        <CheckCircle2 v-if="summary.is_balanced" class="w-5 h-5" />
                        <AlertTriangle v-else class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-sm" :class="summary.is_balanced ? 'text-emerald-800' : 'text-rose-800'">
                            {{ summary.is_balanced ? 'Neraca Seimbang (Balanced)' : 'Neraca Tidak Seimbang (Unbalanced)' }}
                        </h3>
                        <p class="text-xs" :class="summary.is_balanced ? 'text-emerald-600' : 'text-rose-600'">
                            Total Aset sama dengan Total Kewajiban + Ekuitas.
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-muted-foreground">Selisih:</div>
                    <div class="font-bold" :class="summary.is_balanced ? 'text-emerald-700' : 'text-rose-700'">
                        {{ formatCurrency(Math.abs(summary.total_aset - summary.total_pasiva)) }}
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Split View for Balance Sheet -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
            
            <!-- Left Side: ASET -->
            <div class="space-y-4">
                <BalanceSheetTable 
                    title="AKTIVA (ASET)" 
                    :data="asetData" 
                />
                
                <Card class="bg-muted/10">
                    <CardContent class="flex justify-between items-center font-bold text-lg text-primary">
                        <span>TOTAL ASET</span>
                        <span>{{ formatCurrency(summary.total_aset) }}</span>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Side: KEWAJIBAN & EKUITAS -->
            <div class="space-y-4">
                <BalanceSheetTable 
                    title="PASIVA (KEWAJIBAN)" 
                    :data="kewajibanData" 
                />
                <BalanceSheetTable 
                    title="EKUITAS" 
                    :data="ekuitasData" 
                />
                
                <Card class="bg-muted/10">
                    <CardContent class="flex justify-between items-center font-bold text-lg text-primary">
                        <span>TOTAL KEWAJIBAN + EKUITAS</span>
                        <span>{{ formatCurrency(summary.total_pasiva) }}</span>
                    </CardContent>
                </Card>
            </div>

        </div>

    </AuthenticatedLayout>
</template>
