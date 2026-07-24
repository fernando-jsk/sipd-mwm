<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Search, ListTree } from '@lucide/vue';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { ref, watch, computed } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

const props = defineProps({
    receiptTypes: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/receipt-types', { search: value }, { preserveState: true, replace: true });
    }, 300);
});

// Dialog States
const isFormDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isSubListDialogOpen = ref(false);
const editingId = ref(null);
const typeToDelete = ref(null);
const activeParentId = ref(null);

const activeParent = computed(() => {
    if (!activeParentId.value) return null;
    return props.receiptTypes.data.find(r => r.id === activeParentId.value);
});

const form = useForm({
    name: '',
    description: '',
    is_active: true,
    parent_id: null
});

const deleteForm = useForm({});

const openCreateDialog = () => {
    editingId.value = null;
    form.reset();
    form.parent_id = null;
    form.clearErrors();
    isFormDialogOpen.value = true;
};

const openCreateSubDialog = () => {
    editingId.value = null;
    form.reset();
    form.parent_id = activeParentId.value;
    form.clearErrors();
    isFormDialogOpen.value = true;
};

const openEditDialog = (item) => {
    editingId.value = item.id;
    form.name = item.name;
    form.description = item.description || '';
    form.is_active = item.is_active == 1;
    form.parent_id = item.parent_id;
    form.clearErrors();
    isFormDialogOpen.value = true;
};

const openSubListDialog = (item) => {
    activeParentId.value = item.id;
    isSubListDialogOpen.value = true;
};

const submitForm = () => {
    if (editingId.value) {
        form.put(`/receipt-types/${editingId.value}`, {
            onSuccess: () => {
                isFormDialogOpen.value = false;
            }
        });
    } else {
        form.post('/receipt-types', {
            onSuccess: () => {
                isFormDialogOpen.value = false;
            }
        });
    }
};

const confirmDelete = (item) => {
    typeToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const deleteItem = () => {
    deleteForm.delete(`/receipt-types/${typeToDelete.value.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            typeToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Master Jenis Penerimaan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Pengaturan</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Jenis Penerimaan</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Master Jenis Penerimaan
                    </h2>
                </div>
                <Button @click="openCreateDialog">Tambah Jenis</Button>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 bg-red-500/10 border border-red-500/20 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.error }}</span>
        </div>

        <div class="mb-5 flex items-center justify-between">
            <div class="relative flex w-full sm:max-w-md items-center">
                <Search class="absolute left-3 text-muted-foreground size-4" />
                <Input
                    type="text"
                    placeholder="Cari jenis penerimaan..."
                    v-model="search"
                    class="w-full pl-9 shadow-sm bg-white dark:bg-slate-900"
                />
            </div>
        </div>

        <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
            <Table class="min-w-full">
                <TableHeader class="bg-muted/40">
                    <TableRow>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Nama Jenis</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Keterangan</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Status</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in receiptTypes.data" :key="item.id">
                        <TableCell class="py-3 font-medium text-foreground">
                            {{ item.name }}
                        </TableCell>
                        <TableCell class="py-3 text-sm text-muted-foreground">{{ item.description || '-' }}</TableCell>
                        <TableCell class="py-3">
                            <span :class="item.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ item.is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </TableCell>
                        <TableCell class="py-3 text-right space-x-2">
                            <Button variant="secondary" size="sm" @click="openSubListDialog(item)" class="gap-1">
                                <ListTree class="size-3.5" />
                                Sub-Jenis ({{ item.children?.length || 0 }})
                            </Button>
                            <Button variant="outline" size="sm" @click="openEditDialog(item)">Edit</Button>
                            <Button variant="destructive" size="sm" @click="confirmDelete(item)">Hapus</Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="receiptTypes.data.length === 0">
                        <TableCell colspan="4" class="h-24 text-center text-muted-foreground text-sm">
                            Belum ada data jenis penerimaan.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>



        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Penghapusan</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus jenis penerimaan <strong>{{ typeToDelete?.name }}</strong>?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteItem" :disabled="deleteForm.processing">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="isSubListDialogOpen" @update:open="isSubListDialogOpen = $event">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Kelola Sub-Jenis: {{ activeParent?.name }}</DialogTitle>
                    <DialogDescription>
                        Daftar sub-jenis untuk penerimaan ini.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <div class="flex justify-end mb-3">
                        <Button size="sm" @click="openCreateSubDialog">Tambah Sub-Jenis</Button>
                    </div>
                    
                    <div class="border rounded-md">
                        <Table>
                            <TableHeader class="bg-muted/40">
                                <TableRow>
                                    <TableHead class="py-2 text-xs">Nama Sub-Jenis</TableHead>
                                    <TableHead class="py-2 text-xs">Status</TableHead>
                                    <TableHead class="py-2 text-xs text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="child in activeParent?.children" :key="child.id">
                                    <TableCell class="py-2 font-medium">{{ child.name }}</TableCell>
                                    <TableCell class="py-2">
                                        <span :class="child.is_active ? 'text-emerald-600' : 'text-red-600'" class="text-xs font-medium">
                                            {{ child.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-2 text-right space-x-2">
                                        <Button variant="ghost" size="sm" class="h-7 px-2" @click="openEditDialog(child)">Edit</Button>
                                        <Button variant="ghost" size="sm" class="h-7 px-2 text-destructive" @click="confirmDelete(child)">Hapus</Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!activeParent?.children || activeParent.children.length === 0">
                                    <TableCell colspan="3" class="text-center text-muted-foreground text-sm py-4">Belum ada sub-jenis.</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="isSubListDialogOpen = false">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog :open="isFormDialogOpen" @update:open="isFormDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Edit' : 'Tambah' }} Jenis Penerimaan</DialogTitle>
                    <DialogDescription>
                        Isi form di bawah ini untuk {{ editingId ? 'mengubah' : 'menambahkan' }} jenis penerimaan.
                    </DialogDescription>
                </DialogHeader>
                
                <form @submit.prevent="submitForm" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="name">Nama Jenis</Label>
                        <Input id="name" v-model="form.name" placeholder="Contoh: Pajak Daerah, Retribusi, dll" />
                        <span class="text-xs text-red-500" v-if="form.errors.name">{{ form.errors.name }}</span>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="description">Keterangan (Opsional)</Label>
                        <Textarea id="description" v-model="form.description" placeholder="Penjelasan singkat mengenai jenis penerimaan ini" />
                        <span class="text-xs text-red-500" v-if="form.errors.description">{{ form.errors.description }}</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Switch id="is_active" v-model="form.is_active" />
                        <Label for="is_active">Aktif</Label>
                    </div>
                    
                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isFormDialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>

</template>
