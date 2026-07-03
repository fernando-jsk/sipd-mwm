<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Label } from '@/Components/ui/label';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    settings: [
        {
            key: 'budget_validation_type',
            value: props.settings.budget_validation_type?.value || 'warning'
        }
    ]
});

const submit = () => {
    form.post('/settings');
};
</script>

<template>
    <Head title="Pengaturan Sistem" />

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
                                <BreadcrumbPage class="text-xs">Sistem</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Pengaturan Sistem
                    </h2>
                </div>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>

        <div class="max-w-4xl mx-auto py-6">
            <form @submit.prevent="submit">
                <Card class="border-border/80 shadow-sm">
                    <CardHeader class="border-b border-border/80 pb-4">
                        <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Konfigurasi Modul Keuangan</CardTitle>
                        <CardDescription class="text-xs text-muted-foreground mt-0.5">Atur perilaku sistem terkait anggaran dan transaksi.</CardDescription>
                    </CardHeader>
                    
                    <CardContent class="space-y-6">
                        <div class="grid gap-3">
                            <div>
                                <Label class="text-sm font-semibold text-foreground">Validasi Pagu Anggaran</Label>
                                <p class="text-xs text-muted-foreground mt-1">{{ props.settings.budget_validation_type?.description || 'Tipe validasi pagu saat pengeluaran melebihi anggaran.' }}</p>
                            </div>
                            
                            <RadioGroup v-model="form.settings[0].value" class="flex flex-col space-y-2 mt-2">
                                <div class="flex items-center space-x-2 border rounded-md p-3" :class="form.settings[0].value === 'warning' ? 'border-primary bg-primary/5' : 'border-border'">
                                    <RadioGroupItem id="warning" value="warning" />
                                    <Label for="warning" class="flex flex-col cursor-pointer">
                                        <span class="font-medium me-auto">Warning (Hanya Peringatan)</span>
                                        <span class="text-xs text-muted-foreground">Mengizinkan transaksi dilanjutkan meskipun melebihi pagu anggaran, namun akan memunculkan peringatan.</span>
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2 border rounded-md p-3" :class="form.settings[0].value === 'block' ? 'border-destructive bg-destructive/5' : 'border-border'">
                                    <RadioGroupItem id="block" value="block" />
                                    <Label for="block" class="flex flex-col cursor-pointer">
                                        <span class="font-medium me-auto text-destructive">Strict / Block (Cegah Transaksi)</span>
                                        <span class="text-xs text-muted-foreground">Sistem akan memblokir secara paksa (error) jika input pengeluaran melebihi sisa pagu anggaran.</span>
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>
                    </CardContent>
                    
                    <CardFooter class="flex justify-end space-x-2 bg-muted/20 border-t border-border/80 py-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
