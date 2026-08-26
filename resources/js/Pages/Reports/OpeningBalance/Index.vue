<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, onMounted, computed } from 'vue';
import axios from 'axios';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { RefreshCw, Search, Save, AlertTriangle, CheckCircle } from 'lucide-vue-next';

const successMessage = ref(null);
const errorMessage = ref(null);

const currentYear = new Date().getFullYear();
const selectedYear = ref(currentYear);

const data = ref([]);
const loading = ref(false);
const saving = ref(false);

const fetchData = async () => {
    loading.value = true;
    try {
        errorMessage.value = null;
        successMessage.value = null;
        const response = await axios.get('/reports/opening-balance/data', { params: { year: selectedYear.value } });
        // Mengubah string nilai dari db menjadi number agar bisa di-bind ke v-model number type properly, meski server return double/string
        data.value = response.data.data.map(item => ({
            ...item,
            debit: parseFloat(item.debit) || 0,
            credit: parseFloat(item.credit) || 0,
        }));
    } catch (error) {
        console.error("Gagal mengambil data Saldo Awal:", error);
        errorMessage.value = "Terjadi kesalahan saat memuat form saldo awal.";
    } finally {
        loading.value = false;
    }
};

const totalDebit = computed(() => {
    return data.value.reduce((sum, item) => sum + (parseFloat(item.debit) || 0), 0);
});

const totalCredit = computed(() => {
    return data.value.reduce((sum, item) => sum + (parseFloat(item.credit) || 0), 0);
});

const isBalanced = computed(() => {
    // Gunakan Math.round untuk meminimalkan isu floating point precision
    return Math.round(totalDebit.value * 100) === Math.round(totalCredit.value * 100);
});

const formatCurrency = (value) => {
    if (!value || value === 0) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const saveData = async () => {
    successMessage.value = null;
    errorMessage.value = null;
    
    if (!isBalanced.value) {
        errorMessage.value = "Total Debit dan Kredit harus sama sebelum disimpan.";
        return;
    }

    saving.value = true;
    try {
        await axios.post('/reports/opening-balance', {
            year: selectedYear.value,
            balances: data.value.map(item => ({
                id: item.id,
                debit: parseFloat(item.debit) || 0,
                credit: parseFloat(item.credit) || 0
            }))
        });

        successMessage.value = "Data Saldo Awal berhasil disimpan.";
        
        await fetchData();
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Terjadi kesalahan pada server.";
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head title="Saldo Awal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Saldo Awal
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Form input posisi neraca awal tahun. Hanya akun riil (awalan 1, 2, 3) yang ditampilkan.
                    </p>
                </div>
            </div>
        </template>

        <div v-if="successMessage" class="mb-4 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-200 p-4 flex items-center gap-3">
            <CheckCircle class="w-5 h-5" />
            <div>{{ successMessage }}</div>
        </div>

        <div v-if="errorMessage" class="mb-4 bg-red-50 text-red-800 rounded-lg border border-red-200 p-4 flex items-center gap-3">
            <AlertTriangle class="w-5 h-5" />
            <div>{{ errorMessage }}</div>
        </div>

        <Card class="mb-6">
            <CardHeader class="pb-3 border-b border-border/50">
                <CardTitle class="text-sm font-semibold flex items-center gap-2">
                    <Search class="w-4 h-4 text-muted-foreground" />
                    Pilih Tahun Anggaran
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/4 space-y-1.5">
                        <Label>Tahun</Label>
                        <Input type="number" v-model="selectedYear" />
                    </div>

                    <div class="w-full md:w-auto">
                        <Button variant="outline" class="w-full md:w-auto" @click="fetchData" :disabled="loading">
                            <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                            Muat Ulang
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="space-y-4">
            <Card class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="w-[300px]">Kode / Nama Akun</TableHead>
                                <TableHead class="text-center w-[250px]">Debit (Rp)</TableHead>
                                <TableHead class="text-center w-[250px]">Kredit (Rp)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in data" :key="item.id" class="hover:bg-muted/30">
                                <TableCell>
                                    <div class="font-medium">{{ item.code }}</div>
                                    <div class="text-xs text-muted-foreground">{{ item.name }}</div>
                                </TableCell>
                                <TableCell>
                                    <Input 
                                        type="number" 
                                        step="0.01" 
                                        min="0"
                                        v-model.number="item.debit" 
                                        class="text-right h-8" 
                                        :disabled="saving"
                                        @input="item.credit = 0"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Input 
                                        type="number" 
                                        step="0.01" 
                                        min="0"
                                        v-model.number="item.credit" 
                                        class="text-right h-8" 
                                        :disabled="saving"
                                        @input="item.debit = 0"
                                    />
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="data.length === 0 && !loading">
                                <TableCell colspan="3" class="h-24 text-center text-muted-foreground">
                                    Data akun tidak ditemukan.
                                </TableCell>
                            </TableRow>
                            
                            <TableRow v-if="loading">
                                <TableCell colspan="3" class="h-24 text-center text-muted-foreground">
                                    Memuat data...
                                </TableCell>
                            </TableRow>

                            <!-- Grand Total -->
                            <TableRow class="bg-muted/50 font-bold border-t-2 border-border" v-if="data.length > 0">
                                <TableCell class="text-right uppercase">Total Saldo Awal</TableCell>
                                <TableCell class="text-right text-lg" :class="isBalanced ? 'text-emerald-600' : 'text-red-600'">
                                    {{ formatCurrency(totalDebit) }}
                                </TableCell>
                                <TableCell class="text-right text-lg" :class="isBalanced ? 'text-emerald-600' : 'text-red-600'">
                                    {{ formatCurrency(totalCredit) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <div v-if="data.length > 0 && !isBalanced" class="p-4 bg-red-50 text-red-800 rounded-lg border border-red-200 flex items-start gap-3">
                <AlertTriangle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <div>
                    <strong>Peringatan!</strong> Saldo Awal tidak seimbang (Unbalanced). 
                    Terdapat selisih sebesar {{ formatCurrency(Math.abs(totalDebit - totalCredit)) }}. Sistem menolak untuk menyimpan data yang tidak seimbang.
                </div>
            </div>

            <div v-if="data.length > 0" class="flex justify-end mt-4">
                <Button @click="saveData" :disabled="saving || !isBalanced" size="lg" class="w-full md:w-auto">
                    <Save class="w-4 h-4 mr-2" :class="{'animate-spin': saving}" />
                    Simpan Saldo Awal
                </Button>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
