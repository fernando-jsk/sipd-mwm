<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';

const props = defineProps({
    role: Object,
    permissions: Array,
});

// Get an array of permission names that this role currently has
const currentPermissions = props.role.permissions.map(p => p.name);

const form = useForm({
    name: props.role.name,
    permissions: currentPermissions,
});

const submit = () => {
    form.put(`/roles/${props.role.id}`);
};

const togglePermission = (permissionName) => {
    const index = form.permissions.indexOf(permissionName);
    if (index === -1) {
        form.permissions.push(permissionName);
    } else {
        form.permissions.splice(index, 1);
    }
};
</script>

<template>
    <Head title="Edit Role" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Role
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Data Role</CardTitle>
                        <CardDescription>Ubah nama role atau sesuaikan hak aksesnya.</CardDescription>
                    </CardHeader>
                    <form @submit.prevent="submit">
                        <CardContent class="space-y-6">
                            <div class="space-y-2">
                                <Label for="name">Nama Role</Label>
                                <Input 
                                    id="name" 
                                    type="text" 
                                    v-model="form.name" 
                                    :class="{'border-red-500': form.errors.name}"
                                    :disabled="role.name === 'super-admin'"
                                    required 
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                                <p v-if="role.name === 'super-admin'" class="text-xs text-amber-600">Nama role super-admin tidak dapat diubah.</p>
                            </div>
                            
                            <div class="space-y-3">
                                <Label class="text-base font-semibold">Hak Akses (Permissions)</Label>
                                <p v-if="form.errors.permissions" class="text-sm text-red-500">{{ form.errors.permissions }}</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border p-4 rounded-md bg-gray-50">
                                    <div v-for="permission in permissions" :key="permission.id" class="flex items-start space-x-2">
                                        <Checkbox 
                                            :id="`permission-${permission.id}`" 
                                            :model-value="form.permissions.includes(permission.name)"
                                            @update:model-value="togglePermission(permission.name)"
                                        />
                                        <label 
                                            :for="`permission-${permission.id}`" 
                                            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                        >
                                            {{ permission.name }}
                                        </label>
                                    </div>
                                    <div v-if="permissions.length === 0" class="text-sm text-gray-500">
                                        Belum ada permission di database.
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter class="flex justify-end space-x-2 border-t pt-4">
                            <Link href="/roles">
                                <Button variant="outline" type="button">Batal</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">Simpan Perubahan</Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
