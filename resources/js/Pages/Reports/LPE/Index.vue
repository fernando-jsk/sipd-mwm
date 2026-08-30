<script setup>
import { Head, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { RefreshCw, Download, Calendar } from 'lucide-vue-next';
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
const lpeData = ref({
    ekuitas_awal: 0,
    surplus_defisit: 0,
    koreksi: 0,
    ekuitas_akhir: 0
});
const loading = ref(true);

const fetchLpeData = async () => {
    loading.value = true;
    try {
        const params = {
            year: activeYear.value
        };
        
        const response = await axios.get('/reports/lpe/data', { params });
        lpeData.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil data LPE:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchLpeData();
});

watch(activeYear, () => {
    router.get('/reports/lpe', { year: activeYear.value }, {
        preserveState: true,
        replace: true
    });
    fetchLpeData();
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
    <Head title="Laporan Perubahan Ekuitas (LPE)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Laporan Perubahan Ekuitas (LPE)
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Menyajikan informasi kenaikan atau penurunan ekuitas tahun pelaporan.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="fetchLpeData" :disabled="loading">
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

        <!-- LPE Report Table -->
        <Card>
            <CardHeader class="border-b border-border/50">
                <CardTitle class="text-sm font-semibold text-primary uppercase tracking-wider text-center">
                    Laporan Perubahan Ekuitas <br/>
                    Untuk Tahun Yang Berakhir Sampai Dengan 31 Desember {{ activeYear }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="rounded-md border border-border/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-muted-foreground uppercase bg-muted/50 border-b border-border/50">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-center w-16">NO</th>
                                    <th class="px-4 py-3 font-semibold">URAIAN</th>
                                    <th class="px-4 py-3 font-semibold text-right">NILAI (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50">
                                <!-- Ekuitas Awal -->
                                <tr class="hover:bg-muted/30 transition-colors">
                                    <td class="px-4 py-3 text-center text-muted-foreground">1</td>
                                    <td class="px-4 py-3 font-medium">Ekuitas Awal</td>
                                    <td class="px-4 py-3 text-right font-medium text-foreground">
                                        {{ formatCurrency(lpeData.ekuitas_awal) }}
                                    </td>
                                </tr>
                                
                                <!-- Surplus/Defisit-LO -->
                                <tr class="hover:bg-muted/30 transition-colors">
                                    <td class="px-4 py-3 text-center text-muted-foreground">2</td>
                                    <td class="px-4 py-3 font-medium">Surplus/Defisit-LO</td>
                                    <td class="px-4 py-3 text-right font-medium" :class="lpeData.surplus_defisit < 0 ? 'text-rose-600' : 'text-emerald-600'">
                                        {{ formatCurrency(lpeData.surplus_defisit) }}
                                    </td>
                                </tr>

                                <!-- Koreksi Ekuitas -->
                                <tr class="hover:bg-muted/30 transition-colors">
                                    <td class="px-4 py-3 text-center text-muted-foreground">3</td>
                                    <td class="px-4 py-3 font-medium flex flex-col">
                                        <span>Koreksi & Dampak Kumulatif</span>
                                        <span class="text-xs text-muted-foreground font-normal">Mutasi tambah/kurang langsung pada akun ekuitas</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-foreground">
                                        {{ formatCurrency(lpeData.koreksi) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-primary/5 font-bold border-t-2 border-primary/20">
                                    <td class="px-4 py-4 text-center">4</td>
                                    <td class="px-4 py-4 text-primary uppercase">Ekuitas Akhir</td>
                                    <td class="px-4 py-4 text-right text-primary text-base">
                                        {{ formatCurrency(lpeData.ekuitas_akhir) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AuthenticatedLayout>
</template>
