<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

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
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Pengaturan</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link href="/users" class="text-xs">User</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Edit</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Edit User
                    </h2>
                </div>
            </div>
        </template>

        <div class="max-w-3xl mx-auto">
            <Card class="border border-border/80 shadow-sm">
                <CardHeader class="border-b border-border/80">
                    <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Data User</CardTitle>
                    <CardDescription class="text-xs text-muted-foreground mt-0.5">Ubah detail informasi user. Biarkan password kosong jika tidak ingin mengubahnya.</CardDescription>
                </CardHeader>
                <form @submit.prevent="submit">
                        <CardContent class="space-y-4">
                            <div class="grid gap-1.5">
                                <Label for="name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.name}">Nama Lengkap</Label>
                                <Input 
                                    id="name" 
                                    type="text" 
                                    v-model="form.name" 
                                    :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.name, 'focus-visible:ring-primary': !form.errors.name}"
                                    :aria-invalid="!!form.errors.name"
                                    required 
                                />
                                <p v-if="form.errors.name" class="text-[11px] text-destructive">{{ form.errors.name }}</p>
                            </div>
                            
                            <div class="grid gap-1.5">
                                <Label for="username" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.username}">Username</Label>
                                <Input 
                                    id="username" 
                                    type="text" 
                                    v-model="form.username" 
                                    :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.username, 'focus-visible:ring-primary': !form.errors.username}"
                                    :aria-invalid="!!form.errors.username"
                                    required 
                                />
                                <p v-if="form.errors.username" class="text-[11px] text-destructive">{{ form.errors.username }}</p>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="password" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.password}">Password (Opsional)</Label>
                                <Input 
                                    id="password" 
                                    type="password" 
                                    v-model="form.password" 
                                    placeholder="Kosongkan jika tidak diubah"
                                    :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.password, 'focus-visible:ring-primary': !form.errors.password}"
                                    :aria-invalid="!!form.errors.password"
                                />
                                <p v-if="form.errors.password" class="text-[11px] text-destructive">{{ form.errors.password }}</p>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="role" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.role}">Role (Hak Akses)</Label>
                                <Select v-model="form.role" required>
                                    <SelectTrigger :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.role, 'focus-visible:ring-primary': !form.errors.role}" :aria-invalid="!!form.errors.role">
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
                                <p v-if="form.errors.role" class="text-[11px] text-destructive">{{ form.errors.role }}</p>
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
    </AuthenticatedLayout>
</template>
