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
    users: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/users', { search: value }, { preserveState: true, replace: true });
    }, 300);
});

const deleteForm = useForm({});
const isDeleteDialogOpen = ref(false);
const userToDelete = ref(null);

const confirmDelete = (user) => {
    userToDelete.value = user;
    isDeleteDialogOpen.value = true;
};

const deleteUser = () => {
    deleteForm.delete(`/users/${userToDelete.value.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            userToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Manajemen User" />

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
                                <BreadcrumbPage class="text-xs">User</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Manajemen User
                    </h2>
                </div>
                <Link href="/users/create">
                    <Button>Tambah User</Button>
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
                    placeholder="Cari nama atau username..."
                    v-model="search"
                    class="w-full pl-9 shadow-sm bg-white dark:bg-slate-900"
                />
            </div>
        </div>

        <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
            <Table class="min-w-full">
                <TableHeader class="bg-muted/40">
                    <TableRow>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Nama</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Username</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Role</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell class="py-3 font-medium text-foreground">{{ user.name }}</TableCell>
                        <TableCell class="py-3 text-sm text-muted-foreground">{{ user.username }}</TableCell>
                        <TableCell class="py-3">
                            <span v-for="role in user.roles" :key="role.id" class="text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1 bg-[#4ADE80]/10 text-emerald-700 dark:text-emerald-400 mr-1">
                                <span class="size-1.5 rounded-full bg-[#4ADE80]"></span>
                                {{ role.name }}
                            </span>
                        </TableCell>
                        <TableCell class="py-3 text-right space-x-2">
                            <Link :href="`/users/${user.id}/edit`">
                                <Button variant="outline" size="sm">Edit</Button>
                            </Link>
                            <Button variant="destructive" size="sm" @click="confirmDelete(user)">Hapus</Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="users.data.length === 0">
                        <TableCell colspan="4" class="h-24 text-center text-muted-foreground text-sm">
                            Belum ada data user.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            
            <!-- Pagination placeholder if needed -->
        </div>

        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Penghapusan</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus user <strong>{{ userToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteUser" :disabled="deleteForm.processing">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
