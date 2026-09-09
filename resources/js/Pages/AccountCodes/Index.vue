<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Search, FileText } from 'lucide-vue-next';
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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

const props = defineProps({
    accountCodes: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
watch(search, (value, oldValue, onCleanup) => {
    const searchTimeout = setTimeout(() => {
        router.get('/account-codes', { search: value }, { preserveState: true, replace: true });
    }, 300);

    onCleanup(() => {
        clearTimeout(searchTimeout);
    });
});

const deleteForm = useForm({});
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const deleteItem = () => {
    deleteForm.delete(`/account-codes/${itemToDelete.value.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            itemToDelete.value = null;
        }
    });
};

// Full Description Dialog State
const selectedDescription = ref(null);

const viewFullDescription = (item) => {
    selectedDescription.value = item;
};
</script>

<template>
    <Head title="Master Kode Rekening" />

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
                                <BreadcrumbPage class="text-xs">Kode Rekening</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Master Kode Rekening
                    </h2>
                </div>
                <Link href="/account-codes/create">
                    <Button>Tambah Kode</Button>
                </Link>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>

        <div class="bg-card text-card-foreground border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-border/80 bg-muted/20 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="font-semibold text-sm">Daftar Kode Rekening Aktif</h3>
                
                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                        <Search class="w-4 h-4" />
                    </div>
                    <Input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari kode atau nama..." 
                        class="pl-9 h-9 w-full bg-background"
                    />
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table class="w-full">
                    <TableHeader class="bg-muted/50">
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[160px]">Kode Rekening</TableHead>
                            <TableHead class="min-w-[220px]">Nama Akun</TableHead>
                            <TableHead class="w-[280px] max-w-[320px]">Keterangan</TableHead>
                            <TableHead class="w-[100px]">Status</TableHead>
                            <TableHead class="text-right w-[140px]">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in accountCodes.data" :key="item.id">
                            <TableCell class="font-medium font-mono text-xs text-secondary dark:text-foreground">
                                {{ item.code }}
                            </TableCell>
                            <TableCell class="font-semibold text-secondary dark:text-foreground text-sm">
                                <div class="flex items-center" :style="{ paddingLeft: `${(item.level - 1) * 1.5}rem` }">
                                    <span v-if="item.level > 1" class="text-muted-foreground mr-1.5 opacity-50">↳</span>
                                    <span :class="{'text-primary': item.level === 1}">{{ item.name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-xs max-w-[320px]">
                                <div v-if="item.description">
                                    <p class="text-muted-foreground line-clamp-2 leading-relaxed break-words" :title="item.description">
                                        {{ item.description }}
                                    </p>
                                    <button 
                                        v-if="item.description.length > 80"
                                        type="button"
                                        @click="viewFullDescription(item)"
                                        class="text-[11px] font-medium text-primary hover:underline mt-0.5 inline-flex items-center gap-1 focus:outline-none"
                                    >
                                        Lihat Selengkapnya
                                    </button>
                                </div>
                                <span v-else class="text-muted-foreground/50">-</span>
                            </TableCell>
                            <TableCell>
                                <Badge 
                                    :variant="item.is_active ? 'default' : 'secondary'" 
                                    :class="item.is_active ? 'bg-[#4ADE80]/15 text-emerald-700 dark:text-emerald-400 border-emerald-500/20' : 'bg-muted text-muted-foreground'"
                                    class="text-[10px] tracking-wider uppercase font-semibold"
                                >
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right space-x-2">
                                <Link :href="`/account-codes/${item.id}/edit`">
                                    <Button variant="outline" size="sm" class="h-8 px-3 text-xs">Edit</Button>
                                </Link>
                                <Button variant="destructive" size="sm" class="h-8 px-3 text-xs bg-red-50 hover:bg-red-100 text-red-600 border-red-200" @click="confirmDelete(item)">
                                    Hapus
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="accountCodes.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                Tidak ada data kode rekening ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            
            <!-- Pagination -->
            <div class="p-4 border-t border-border/80 bg-muted/20 flex items-center justify-between" v-if="accountCodes.data.length > 0">
                <span class="text-xs text-muted-foreground">
                    Menampilkan {{ accountCodes.from }} - {{ accountCodes.to }} dari {{ accountCodes.total }} data
                </span>
                <div class="flex space-x-1">
                    <Link 
                        v-for="(link, index) in accountCodes.links" 
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
                        Apakah Anda yakin ingin menghapus kode rekening <span class="font-bold text-foreground">{{ itemToDelete?.code }} - {{ itemToDelete?.name }}</span>? Data yang dihapus mungkin akan memengaruhi catatan transaksi sebelumnya.
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

        <!-- Full Description Dialog -->
        <Dialog :open="!!selectedDescription" @update:open="!$event && (selectedDescription = null)">
            <DialogContent class="sm:max-w-[550px] max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-base">
                        <FileText class="w-4 h-4 text-primary" />
                        Keterangan Kode Rekening
                    </DialogTitle>
                    <DialogDescription class="font-mono text-xs text-secondary dark:text-foreground font-semibold mt-1">
                        {{ selectedDescription?.code }} - {{ selectedDescription?.name }}
                    </DialogDescription>
                </DialogHeader>
                <div class="overflow-y-auto max-h-[50vh] pr-1 my-3 text-xs leading-relaxed text-foreground whitespace-pre-line bg-muted/30 p-3.5 rounded-lg border border-border/60">
                    {{ selectedDescription?.description }}
                </div>
                <DialogFooter>
                    <Button variant="outline" size="sm" @click="selectedDescription = null">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
