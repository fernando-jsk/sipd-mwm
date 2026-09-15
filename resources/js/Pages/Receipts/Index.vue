<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search, ArrowUpDown, Calendar, Tag, Layers, RotateCcw } from '@lucide/vue';
import { Card } from '@/Components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    receipts: Object,
    receiptTypes: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const startDate = ref(props.filters?.start_date || props.filters?.date || '');
const endDate = ref(props.filters?.end_date || props.filters?.date || '');
const receiptTypeId = ref(props.filters?.receipt_type_id ? props.filters.receipt_type_id.toString() : 'all');
const receiptSubTypeId = ref(props.filters?.receipt_sub_type_id ? props.filters.receipt_sub_type_id.toString() : 'all');
const sort = ref(props.filters?.sort || 'date_desc');

const availableSubTypes = computed(() => {
    if (!receiptTypeId.value || receiptTypeId.value === 'all') return [];
    const parent = props.receiptTypes.find(t => t.id.toString() === receiptTypeId.value.toString());
    return parent?.children || [];
});

watch(receiptTypeId, () => {
    if (receiptSubTypeId.value !== 'all') {
        const isValid = availableSubTypes.value.some(s => s.id.toString() === receiptSubTypeId.value.toString());
        if (!isValid) {
            receiptSubTypeId.value = 'all';
        }
    }
});

const toggleSort = () => {
    sort.value = sort.value === 'date_desc' ? 'date_asc' : 'date_desc';
};

const hasActiveFilters = computed(() => {
    return !!(
        search.value ||
        (status.value && status.value !== 'all') ||
        startDate.value ||
        endDate.value ||
        (receiptTypeId.value && receiptTypeId.value !== 'all') ||
        (receiptSubTypeId.value && receiptSubTypeId.value !== 'all') ||
        sort.value !== 'date_desc'
    );
});

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    startDate.value = '';
    endDate.value = '';
    receiptTypeId.value = 'all';
    receiptSubTypeId.value = 'all';
    sort.value = 'date_desc';
};

watch([search, status, startDate, endDate, receiptTypeId, receiptSubTypeId, sort], 
  ([newSearch, newStatus, newStartDate, newEndDate, newTypeId, newSubTypeId, newSort], oldValue, onCleanup) => {
    const searchTimeout = setTimeout(() => {
        let params = {};
        if (newSearch) params.search = newSearch;
        if (newStatus && newStatus !== 'all') params.status = newStatus;
        if (newStartDate) params.start_date = newStartDate;
        if (newEndDate) params.end_date = newEndDate;
        if (newTypeId && newTypeId !== 'all') params.receipt_type_id = newTypeId;
        if (newSubTypeId && newSubTypeId !== 'all') params.receipt_sub_type_id = newSubTypeId;
        if (newSort) params.sort = newSort;
        
        router.get('/receipts', params, { preserveState: true, replace: true });
    }, 300);

    onCleanup(() => {
        clearTimeout(searchTimeout);
    });
});

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        if (newFilters.search !== undefined && newFilters.search !== search.value) search.value = newFilters.search || '';
        if (newFilters.status !== undefined && newFilters.status !== status.value) status.value = newFilters.status || 'all';
        if (newFilters.start_date !== undefined && newFilters.start_date !== startDate.value) startDate.value = newFilters.start_date || '';
        if (newFilters.end_date !== undefined && newFilters.end_date !== endDate.value) endDate.value = newFilters.end_date || '';
        if (newFilters.receipt_type_id !== undefined && newFilters.receipt_type_id?.toString() !== receiptTypeId.value) {
            receiptTypeId.value = newFilters.receipt_type_id ? newFilters.receipt_type_id.toString() : 'all';
        }
        if (newFilters.receipt_sub_type_id !== undefined && newFilters.receipt_sub_type_id?.toString() !== receiptSubTypeId.value) {
            receiptSubTypeId.value = newFilters.receipt_sub_type_id ? newFilters.receipt_sub_type_id.toString() : 'all';
        }
        if (newFilters.sort !== undefined && newFilters.sort !== sort.value) sort.value = newFilters.sort || 'date_desc';
    }
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

const isImportModalOpen = ref(false);
const importForm = useForm({
    file: null,
    status: 'draft'
});

const submitImport = () => {
    importForm.post('/receipts/import', {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
        }
    });
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};
</script>

<template>
    <Head title="Rekap Penerimaan Harian" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Penerimaan</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Rekap Penerimaan Harian</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Rekap Penerimaan Harian
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <Dialog v-model:open="isImportModalOpen">
                        <DialogTrigger as-child>
                            <Button variant="outline">Import CSV</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle>Import Data Rekap Penerimaan</DialogTitle>
                                <DialogDescription>
                                    Pilih file CSV hasil export dari SIMRS / Kasir. Pastikan format kolom sesuai dengan template.
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="submitImport" class="space-y-4 py-4">
                                <div class="space-y-2">
                                    <Label>File CSV</Label>
                                    <Input type="file" accept=".csv" @change="handleFileChange" required />
                                    <div v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</div>
                                </div>
                                <div class="space-y-2">
                                    <Label>Status Penerimaan</Label>
                                    <Select v-model="importForm.status">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="draft">Draft</SelectItem>
                                                <SelectItem value="submitted">Submitted</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="importForm.errors.status" class="text-xs text-red-500">{{ importForm.errors.status }}</div>
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="isImportModalOpen = false" :disabled="importForm.processing">Batal</Button>
                                    <Button type="submit" :disabled="importForm.processing || !importForm.file">
                                        {{ importForm.processing ? 'Mengimpor...' : 'Import' }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <Link href="/receipts/create">
                        <Button>Entri Rekap Baru</Button>
                    </Link>
                </div>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 bg-red-500/10 border border-red-500/20 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.error }}</span>
        </div>

        <!-- Filter Section -->
        <Card class="mb-6 border-border/80 shadow-sm p-4 sm:p-5">
            <div class="flex flex-col gap-4">
                <!-- Baris 1: Pencarian Cepat, Status, Urutan, dan Tombol Reset -->
                <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground size-4" />
                        <Input
                            type="text"
                            placeholder="Cari No. Dokumen, Uraian, Penyetor..."
                            v-model="search"
                            class="pl-9 bg-background shadow-sm focus-visible:ring-primary w-full"
                        />
                    </div>
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
                        <div class="w-full sm:w-40">
                            <Select v-model="status">
                                <SelectTrigger class="w-full shadow-sm bg-background">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="submitted">Submitted</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-full sm:w-44">
                            <Select v-model="sort">
                                <SelectTrigger class="w-full shadow-sm bg-background">
                                    <SelectValue placeholder="Urutkan Tanggal" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="date_desc">Tanggal Terbaru</SelectItem>
                                        <SelectItem value="date_asc">Tanggal Terlama</SelectItem>
                                    </SelectGroup>
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

                <!-- Baris 2: Filter Rentang Tanggal & Kategori Jenis Penerimaan -->
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

                    <!-- Jenis Penerimaan (Parent Category) -->
                    <div class="grid gap-1.5">
                        <Label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                            <Tag class="size-3.5 text-primary" />
                            Jenis Penerimaan
                        </Label>
                        <Select v-model="receiptTypeId">
                            <SelectTrigger class="bg-background shadow-sm">
                                <SelectValue placeholder="Semua Jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="all">Semua Jenis Penerimaan</SelectItem>
                                    <SelectItem v-for="type in receiptTypes" :key="type.id" :value="type.id.toString()">
                                        {{ type.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Sub-Jenis Penerimaan (Child Category) -->
                    <div class="grid gap-1.5">
                        <Label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                            <Layers class="size-3.5 text-primary" />
                            Sub-Jenis Penerimaan
                        </Label>
                        <Select v-model="receiptSubTypeId" :disabled="availableSubTypes.length === 0">
                            <SelectTrigger class="bg-background shadow-sm" :class="{ 'opacity-60 cursor-not-allowed': availableSubTypes.length === 0 }">
                                <SelectValue :placeholder="availableSubTypes.length > 0 ? 'Semua Sub-Jenis' : (receiptTypeId !== 'all' ? 'Tanpa Sub-Jenis' : 'Pilih Jenis Dulu')" />
                            </SelectTrigger>
                            <SelectContent v-if="availableSubTypes.length > 0">
                                <SelectGroup>
                                    <SelectItem value="all">Semua Sub-Jenis</SelectItem>
                                    <SelectItem v-for="sub in availableSubTypes" :key="sub.id" :value="sub.id.toString()">
                                        {{ sub.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Tabel Data Rekap Penerimaan Sesuai Style Guide -->
        <Card class="p-0 overflow-hidden shadow-sm border-border/80">
            <div class="overflow-x-auto">
                <Table class="min-w-full">
                    <TableHeader class="bg-muted/40">
                        <TableRow>
                            <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 cursor-pointer select-none hover:text-foreground transition-colors w-[180px]" @click="toggleSort" title="Klik untuk mengubah urutan tanggal">
                                <div class="flex items-center gap-1.5">
                                    <span>Tanggal</span>
                                    <ArrowUpDown class="size-3.5 text-muted-foreground/70" />
                                </div>
                            </TableHead>
                            <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Jenis</TableHead>
                            <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Nominal</TableHead>
                            <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-center">Status</TableHead>
                            <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in receipts.data" :key="item.id">
                            <TableCell class="py-3 font-medium text-foreground whitespace-nowrap">
                                {{ new Date(item.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}
                            </TableCell>
                            <TableCell class="py-3">
                                <div class="font-medium text-xs px-2.5 py-1 rounded-md bg-primary/10 text-primary w-fit inline-flex items-center gap-1.5">
                                    <span>{{ item.type?.name || '-' }}</span>
                                    <template v-if="item.sub_type">
                                        <span class="text-primary/60">&rsaquo;</span>
                                        <span class="font-semibold">{{ item.sub_type.name }}</span>
                                    </template>
                                </div>
                            </TableCell>
                            <TableCell class="py-3 text-sm font-semibold text-foreground text-right whitespace-nowrap">
                                {{ formatCurrency(item.details_sum_amount ?? item.total_amount ?? 0) }}
                            </TableCell>
                            <TableCell class="py-3 text-center">
                                <span :class="{
                                    'bg-slate-100 text-slate-700 border-slate-200': item.status === 'draft',
                                    'bg-blue-100 text-blue-700 border-blue-200': item.status === 'submitted',
                                }" class="px-2.5 py-0.5 rounded-full text-xs font-medium border">
                                    {{ item.status.toUpperCase() }}
                                </span>
                            </TableCell>
                            <TableCell class="py-3 text-right space-x-2">
                                <Link :href="`/receipts/${item.id}`">
                                    <Button variant="outline" size="sm">Detail</Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="receipts.data.length === 0">
                            <TableCell colspan="5" class="h-32 text-center text-muted-foreground text-sm">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span>Tidak ada data penerimaan yang sesuai dengan filter.</span>
                                    <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="resetFilters">
                                        <RotateCcw class="size-3.5 mr-1.5" />
                                        Reset Filter
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            
            <!-- Pagination -->
            <div class="p-4 border-t border-border/80 bg-muted/20 flex flex-col sm:flex-row gap-4 items-center justify-between" v-if="receipts.data.length > 0">
                <span class="text-xs text-muted-foreground">
                    Menampilkan {{ receipts.from }} - {{ receipts.to }} dari {{ receipts.total }} data
                </span>
                <div class="flex flex-wrap gap-1">
                    <Link 
                        v-for="(link, index) in receipts.links" 
                        :key="index"
                        :href="link.url || '#'"
                        class="px-3 py-1 text-xs border rounded-md"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted text-foreground',
                            !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                        ]"
                        v-html="link.label"
                    ></Link>
                </div>
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
