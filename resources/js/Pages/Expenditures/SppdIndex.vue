<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search, Plus, Eye, FileSpreadsheet, Upload } from '@lucide/vue';
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
import { ref, watch } from 'vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    expenditures: Object,
    filters: Object,
    users: Array,
});

const importForm = useForm({
    file: null,
    treasurer_id: '',
    kpa_id: '',
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
const statusFilter = ref(props.filters?.status || '');
const sortFilter = ref(props.filters?.sort || 'newest');

let searchTimeout = null;

watch([search, statusFilter, sortFilter], ([newSearch, newStatus, newSort]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/expenditures/sppd', { search: newSearch, status: newStatus, sort: newSort }, { preserveState: true, replace: true });
    }, 300);
});

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

        <div class="bg-card text-card-foreground border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-border/80 bg-muted/20 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="font-semibold text-sm">Dokumen SPPD</h3>
                
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <select v-model="statusFilter" class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Diajukan</option>
                        <option value="authorized">Diotorisasi</option>
                        <option value="disbursed">Dicairkan</option>
                        <option value="rejected">Ditolak</option>
                    </select>

                    <select v-model="sortFilter" class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="newest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                    </select>

                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                            <Search class="w-4 h-4" />
                        </div>
                        <Input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari No SPPD / Uraian..." 
                            class="pl-9 h-9 w-full bg-background"
                        />
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>No. SPPD / Tanggal</TableHead>
                            <TableHead>Uraian Pembayaran</TableHead>
                            <TableHead>Jenis / Penerima</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in expenditures.data" :key="item.id">
                            <TableCell>
                                <div class="font-semibold text-sm text-secondary font-mono">{{ item.document_number }}</div>
                                <div class="text-xs text-muted-foreground">{{ format(new Date(item.date), 'dd MMM yyyy', { locale: id }) }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm max-w-xs truncate" :title="item.description">{{ item.description }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col items-start gap-1">
                                    <Badge variant="outline" class="text-[10px] font-mono">{{ item.type }}</Badge>
                                    <span class="text-xs text-muted-foreground line-clamp-2" :title="item.payment_method === 'rekanan' && item.vendor ? item.vendor.name : (item.payment_method === 'pegawai' ? 'Pegawai Internal' : item.payment_method)">
                                        {{ item.payment_method === 'rekanan' && item.vendor ? item.vendor.name : (item.payment_method === 'pegawai' ? 'Pegawai Internal' : item.payment_method) }}
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
            <div class="p-4 border-t border-border/80 bg-muted/20 flex items-center justify-between" v-if="expenditures.data.length > 0">
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
        </div>
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
