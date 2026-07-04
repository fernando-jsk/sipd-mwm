<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search } from '@lucide/vue';
import { Badge } from '@/Components/ui/badge';
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
import { ref, watch } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

const props = defineProps({
    vendors: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || 'all');
let searchTimeout = null;

const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/vendors', { search: search.value, type: typeFilter.value }, { preserveState: true, replace: true });
    }, 300);
};

watch([search, typeFilter], applyFilters);

const deleteForm = useForm({});
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const deleteItem = () => {
    deleteForm.delete(`/vendors/${itemToDelete.value.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            itemToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Master Data Rekanan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
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
                <Link href="/vendors/create">
                    <Button>Tambah Rekanan</Button>
                </Link>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>

        <div class="bg-card text-card-foreground border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-border/80 bg-muted/20 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="font-semibold text-sm">Daftar Rekanan Terdaftar</h3>
                
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <!-- Type Filter -->
                    <Select v-model="typeFilter">
                        <SelectTrigger class="w-full sm:w-[150px] h-9">
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

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                            <Search class="w-4 h-4" />
                        </div>
                        <Input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari nama, pimpinan, NPWP..." 
                            class="pl-9 h-9 w-full bg-background"
                        />
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>Nama Rekanan</TableHead>
                            <TableHead>Pimpinan</TableHead>
                            <TableHead>Kontak & Alamat</TableHead>
                            <TableHead>Bank</TableHead>
                            <TableHead class="w-[100px]">Status</TableHead>
                            <TableHead class="text-right w-[150px]">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in vendors.data" :key="item.id">
                            <TableCell>
                                <div class="font-semibold text-secondary">{{ item.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    <Badge variant="outline" class="mr-1 text-[10px] px-1 py-0 h-4">{{ item.type }}</Badge>
                                    NPWP: {{ item.npwp || '-' }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm">{{ item.director_name || '-' }}</span>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm">{{ item.phone || '-' }}</div>
                                <div class="text-xs text-muted-foreground truncate max-w-[200px]" :title="item.address">{{ item.address || '-' }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm font-medium">{{ item.bank_name || '-' }}</div>
                                <div class="text-[11px] text-muted-foreground">{{ item.bank_account_number || '-' }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="item.is_active ? 'default' : 'secondary'" class="text-[10px] tracking-wider uppercase">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right space-x-2">
                                <Link :href="`/vendors/${item.id}/edit`">
                                    <Button variant="outline" size="sm" class="h-8 px-3 text-xs">Edit</Button>
                                </Link>
                                <Button variant="destructive" size="sm" class="h-8 px-3 text-xs bg-red-50 hover:bg-red-100 text-red-600 border-red-200" @click="confirmDelete(item)">
                                    Hapus
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="vendors.data.length === 0">
                            <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                Tidak ada data rekanan ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            
            <!-- Pagination -->
            <div class="p-4 border-t border-border/80 bg-muted/20 flex items-center justify-between" v-if="vendors.data.length > 0">
                <span class="text-xs text-muted-foreground">
                    Menampilkan {{ vendors.from }} - {{ vendors.to }} dari {{ vendors.total }} data
                </span>
                <div class="flex space-x-1">
                    <Link 
                        v-for="(link, index) in vendors.links" 
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
        
        <!-- Delete Confirmation Dialog -->
        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="text-destructive flex items-center gap-2">
                        Konfirmasi Hapus
                    </DialogTitle>
                    <DialogDescription class="pt-3">
                        Apakah Anda yakin ingin menghapus rekanan <span class="font-bold text-foreground">{{ itemToDelete?.name }}</span>? 
                        Tindakan ini tidak dapat dibatalkan.
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
