<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search, Plus, Eye, FileSpreadsheet, Upload, Calendar, Coins, RotateCcw } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { ref, computed, watch } from 'vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    expenditures: Object,
    filters: Object,
    totalAmount: {
        type: Number,
        default: 0
    },
    users: Array,
});

const importForm = useForm({
    file: null,
    treasurer_id: '',
    kpa_id: '',
    ppk_id: '',
    status: 'draft',
    end_row: '',
});

const isImportModalOpen = ref(false);

const submitImport = () => {
    importForm.post('/expenditures/import', {
        preserveScroll: true,
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
        },
    });
};

const search = ref(props.filters?.search || '');
const searchBy = ref(props.filters?.search_by || 'all');
const statusFilter = ref(props.filters?.status || 'all');
const sortFilter = ref(props.filters?.sort || 'doc_desc');
const startDate = ref(props.filters?.start_date || props.filters?.date || '');
const endDate = ref(props.filters?.end_date || props.filters?.date || '');

const hasActiveFilters = computed(() => {
    return !!(
        search.value ||
        (searchBy.value && searchBy.value !== 'all') ||
        (statusFilter.value && statusFilter.value !== 'all') ||
        startDate.value ||
        endDate.value ||
        sortFilter.value !== 'doc_desc'
    );
});

const resetFilters = () => {
    search.value = '';
    searchBy.value = 'all';
    statusFilter.value = 'all';
    startDate.value = '';
    endDate.value = '';
    sortFilter.value = 'doc_desc';
};

watch([search, searchBy, statusFilter, sortFilter, startDate, endDate], ([newSearch, newSearchBy, newStatus, newSort, newStartDate, newEndDate], oldValue, onCleanup) => {
    const searchTimeout = setTimeout(() => {
        const params = {};
        if (newSearch) params.search = newSearch;
        if (newSearchBy && newSearchBy !== 'all') params.search_by = newSearchBy;
        if (newStatus && newStatus !== 'all') params.status = newStatus;
        if (newSort) params.sort = newSort;
        if (newStartDate) params.start_date = newStartDate;
        if (newEndDate) params.end_date = newEndDate;

        const routeUrl = window.location.pathname.startsWith('/expenditures/sppd') ? '/expenditures/sppd' : '/expenditures';
        router.get(routeUrl, params, { preserveState: true, replace: true });
    }, 300);

    onCleanup(() => {
        clearTimeout(searchTimeout);
    });
});

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        if (newFilters.search !== undefined && newFilters.search !== search.value) search.value = newFilters.search || '';
        if (newFilters.search_by !== undefined && newFilters.search_by !== searchBy.value) searchBy.value = newFilters.search_by || 'all';
        if (newFilters.status !== undefined && newFilters.status !== statusFilter.value) statusFilter.value = newFilters.status || 'all';
        if (newFilters.start_date !== undefined && newFilters.start_date !== startDate.value) startDate.value = newFilters.start_date || '';
        if (newFilters.end_date !== undefined && newFilters.end_date !== endDate.value) endDate.value = newFilters.end_date || '';
        if (newFilters.sort !== undefined && newFilters.sort !== sortFilter.value) sortFilter.value = newFilters.sort || 'doc_desc';
    }
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

const getRecipientName = (item) => {
    if (item.payment_method === 'rekanan' && item.vendor) {
        return item.vendor.name;
    }
    if (item.payment_method === 'pegawai') {
        return 'Pegawai Internal';
    }
    return item.payment_method || '-';
};

const getStatusColor = (status) => {
    switch (status) {
        case 'draft': return 'secondary';
        case 'submitted': return 'default';
        case 'authorized': return 'warning';
        case 'disbursed': return 'outline';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'draft': return 'Draft SPPD';
        case 'submitted': return 'Diajukan (Menunggu OPD)';
        case 'authorized': return 'Diotorisasi (OPD Terbit)';
        case 'disbursed': return 'Dana Cair (SPD Terbit)';
        case 'rejected': return 'Ditolak';
        default: return status;
    }
};
</script>

<template>
    <Head title="1. Pengajuan SPPD" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Bendahara</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">1. Pengajuan SPPD</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Daftar Pengajuan SPPD (Surat Permintaan Pencairan Dana)
                    </h2>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="isImportModalOpen = true">
                        <FileSpreadsheet class="w-4 h-4 mr-2"/> Import Excel
                    </Button>
                    <Link href="/expenditures/create">
                        <Button><Plus class="w-4 h-4 mr-2"/> Buat SPPD Baru</Button>
                    </Link>
                </div>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>

        <div v-if="$page.props.flash?.skipped_reasons && $page.props.flash.skipped_reasons.length > 0" class="mb-4 bg-orange-500/10 border border-orange-500/20 text-orange-700 px-4 py-3 rounded-lg relative" role="alert">
            <h4 class="font-bold text-sm mb-1">Detail Data yang Dilewati:</h4>
            <ul class="list-disc pl-5 text-xs space-y-1 max-h-40 overflow-y-auto">
                <li v-for="(reason, index) in $page.props.flash.skipped_reasons" :key="index">{{ reason }}</li>
            </ul>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 bg-red-500/10 border border-red-500/20 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.error }}</span>
        </div>

        <!-- Ringkasan Nominal Sesuai Filter -->
        <div class="flex items-center">
            <Card size="sm" class="border-border/80 shadow-sm bg-card w-full sm:w-80">
                <CardHeader class="flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Total Nominal Pencairan
                    </CardTitle>
                    <div class="size-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Coins class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold tracking-tight text-secondary dark:text-foreground">
                        {{ formatCurrency(totalAmount) }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1.5">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                            {{ expenditures.total }} Data
                        </span>
                        <span v-if="hasActiveFilters">sesuai filter aktif</span>
                        <span v-else>total keseluruhan</span>
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Filter Section -->
        <Card class="mb-6 border-border/80 shadow-sm p-4 sm:p-5">
            <div class="flex flex-col gap-4">
                <!-- Baris 1: Pencarian Cepat, Status, Urutan, dan Tombol Reset -->
                <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
                    <div class="flex items-center space-x-0 flex-1">
                        <Select v-model="searchBy">
                            <SelectTrigger class="w-[140px] rounded-r-none border-r-0 bg-muted/50 focus:ring-0 focus:ring-offset-0">
                                <SelectValue placeholder="Pencarian" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Kolom</SelectItem>
                                <SelectItem value="document_number">No. SPPD</SelectItem>
                                <SelectItem value="description">Uraian</SelectItem>
                                <SelectItem value="vendor_name">Rekanan</SelectItem>
                                <SelectItem value="opd_number">No. OPD</SelectItem>
                                <SelectItem value="spd_number">No. SPD</SelectItem>
                            </SelectContent>
                        </Select>
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                                <Search class="w-4 h-4" />
                            </div>
                            <Input 
                                v-model="search" 
                                type="text" 
                                placeholder="Ketik kata kunci pencarian..." 
                                class="pl-9 w-full rounded-l-none bg-background focus-visible:ring-primary shadow-sm"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
                        <div class="w-full sm:w-40">
                            <Select v-model="statusFilter">
                                <SelectTrigger class="w-full shadow-sm bg-background">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="submitted">Diajukan</SelectItem>
                                    <SelectItem value="authorized">Diotorisasi</SelectItem>
                                    <SelectItem value="disbursed">Dicairkan</SelectItem>
                                    <SelectItem value="rejected">Ditolak</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="w-full sm:w-44">
                            <Select v-model="sortFilter">
                                <SelectTrigger class="w-full shadow-sm bg-background">
                                    <SelectValue placeholder="Urutkan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="doc_desc">No. SPPD (Z-A)</SelectItem>
                                    <SelectItem value="doc_asc">No. SPPD (A-Z)</SelectItem>
                                    <SelectItem value="date_desc">Tanggal Terbaru</SelectItem>
                                    <SelectItem value="date_asc">Tanggal Terlama</SelectItem>
                                    <SelectItem value="newest">Input Terbaru</SelectItem>
                                    <SelectItem value="oldest">Input Terlama</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="default"
                            @click="resetFilters"
                            class="text-xs font-medium text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors h-9 px-3 shrink-0"
                            title="Reset semua filter ke kondisi awal"
                        >
                            <RotateCcw class="size-3.5 mr-1.5" />
                            Reset
                        </Button>
                    </div>
                </div>

                <!-- Baris 2: Filter Rentang Tanggal -->
                <div class="pt-3 border-t border-border/60 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Tanggal Mulai -->
                    <div class="grid gap-1.5">
                        <Label for="start_date" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                            <Calendar class="size-3.5 text-primary" />
                            Tanggal Mulai
                        </Label>
                        <Input
                            id="start_date"
                            type="date"
                            v-model="startDate"
                            :max="endDate || undefined"
                            class="bg-background shadow-sm focus-visible:ring-primary"
                        />
                    </div>

                    <!-- Tanggal Selesai -->
                    <div class="grid gap-1.5">
                        <Label for="end_date" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                            <Calendar class="size-3.5 text-primary" />
                            Tanggal Selesai
                        </Label>
                        <Input
                            id="end_date"
                            type="date"
                            v-model="endDate"
                            :min="startDate || undefined"
                            class="bg-background shadow-sm focus-visible:ring-primary"
                        />
                    </div>
                </div>
            </div>
        </Card>

        <!-- Tabel SPPD -->
        <Card class="p-0 overflow-hidden border-border/80 shadow-sm">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader class="bg-muted/50">
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">No. SPPD / Tanggal</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Uraian Pembayaran</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Penerima / Nominal</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</TableHead>
                            <TableHead class="text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in expenditures.data" :key="item.id">
                            <TableCell>
                                <div class="font-semibold text-sm text-secondary dark:text-foreground font-mono">{{ item.document_number }}</div>
                                <div class="text-xs text-muted-foreground">{{ format(new Date(item.date), 'dd MMM yyyy', { locale: id }) }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm max-w-xs truncate" :title="item.description">{{ item.description }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col items-start gap-0.5">
                                    <span class="font-medium text-sm text-foreground line-clamp-1" :title="getRecipientName(item)">
                                        {{ getRecipientName(item) }}
                                    </span>
                                    <span class="font-semibold text-xs text-emerald-600 dark:text-emerald-400 font-mono">
                                        {{ formatCurrency(item.total_amount || item.details_sum_amount) }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusColor(item.status)" class="text-[10px] tracking-wider uppercase" :class="item.status === 'disbursed' ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-transparent' : ''">
                                    {{ getStatusLabel(item.status) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right space-x-2">
                                <Link :href="`/expenditures/${item.id}`">
                                    <Button variant="outline" size="sm" class="h-8 px-3 text-xs">
                                        <Eye class="w-3.5 h-3.5 mr-1" /> Detail
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="expenditures.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                Tidak ada data pengajuan SPPD ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            
            <!-- Pagination -->
            <div class="p-4 border-t border-border/80 bg-muted/20 flex flex-col sm:flex-row items-center justify-between gap-4" v-if="expenditures.data.length > 0">
                <span class="text-xs text-muted-foreground">
                    Menampilkan {{ expenditures.from }} - {{ expenditures.to }} dari {{ expenditures.total }} data
                </span>
                <div class="flex items-center gap-1">
                    <Link 
                        v-for="(link, index) in expenditures.links" 
                        :key="index"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 text-xs rounded-lg border transition-colors"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary font-medium shadow-sm' : 'bg-card text-foreground border-border/80 hover:bg-muted',
                            !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                        ]"
                        v-html="link.label"
                    ></Link>
                </div>
            </div>
        </Card>

        <!-- Modal Import Excel -->
        <Dialog :open="isImportModalOpen" @update:open="isImportModalOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Import Data SPPD Excel</DialogTitle>
                    <DialogDescription>
                        Unggah file Excel yang berisi historis SPPD. Pastikan format kolom sesuai dengan standar.
                    </DialogDescription>
                </DialogHeader>
                
                <form @submit.prevent="submitImport" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label>File Excel (.xlsx)</Label>
                        <Input type="file" accept=".xlsx,.xls,.csv" @change="e => importForm.file = e.target.files[0]" />
                        <p v-if="importForm.errors.file" class="text-sm text-destructive">{{ importForm.errors.file }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>Bendahara (Default)</Label>
                        <Select v-model="importForm.treasurer_id">
                            <SelectTrigger><SelectValue placeholder="Pilih Bendahara..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">{{ user.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="importForm.errors.treasurer_id" class="text-sm text-destructive">{{ importForm.errors.treasurer_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>KPA (Default)</Label>
                        <Select v-model="importForm.kpa_id">
                            <SelectTrigger><SelectValue placeholder="Pilih KPA..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">{{ user.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="importForm.errors.kpa_id" class="text-sm text-destructive">{{ importForm.errors.kpa_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>PPK / Kabag Keuangan (Pencair SPD)</Label>
                        <Select v-model="importForm.ppk_id">
                            <SelectTrigger><SelectValue placeholder="Pilih PPK / Kabag Keuangan..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">{{ user.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="importForm.errors.ppk_id" class="text-sm text-destructive">{{ importForm.errors.ppk_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>Status Akhir Data</Label>
                        <Select v-model="importForm.status">
                            <SelectTrigger><SelectValue placeholder="Pilih Status..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft SPPD</SelectItem>
                                <SelectItem value="submitted">Diajukan (Menunggu OPD)</SelectItem>
                                <SelectItem value="authorized">Diotorisasi (OPD Terbit)</SelectItem>
                                <SelectItem value="disbursed">Dana Cair (SPD Terbit)</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="importForm.errors.status" class="text-sm text-destructive">{{ importForm.errors.status }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>Import Sampai Baris Ke- <span class="text-xs font-normal text-muted-foreground">(Opsional, Min: 2)</span></Label>
                        <Input type="number" v-model="importForm.end_row" min="2" placeholder="Kosongkan untuk import semua baris" />
                        <p v-if="importForm.errors.end_row" class="text-sm text-destructive">{{ importForm.errors.end_row }}</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isImportModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="importForm.processing">
                            <Upload class="w-4 h-4 mr-2" v-if="!importForm.processing"/>
                            <span v-if="importForm.processing">Memproses...</span>
                            <span v-else>Import Data</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
