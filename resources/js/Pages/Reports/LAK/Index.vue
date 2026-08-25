<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LakTable from './Components/LakTable.vue';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import {
    Card,
    CardContent,
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
import { RefreshCw, Download, ArrowUpRight, ArrowDownRight, Wallet, Search } from 'lucide-vue-next';

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
    period: 'all'
});

const lakData = ref([]);
const summary = ref({
    total_inflow: 0,
    total_outflow: 0,
    net_cash_flow: 0
});
const loading = ref(true);

const fetchLakData = async () => {
    loading.value = true;
    try {
        const params = {
            year: filters.value.year
        };
        
        if (filters.value.period !== 'all') {
            params.period = filters.value.period;
        }
        
        const response = await axios.get('/reports/lak/data', { params });
        lakData.value = response.data.tree;
        summary.value = response.data.summary;
    } catch (error) {
        console.error("Gagal mengambil data LAK:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchLakData();
});

watch(filters, () => {
    fetchLakData();
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
    <Head title="Laporan Arus Kas (LAK)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Laporan Arus Kas (LAK)
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Pantau pergerakan arus kas riil berdasarkan aktivitas operasi, investasi, dan pendanaan.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="fetchLakData" :disabled="loading">
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
                            <Select v-model="filters.period">
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Periode" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="all">Satu Tahun Penuh</SelectItem>
                                        <SelectItem value="s1">Semester 1 (Jan - Jun)</SelectItem>
                                        <SelectItem value="s2">Semester 2 (Jul - Des)</SelectItem>
                                        <SelectItem value="q1">Kuartal 1 (Jan - Mar)</SelectItem>
                                        <SelectItem value="q2">Kuartal 2 (Apr - Jun)</SelectItem>
                                        <SelectItem value="q3">Kuartal 3 (Jul - Sep)</SelectItem>
                                        <SelectItem value="q4">Kuartal 4 (Okt - Des)</SelectItem>
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
                            Total Arus Kas Masuk
                        </CardTitle>
                        <ArrowDownRight class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                            {{ formatCurrency(summary.total_inflow) }}
                        </div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                            Akumulasi seluruh penerimaan kas riil
                        </p>
                    </CardContent>
                </Card>

                <Card class="bg-rose-50 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/50">
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-rose-800 dark:text-rose-300">
                            Total Arus Kas Keluar
                        </CardTitle>
                        <ArrowUpRight class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-rose-900 dark:text-rose-100">
                            {{ formatCurrency(summary.total_outflow) }}
                        </div>
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">
                            Akumulasi seluruh pengeluaran kas riil
                        </p>
                    </CardContent>
                </Card>

                <Card :class="summary.net_cash_flow >= 0 ? 'bg-blue-50 dark:bg-blue-950/20 border-blue-100 dark:border-blue-900/50' : 'bg-amber-50 dark:bg-amber-950/20 border-amber-100 dark:border-amber-900/50'">
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium" :class="summary.net_cash_flow >= 0 ? 'text-blue-800 dark:text-blue-300' : 'text-amber-800 dark:text-amber-300'">
                            Kenaikan / (Penurunan) Kas Bersih
                        </CardTitle>
                        <Wallet class="w-4 h-4" :class="summary.net_cash_flow >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="summary.net_cash_flow >= 0 ? 'text-blue-900 dark:text-blue-100' : 'text-amber-900 dark:text-amber-100'">
                            {{ formatCurrency(summary.net_cash_flow) }}
                        </div>
                        <p class="text-xs mt-1" :class="summary.net_cash_flow >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'">
                            Ekuivalen dengan SiLPA (Arus Masuk dikurangi Arus Keluar)
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Table -->
            <LakTable :data="lakData" :loading="loading" />
    </AuthenticatedLayout>
</template>
