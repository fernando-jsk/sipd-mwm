<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search, Eye, CheckCircle } from '@lucide/vue';
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
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const sortFilter = ref(props.filters?.sort || 'newest');

let searchTimeout = null;

watch([search, statusFilter, sortFilter], ([newSearch, newStatus, newSort]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/expenditures/spd', { search: newSearch, status: newStatus, sort: newSort }, { preserveState: true, replace: true });
    }, 300);
});

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

        <div class="bg-card text-card-foreground border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-border/80 bg-muted/20 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="font-semibold text-sm">Dokumen Pencairan Dana SPD</h3>
                
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <select v-model="statusFilter" class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="">Semua Status</option>
                        <option value="authorized">Menunggu Pencairan</option>
                        <option value="disbursed">Dicairkan</option>
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
                            placeholder="Cari No. SPPD / OPD / SPD..." 
                            class="pl-9 h-9 w-full bg-background"
                        />
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>No. SPPD / No. OPD</TableHead>
                            <TableHead>No. SPD / Tgl Cair</TableHead>
                            <TableHead>Uraian Pembayaran</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in expenditures.data" :key="item.id">
                            <TableCell>
                                <div class="font-semibold text-xs text-secondary font-mono">SPPD: {{ item.document_number }}</div>
                                <div class="font-semibold text-xs text-amber-700 font-mono">OPD: {{ item.opd_number || '-' }}</div>
                            </TableCell>
                            <TableCell>
                                <div v-if="item.spd_number" class="font-semibold text-sm text-emerald-700 font-mono">{{ item.spd_number }}</div>
                                <div v-else class="text-xs text-muted-foreground italic">Belum dicairkan</div>
                                <div v-if="item.spd_date" class="text-xs text-muted-foreground">{{ format(new Date(item.spd_date), 'dd MMM yyyy', { locale: id }) }}</div>
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
                                    <Button :variant="item.status === 'authorized' ? 'default' : 'outline'" size="sm" class="h-8 px-3 text-xs" :class="item.status === 'authorized' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''">
                                        <CheckCircle v-if="item.status === 'authorized'" class="w-3.5 h-3.5 mr-1" />
                                        <Eye v-else class="w-3.5 h-3.5 mr-1" />
                                        {{ item.status === 'authorized' ? 'Cairkan SPD' : 'Detail' }}
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="expenditures.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                Tidak ada data antrean pencairan SPD ditemukan.
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
