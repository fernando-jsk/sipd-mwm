<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const props = defineProps({
    user: Object,
    roles: Array,
});

const currentRole = props.user.roles.length > 0 ? props.user.roles[0].name : '';

const form = useForm({
    name: props.user.name,
    username: props.user.username,
    password: '',
    role: currentRole,
});

const submit = () => {
    form.put(`/users/${props.user.id}`);
};
</script>

<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit User
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Data User</CardTitle>
                        <CardDescription>Ubah detail informasi user. Biarkan password kosong jika tidak ingin mengubahnya.</CardDescription>
                    </CardHeader>
                    <form @submit.prevent="submit">
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Nama Lengkap</Label>
                                <Input 
                                    id="name" 
                                    type="text" 
                                    v-model="form.name" 
                                    :class="{'border-red-500': form.errors.name}"
                                    required 
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="username">Username</Label>
                                <Input 
                                    id="username" 
                                    type="text" 
                                    v-model="form.username" 
                                    :class="{'border-red-500': form.errors.username}"
                                    required 
                                />
                                <p v-if="form.errors.username" class="text-sm text-red-500">{{ form.errors.username }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="password">Password (Opsional)</Label>
                                <Input 
                                    id="password" 
                                    type="password" 
                                    v-model="form.password" 
                                    placeholder="Kosongkan jika tidak diubah"
                                    :class="{'border-red-500': form.errors.password}"
                                />
                                <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="role">Role (Hak Akses)</Label>
                                <Select v-model="form.role" required>
                                    <SelectTrigger :class="{'border-red-500': form.errors.role}">
                                        <SelectValue placeholder="Pilih role..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                                                {{ role.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role" class="text-sm text-red-500">{{ form.errors.role }}</p>
                            </div>
                        </CardContent>
                        <CardFooter class="flex justify-end space-x-2">
                            <Link href="/users">
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
