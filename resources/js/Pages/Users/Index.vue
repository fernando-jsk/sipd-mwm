<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { ref } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps({
    users: Object,
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
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Manajemen User
                </h2>
                <Link href="/users/create">
                    <Button>Tambah User</Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash?.message" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.flash.message }}</span>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Username</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users.data" :key="user.id">
                                    <TableCell class="font-medium">{{ user.name }}</TableCell>
                                    <TableCell>{{ user.username }}</TableCell>
                                    <TableCell>
                                        <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 mr-1">
                                            {{ role.name }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Link :href="`/users/${user.id}/edit`">
                                            <Button variant="outline" size="sm">Edit</Button>
                                        </Link>
                                        <Button variant="destructive" size="sm" @click="confirmDelete(user)">Hapus</Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="users.data.length === 0">
                                    <TableCell colspan="4" class="h-24 text-center">
                                        Belum ada data user.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        
                        <!-- Pagination placeholder if needed -->
                    </div>
                </div>
            </div>
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
