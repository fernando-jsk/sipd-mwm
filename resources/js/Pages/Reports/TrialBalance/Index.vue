<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, onMounted } from 'vue';
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
import { RefreshCw, Search, Calculator } from 'lucide-vue-next';

const filters = ref({
    start_date: new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
});

const data = ref([]);
const totals = ref(null);
const loading = ref(false);

const fetchData = async () => {
    if (!filters.value.start_date || !filters.value.end_date) return;

    loading.value = true;
    try {
        const response = await axios.get('/reports/trial-balance/data', { params: filters.value });
        data.value = response.data.data;
        totals.value = response.data.totals;
    } catch (error) {
        console.error("Gagal mengambil data Neraca Saldo:", error);
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
</script>

<template>
    <Head title="Neraca Saldo (Trial Balance)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Neraca Saldo (Trial Balance)
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Rekapitulasi total mutasi dan saldo akhir setiap akun untuk memastikan <i>balance</i>.
                    </p>
                </div>
            </div>
        </template>

        <Card class="mb-6">
            <CardHeader class="pb-3 border-b border-border/50">
                <CardTitle class="text-sm font-semibold flex items-center gap-2">
                    <Search class="w-4 h-4 text-muted-foreground" />
                    Filter Periode
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3 space-y-1.5">
                        <Label>Tanggal Awal</Label>
                        <Input type="date" v-model="filters.start_date" />
                    </div>

                    <div class="w-full md:w-1/3 space-y-1.5">
                        <Label>Tanggal Akhir</Label>
                        <Input type="date" v-model="filters.end_date" />
                    </div>

                    <div class="w-full md:w-auto">
                        <Button variant="default" class="w-full md:w-auto" @click="fetchData" :disabled="loading">
                            <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                            Tampilkan Data
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div v-if="totals" class="space-y-4">
            <Card class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead rowspan="2" class="align-middle w-[250px]">Kode / Nama Akun</TableHead>
                                <TableHead colspan="2" class="text-center border-b">Saldo Awal</TableHead>
                                <TableHead colspan="2" class="text-center border-b">Mutasi</TableHead>
                                <TableHead colspan="2" class="text-center border-b">Saldo Akhir</TableHead>
                            </TableRow>
                            <TableRow>
                                <TableHead class="text-right w-[150px]">Debit</TableHead>
                                <TableHead class="text-right w-[150px]">Kredit</TableHead>
                                <TableHead class="text-right w-[150px]">Debit</TableHead>
                                <TableHead class="text-right w-[150px]">Kredit</TableHead>
                                <TableHead class="text-right w-[150px]">Debit</TableHead>
                                <TableHead class="text-right w-[150px]">Kredit</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in data" :key="item.id" class="hover:bg-muted/30">
                                <TableCell>
                                    <div class="font-medium">{{ item.code }}</div>
                                    <div class="text-xs text-muted-foreground">{{ item.name }}</div>
                                </TableCell>
                                <TableCell class="text-right">{{ formatCurrency(item.opening_debit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(item.opening_credit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(item.mutation_debit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(item.mutation_credit) }}</TableCell>
                                <TableCell class="text-right font-medium" :class="{'text-emerald-600': item.closing_debit > 0}">{{ formatCurrency(item.closing_debit) }}</TableCell>
                                <TableCell class="text-right font-medium" :class="{'text-emerald-600': item.closing_credit > 0}">{{ formatCurrency(item.closing_credit) }}</TableCell>
                            </TableRow>

                            <TableRow v-if="data.length === 0">
                                <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                    Tidak ada data untuk periode terpilih.
                                </TableCell>
                            </TableRow>

                            <!-- Grand Total -->
                            <TableRow class="bg-muted/50 font-bold border-t-2 border-border">
                                <TableCell class="text-right">GRAND TOTAL</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.opening_debit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.opening_credit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.mutation_debit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(totals.mutation_credit) }}</TableCell>
                                <TableCell class="text-right" :class="totals.closing_debit !== totals.closing_credit ? 'text-red-600' : 'text-emerald-700 dark:text-emerald-400'">
                                    {{ formatCurrency(totals.closing_debit) }}
                                </TableCell>
                                <TableCell class="text-right" :class="totals.closing_debit !== totals.closing_credit ? 'text-red-600' : 'text-emerald-700 dark:text-emerald-400'">
                                    {{ formatCurrency(totals.closing_credit) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <div v-if="totals.closing_debit !== totals.closing_credit" class="p-4 bg-red-50 text-red-800 rounded-lg border border-red-200">
                <strong>Peringatan!</strong> Neraca Saldo tidak seimbang (Unbalanced). Terdapat selisih sebesar {{ formatCurrency(Math.abs(totals.closing_debit - totals.closing_credit)) }}. Silakan periksa kembali entri jurnal Anda.
            </div>
        </div>

        <div v-else-if="!loading" class="flex flex-col items-center justify-center py-16 text-center">
            <Calculator class="w-12 h-12 text-muted-foreground/30 mb-4" />
            <h3 class="text-lg font-medium text-foreground">Neraca Saldo</h3>
            <p class="text-sm text-muted-foreground mt-1 max-w-sm">
                Pilih rentang tanggal di atas, lalu klik "Tampilkan Data" untuk memuat rekapitulasi akun.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
