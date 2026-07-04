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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

const props = defineProps({
    vendor: Object,
});

const form = useForm({
    name: props.vendor.name,
    type: props.vendor.type,
    address: props.vendor.address || '',
    phone: props.vendor.phone || '',
    director_name: props.vendor.director_name || '',
    npwp: props.vendor.npwp || '',
    bank_name: props.vendor.bank_name || '',
    bank_account_number: props.vendor.bank_account_number || '',
    bank_account_name: props.vendor.bank_account_name || '',
    is_active: !!props.vendor.is_active,
});

const submit = () => {
    form.put(`/vendors/${props.vendor.id}`);
};
</script>

<template>
    <Head title="Edit Rekanan" />

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
                                <Link href="/vendors" class="text-xs text-muted-foreground hover:text-foreground">Rekanan</Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Edit Data</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Edit Rekanan (Vendor)
                    </h2>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto py-6">
            <form @submit.prevent="submit">
                <Card class="border-border/80 shadow-sm">
                    <CardHeader class="border-b border-border/80 pb-4">
                        <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Informasi Rekanan</CardTitle>
                        <CardDescription class="text-xs text-muted-foreground mt-0.5">Perbarui profil rekanan untuk keperluan dokumen tagihan, SPK, dan pajak.</CardDescription>
                    </CardHeader>
                    
                    <CardContent class="space-y-6">
                        
                        <!-- Profil Dasar -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold border-b pb-2">Profil Dasar</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="grid gap-1.5">
                                    <Label for="name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.name}">Nama Rekanan / Perusahaan</Label>
                                    <Input 
                                        id="name" 
                                        v-model="form.name" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.name}"
                                        required 
                                    />
                                    <p v-if="form.errors.name" class="text-[11px] text-destructive">{{ form.errors.name }}</p>
                                </div>
                                
                                <div class="grid gap-1.5">
                                    <Label for="type" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.type}">Bentuk Usaha</Label>
                                    <Select v-model="form.type">
                                        <SelectTrigger :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.type}">
                                            <SelectValue placeholder="Pilih Bentuk Usaha" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="PT">PT (Perseroan Terbatas)</SelectItem>
                                            <SelectItem value="CV">CV (Commanditaire Vennootschap)</SelectItem>
                                            <SelectItem value="UD">UD (Usaha Dagang)</SelectItem>
                                            <SelectItem value="Koperasi">Koperasi</SelectItem>
                                            <SelectItem value="Perorangan">Perorangan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.type" class="text-[11px] text-destructive">{{ form.errors.type }}</p>
                                </div>

                                <div class="grid gap-1.5 md:col-span-2">
                                    <Label for="director_name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.director_name}">Nama Direktur / Pimpinan</Label>
                                    <Input 
                                        id="director_name" 
                                        v-model="form.director_name" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.director_name}"
                                    />
                                    <p v-if="form.errors.director_name" class="text-[11px] text-destructive">{{ form.errors.director_name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Kontak & Pajak -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold border-b pb-2">Kontak & Pajak</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="grid gap-1.5">
                                    <Label for="phone" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.phone}">Nomor Telepon</Label>
                                    <Input 
                                        id="phone" 
                                        v-model="form.phone" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.phone}"
                                    />
                                    <p v-if="form.errors.phone" class="text-[11px] text-destructive">{{ form.errors.phone }}</p>
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="npwp" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.npwp}">NPWP</Label>
                                    <Input 
                                        id="npwp" 
                                        v-model="form.npwp" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.npwp}"
                                    />
                                    <p v-if="form.errors.npwp" class="text-[11px] text-destructive">{{ form.errors.npwp }}</p>
                                </div>

                                <div class="grid gap-1.5 md:col-span-2">
                                    <Label for="address" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.address}">Alamat Lengkap</Label>
                                    <Textarea 
                                        id="address" 
                                        v-model="form.address" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.address}"
                                        rows="2"
                                    />
                                    <p v-if="form.errors.address" class="text-[11px] text-destructive">{{ form.errors.address }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Data Rekening Bank -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold border-b pb-2">Informasi Rekening Bank</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="grid gap-1.5">
                                    <Label for="bank_name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.bank_name}">Nama Bank</Label>
                                    <Input 
                                        id="bank_name" 
                                        v-model="form.bank_name" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.bank_name}"
                                    />
                                    <p v-if="form.errors.bank_name" class="text-[11px] text-destructive">{{ form.errors.bank_name }}</p>
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="bank_account_number" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.bank_account_number}">Nomor Rekening</Label>
                                    <Input 
                                        id="bank_account_number" 
                                        v-model="form.bank_account_number" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.bank_account_number}"
                                    />
                                    <p v-if="form.errors.bank_account_number" class="text-[11px] text-destructive">{{ form.errors.bank_account_number }}</p>
                                </div>

                                <div class="grid gap-1.5 md:col-span-2">
                                    <Label for="bank_account_name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider" :class="{'text-destructive': form.errors.bank_account_name}">Nama Pemilik Rekening</Label>
                                    <Input 
                                        id="bank_account_name" 
                                        v-model="form.bank_account_name" 
                                        :class="{'border-destructive focus-visible:ring-destructive/20': form.errors.bank_account_name}"
                                    />
                                    <p v-if="form.errors.bank_account_name" class="text-[11px] text-destructive">{{ form.errors.bank_account_name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Aktif -->
                        <div class="flex items-center space-x-2 pt-2 border-t mt-4">
                            <Switch id="is_active" v-model="form.is_active" />
                            <Label for="is_active" class="text-sm cursor-pointer">Status Aktif</Label>
                        </div>
                    </CardContent>
                    
                    <CardFooter class="flex justify-end space-x-2 bg-muted/20 border-t border-border/80 py-4">
                        <Link href="/vendors">
                            <Button variant="outline" type="button">Batal</Button>
                        </Link>
                        <Button type="submit" :disabled="form.processing">Simpan Perubahan</Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
