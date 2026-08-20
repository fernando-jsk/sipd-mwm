<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LraTable from './Components/LraTable.vue';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Button } from '@/Components/ui/button';
import { RefreshCw, Download, FileText, ArrowUpRight, ArrowDownRight, Wallet, Search } from 'lucide-vue-next';

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
    year: props.defaultYear.toString(),
    month: 'all',
    version: 'all'
});

const lraData = ref([]);
const summary = ref({
    total_revenue_budget: 0,
    total_revenue_realization: 0,
    total_expenditure_budget: 0,
    total_expenditure_realization: 0,
    silpa: 0
});
const loading = ref(true);

const fetchLraData = async () => {
    loading.value = true;
    try {
        const params = {
            year: filters.value.year
        };
        
        if (filters.value.month !== 'all') {
            params.month = filters.value.month;
        }
        
        if (filters.value.version !== 'all') {
            params.version = filters.value.version;
        }
        
        const response = await axios.get('/reports/lra/data', { params });
        lraData.value = response.data.tree;
        summary.value = response.data.summary;
    } catch (error) {
        console.error("Gagal mengambil data LRA:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchLraData();
});

watch(filters, () => {
    fetchLraData();
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

// Calculate percentage
const calculatePercentage = (realization, budget) => {
    if (budget <= 0) return realization > 0 ? 100 : 0;
    return (realization / budget) * 100;
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    }).format(value);
};
</script>

<template>
    <Head title="Laporan Realisasi Anggaran (LRA)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Laporan Realisasi Anggaran
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Pantau realisasi pendapatan dan belanja secara real-time.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="fetchLraData" :disabled="loading">
                        <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                        Segarkan
                    </Button>
                    <!-- Future enhancement: PDF/Excel Export -->
                    <Button variant="default" size="sm" class="hidden sm:flex">
                        <Download class="w-4 h-4 mr-2" />
                        Unduh
                    </Button>
                </div>
            </div>
        </template>

        <!-- Filter Section -->
        <Card>
            <CardHeader class="pb-3 border-b border-border/50">
                <CardTitle class="text-sm font-semibold flex items-center gap-2">
                    <Search class="w-4 h-4 text-muted-foreground" />
                    Filter Data Laporan
                </CardTitle>
            </CardHeader>
            <CardContent class="">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="space-y-1.5">
                            <Select v-model="filters.year">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Tahun" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem v-for="year in years" :key="year" :value="year.toString()">
                                            {{ year }}
                                        </SelectItem>
                                        <!-- Fallback if no years -->
                                        <SelectItem v-if="years.length === 0" :value="defaultYear.toString()">
                                            {{ defaultYear }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div class="space-y-1.5">
                            <Select v-model="filters.month">
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Bulan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="all">Satu Tahun Penuh</SelectItem>
                                        <SelectItem value="1">Januari</SelectItem>
                                        <SelectItem value="2">Februari</SelectItem>
                                        <SelectItem value="3">Maret</SelectItem>
                                        <SelectItem value="4">April</SelectItem>
                                        <SelectItem value="5">Mei</SelectItem>
                                        <SelectItem value="6">Juni</SelectItem>
                                        <SelectItem value="7">Juli</SelectItem>
                                        <SelectItem value="8">Agustus</SelectItem>
                                        <SelectItem value="9">September</SelectItem>
                                        <SelectItem value="10">Oktober</SelectItem>
                                        <SelectItem value="11">November</SelectItem>
                                        <SelectItem value="12">Desember</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="bg-emerald-50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/50">
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-emerald-800 dark:text-emerald-300">
                            Pendapatan (Realisasi)
                        </CardTitle>
                        <ArrowDownRight class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                            {{ formatCurrency(summary.total_revenue_realization) }}
                        </div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center justify-between">
                            <span>Dari target: {{ formatCurrency(summary.total_revenue_budget) }}</span>
                            <span class="font-semibold">{{ formatNumber(calculatePercentage(summary.total_revenue_realization, summary.total_revenue_budget)) }}%</span>
                        </p>
                    </CardContent>
                </Card>

                <Card class="bg-rose-50 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/50">
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-rose-800 dark:text-rose-300">
                            Belanja (Realisasi)
                        </CardTitle>
                        <ArrowUpRight class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-rose-900 dark:text-rose-100">
                            {{ formatCurrency(summary.total_expenditure_realization) }}
                        </div>
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center justify-between">
                            <span>Dari plafon: {{ formatCurrency(summary.total_expenditure_budget) }}</span>
                            <span class="font-semibold">{{ formatNumber(calculatePercentage(summary.total_expenditure_realization, summary.total_expenditure_budget)) }}%</span>
                        </p>
                    </CardContent>
                </Card>

                <Card class="bg-blue-50 dark:bg-blue-950/20 border-blue-100 dark:border-blue-900/50">
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-blue-800 dark:text-blue-300">
                            Sisa Lebih/Kurang (SILPA)
                        </CardTitle>
                        <Wallet class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                            {{ formatCurrency(summary.silpa) }}
                        </div>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            Pendapatan dikurangi Belanja
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Table -->
            <LraTable :data="lraData" :loading="loading" />
    </AuthenticatedLayout>
</template>
