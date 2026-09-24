<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
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
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { ref, watch, computed } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { 
    Search, Plus, Download, Building2, Briefcase, UserCheck, 
    CheckCircle2, Copy, Check, RotateCcw, Edit, Trash2, AlertCircle 
} from '@lucide/vue';

const props = defineProps({
    vendors: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || 'all');
const statusFilter = ref(props.filters?.status || 'all');
const completenessFilter = ref(props.filters?.completeness || 'all');

const hasActiveFilters = computed(() => {
    return Boolean(search.value || (typeFilter.value && typeFilter.value !== 'all') || (statusFilter.value && statusFilter.value !== 'all') || (completenessFilter.value && completenessFilter.value !== 'all'));
});

const applyFilters = ([newSearch, newType, newStatus, newCompleteness], oldValue, onCleanup) => {
    const searchTimeout = setTimeout(() => {
        router.get('/vendors', { 
            search: newSearch, 
            type: newType, 
            status: newStatus, 
            completeness: newCompleteness 
        }, { preserveState: true, replace: true });
    }, 300);

    onCleanup(() => {
        clearTimeout(searchTimeout);
    });
};

watch([search, typeFilter, statusFilter, completenessFilter], applyFilters);

const resetFilters = () => {
    search.value = '';
    typeFilter.value = 'all';
    statusFilter.value = 'all';
    completenessFilter.value = 'all';
};

// Copy to clipboard helper
const copiedKey = ref(null);
const copyToClipboard = async (text, key) => {
    if (!text || text === '-') return;
    try {
        await navigator.clipboard.writeText(text);
        copiedKey.value = key;
        setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = null;
            }
        }, 1800);
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};

const deleteForm = useForm({});
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(`/vendors/${itemToDelete.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            itemToDelete.value = null;
        }
    });
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (typeFilter.value && typeFilter.value !== 'all') params.append('type', typeFilter.value);
    if (statusFilter.value && statusFilter.value !== 'all') params.append('status', statusFilter.value);
    if (completenessFilter.value && completenessFilter.value !== 'all') params.append('completeness', completenessFilter.value);
    const queryString = params.toString();
    return `/vendors/export${queryString ? '?' + queryString : ''}`;
});
</script>

<template>
    <Head title="Master Data Rekanan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Master Data</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Rekanan / Vendor</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Master Rekanan
                    </h2>
                </div>
                
                <div class="flex items-center gap-2">
                    <a :href="exportUrl" target="_blank" rel="noopener noreferrer">
                        <Button variant="outline" class="gap-1.5 h-9 text-xs font-medium">
                            <Download class="w-3.5 h-3.5" />
                            <span>Export CSV</span>
                        </Button>
                    </a>
                    <Link href="/vendors/create">
                        <Button class="gap-1.5 h-9 text-xs font-medium">
                            <Plus class="w-3.5 h-3.5" />
                            <span>Tambah Rekanan</span>
                        </Button>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Flash Notification: Success Message -->
        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-xs" role="alert">
            <CheckCircle2 class="w-5 h-5 shrink-0 text-emerald-600" />
            <span class="text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>

        <!-- Flash Notification: Error Message -->
        <div v-if="$page.props.flash?.error" class="mb-4 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-xl flex items-center gap-3 shadow-xs" role="alert">
            <AlertCircle class="w-5 h-5 shrink-0" />
            <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
        </div>

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <Card class="border border-border/80 rounded-xl shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Rekanan</CardTitle>
                    <Building2 class="w-4 h-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-secondary dark:text-foreground">{{ stats?.total || 0 }}</div>
                    <p class="text-[11px] text-muted-foreground mt-0.5">Seluruh rekanan terdaftar</p>
                </CardContent>
            </Card>

            <Card class="border border-border/80 rounded-xl shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Badan Usaha</CardTitle>
                    <Briefcase class="w-4 h-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-secondary dark:text-foreground">{{ stats?.corporate || 0 }}</div>
                    <p class="text-[11px] text-muted-foreground mt-0.5">PT, CV, UD, Koperasi</p>
                </CardContent>
            </Card>

            <Card class="border border-border/80 rounded-xl shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Perorangan</CardTitle>
                    <UserCheck class="w-4 h-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-secondary dark:text-foreground">{{ stats?.individual || 0 }}</div>
                    <p class="text-[11px] text-muted-foreground mt-0.5">Penyedia perorangan / toko</p>
                </CardContent>
            </Card>

            <Card class="border border-border/80 rounded-xl shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Rekanan Aktif</CardTitle>
                    <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats?.active || 0 }}</div>
                    <p class="text-[11px] text-muted-foreground mt-0.5">Siap digunakan transaksi</p>
                </CardContent>
            </Card>
        </div>

        <!-- Main Card Table -->
        <div class="bg-card text-card-foreground border border-border/80 rounded-xl shadow-xs overflow-hidden">
            <!-- Filter & Search Toolbar -->
            <div class="p-4 border-b border-border/80 bg-muted/20 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-sm">Daftar Rekanan Terdaftar</h3>
                    <Badge variant="outline" class="text-xs font-normal bg-background">
                        {{ vendors.total }} Data
                    </Badge>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:flex items-center gap-2 w-full lg:w-auto">
                    <!-- Type Filter -->
                    <Select v-model="typeFilter">
                        <SelectTrigger class="w-full xl:w-[130px] h-9 text-xs">
                            <SelectValue placeholder="Tipe" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Tipe</SelectItem>
                            <SelectItem value="PT">PT</SelectItem>
                            <SelectItem value="CV">CV</SelectItem>
                            <SelectItem value="UD">UD</SelectItem>
                            <SelectItem value="Koperasi">Koperasi</SelectItem>
                            <SelectItem value="Perorangan">Perorangan</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Status Filter -->
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-full xl:w-[130px] h-9 text-xs">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="active">Aktif</SelectItem>
                            <SelectItem value="inactive">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Completeness Filter -->
                    <Select v-model="completenessFilter">
                        <SelectTrigger class="w-full xl:w-[160px] h-9 text-xs">
                            <SelectValue placeholder="Kelengkapan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Kelengkapan</SelectItem>
                            <SelectItem value="no_account">Tanpa Rekening</SelectItem>
                            <SelectItem value="no_npwp">Tanpa NPWP</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Search Input -->
                    <div class="relative w-full sm:col-span-2 xl:w-64">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-muted-foreground">
                            <Search class="w-4 h-4" />
                        </div>
                        <Input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari nama, pimpinan, rekening, bank..." 
                            class="pl-8.5 h-9 w-full bg-background text-xs"
                        />
                    </div>

                    <!-- Reset Filter Button -->
                    <Button 
                        v-if="hasActiveFilters" 
                        variant="ghost" 
                        size="icon" 
                        class="h-9 w-9 shrink-0 text-muted-foreground hover:text-foreground" 
                        title="Reset Filter"
                        @click="resetFilters"
                    >
                        <RotateCcw class="w-4 h-4" />
                    </Button>
                </div>
            </div>

            <!-- Table Container with Horizontal Scroll & Sticky Action Column -->
            <div class="overflow-x-auto relative">
                <Table>
                    <TableHeader class="bg-muted/50">
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="min-w-[240px]">Nama Rekanan & Pimpinan</TableHead>
                            <TableHead class="min-w-[180px]">Kontak & Alamat</TableHead>
                            <TableHead class="min-w-[240px] max-w-[280px]">Bank & Rekening</TableHead>
                            <TableHead class="w-[90px]">Status</TableHead>
                            <!-- Sticky Action Header -->
                            <TableHead class="sticky right-0 z-10 bg-muted/90 backdrop-blur-xs border-l border-border/60 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.06)] text-right w-[140px] px-4">
                                Aksi
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in vendors.data" :key="item.id" class="group">
                            <!-- Nama Rekanan & Pimpinan -->
                            <TableCell class="align-top py-3.5">
                                <div class="font-semibold text-secondary dark:text-foreground text-sm flex items-center gap-1.5 flex-wrap">
                                    <span>{{ item.name }}</span>
                                    <Badge variant="outline" class="text-[10px] px-1.5 py-0 h-4 font-normal">
                                        {{ item.type }}
                                    </Badge>
                                </div>
                                <div v-if="item.director_name" class="text-xs text-muted-foreground mt-0.5">
                                    Pimpinan: <span class="font-medium text-foreground/80">{{ item.director_name }}</span>
                                </div>
                                <div class="text-[11px] text-muted-foreground mt-1 flex items-center gap-1">
                                    <span>NPWP:</span>
                                    <span v-if="item.npwp" class="font-mono text-foreground/90">{{ item.npwp }}</span>
                                    <span v-else class="italic text-muted-foreground/60">-</span>
                                    <button 
                                        v-if="item.npwp"
                                        type="button" 
                                        @click="copyToClipboard(item.npwp, 'npwp-' + item.id)"
                                        class="text-muted-foreground hover:text-foreground p-0.5 rounded transition-colors"
                                        title="Salin NPWP"
                                    >
                                        <Check v-if="copiedKey === 'npwp-' + item.id" class="w-3 h-3 text-emerald-600" />
                                        <Copy v-else class="w-3 h-3" />
                                    </button>
                                </div>
                            </TableCell>

                            <!-- Kontak & Alamat -->
                            <TableCell class="align-top py-3.5">
                                <div class="text-sm font-medium text-foreground">
                                    {{ item.phone || '-' }}
                                </div>
                                <div class="text-xs text-muted-foreground mt-0.5 max-w-[200px] break-words line-clamp-2" :title="item.address || ''">
                                    {{ item.address || '-' }}
                                </div>
                            </TableCell>

                            <!-- Bank & Rekening (Kompak & Vertikal) -->
                            <TableCell class="align-top py-3.5 max-w-[280px]">
                                <div v-if="item.bank_name || item.bank_account_number" class="space-y-1">
                                    <!-- Nama Bank -->
                                    <div class="text-sm font-medium text-foreground leading-snug break-words line-clamp-2" :title="item.bank_name || ''">
                                        {{ item.bank_name || '-' }}
                                    </div>
                                    
                                    <!-- Nomor Rekening + Copy Button -->
                                    <div v-if="item.bank_account_number" class="flex items-center gap-1.5 text-xs font-mono font-semibold text-secondary dark:text-foreground">
                                        <span>{{ item.bank_account_number }}</span>
                                        <button 
                                            type="button" 
                                            @click="copyToClipboard(item.bank_account_number, 'bank-' + item.id)"
                                            class="text-muted-foreground hover:text-foreground p-0.5 rounded transition-colors"
                                            title="Salin Nomor Rekening"
                                        >
                                            <Check v-if="copiedKey === 'bank-' + item.id" class="w-3.5 h-3.5 text-emerald-600" />
                                            <Copy v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>

                                    <!-- Atas Nama Rekening -->
                                    <div v-if="item.bank_account_name" class="text-[11px] text-muted-foreground truncate" :title="item.bank_account_name">
                                        a.n. {{ item.bank_account_name }}
                                    </div>
                                </div>
                                <div v-else>
                                    <Badge variant="outline" class="text-[10px] text-amber-600 bg-amber-500/10 border-amber-500/20">
                                        Belum Diatur
                                    </Badge>
                                </div>
                            </TableCell>

                            <!-- Status -->
                            <TableCell class="align-top py-3.5">
                                <Badge 
                                    :variant="item.is_active ? 'default' : 'secondary'" 
                                    class="text-[10px] tracking-wider uppercase font-semibold"
                                    :class="item.is_active ? 'bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/20 border-emerald-500/30' : ''"
                                >
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </TableCell>

                            <!-- Sticky Action Cell -->
                            <TableCell class="sticky right-0 z-10 bg-card group-hover:bg-muted/40 transition-colors border-l border-border/60 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.06)] text-right align-top py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link :href="`/vendors/${item.id}/edit`">
                                        <Button variant="outline" size="sm" class="h-8 px-2.5 text-xs gap-1">
                                            <Edit class="w-3.5 h-3.5" />
                                            <span>Edit</span>
                                        </Button>
                                    </Link>
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        class="h-8 px-2.5 text-xs gap-1 text-destructive hover:bg-destructive/10 border-destructive/30" 
                                        @click="confirmDelete(item)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                        <span>Hapus</span>
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="vendors.data.length === 0">
                            <TableCell colspan="5" class="h-32 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    <Building2 class="w-8 h-8 opacity-40 text-muted-foreground mb-1" />
                                    <p class="font-medium text-sm">Tidak ada data rekanan yang cocok.</p>
                                    <p class="text-xs text-muted-foreground">Coba ubah kata kunci pencarian atau reset filter yang dipilih.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            
            <!-- Pagination -->
            <div class="p-4 border-t border-border/80 bg-muted/20 flex flex-col sm:flex-row items-center justify-between gap-3" v-if="vendors.data.length > 0">
                <span class="text-xs text-muted-foreground">
                    Menampilkan {{ vendors.from }} - {{ vendors.to }} dari {{ vendors.total }} data
                </span>
                <div class="flex space-x-1">
                    <Link 
                        v-for="(link, index) in vendors.links" 
                        :key="index"
                        :href="link.url || '#'"
                        class="px-3 py-1 text-xs border rounded-md transition-colors"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary font-medium' : 'bg-background hover:bg-muted text-foreground',
                            !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                        ]"
                        v-html="link.label"
                    ></Link>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Dialog -->
        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="text-destructive flex items-center gap-2">
                        <AlertCircle class="w-5 h-5 text-destructive" />
                        Konfirmasi Hapus Rekanan
                    </DialogTitle>
                    <DialogDescription class="pt-3">
                        Apakah Anda yakin ingin menghapus rekanan <strong class="text-foreground">{{ itemToDelete?.name }}</strong>? 
                        <p class="mt-2 text-xs text-muted-foreground">
                            Catatan: Rekanan yang sudah memiliki riwayat SPPD / Pengeluaran tidak dapat dihapus permanen demi integritas laporan keuangan.
                        </p>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-6 flex sm:justify-end gap-2">
                    <Button variant="outline" @click="isDeleteDialogOpen = false" :disabled="deleteForm.processing">
                        Batal
                    </Button>
                    <Button variant="destructive" @click="deleteItem" :disabled="deleteForm.processing">
                        {{ deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus Data' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
