<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogTrigger, DialogFooter } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { ArrowRight, FolderOpen, Plus, Search } from 'lucide-vue-next';
import AccountTreeRow from '@/Components/AccountTreeRow.vue';

const props = defineProps({
    activeTree: Array,
    leafAccounts: Array,
    currentVersion: {
        type: Number,
        default: 0
    },
    currentVersionName: {
        type: String,
        default: 'Induk'
    },
    fundingSources: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    },
    rbaType: {
        type: String,
        default: 'Belanja'
    }
});

const isAddDialogOpen = ref(false);
const searchQuery = ref('');

const filteredLeaves = computed(() => {
    if (!searchQuery.value) return props.leafAccounts;
    const lowerQuery = searchQuery.value.toLowerCase();
    return props.leafAccounts.filter(acc => 
        acc.code.toLowerCase().includes(lowerQuery) || 
        acc.name.toLowerCase().includes(lowerQuery)
    );
});

const isSetupDialogOpen = ref(false);
const selectedAccount = ref(null);

const documentForm = useForm({
    account_code_id: '',
    funding_source_id: '',
    pptk_id: ''
});

const openSetupDocument = (acc) => {
    selectedAccount.value = acc;
    documentForm.account_code_id = acc.id;
    documentForm.funding_source_id = '';
    documentForm.pptk_id = '';
    documentForm.clearErrors();
    
    isAddDialogOpen.value = false;
    isSetupDialogOpen.value = true;
};

const submitDocument = () => {
    documentForm.post('/rba/documents', {
        onSuccess: () => {
            isSetupDialogOpen.value = false;
            searchQuery.value = '';
            selectedAccount.value = null;
        }
    });
};

const isDeleteDialogOpen = ref(false);
const documentToDelete = ref(null);

const confirmDeleteDocument = (row) => {
    documentToDelete.value = row;
    isDeleteDialogOpen.value = true;
};

const deleteDocument = () => {
    if (!documentToDelete.value || !documentToDelete.value.rba_document_id) return;
    
    router.delete(`/rba/documents/${documentToDelete.value.rba_document_id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            documentToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head :title="`Kertas Kerja RBA ${props.rbaType}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child class="text-xs text-muted-foreground">
                                    <span class="cursor-default">Perencanaan</span>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">RBA {{ props.rbaType }}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <div class="flex items-center gap-3">
                        <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">Kertas Kerja RBA {{ props.rbaType }}</h2>
                        <div class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                            Versi Aktif: {{ props.currentVersionName }}
                        </div>
                    </div>
                </div>
                <Dialog v-model:open="isAddDialogOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="w-4 h-4 mr-2" />
                            Tambah Rekening RBA
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[600px] max-h-[85vh] flex flex-col">
                        <DialogHeader>
                            <DialogTitle>Tambah Rekening ke Kertas Kerja</DialogTitle>
                            <DialogDescription>
                                Cari dan pilih rekening rincian (daun) yang akan disusun anggarannya.
                            </DialogDescription>
                        </DialogHeader>
                        
                        <div class="relative mt-2">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input v-model="searchQuery" placeholder="Cari kode atau nama rekening..." class="pl-9" />
                        </div>

                        <div class="flex-1 overflow-y-auto mt-4 border rounded-md min-h-[300px]">
                            <Table>
                                <TableHeader class="sticky top-0 bg-background z-10 shadow-sm">
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama Rekening</TableHead>
                                        <TableHead class="w-[100px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="acc in filteredLeaves.slice(0, 100)" :key="acc.id">
                                        <TableCell class="font-medium">{{ acc.code }}</TableCell>
                                        <TableCell>{{ acc.name }}</TableCell>
                                        <TableCell class="text-right">
                                            <Button @click="openSetupDocument(acc)" variant="outline" size="sm">
                                                Pilih
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="filteredLeaves.length === 0">
                                        <TableCell colspan="3" class="h-24 text-center text-muted-foreground">
                                            Tidak ada rekening yang cocok dengan pencarian.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-else-if="filteredLeaves.length > 100">
                                        <TableCell colspan="3" class="text-center text-xs text-muted-foreground py-2 bg-muted/30">
                                            Menampilkan 100 dari {{ filteredLeaves.length }} hasil. Ketik lebih spesifik untuk menyaring.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </DialogContent>
                </Dialog>

                <!-- Dialog Setup Document -->
                <Dialog v-model:open="isSetupDialogOpen">
                    <DialogContent class="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>Pengaturan Dokumen RBA</DialogTitle>
                            <DialogDescription>
                                Tentukan sumber dana dan PPTK penanggung jawab untuk <strong>{{ selectedAccount?.name }}</strong>.
                            </DialogDescription>
                        </DialogHeader>
                        
                        <form @submit.prevent="submitDocument" class="space-y-4 py-4">
                            <div class="space-y-2">
                                <Label for="funding_source_id">Sumber Dana <span class="text-destructive">*</span></Label>
                                <Select v-model="documentForm.funding_source_id" required>
                                    <SelectTrigger id="funding_source_id">
                                        <SelectValue placeholder="Pilih Sumber Dana" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="fs in fundingSources" :key="fs.id" :value="fs.id.toString()">
                                                {{ fs.name }} <span v-if="fs.code" class="text-muted-foreground text-xs ml-1">({{ fs.code }})</span>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p v-if="documentForm.errors.funding_source_id" class="text-[10px] text-destructive">{{ documentForm.errors.funding_source_id }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="pptk_id">Penanggung Jawab (PPTK) <span class="text-destructive">*</span></Label>
                                <Select v-model="documentForm.pptk_id" required>
                                    <SelectTrigger id="pptk_id">
                                        <SelectValue placeholder="Pilih PPTK" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                                {{ user.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p v-if="documentForm.errors.pptk_id" class="text-[10px] text-destructive">{{ documentForm.errors.pptk_id }}</p>
                            </div>
                            
                            <DialogFooter class="mt-6 pt-4 border-t">
                                <Button type="button" variant="outline" @click="isSetupDialogOpen = false; isAddDialogOpen = true" :disabled="documentForm.processing">Kembali</Button>
                                <Button type="submit" variant="default" :disabled="documentForm.processing || !documentForm.funding_source_id || !documentForm.pptk_id">
                                    <span v-if="documentForm.processing">Memproses...</span>
                                    <span v-else>Buat Dokumen</span>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <!-- Delete Confirmation Dialog -->
                <Dialog v-model:open="isDeleteDialogOpen">
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Konfirmasi Penghapusan</DialogTitle>
                            <DialogDescription class="text-destructive font-semibold">
                                Peringatan! Tindakan ini tidak dapat dibatalkan.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="py-4">
                            <p class="text-sm">Anda yakin ingin menghapus Kertas Kerja RBA untuk rekening <strong>{{ documentToDelete?.code }} - {{ documentToDelete?.name }}</strong>?</p>
                            <p class="text-sm mt-2 text-muted-foreground">Menghapus dokumen ini juga akan menghapus <strong>seluruh rincian</strong> yang ada di dalamnya secara permanen.</p>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                            <Button variant="destructive" @click="deleteDocument">Hapus Permanen</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FolderOpen class="w-5 h-5" /> Dokumen RBA Aktif ({{ props.rbaType }})
                    </CardTitle>
                    <CardDescription>
                        Daftar rekening yang telah dianggarkan pada tahun aktif. Klik tombol Tambah di pojok kanan atas untuk memasukkan rekening baru.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[250px]">Kode</TableHead>
                                    <TableHead>Nama Rekening</TableHead>
                                    <TableHead class="text-right w-[200px]">Total Anggaran</TableHead>
                                    <TableHead class="text-right w-[150px]">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="activeTree.length > 0">
                                    <AccountTreeRow 
                                        v-for="node in activeTree" 
                                        :key="node.id" 
                                        :row="node" 
                                        :level="0" 
                                        @delete-document="confirmDeleteDocument"
                                    />
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="4" class="h-32 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center justify-center">
                                            <FolderOpen class="w-10 h-10 mb-2 text-muted-foreground/50" />
                                            <p>Belum ada dokumen RBA yang disusun di tahun ini.</p>
                                            <p class="text-sm">Klik "Tambah Rekening RBA" untuk memulai.</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
