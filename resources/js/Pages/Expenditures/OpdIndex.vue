<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search, Eye, ShieldCheck } from '@lucide/vue';
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
import { ref, watch } from 'vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    expenditures: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const searchBy = ref(props.filters?.search_by || 'all');
const statusFilter = ref(props.filters?.status || '');
const sortFilter = ref(props.filters?.sort || 'doc_desc');

let searchTimeout = null;

watch([search, searchBy, statusFilter, sortFilter], ([newSearch, newSearchBy, newStatus, newSort]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/expenditures/opd', { search: newSearch, search_by: newSearchBy, status: newStatus, sort: newSort }, { preserveState: true, replace: true });
    }, 300);
});

const getStatusColor = (status) => {
    switch (status) {
        case 'submitted': return 'warning';
        case 'authorized': return 'default';
        case 'disbursed': return 'outline';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'submitted': return 'Menunggu Otorisasi OPD';
        case 'authorized': return 'OPD Terotorisasi';
        case 'disbursed': return 'Dana Cair (SPD Terbit)';
        case 'rejected': return 'Ditolak';
        default: return status;
    }
};
</script>

<template>
    <Head title="2. Otorisasi OPD (Direktur)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Direktur</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">2. Otorisasi OPD</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Daftar Otorisasi OPD (Surat Otorisasi Pencairan Dana)
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

        <div class="bg-card text-card-foreground border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-border/80 bg-muted/20 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="font-semibold text-sm">Dokumen Antrean Otorisasi OPD</h3>
                
                <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-full sm:w-[150px] bg-background">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Status</SelectItem>
                            <SelectItem value="submitted">Menunggu Otorisasi</SelectItem>
                            <SelectItem value="authorized">Diotorisasi</SelectItem>
                            <SelectItem value="disbursed">Dicairkan</SelectItem>
                            <SelectItem value="rejected">Ditolak</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="sortFilter">
                        <SelectTrigger class="w-full sm:w-[160px] bg-background">
                            <SelectValue placeholder="Urutkan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="newest">Terbaru</SelectItem>
                            <SelectItem value="oldest">Terlama</SelectItem>
                            <SelectItem value="doc_asc">No. SPPD (A-Z)</SelectItem>
                            <SelectItem value="doc_desc">No. SPPD (Z-A)</SelectItem>
                        </SelectContent>
                    </Select>

                    <div class="flex items-center space-x-0 w-full sm:w-auto">
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
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                                <Search class="w-4 h-4" />
                            </div>
                            <Input 
                                v-model="search" 
                                type="text" 
                                placeholder="Ketik kata kunci..." 
                                class="pl-9 w-full rounded-l-none bg-background focus-visible:z-10"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>No. SPPD</TableHead>
                            <TableHead>No. OPD / Tgl Otorisasi</TableHead>
                            <TableHead>Uraian Pembayaran</TableHead>
                            <TableHead>Status Otorisasi</TableHead>
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
                                <div v-if="item.opd_number" class="font-semibold text-sm text-emerald-700 font-mono">{{ item.opd_number }}</div>
                                <div v-else class="text-xs text-muted-foreground italic">Belum diotorisasi</div>
                                <div v-if="item.opd_date" class="text-xs text-muted-foreground">{{ format(new Date(item.opd_date), 'dd MMM yyyy', { locale: id }) }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm max-w-xs truncate" :title="item.description">{{ item.description }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusColor(item.status)" class="text-[10px] tracking-wider uppercase" :class="item.status === 'disbursed' ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-transparent' : ''">
                                    {{ getStatusLabel(item.status) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right space-x-2">
                                <Link :href="`/expenditures/${item.id}`">
                                    <Button :variant="item.status === 'submitted' ? 'default' : 'outline'" size="sm" class="h-8 px-3 text-xs">
                                        <ShieldCheck v-if="item.status === 'submitted'" class="w-3.5 h-3.5 mr-1" />
                                        <Eye v-else class="w-3.5 h-3.5 mr-1" />
                                        {{ item.status === 'submitted' ? 'Proses Otorisasi' : 'Detail' }}
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="expenditures.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                Tidak ada data antrean otorisasi OPD ditemukan.
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
    </AuthenticatedLayout>
</template>
