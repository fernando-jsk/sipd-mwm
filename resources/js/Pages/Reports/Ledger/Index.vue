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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
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
import { RefreshCw, Search, BookOpen } from 'lucide-vue-next';

const props = defineProps({
    accounts: {
        type: Array,
        required: true,
        default: () => []
    }
});

const filters = ref({
    account_code_id: '',
    start_date: new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
});

const data = ref(null);
const loading = ref(false);

const fetchData = async () => {
    if (!filters.value.account_code_id || !filters.value.start_date || !filters.value.end_date) {
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get('/reports/ledger/data', { params: filters.value });
        data.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil data Buku Besar:", error);
    } finally {
        loading.value = false;
    }
};

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return format(new Date(dateString), 'dd MMM yyyy', { locale: id });
};
</script>

<template>
    <Head title="Buku Besar (General Ledger)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Buku Besar (General Ledger)
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Pantau riwayat mutasi per akun secara kronologis.
                    </p>
                </div>
            </div>
        </template>

        <!-- Filter Section -->
        <Card class="mb-6">
            <CardHeader class="pb-3 border-b border-border/50">
                <CardTitle class="text-sm font-semibold flex items-center gap-2">
                    <Search class="w-4 h-4 text-muted-foreground" />
                    Filter Buku Besar
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/2 space-y-1.5">
                        <Label>Kode Rekening / Akun</Label>
                        <Select v-model="filters.account_code_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Akun..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="acc in accounts" :key="acc.id" :value="acc.id.toString()">
                                        {{ acc.code }} - {{ acc.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <div class="w-full md:w-1/4 space-y-1.5">
                        <Label>Tanggal Awal</Label>
                        <Input type="date" v-model="filters.start_date" />
                    </div>

                    <div class="w-full md:w-1/4 space-y-1.5">
                        <Label>Tanggal Akhir</Label>
                        <Input type="date" v-model="filters.end_date" />
                    </div>

                    <div class="w-full md:w-auto">
                        <Button variant="default" class="w-full md:w-auto" @click="fetchData" :disabled="loading || !filters.account_code_id">
                            <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                            Tampilkan Data
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div v-if="data" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-foreground">{{ data.account.code }} - {{ data.account.name }}</h3>
                    <p class="text-sm text-muted-foreground">Saldo Normal: <strong>{{ data.normal_balance === 'D' ? 'Debit' : 'Kredit' }}</strong></p>
                </div>
            </div>
            <Card class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="w-[120px]">Tanggal</TableHead>
                                <TableHead class="w-[180px]">No. Referensi</TableHead>
                                <TableHead>Keterangan</TableHead>
                                <TableHead class="text-right w-[150px]">Debit</TableHead>
                                <TableHead class="text-right w-[150px]">Kredit</TableHead>
                                <TableHead class="text-right w-[180px]">Saldo Berjalan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <!-- Saldo Awal -->
                            <TableRow class="bg-muted/20 font-medium">
                                <TableCell colspan="5" class="text-right">Saldo Awal per {{ formatDate(filters.start_date) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(data.opening_balance) }}</TableCell>
                            </TableRow>

                            <!-- Mutasi -->
                            <TableRow v-for="(mut, i) in data.mutations" :key="i">
                                <TableCell>{{ formatDate(mut.date) }}</TableCell>
                                <TableCell class="font-mono text-xs">{{ mut.reference_no }}</TableCell>
                                <TableCell>{{ mut.description || '-' }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(mut.debit) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(mut.credit) }}</TableCell>
                                <TableCell class="text-right font-medium" :class="{'text-red-500': mut.balance < 0}">
                                    {{ formatCurrency(mut.balance) }}
                                </TableCell>
                            </TableRow>

                            <!-- Kosong -->
                            <TableRow v-if="data.mutations.length === 0">
                                <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                    Tidak ada mutasi pada periode ini.
                                </TableCell>
                            </TableRow>

                            <!-- Saldo Akhir -->
                            <TableRow class="bg-muted/50 font-bold">
                                <TableCell colspan="5" class="text-right">Saldo Akhir per {{ formatDate(filters.end_date) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(data.closing_balance) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>

        <div v-else-if="!loading" class="flex flex-col items-center justify-center py-16 text-center">
            <BookOpen class="w-12 h-12 text-muted-foreground/30 mb-4" />
            <h3 class="text-lg font-medium text-foreground">Buku Besar</h3>
            <p class="text-sm text-muted-foreground mt-1 max-w-sm">
                Pilih Kode Akun beserta rentang tanggal di atas, lalu klik "Tampilkan Data" untuk melihat riwayat mutasi.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
