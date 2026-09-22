<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Dialog,
  DialogScrollContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import {
  Search,
  Plus,
  Printer,
  FileText,
  Trash2,
  Edit,
  RotateCcw,
  Receipt,
  Wallet,
  Clock,
  CheckCircle2,
  Paperclip,
  ExternalLink,
  ChevronDown
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { terbilang } from '@/lib/utils';

const props = defineProps({
    receipts: Object,
    filters: Object,
    stats: Object,
    accountCodes: Array,
});

// Formatters
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

// Filter states
const search = ref(props.filters?.search || '');
const monthFilter = ref(props.filters?.month || 'all');
const yearFilter = ref(props.filters?.year || new Date().getFullYear().toString());
const statusFilter = ref(props.filters?.status || 'all');
const sortFilter = ref(props.filters?.sort || 'date_desc');

const months = [
    { value: 'all', label: 'Semua Bulan' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const hasActiveFilters = computed(() => {
    return !!(
        search.value ||
        (monthFilter.value && monthFilter.value !== 'all') ||
        (statusFilter.value && statusFilter.value !== 'all') ||
        sortFilter.value !== 'date_desc'
    );
});

const resetFilters = () => {
    search.value = '';
    monthFilter.value = 'all';
    statusFilter.value = 'all';
    sortFilter.value = 'date_desc';
};

// Watch filters with debounce for search
watch([search, monthFilter, yearFilter, statusFilter, sortFilter], ([newSearch, newMonth, newYear, newStatus, newSort], oldValue, onCleanup) => {
    const searchTimeout = setTimeout(() => {
        const params = {};
        if (newSearch) params.search = newSearch;
        if (newMonth && newMonth !== 'all') params.month = newMonth;
        if (newYear) params.year = newYear;
        if (newStatus && newStatus !== 'all') params.status = newStatus;
        if (newSort) params.sort = newSort;

        router.get('/expenditure-receipts', params, { preserveState: true, replace: true });
    }, 300);

    onCleanup(() => {
        clearTimeout(searchTimeout);
    });
});

// Modal State: Create / Edit
const isModalOpen = ref(false);
const editingReceipt = ref(null);
const fileInput = ref(null);
const selectedFileName = ref('');

const form = useForm({
    receipt_number: '',
    date: new Date().toISOString().split('T')[0],
    account_code_id: '',
    recipient_name: '',
    description: '',
    amount: '',
    tax_type: 'none',
    tax_amount: 0,
    billing_code: '',
    status: 'paid',
    attachment: null,
});

const generateReceiptNumber = () => {
    const now = new Date();
    const yr = now.getFullYear();
    const mo = String(now.getMonth() + 1).padStart(2, '0');
    const rand = Math.floor(1000 + Math.random() * 9000);
    form.receipt_number = `KWT-${yr}${mo}-${rand}`;
};

const openCreateModal = () => {
    editingReceipt.value = null;
    form.reset();
    form.date = new Date().toISOString().split('T')[0];
    form.status = 'paid';
    form.tax_type = 'none';
    form.tax_amount = 0;
    selectedFileName.value = '';
    generateReceiptNumber();
    isModalOpen.value = true;
};

const isReceiptLocked = (receipt) => {
    return receipt.status === 'completed' || receipt.status === 'in_gu' || Boolean(receipt.expenditure_id);
};

const openEditModal = (receipt) => {
    if (isReceiptLocked(receipt)) {
        return;
    }
    editingReceipt.value = receipt;
    form.receipt_number = receipt.receipt_number;
    form.date = receipt.date ? new Date(receipt.date).toISOString().split('T')[0] : '';
    form.account_code_id = receipt.account_code_id ? receipt.account_code_id.toString() : '';
    form.recipient_name = receipt.recipient_name;
    form.description = receipt.description;
    form.amount = receipt.amount;
    form.tax_type = receipt.tax_type || 'none';
    form.tax_amount = receipt.tax_amount || 0;
    form.billing_code = receipt.billing_code || '';
    form.status = receipt.status;
    form.attachment = null;
    selectedFileName.value = receipt.attachment_path ? 'Berkas sudah terlampir' : '';
    isModalOpen.value = true;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.attachment = file;
        selectedFileName.value = file.name;
    }
};

const submitForm = () => {
    const payloadTaxType = form.tax_type === 'none' ? null : form.tax_type;
    
    if (editingReceipt.value) {
        form.transform((data) => ({
            ...data,
            tax_type: payloadTaxType,
            _method: 'PUT',
        })).post(`/expenditure-receipts/${editingReceipt.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    } else {
        form.transform((data) => ({
            ...data,
            tax_type: payloadTaxType,
        })).post('/expenditure-receipts', {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const deleteReceipt = (receipt) => {
    if (isReceiptLocked(receipt)) {
        return;
    }
    if (confirm(`Apakah Anda yakin ingin menghapus kwitansi ${receipt.receipt_number}?`)) {
        router.delete(`/expenditure-receipts/${receipt.id}`, {
            preserveScroll: true
        });
    }
};

const netAmountCalculated = computed(() => {
    const amt = Number(form.amount || 0);
    const tax = Number(form.tax_amount || 0);
    return Math.max(0, amt - tax);
});

const getStatusBadge = (receipt) => {
    if (receipt.status === 'completed') {
        return { label: 'Sudah GU', variant: 'outline', class: 'bg-primary/10 text-primary border-primary/30 font-semibold' };
    }
    if (receipt.status === 'in_gu' || receipt.expenditure_id) {
        return { label: 'Proses GU', variant: 'outline', class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/30 font-semibold' };
    }
    if (receipt.status === 'paid') {
        return { label: 'Cair', variant: 'outline', class: 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-400 font-semibold border-emerald-600/30' };
    }
    return { label: 'Draft', variant: 'outline', class: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-700 font-medium' };
};
</script>

<template>
    <Head title="Kwitansi Belanja UP (SPJ)" />

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
                                <span class="text-xs text-muted-foreground">Pengeluaran</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Kwitansi Belanja UP</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground flex items-center gap-2">
                        <Receipt class="w-6 h-6 text-primary" />
                        Kwitansi Belanja Kas UP (SPJ)
                    </h2>
                </div>
                
                <div class="flex items-center gap-3">
                    <Button @click="openCreateModal" class="bg-primary text-primary-foreground hover:bg-primary/90 shadow-sm">
                        <Plus class="w-4 h-4 mr-1.5" /> Catat Kwitansi Baru
                    </Button>
                </div>
            </div>
        </template>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg text-sm font-medium">
            {{ $page.props.flash.message }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 bg-red-500/10 border border-red-500/20 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
            {{ $page.props.flash.error }}
        </div>

        <!-- Akumulasi Total Belanja Periode Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 bg-card border border-border/80 px-4 py-3 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-primary/10 text-primary">
                    <Wallet class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Realisasi Belanja Kas UP</div>
                    <div class="text-xs text-muted-foreground">Akumulasi pengeluaran kas riil bendahara pada periode terpilih</div>
                </div>
            </div>
            <div class="text-2xl font-bold font-mono text-secondary dark:text-foreground sm:text-right">
                {{ formatCurrency(stats?.total_month) }}
            </div>
        </div>

        <!-- 1. STATS CARDS (4 STATUS PIPELINE: DRAFT - CAIR - PROSES GU - SUDAH GU) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- 1. Draft -->
            <Card 
                @click="statusFilter = statusFilter === 'draft' ? 'all' : 'draft'"
                class="border shadow-sm cursor-pointer transition-all hover:shadow-md"
                :class="statusFilter === 'draft' ? 'ring-2 ring-slate-500 border-slate-500 bg-slate-50/80 dark:bg-slate-900/50' : 'border-border/80 hover:border-slate-300'"
            >
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        Draft
                    </CardTitle>
                    <FileText class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold font-mono text-muted-foreground">
                        {{ formatCurrency(stats?.total_draft) }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Belum dibayarkan</p>
                </CardContent>
            </Card>

            <!-- 2. Cair -->
            <Card 
                @click="statusFilter = statusFilter === 'cair' ? 'all' : 'cair'"
                class="border shadow-sm cursor-pointer transition-all hover:shadow-md"
                :class="statusFilter === 'cair' ? 'ring-2 ring-emerald-600 border-emerald-600 bg-emerald-50/60 dark:bg-emerald-950/20' : 'border-border/80 hover:border-emerald-300'"
            >
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                        Cair
                    </CardTitle>
                    <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold font-mono text-emerald-600">
                        {{ formatCurrency(stats?.total_cair) }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Siap diajukan ke GU</p>
                </CardContent>
            </Card>

            <!-- 3. Proses GU -->
            <Card 
                @click="statusFilter = statusFilter === 'in_gu' ? 'all' : 'in_gu'"
                class="border shadow-sm cursor-pointer transition-all hover:shadow-md"
                :class="statusFilter === 'in_gu' ? 'ring-2 ring-blue-600 border-blue-600 bg-blue-50/60 dark:bg-blue-950/20' : 'border-border/80 hover:border-blue-300'"
            >
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wider">
                        Proses GU
                    </CardTitle>
                    <Clock class="h-4 w-4 text-blue-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold font-mono text-blue-600">
                        {{ formatCurrency(stats?.total_in_gu) }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Masuk pengajuan SPPD GU</p>
                </CardContent>
            </Card>

            <!-- 4. Sudah GU -->
            <Card 
                @click="statusFilter = statusFilter === 'completed' ? 'all' : 'completed'"
                class="border shadow-sm cursor-pointer transition-all hover:shadow-md"
                :class="statusFilter === 'completed' ? 'ring-2 ring-primary border-primary bg-primary/5' : 'border-border/80 hover:border-primary/40'"
            >
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-semibold text-primary uppercase tracking-wider">
                        Sudah GU
                    </CardTitle>
                    <CheckCircle2 class="h-4 w-4 text-primary" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold font-mono text-primary">
                        {{ formatCurrency(stats?.total_completed) }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Kas UP telah diganti (SPD)</p>
                </CardContent>
            </Card>
        </div>

        <!-- 2. FILTER SECTION -->
        <Card class="border border-border/80 shadow-sm mb-6">
            <CardContent class="pt-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Pencarian</Label>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="No. kwitansi, toko, uraian..."
                                class="pl-8 text-sm focus-visible:ring-primary"
                            />
                        </div>
                    </div>

                    <!-- Bulan -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Bulan Transaksi</Label>
                        <Select v-model="monthFilter">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Bulan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                                    {{ m.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Status</Label>
                        <Select v-model="statusFilter">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="cair">Cair</SelectItem>
                                <SelectItem value="in_gu">Proses GU</SelectItem>
                                <SelectItem value="completed">Sudah GU</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Reset Button -->
                    <div class="flex items-end">
                        <Button
                            variant="outline"
                            class="w-full text-xs"
                            @click="resetFilters"
                            :disabled="!hasActiveFilters"
                        >
                            <RotateCcw class="w-3.5 h-3.5 mr-1.5" /> Reset Filter
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 3. DATA TABLE -->
        <Card class="p-0 overflow-hidden border border-border/80 shadow-sm">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader class="bg-muted/50">
                        <TableRow>
                            <TableHead class="w-[180px]">No. Kwitansi / Tgl</TableHead>
                            <TableHead class="w-[220px]">Akun Belanja (5.x)</TableHead>
                            <TableHead class="w-[280px]">Penerima & Uraian Belanja</TableHead>
                            <TableHead class="text-right w-[140px]">Nominal (Rp)</TableHead>
                            <TableHead class="text-right w-[120px]">Pajak</TableHead>
                            <TableHead class="text-right w-[140px]">Netto</TableHead>
                            <TableHead class="text-center w-[140px]">Status</TableHead>
                            <TableHead class="text-center w-[110px]">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="receipt in receipts.data" :key="receipt.id" class="hover:bg-muted/30">
                            <!-- No Kwitansi & Tgl -->
                            <TableCell class="align-top">
                                <div class="font-semibold font-mono text-xs text-foreground">{{ receipt.receipt_number }}</div>
                                <div class="text-[11px] text-muted-foreground mt-0.5">
                                    {{ receipt.date ? format(new Date(receipt.date), 'dd MMMM yyyy', { locale: id }) : '-' }}
                                </div>
                                <div v-if="receipt.attachment_path" class="mt-1">
                                    <a :href="`/storage/${receipt.attachment_path}`" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-primary hover:underline">
                                        <Paperclip class="w-3 h-3" /> Lampiran Nota
                                    </a>
                                </div>
                            </TableCell>

                            <!-- Akun Belanja -->
                            <TableCell class="align-top">
                                <div class="font-mono text-xs text-foreground">{{ receipt.account_code?.code }}</div>
                                <div class="text-xs text-muted-foreground line-clamp-2 mt-0.5">{{ receipt.account_code?.name }}</div>
                            </TableCell>

                            <!-- Penerima & Uraian -->
                            <TableCell class="align-top">
                                <div class="font-semibold text-xs text-secondary dark:text-foreground">{{ receipt.recipient_name }}</div>
                                <div class="text-xs text-muted-foreground mt-0.5 leading-snug">{{ receipt.description }}</div>
                                <div v-if="receipt.expenditure" class="mt-1 text-[11px] text-blue-600 dark:text-blue-400 font-mono">
                                    SPP: {{ receipt.expenditure.document_number }}
                                </div>
                            </TableCell>

                            <!-- Nominal Bruto -->
                            <TableCell class="align-top text-right font-mono font-semibold text-xs text-foreground">
                                {{ formatCurrency(receipt.amount) }}
                            </TableCell>

                            <!-- Pajak -->
                            <TableCell class="align-top text-right text-xs">
                                <template v-if="Number(receipt.tax_amount) > 0">
                                    <span class="font-mono text-destructive">-{{ formatCurrency(receipt.tax_amount) }}</span>
                                    <div class="text-[10px] text-muted-foreground">{{ receipt.tax_type }}</div>
                                </template>
                                <span v-else class="text-muted-foreground">-</span>
                            </TableCell>

                            <!-- Netto -->
                            <TableCell class="align-top text-right font-mono font-bold text-xs text-emerald-600">
                                {{ formatCurrency(receipt.net_amount) }}
                            </TableCell>

                            <!-- Status -->
                            <TableCell class="align-top text-center">
                                <Badge :variant="getStatusBadge(receipt).variant" :class="['text-[11px]', getStatusBadge(receipt).class]">
                                    {{ getStatusBadge(receipt).label }}
                                </Badge>
                            </TableCell>

                            <!-- Aksi -->
                            <TableCell class="align-top text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Cetak -->
                                    <a :href="`/expenditure-receipts/${receipt.id}/print`" target="_blank">
                                        <Button variant="ghost" size="icon" class="h-7 w-7 text-primary hover:bg-primary/10" title="Cetak Kuitansi">
                                            <Printer class="w-3.5 h-3.5" />
                                        </Button>
                                    </a>

                                    <!-- Edit (Disabled jika sedang proses GU atau sudah GU) -->
                                    <span 
                                        :title="isReceiptLocked(receipt) ? `Kwitansi berstatus ${getStatusBadge(receipt).label} tidak dapat diedit` : 'Edit Kuitansi'"
                                        :class="isReceiptLocked(receipt) ? 'cursor-not-allowed inline-flex' : 'inline-flex'"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7 text-secondary hover:bg-muted"
                                            :disabled="isReceiptLocked(receipt)"
                                            @click="openEditModal(receipt)"
                                        >
                                            <Edit class="w-3.5 h-3.5" />
                                        </Button>
                                    </span>

                                    <!-- Hapus (Disabled jika sedang proses GU atau sudah GU) -->
                                    <span 
                                        :title="isReceiptLocked(receipt) ? `Kwitansi berstatus ${getStatusBadge(receipt).label} tidak dapat dihapus` : 'Hapus Kuitansi'"
                                        :class="isReceiptLocked(receipt) ? 'cursor-not-allowed inline-flex' : 'inline-flex'"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7 text-destructive hover:bg-destructive/10"
                                            :disabled="isReceiptLocked(receipt)"
                                            @click="deleteReceipt(receipt)"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </span>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="receipts.data.length === 0">
                            <TableCell colspan="8" class="text-center py-12 text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Receipt class="w-10 h-10 text-muted-foreground/50" />
                                    <p class="font-medium text-sm">Belum ada data kuitansi belanja kas UP.</p>
                                    <p class="text-xs">Klik tombol "Catat Kwitansi Baru" di atas untuk menambahkan transaksi.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div v-if="receipts.links && receipts.links.length > 3" class="p-4 border-t flex items-center justify-between">
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ receipts.from || 0 }} sampai {{ receipts.to || 0 }} dari {{ receipts.total }} kuitansi
                </div>
                <div class="flex gap-1">
                    <Link
                        v-for="(link, i) in receipts.links"
                        :key="i"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-md transition-colors',
                            link.active ? 'bg-primary text-primary-foreground font-semibold' : 'bg-muted text-muted-foreground hover:bg-muted/80',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </Card>

        <!-- 4. MODAL DIALOG INPUT/EDIT KWITANSI -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogScrollContent class="max-w-lg sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-secondary flex items-center gap-2">
                        <Receipt class="w-5 h-5 text-primary" />
                        {{ editingReceipt ? 'Edit Kwitansi Belanja Kas UP' : 'Catat Kwitansi Belanja Kas UP Baru' }}
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Catatan transaksi pengeluaran riil kas operasional (UP) yang dibayarkan oleh Bendahara Pengeluaran.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- No Kwitansi -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <Label for="receipt_number" class="text-xs font-semibold text-muted-foreground uppercase">
                                        No. Kwitansi <span class="text-destructive">*</span>
                                    </Label>
                                    <button type="button" @click="generateReceiptNumber" class="text-[11px] text-primary hover:underline">
                                        Auto Generate
                                    </button>
                                </div>
                                <Input id="receipt_number" v-model="form.receipt_number" placeholder="Contoh: KWT-202608-001" class="font-mono" required />
                                <p v-if="form.errors.receipt_number" class="text-[11px] text-destructive">{{ form.errors.receipt_number }}</p>
                            </div>

                            <!-- Tanggal Transaksi -->
                            <div class="space-y-1.5">
                                <Label for="date" class="text-xs font-semibold text-muted-foreground uppercase">
                                    Tanggal Transaksi <span class="text-destructive">*</span>
                                </Label>
                                <Input id="date" type="date" v-model="form.date" required />
                                <p v-if="form.errors.date" class="text-[11px] text-destructive">{{ form.errors.date }}</p>
                            </div>
                        </div>

                        <!-- Akun Belanja 5.x -->
                        <div class="space-y-1.5">
                            <Label for="account_code_id" class="text-xs font-semibold text-muted-foreground uppercase">
                                Akun Rekening Belanja (RBA) <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.account_code_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih Akun Rekening Belanja" />
                                </SelectTrigger>
                                <SelectContent class="max-h-60">
                                    <SelectItem v-for="acc in accountCodes" :key="acc.id" :value="acc.id.toString()">
                                        {{ acc.code }} - {{ acc.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.account_code_id" class="text-[11px] text-destructive">{{ form.errors.account_code_id }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Toko / Penerima -->
                            <div class="space-y-1.5">
                                <Label for="recipient_name" class="text-xs font-semibold text-muted-foreground uppercase">
                                    Toko / Penerima Uang <span class="text-destructive">*</span>
                                </Label>
                                <Input id="recipient_name" v-model="form.recipient_name" placeholder="Contoh: Toko Buku ABC / RM Sederhana" required />
                                <p v-if="form.errors.recipient_name" class="text-[11px] text-destructive">{{ form.errors.recipient_name }}</p>
                            </div>

                            <!-- Status -->
                            <div class="space-y-1.5">
                                <Label for="status" class="text-xs font-semibold text-muted-foreground uppercase">
                                    Status
                                </Label>
                                <Select v-model="form.status">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Pilih Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="paid">Cair</SelectItem>
                                        <SelectItem v-if="editingReceipt" value="in_gu">Proses GU</SelectItem>
                                        <SelectItem v-if="editingReceipt" value="completed">Sudah GU</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Uraian Belanja -->
                        <div class="space-y-1.5">
                            <Label for="description" class="text-xs font-semibold text-muted-foreground uppercase">
                                Uraian Keperluan Pembayaran <span class="text-destructive">*</span>
                            </Label>
                            <Textarea id="description" v-model="form.description" rows="2" placeholder="Jelaskan belanja apa dan untuk unit mana..." required />
                            <p v-if="form.errors.description" class="text-[11px] text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <!-- Nominal Belanja Bruto -->
                        <div class="space-y-1.5">
                            <Label for="amount" class="text-xs font-semibold text-muted-foreground uppercase">
                                Nominal Belanja Kotor (Rp) <span class="text-destructive">*</span>
                            </Label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground font-semibold text-sm">Rp</span>
                                <Input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    v-model="form.amount"
                                    class="pl-10 font-mono text-base font-bold focus-visible:ring-primary"
                                    placeholder="0"
                                    required
                                />
                            </div>
                            <!-- Live Terbilang -->
                            <div v-if="form.amount && Number(form.amount) > 0" class="text-xs p-2 rounded-md bg-muted/40 border text-muted-foreground leading-snug">
                                <span class="font-semibold text-foreground">Terbilang:</span>
                                <span class="italic text-primary font-medium ml-1">{{ terbilang(form.amount) }} Rupiah</span>
                            </div>
                            <p v-if="form.errors.amount" class="text-[11px] text-destructive">{{ form.errors.amount }}</p>
                        </div>

                        <!-- Potongan Pajak Section -->
                        <div class="p-3.5 bg-muted/30 border rounded-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-secondary uppercase tracking-wider">Potongan Pajak (Jika Ada)</span>
                                <span class="text-xs text-muted-foreground">Opsional</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="space-y-1">
                                    <Label class="text-[11px] text-muted-foreground">Jenis Pajak</Label>
                                    <Select v-model="form.tax_type">
                                        <SelectTrigger class="h-9 text-xs">
                                            <SelectValue placeholder="Pilih Jenis" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Tidak Ada Pajak</SelectItem>
                                            <SelectItem value="PPN">PPN</SelectItem>
                                            <SelectItem value="PPh 21">PPh 21</SelectItem>
                                            <SelectItem value="PPh 22">PPh 22</SelectItem>
                                            <SelectItem value="PPh 23">PPh 23</SelectItem>
                                            <SelectItem value="PPh Final">PPh Final</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-[11px] text-muted-foreground">Nominal Pajak (Rp)</Label>
                                    <Input type="number" step="0.01" v-model="form.tax_amount" class="h-9 text-xs font-mono" placeholder="0" :disabled="form.tax_type === 'none'" />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-[11px] text-muted-foreground">Kode Billing</Label>
                                    <Input v-model="form.billing_code" class="h-9 text-xs font-mono" placeholder="Kode billing pajak" :disabled="form.tax_type === 'none'" />
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-2 border-t text-xs">
                                <span class="font-semibold text-muted-foreground">Nilai Bersih Diterima (Netto):</span>
                                <span class="font-bold font-mono text-emerald-600 text-sm">{{ formatCurrency(netAmountCalculated) }}</span>
                            </div>
                        </div>

                        <!-- Upload Nota / Bukti Fisik -->
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-muted-foreground uppercase">
                                Upload Bukti Nota / Foto Kuitansi Fisik (Opsional)
                            </Label>
                            <div class="flex items-center gap-2">
                                <Button type="button" variant="outline" size="sm" @click="$refs.fileInput.click()" class="text-xs">
                                    <Paperclip class="w-3.5 h-3.5 mr-1.5" /> Pilih File
                                </Button>
                                <span class="text-xs text-muted-foreground truncate">{{ selectedFileName || 'Belum ada file dipilih (Maks 5MB)' }}</span>
                                <input
                                    type="file"
                                    ref="fileInput"
                                    class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="handleFileChange"
                                />
                            </div>
                        </div>

                    <DialogFooter class="pt-4 border-t flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="isModalOpen = false" :disabled="form.processing">
                            Batal
                        </Button>
                        <Button type="submit" class="bg-primary text-primary-foreground hover:bg-primary/90" :disabled="form.processing || !form.amount || form.amount <= 0">
                            {{ editingReceipt ? 'Simpan Perubahan' : 'Simpan Kwitansi' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
