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
import { ref, watch } from 'vue';

const props = defineProps({
    receipts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const date = ref(props.filters?.date || '');
let searchTimeout = null;

watch([search, status, date], ([newSearch, newStatus, newDate]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        let params = {};
        if (newSearch) params.search = newSearch;
        if (newStatus && newStatus !== 'all') params.status = newStatus;
        if (newDate) params.date = newDate;
        
        router.get('/receipts', params, { preserveState: true, replace: true });
    }, 300);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
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
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="w-full sm:w-40">
                    <Input type="date" v-model="date" class="w-full shadow-sm bg-white dark:bg-slate-900" />
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
                            <div class="font-medium text-xs px-2 py-0.5 rounded bg-primary/10 text-primary w-fit mb-1">
                                {{ item.type?.name }}
                                <template v-if="item.sub_type"> &rsaquo; {{ item.sub_type.name }}</template>
                            </div>
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
                            Belum ada data rekap penerimaan.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            
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
        </div>
    </AuthenticatedLayout>
</template>
