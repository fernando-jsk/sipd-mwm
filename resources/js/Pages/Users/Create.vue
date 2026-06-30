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
    roles: Array,
});

const form = useForm({
    name: '',
    username: '',
    password: '',
    role: '',
});

const submit = () => {
    form.post('/users');
};
</script>

<template>
    <Head title="Tambah User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Tambah User Baru
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Data User</CardTitle>
                        <CardDescription>Masukkan detail user baru di bawah ini.</CardDescription>
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
                                <Label for="password">Password</Label>
                                <Input 
                                    id="password" 
                                    type="password" 
                                    v-model="form.password" 
                                    :class="{'border-red-500': form.errors.password}"
                                    required 
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
                            <Button type="submit" :disabled="form.processing">Simpan</Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
