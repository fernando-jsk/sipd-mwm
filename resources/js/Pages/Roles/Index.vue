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
    roles: Object,
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
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Manajemen Role
                </h2>
                <Link href="/roles/create">
                    <Button>Tambah Role</Button>
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
                                    <TableHead>Nama Role</TableHead>
                                    <TableHead>Jumlah User</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="role in roles.data" :key="role.id">
                                    <TableCell class="font-medium">{{ role.name }}</TableCell>
                                    <TableCell>{{ role.users_count }} Users</TableCell>
                                    <TableCell class="text-right space-x-2">
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
                                    <TableCell colspan="3" class="h-24 text-center">
                                        Belum ada data role.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        
                    </div>
                </div>
            </div>
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
