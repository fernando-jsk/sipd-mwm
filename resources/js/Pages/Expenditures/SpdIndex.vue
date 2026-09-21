<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Search, Eye, CheckCircle, RotateCcw, Calendar, Coins } from '@lucide/vue';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
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
    totalAmount: Number,
});

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

        router.get('/expenditures/spd', params, { preserveState: true, replace: true });
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
    if (item.payment_method === 'ls_bendahara') {
        return item.treasurer?.name ? `Bendahara: ${item.treasurer.name}` : 'Kas Bendahara';
    }
    return item.payment_method || '-';
};

const getStatusColor = (status) => {
    switch (status) {
        case 'authorized': return 'warning';
        case 'disbursed': return 'outline';
        default: return 'outline';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'authorized': return 'Menunggu Pencairan SPD';
        case 'disbursed': return 'Dana Cair (SPD Terbit)';
        default: return status;
    }
};
</script>

<template>
    <Head title="3. Pencairan SPD (Kabag Keuangan)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Kabag Keuangan</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">3. Pencairan SPD</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Daftar Pencairan SPD (Surat Pencairan Dana)
                    </h2>
                </div>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 bg-red-500/10 border border-red-500/20 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.error }}</span>
        </div>

        <!-- Ringkasan Nominal Sesuai Filter -->
        <div class="flex items-center mb-6">
            <Card size="sm" class="border-border/80 shadow-sm bg-card w-full sm:w-80">
                <CardHeader class="flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Total Nominal Pencairan SPD
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
                        <div class="w-full sm:w-44">
                            <Select v-model="statusFilter">
                                <SelectTrigger class="w-full shadow-sm bg-background">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="authorized">Menunggu Pencairan</SelectItem>
                                    <SelectItem value="disbursed">Dicairkan</SelectItem>
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

        <!-- Tabel Antrean Pencairan SPD -->
        <Card class="p-0 overflow-hidden border-border/80 shadow-sm">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader class="bg-muted/50">
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">No. SPPD / No. OPD</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">No. SPD / Tgl Cair</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Uraian Pembayaran</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Penerima / Nominal</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</TableHead>
                            <TableHead class="text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in expenditures.data" :key="item.id">
                            <TableCell>
                                <div class="font-semibold text-xs text-secondary dark:text-foreground font-mono">SPPD: {{ item.document_number }}</div>
                                <div class="font-semibold text-xs text-amber-600 dark:text-amber-500 font-mono">OPD: {{ item.opd_number || '-' }}</div>
                            </TableCell>
                            <TableCell>
                                <div v-if="item.spd_number" class="font-semibold text-sm text-emerald-700 dark:text-emerald-400 font-mono">{{ item.spd_number }}</div>
                                <div v-else class="text-xs text-muted-foreground italic">Belum dicairkan</div>
                                <div v-if="item.spd_date" class="text-xs text-muted-foreground">{{ format(new Date(item.spd_date), 'dd MMM yyyy', { locale: id }) }}</div>
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
                                    <Button :variant="item.status === 'authorized' ? 'default' : 'outline'" size="sm" class="h-8 px-3 text-xs" :class="item.status === 'authorized' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''">
                                        <CheckCircle v-if="item.status === 'authorized'" class="w-3.5 h-3.5 mr-1" />
                                        <Eye v-else class="w-3.5 h-3.5 mr-1" />
                                        {{ item.status === 'authorized' ? 'Cairkan SPD' : 'Detail' }}
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="expenditures.data.length === 0">
                            <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                Tidak ada data antrean pencairan SPD ditemukan.
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
                <div class="flex space-x-1">
                    <Link 
                        v-for="(link, index) in expenditures.links" 
                        :key="index"
                        :href="link.url || '#'"
                        class="px-3 py-1 text-xs border rounded-md"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    ></Link>
                </div>
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
