<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
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
    roles: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/roles', { search: value }, { preserveState: true, replace: true });
    }, 300);
});

const deleteForm = useForm({});
const isDeleteDialogOpen = ref(false);
const roleToDelete = ref(null);

const confirmDelete = (role) => {
    roleToDelete.value = role;
    isDeleteDialogOpen.value = true;
};

const deleteRole = () => {
    deleteForm.delete(`/roles/${roleToDelete.value.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            roleToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Manajemen Role" />

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
                                <BreadcrumbPage class="text-xs">Role</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Manajemen Role
                    </h2>
                </div>
                <Link href="/roles/create">
                    <Button>Tambah Role</Button>
                </Link>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>

        <div class="mb-5 flex items-center justify-between">
            <div class="relative flex w-full sm:max-w-md items-center">
                <Search class="absolute left-3 text-muted-foreground size-4" />
                <Input
                    type="text"
                    placeholder="Cari nama role..."
                    v-model="search"
                    class="w-full pl-9 shadow-sm bg-white dark:bg-slate-900"
                />
            </div>
        </div>

        <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
            <Table class="min-w-full">
                <TableHeader class="bg-muted/40">
                    <TableRow>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Nama Role</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Jumlah User</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="role in roles.data" :key="role.id">
                        <TableCell class="py-3 font-medium text-foreground">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="size-2 rounded-full" :class="role.name === 'super-admin' ? 'bg-destructive' : 'bg-primary'"></span>
                                {{ role.name }}
                            </span>
                        </TableCell>
                        <TableCell class="py-3 text-sm text-muted-foreground">{{ role.users_count }} Users</TableCell>
                        <TableCell class="py-3 text-right space-x-2">
                            <Link :href="`/roles/${role.id}/edit`">
                                <Button variant="outline" size="sm">Edit</Button>
                            </Link>
                            <Button 
                                variant="destructive" 
                                size="sm" 
                                @click="confirmDelete(role)"
                                :disabled="role.name === 'super-admin'"
                            >
                                Hapus
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="roles.data.length === 0">
                        <TableCell colspan="3" class="h-24 text-center text-muted-foreground text-sm">
                            Belum ada data role.
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
                        Apakah Anda yakin ingin menghapus role <strong>{{ roleToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan dan akan mempengaruhi user yang memiliki role ini.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteRole" :disabled="deleteForm.processing">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
