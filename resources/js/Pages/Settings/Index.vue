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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Trash2, AlertTriangle, UploadCloud } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: Object,
    activeVersion: {
        type: Number,
        default: 0
    },
    availableVersions: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const canManageRevision = page.props.auth?.permissions?.includes('manage budget revision');

const isRevisionDialogOpen = ref(false);
const isProcessingRevision = ref(false);

const replicationForm = useForm({
    source_version: props.activeVersion.toString(),
    version_name: ''
});

const executeRevision = () => {
    isProcessingRevision.value = true;
    replicationForm.post('/settings/replikasi', {
        onSuccess: () => {
            isRevisionDialogOpen.value = false;
            replicationForm.reset();
        },
        onFinish: () => {
            isProcessingRevision.value = false;
        }
    });
};

const activeVersionForm = useForm({
    version: props.activeVersion.toString()
});

const saveActiveVersion = () => {
    activeVersionForm.post('/settings/active-version');
};

const isDeleteDialogOpen = ref(false);
const versionToDelete = ref(null);
const deleteConfirmName = ref('');

const openDeleteDialog = (version) => {
    if (version.version === 0) return; // Guard
    versionToDelete.value = version;
    deleteConfirmName.value = '';
    isDeleteDialogOpen.value = true;
};

const confirmDeleteVersion = () => {
    if (deleteConfirmName.value !== versionToDelete.value.version_name) return;
    
    router.delete(`/settings/version/${versionToDelete.value.version}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            versionToDelete.value = null;
        }
    });
};

const importForm = useForm({
    file: null,
    start_row: 4,
    start_column: 'C'
});

const submitImport = () => {
    importForm.post('/settings/import-rba', {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset('file');
            // reset file input visually if needed
        }
    });
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

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

            <!-- Manajemen Versi RBA (Hanya jika punya akses) -->
            <Card v-if="canManageRevision" class="border-border/80 shadow-sm mt-6">
                <CardHeader class="border-b border-border/80 pb-4">
                    <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Manajemen Versi RBA</CardTitle>
                    <CardDescription class="text-xs text-muted-foreground mt-0.5">Kontrol tahapan aktif dan replikasi Kertas Kerja Perencanaan Anggaran.</CardDescription>
                </CardHeader>
                
                <CardContent class="space-y-6">
                    <!-- Aktifkan Versi Tertentu -->
                    <div class="p-4 border rounded-md bg-muted/10 space-y-4">
                        <div>
                            <Label class="text-sm font-semibold text-foreground">Tahapan RBA Aktif Saat Ini</Label>
                            <p class="text-xs text-muted-foreground mt-1">Pilih versi/tahapan RBA yang akan digunakan secara global oleh sistem pada tahun anggaran saat ini.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Select v-model="activeVersionForm.version">
                                <SelectTrigger class="w-[300px]">
                                    <SelectValue placeholder="Pilih Tahapan RBA" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem v-for="v in props.availableVersions" :key="v.version" :value="v.version.toString()">
                                            {{ v.version_name }} (Versi {{ v.version }})
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <Button @click="saveActiveVersion" :disabled="activeVersionForm.processing || activeVersionForm.version === props.activeVersion.toString()">
                                Set Sebagai Aktif
                            </Button>
                        </div>
                    </div>

                    <!-- Daftar Riwayat Versi & Tombol Replikasi -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <Label class="text-sm font-semibold text-foreground">Riwayat Dokumen RBA</Label>
                            <Button @click="isRevisionDialogOpen = true" variant="outline" size="sm" class="border-primary text-primary hover:bg-primary hover:text-white">
                                + Buat Replikasi
                            </Button>
                        </div>
                        
                        <div class="border rounded-md divide-y overflow-hidden">
                            <div v-for="v in props.availableVersions" :key="v.version" class="flex items-center justify-between p-3 bg-card hover:bg-muted/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                        V{{ v.version }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-sm">{{ v.version_name }}</span>
                                        <span v-if="v.version === props.activeVersion" class="text-[10px] uppercase font-bold text-emerald-600">Terpilih Aktif</span>
                                    </div>
                                </div>
                                <Button 
                                    v-if="v.version !== 0" 
                                    variant="ghost" 
                                    size="icon" 
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    @click="openDeleteDialog(v)"
                                    title="Hapus Permanen">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Panel Impor Massal Data RBA -->
            <Card v-if="canManageRevision" class="border-border/80 shadow-sm mt-6">
                <CardHeader class="border-b border-border/80 pb-4 bg-muted/10">
                    <CardTitle class="text-base font-bold text-secondary dark:text-foreground flex items-center gap-2">
                        <UploadCloud class="w-5 h-5 text-primary" />
                        Impor Massal Data RBA
                    </CardTitle>
                    <CardDescription class="text-xs text-muted-foreground mt-0.5">Unggah file Excel (.xlsx) Kertas Kerja RBA untuk diparsing otomatis menjadi struktur pohon rincian.</CardDescription>
                </CardHeader>
                
                <CardContent class="pt-6">
                    <form @submit.prevent="submitImport" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <Label for="rba_file">File Kertas Kerja RBA (.xlsx) <span class="text-destructive">*</span></Label>
                                <Input id="rba_file" type="file" accept=".xlsx, .xls" @change="handleFileChange" class="cursor-pointer" required />
                                <p class="text-[10px] text-muted-foreground">Pastikan format sejajar dengan template ekspor.</p>
                                <p v-if="importForm.errors.file" class="text-[10px] text-destructive">{{ importForm.errors.file }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="start_column">Huruf Kolom "Harga" <span class="text-destructive">*</span></Label>
                                <Input id="start_column" type="text" maxlength="2" class="uppercase" v-model="importForm.start_column" required />
                                <p class="text-[10px] text-muted-foreground">Misal: <strong>C</strong> untuk Induk, atau <strong>I</strong> untuk Pergeseran. Sistem akan otomatis membaca 5 kolom berurutan (Harga, Sat1, Vol1, Sat2, Vol2).</p>
                                <p v-if="importForm.errors.start_column" class="text-[10px] text-destructive">{{ importForm.errors.start_column }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="start_row">Mulai Baca Dari Baris Ke- <span class="text-destructive">*</span></Label>
                                <Input id="start_row" type="number" min="1" v-model="importForm.start_row" required />
                                <p class="text-[10px] text-muted-foreground">Abaikan baris kop surat & header tabel di atasnya.</p>
                                <p v-if="importForm.errors.start_row" class="text-[10px] text-destructive">{{ importForm.errors.start_row }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-amber-500/10 border border-amber-500/20 text-amber-700 rounded-md text-xs font-medium">
                            <AlertTriangle class="w-4 h-4 shrink-0" />
                            <p>Proses ini akan mereplace (menimpa) seluruh data rincian RBA pada tahun dan versi aktif saat ini (Versi {{ props.activeVersion }}). Pastikan Anda berada di tahapan versi yang tepat sebelum melakukan impor.</p>
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" variant="default" :disabled="importForm.processing || !importForm.file">
                                <span v-if="importForm.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Memproses Ribuan Baris...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <UploadCloud class="w-4 h-4" />
                                    Mulai Impor Data
                                </span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Dialog Konfirmasi Replikasi -->
            <Dialog v-model:open="isRevisionDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle class="text-primary">Buat Replikasi RBA</DialogTitle>
                        <DialogDescription class="mt-2">
                            Aksi ini akan menduplikasi seluruh data pada Kertas Kerja versi sumber menjadi sebuah tahapan versi yang baru.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form @submit.prevent="executeRevision" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="source_version">Salin Dari Versi Mana?</Label>
                            <Select v-model="replicationForm.source_version">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Sumber Salinan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem v-for="v in props.availableVersions" :key="v.version" :value="v.version.toString()">
                                            {{ v.version_name }} (Versi {{ v.version }})
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="version_name">Nama Versi / Tahapan Baru <span class="text-destructive">*</span></Label>
                            <Input id="version_name" v-model="replicationForm.version_name" placeholder="Misal: Pergeseran 1" required />
                            <p class="text-[10px] text-muted-foreground">Versi baru ini TIDAK akan otomatis aktif setelah dibuat.</p>
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                            <Button type="button" variant="outline" @click="isRevisionDialogOpen = false" :disabled="replicationForm.processing">Batal</Button>
                            <Button type="submit" variant="default" :disabled="replicationForm.processing || !replicationForm.version_name">
                                {{ replicationForm.processing ? 'Memproses...' : 'Mulai Replikasi' }}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Dialog Konfirmasi Hapus -->
            <Dialog v-model:open="isDeleteDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle class="text-destructive flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5" />
                            Peringatan Penghapusan
                        </DialogTitle>
                        <DialogDescription class="mt-2">
                            Anda akan menghapus permanen versi <strong>{{ versionToDelete?.version_name }}</strong>. Seluruh Kertas Kerja dan anggarannya pada tahapan ini akan hilang dan tidak dapat dipulihkan.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="space-y-4 py-4">
                        <div class="space-y-3">
                            <label for="confirmName" class="text-sm font-medium text-foreground leading-normal block">
                                Untuk mengkonfirmasi, ketikkan nama versi tersebut (<span class="font-bold select-all">{{ versionToDelete?.version_name }}</span>) di bawah ini:
                            </label>
                            <Input id="confirmName" v-model="deleteConfirmName" autocomplete="off" />
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                            <Button type="button" variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                            <Button type="button" variant="destructive" @click="confirmDeleteVersion" :disabled="deleteConfirmName !== versionToDelete?.version_name">
                                Ya, Hapus Permanen
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>
