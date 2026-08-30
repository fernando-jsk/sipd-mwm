<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LoTable from './Components/LoTable.vue';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { RefreshCw, Download, Calendar, ArrowDownRight, ArrowUpRight, Scale } from 'lucide-vue-next';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

const props = defineProps({
    year: {
        type: [String, Number],
        required: true
    }
});

const activeYear = ref(props.year.toString());
const pendapatanData = ref([]);
const bebanData = ref([]);
const nonOperasionalData = ref([]);
const summary = ref({
    total_pendapatan: 0,
    total_beban: 0,
    surplus_defisit_operasi: 0,
    total_non_operasional: 0,
    surplus_defisit_lo: 0
});
const loading = ref(true);

const fetchLoData = async () => {
    loading.value = true;
    try {
        const params = {
            year: activeYear.value
        };
        
        const response = await axios.get('/reports/lo/data', { params });
        pendapatanData.value = response.data.pendapatan || [];
        bebanData.value = response.data.beban || [];
        nonOperasionalData.value = response.data.non_operasional || [];
        summary.value = response.data.summary || {
            total_pendapatan: 0,
            total_beban: 0,
            surplus_defisit_operasi: 0,
            total_non_operasional: 0,
            surplus_defisit_lo: 0
        };
    } catch (error) {
        console.error("Gagal mengambil data LO:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchLoData();
});

watch(activeYear, () => {
    router.get('/reports/lo', { year: activeYear.value }, {
        preserveState: true,
        replace: true
    });
    fetchLoData();
});

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
};
</script>

<template>
    <Head title="Laporan Operasional (LO)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Laporan Operasional (LO)
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Ikhtisar pendapatan dan beban operasional berbasis akrual.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="fetchLoData" :disabled="loading">
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
                    <Calendar class="w-4 h-4 text-muted-foreground" />
                    Parameter Laporan
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="space-y-1.5 flex flex-col w-full md:w-64">
                        <label class="text-xs font-medium text-muted-foreground">Tahun Anggaran</label>
                        <Select v-model="activeYear">
                            <SelectTrigger class="w-full h-10">
                                <SelectValue placeholder="Pilih Tahun" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="2025">Tahun 2025</SelectItem>
                                    <SelectItem value="2026">Tahun 2026</SelectItem>
                                    <SelectItem value="2027">Tahun 2027</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <Card class="bg-emerald-50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/50">
                <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-sm font-medium text-emerald-800 dark:text-emerald-300">
                        Total Pendapatan - LO
                    </CardTitle>
                    <ArrowDownRight class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                        {{ formatCurrency(summary.total_pendapatan) }}
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                        Hak entitas penambah ekuitas
                    </p>
                </CardContent>
            </Card>

            <Card class="bg-rose-50 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/50">
                <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-sm font-medium text-rose-800 dark:text-rose-300">
                        Total Beban - LO
                    </CardTitle>
                    <ArrowUpRight class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-rose-900 dark:text-rose-100">
                        {{ formatCurrency(summary.total_beban) }}
                    </div>
                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">
                        Kewajiban penurun ekuitas
                    </p>
                </CardContent>
            </Card>

            <Card :class="summary.surplus_defisit_lo >= 0 ? 'bg-blue-50 dark:bg-blue-950/20 border-blue-100 dark:border-blue-900/50' : 'bg-amber-50 dark:bg-amber-950/20 border-amber-100 dark:border-amber-900/50'">
                <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-sm font-medium" :class="summary.surplus_defisit_lo >= 0 ? 'text-blue-800 dark:text-blue-300' : 'text-amber-800 dark:text-amber-300'">
                        {{ summary.surplus_defisit_lo >= 0 ? 'Surplus - LO' : 'Defisit - LO' }}
                    </CardTitle>
                    <Scale class="w-4 h-4" :class="summary.surplus_defisit_lo >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold" :class="summary.surplus_defisit_lo >= 0 ? 'text-blue-900 dark:text-blue-100' : 'text-rose-700 dark:text-rose-400'">
                        {{ formatCurrency(summary.surplus_defisit_lo) }}
                    </div>
                    <p class="text-xs mt-1" :class="summary.surplus_defisit_lo >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'">
                        Hasil operasional bersih periode {{ activeYear }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Tables Section -->
        <div class="space-y-4">
            <!-- 1. PENDAPATAN - LO -->
            <LoTable 
                title="1. PENDAPATAN - LO" 
                :data="pendapatanData" 
            />

            <!-- 2. BEBAN - LO -->
            <LoTable 
                title="2. BEBAN - LO" 
                :data="bebanData" 
            />

            <!-- 3. KEGIATAN NON-OPERASIONAL (Jika ada data) -->
            <LoTable 
                v-if="nonOperasionalData.length"
                title="3. KEGIATAN NON-OPERASIONAL" 
                :data="nonOperasionalData" 
            />

            <!-- Final Result Card -->
            <Card class="bg-primary/5 border-primary/20">
                <CardContent class="flex flex-col sm:flex-row justify-between items-center font-bold text-lg text-primary gap-2">
                    <span class="uppercase tracking-wider">SURPLUS / (DEFISIT) - LO</span>
                    <span class="text-xl" :class="summary.surplus_defisit_lo < 0 ? 'text-rose-600' : 'text-primary'">
                        {{ formatCurrency(summary.surplus_defisit_lo) }}
                    </span>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
