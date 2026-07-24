<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search } from '@lucide/vue';
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
import { ref, watch } from 'vue';

const props = defineProps({
    receipts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
let searchTimeout = null;

watch([search, status], ([newSearch, newStatus]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        let params = {};
        if (newSearch) params.search = newSearch;
        if (newStatus && newStatus !== 'all') params.status = newStatus;
        
        router.get('/receipts', params, { preserveState: true, replace: true });
    }, 300);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};
</script>

<template>
    <Head title="Tanda Bukti Penerimaan" />

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
                                <BreadcrumbPage class="text-xs">Tanda Bukti Penerimaan</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Tanda Bukti Penerimaan
                    </h2>
                </div>
                <Link href="/receipts/create">
                    <Button>Buat Penerimaan</Button>
                </Link>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 bg-red-500/10 border border-red-500/20 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.error }}</span>
        </div>

        <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="relative flex w-full sm:max-w-md items-center">
                <Search class="absolute left-3 text-muted-foreground size-4" />
                <Input
                    type="text"
                    placeholder="Cari No. Dokumen, Uraian, Penyetor..."
                    v-model="search"
                    class="w-full pl-9 shadow-sm bg-white dark:bg-slate-900"
                />
            </div>
            <div class="w-full sm:w-48">
                <Select v-model="status">
                    <SelectTrigger class="w-full shadow-sm bg-white dark:bg-slate-900">
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
        </div>

        <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
            <Table class="min-w-full">
                <TableHeader class="bg-muted/40">
                    <TableRow>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">No. Dokumen & Tanggal</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Jenis & Uraian</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Penyetor</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Status</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in receipts.data" :key="item.id">
                        <TableCell class="py-3">
                            <div class="font-medium text-foreground">{{ item.document_number }}</div>
                            <div class="text-xs text-muted-foreground mt-0.5">{{ new Date(item.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}</div>
                        </TableCell>
                        <TableCell class="py-3">
                            <div class="font-medium text-xs px-2 py-0.5 rounded bg-primary/10 text-primary w-fit mb-1">{{ item.type?.name }}</div>
                            <div class="text-sm text-foreground line-clamp-2 max-w-sm">{{ item.description }}</div>
                        </TableCell>
                        <TableCell class="py-3 text-sm text-foreground">
                            {{ item.payer_name }}
                            <div class="text-xs text-muted-foreground mt-0.5 capitalize">{{ item.payment_method }}</div>
                        </TableCell>
                        <TableCell class="py-3">
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
                        <TableCell colspan="5" class="h-24 text-center text-muted-foreground text-sm">
                            Belum ada data penerimaan.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AuthenticatedLayout>
</template>
