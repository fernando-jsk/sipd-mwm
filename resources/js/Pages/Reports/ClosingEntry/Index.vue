<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription
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
import { RefreshCw, Search, CheckCircle2, Lock, AlertTriangle, CheckCircle } from 'lucide-vue-next';

const successMessage = ref(null);
const errorMessage = ref(null);

const currentYear = new Date().getFullYear();
const selectedYear = ref(currentYear);

const data = ref([]);
const equityAccounts = ref({ sal: null, surplus_defisit: null });
const summary = ref(null);
const status = ref('open');
const closingDate = ref(null);

const loading = ref(false);
const processing = ref(false);

const fetchData = async () => {
    loading.value = true;
    try {
        errorMessage.value = null;
        successMessage.value = null;
        const response = await axios.get('/reports/closing-entry/data', { params: { year: selectedYear.value } });
        data.value = response.data.data;
        status.value = response.data.status;
        equityAccounts.value = response.data.equity_accounts;
        summary.value = response.data.summary;
        closingDate.value = response.data.closing_date;
    } catch (error) {
        console.error("Gagal mengambil data Tutup Buku:", error);
        errorMessage.value = "Terjadi kesalahan saat memuat status tutup buku.";
    } finally {
        loading.value = false;
    }
};

const formatCurrency = (value) => {
    if (!value || value === 0) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const executeClosing = async () => {
    successMessage.value = null;
    errorMessage.value = null;

    if (!confirm(`Apakah Anda yakin ingin menjalankan Tutup Buku untuk tahun anggaran ${selectedYear.value}? Proses ini akan me-nol-kan semua akun Pendapatan dan Belanja/Beban.`)) {
        return;
    }

    processing.value = true;
    try {
        await axios.post('/reports/closing-entry', {
            year: selectedYear.value
        });

        successMessage.value = `Jurnal penutup akhir tahun ${selectedYear.value} berhasil dibuat.`;
        
        await fetchData();
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Terjadi kesalahan pada server.";
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head title="Tutup Buku (Closing Entries)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Tutup Buku Akhir Tahun
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Memindahkan saldo akun nominal (Pendapatan & Beban) ke akun Surplus/Defisit atau Ekuitas.
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
                        <Button variant="outline" class="w-full md:w-auto" @click="fetchData" :disabled="loading || processing">
                            <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                            Cek Status
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Status Closed -->
        <div v-if="status === 'closed'" class="mb-6 p-6 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900 rounded-xl flex items-start gap-4">
            <CheckCircle2 class="w-8 h-8 text-emerald-600 dark:text-emerald-500 mt-1" />
            <div>
                <h3 class="text-lg font-semibold text-emerald-800 dark:text-emerald-400">Tutup Buku Selesai</h3>
                <p class="text-emerald-700/80 dark:text-emerald-500/80 mt-1">
                    Jurnal penutup untuk tahun anggaran <strong>{{ selectedYear }}</strong> telah dieksekusi pada <strong>{{ closingDate }}</strong>. Semua akun nominal telah di-nol-kan.
                </p>
            </div>
        </div>

        <div v-else-if="status === 'open' && !loading" class="space-y-6">
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium">Preview Jurnal Penutup</h3>
                    <p class="text-sm text-muted-foreground">Berikut adalah akun nominal yang saldonya akan di-nol-kan.</p>
                </div>
                
                <Button @click="executeClosing" :disabled="processing || data.length === 0" variant="default" size="lg" class="shadow-sm">
                    <Lock class="w-4 h-4 mr-2" :class="{ 'animate-spin': processing }" v-if="!processing" />
                    <RefreshCw class="w-4 h-4 mr-2 animate-spin" v-else />
                    Jalankan Tutup Buku
                </Button>
            </div>

            <!-- Pesan jika equity account belum tersetting -->
            <div v-if="data.length > 0 && (!equityAccounts.sal || !equityAccounts.surplus_defisit)" class="p-4 bg-red-50 text-red-800 rounded-lg border border-red-200 flex items-start gap-3">
                <AlertTriangle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <div>
                    <strong>Peringatan Sistem!</strong> Akun untuk menampung selisih tutup buku tidak lengkap. Pastikan akun <strong>Ekuitas SAL (3.1.03)</strong> dan <strong>Surplus/Defisit-LO (3.1.02)</strong> sudah terdaftar di Master Data Kode Rekening.
                </div>
            </div>

            <Card class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="w-[300px]">Kode / Nama Akun</TableHead>
                                <TableHead class="text-center">Posisi Jurnal Penutup</TableHead>
                                <TableHead class="text-right w-[200px]">Debit (Rp)</TableHead>
                                <TableHead class="text-right w-[200px]">Kredit (Rp)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in data" :key="item.id" class="hover:bg-muted/30">
                                <TableCell>
                                    <div class="font-medium">{{ item.code }}</div>
                                    <div class="text-xs text-muted-foreground">{{ item.name }}</div>
                                </TableCell>
                                <TableCell class="text-center text-xs">
                                    <span v-if="item.closing_debit > 0" class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full font-medium">Di-Debit</span>
                                    <span v-else-if="item.closing_credit > 0" class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full font-medium">Di-Kredit</span>
                                </TableCell>
                                <TableCell class="text-right">{{ formatCurrency(item.closing_debit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(item.closing_credit) }}</TableCell>
                            </TableRow>

                            <!-- Baris penyeimbang ke SAL -->
                            <TableRow v-if="equityAccounts.sal && equityAccounts.sal.amount > 0" class="bg-blue-50/50 dark:bg-blue-900/10">
                                <TableCell>
                                    <div class="font-bold text-blue-700 dark:text-blue-400">{{ equityAccounts.sal.code }}</div>
                                    <div class="text-xs text-blue-600/80 dark:text-blue-400/80">{{ equityAccounts.sal.name }} (Penampung LRA)</div>
                                </TableCell>
                                <TableCell class="text-center text-xs">
                                    <span class="px-2 py-1 bg-blue-200 text-blue-900 rounded-full font-bold">Penyeimbang</span>
                                </TableCell>
                                <TableCell class="text-right font-bold text-blue-700">{{ formatCurrency(equityAccounts.sal.position === 'debit' ? equityAccounts.sal.amount : 0) }}</TableCell>
                                <TableCell class="text-right font-bold text-blue-700">{{ formatCurrency(equityAccounts.sal.position === 'credit' ? equityAccounts.sal.amount : 0) }}</TableCell>
                            </TableRow>

                            <!-- Baris penyeimbang ke Surplus/Defisit -->
                            <TableRow v-if="equityAccounts.surplus_defisit && equityAccounts.surplus_defisit.amount > 0" class="bg-indigo-50/50 dark:bg-indigo-900/10">
                                <TableCell>
                                    <div class="font-bold text-indigo-700 dark:text-indigo-400">{{ equityAccounts.surplus_defisit.code }}</div>
                                    <div class="text-xs text-indigo-600/80 dark:text-indigo-400/80">{{ equityAccounts.surplus_defisit.name }} (Penampung LO)</div>
                                </TableCell>
                                <TableCell class="text-center text-xs">
                                    <span class="px-2 py-1 bg-indigo-200 text-indigo-900 rounded-full font-bold">Penyeimbang</span>
                                </TableCell>
                                <TableCell class="text-right font-bold text-indigo-700">{{ formatCurrency(equityAccounts.surplus_defisit.position === 'debit' ? equityAccounts.surplus_defisit.amount : 0) }}</TableCell>
                                <TableCell class="text-right font-bold text-indigo-700">{{ formatCurrency(equityAccounts.surplus_defisit.position === 'credit' ? equityAccounts.surplus_defisit.amount : 0) }}</TableCell>
                            </TableRow>

                            <TableRow v-if="data.length === 0">
                                <TableCell colspan="4" class="h-24 text-center text-muted-foreground">
                                    Tidak ada data saldo akun nominal untuk ditutup pada tahun ini.
                                </TableCell>
                            </TableRow>
                            
                            <!-- Grand Total -->
                            <TableRow class="bg-muted/50 font-bold border-t-2 border-border" v-if="data.length > 0">
                                <TableCell colspan="2" class="text-right uppercase">Total Jurnal Penutup</TableCell>
                                <TableCell class="text-right text-emerald-600">
                                    {{ formatCurrency(summary.total_closing_debit + (equityAccounts.sal?.position === 'debit' ? equityAccounts.sal.amount : 0) + (equityAccounts.surplus_defisit?.position === 'debit' ? equityAccounts.surplus_defisit.amount : 0)) }}
                                </TableCell>
                                <TableCell class="text-right text-emerald-600">
                                    {{ formatCurrency(summary.total_closing_credit + (equityAccounts.sal?.position === 'credit' ? equityAccounts.sal.amount : 0) + (equityAccounts.surplus_defisit?.position === 'credit' ? equityAccounts.surplus_defisit.amount : 0)) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>

    </AuthenticatedLayout>
</template>
