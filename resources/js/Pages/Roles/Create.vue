<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';

const props = defineProps({
    permissions: Array,
});

const form = useForm({
    name: '',
    permissions: [],
});

const submit = () => {
    form.post('/roles');
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
    <Head title="Tambah Role" />

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
                                    <Link href="/roles" class="text-xs">Role</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Tambah</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Tambah Role Baru
                    </h2>
                </div>
            </div>
        </template>

        <div class="max-w-3xl mx-auto">
            <form @submit.prevent="submit">
                <Card class="border border-border/80 shadow-sm">
                <CardHeader class="border-b border-border/80">
                    <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Data Role</CardTitle>
                    <CardDescription class="text-xs text-muted-foreground mt-0.5">Masukkan nama role baru dan tentukan hak aksesnya.</CardDescription>
                </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-1.5">
                                <Label for="name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.name}">Nama Role</Label>
                                <Input 
                                    id="name" 
                                    type="text" 
                                    v-model="form.name" 
                                    :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.name, 'focus-visible:ring-primary': !form.errors.name}"
                                    :aria-invalid="!!form.errors.name"
                                    placeholder="Contoh: admin-keuangan"
                                    required 
                                />
                                <p v-if="form.errors.name" class="text-[11px] text-destructive">{{ form.errors.name }}</p>
                            </div>
                            
                            <div class="space-y-3">
                                <Label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.permissions}">Hak Akses (Permissions)</Label>
                                <p v-if="form.errors.permissions" class="text-[11px] text-destructive">{{ form.errors.permissions }}</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border border-border/80 p-5 rounded-lg bg-muted/20">
                                    <div v-for="permission in permissions" :key="permission.id" class="flex items-start space-x-3">
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
                                    <div v-if="permissions.length === 0" class="text-sm text-muted-foreground">
                                        Belum ada permission di database.
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter class="flex justify-end space-x-2">
                            <Link href="/roles">
                                <Button variant="outline" type="button">Batal</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">Simpan Role</Button>
                        </CardFooter>
                </Card>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
