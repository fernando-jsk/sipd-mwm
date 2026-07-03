<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card';

const props = defineProps({
    accountCode: Object,
});

const form = useForm({
    code: props.accountCode.code,
    name: props.accountCode.name,
    description: props.accountCode.description || '',
    is_active: !!props.accountCode.is_active,
});

const submit = () => {
    form.put(`/account-codes/${props.accountCode.id}`);
};
</script>

<template>
    <Head title="Edit Kode Rekening" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Master Data</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <Link href="/account-codes" class="text-xs text-muted-foreground hover:text-foreground">Kode Rekening</Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Edit Kode</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Edit Kode Rekening
                    </h2>
                </div>
            </div>
        </template>

        <div class="max-w-2xl mx-auto py-6">
            <form @submit.prevent="submit">
                <Card class="border-border/80 shadow-sm">
                    <CardHeader class="border-b border-border/80 pb-4">
                        <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Detail Rekening</CardTitle>
                        <CardDescription class="text-xs text-muted-foreground mt-0.5">Perbarui informasi master kode rekening akuntansi.</CardDescription>
                    </CardHeader>
                    
                    <CardContent class="space-y-4">
                        <div class="grid gap-1.5">
                            <Label for="code" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.code}">Kode Rekening</Label>
                            <Input 
                                id="code" 
                                type="text" 
                                v-model="form.code" 
                                :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.code}"
                                required 
                            />
                            <p v-if="form.errors.code" class="text-[11px] text-destructive">{{ form.errors.code }}</p>
                        </div>
                        
                        <div class="grid gap-1.5">
                            <Label for="name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.name}">Nama Akun</Label>
                            <Input 
                                id="name" 
                                type="text" 
                                v-model="form.name" 
                                :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.name}"
                                required 
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="description" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.description}">Keterangan (Opsional)</Label>
                            <Textarea 
                                id="description" 
                                v-model="form.description" 
                                :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.description}"
                                rows="3"
                            />
                            <p v-if="form.errors.description" class="text-[11px] text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="flex items-center space-x-2 pt-2">
                            <Switch id="is_active" v-model="form.is_active" />
                            <Label for="is_active" class="text-sm cursor-pointer">Status Aktif</Label>
                        </div>
                    </CardContent>
                    
                    <CardFooter class="flex justify-end space-x-2 bg-muted/20 border-t border-border/80 py-4">
                        <Link href="/account-codes">
                            <Button variant="outline" type="button">Batal</Button>
                        </Link>
                        <Button type="submit" :disabled="form.processing">Simpan Perubahan</Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
