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
import { ArrowRight, FolderOpen, Plus, Search, Pencil, ArrowLeftRight, CheckCircle2 } from 'lucide-vue-next';
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
    },
    rbaViewType: {
        type: String,
        default: 'gelondongan'
    },
    gelondonganDocs: {
        type: Array,
        default: () => []
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
    pptk_id: '',
    rba_type: 'gelondongan',
    mapped_to_rba_id: ''
});

const openSetupDocument = (acc) => {
    selectedAccount.value = acc;
    documentForm.account_code_id = acc.id;
    documentForm.funding_source_id = '';
    documentForm.pptk_id = '';
    documentForm.rba_type = props.rbaViewType;
    documentForm.mapped_to_rba_id = '';
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

// Edit Document State & Methods
const isEditDialogOpen = ref(false);
const editingRow = ref(null);
const selectedNewAccount = ref(null);
const showAccountPicker = ref(false);
const accountSearchQuery = ref('');

const filteredLeavesForEdit = computed(() => {
    if (!accountSearchQuery.value) return props.leafAccounts;
    const lowerQuery = accountSearchQuery.value.toLowerCase();
    return props.leafAccounts.filter(acc => 
        acc.code.toLowerCase().includes(lowerQuery) || 
        acc.name.toLowerCase().includes(lowerQuery)
    );
});

const editForm = useForm({
    account_code_id: '',
    funding_source_id: '',
    pptk_id: ''
});

const openEditDocument = (row) => {
    editingRow.value = row;
    selectedNewAccount.value = null;
    showAccountPicker.value = false;
    accountSearchQuery.value = '';
    editForm.account_code_id = row.id;
    editForm.funding_source_id = row.funding_source_id ? row.funding_source_id.toString() : '';
    editForm.pptk_id = row.pptk_id ? row.pptk_id.toString() : '';
    editForm.clearErrors();
    isEditDialogOpen.value = true;
};

const selectReplacementAccount = (acc) => {
    selectedNewAccount.value = acc;
    editForm.account_code_id = acc.id;
    showAccountPicker.value = false;
};

const cancelAccountChange = () => {
    selectedNewAccount.value = null;
    if (editingRow.value) {
        editForm.account_code_id = editingRow.value.id;
    }
};

const submitEditDocument = () => {
    if (!editingRow.value || !editingRow.value.rba_document_id) return;

    editForm.put(`/rba/documents/${editingRow.value.rba_document_id}`, {
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editingRow.value = null;
            selectedNewAccount.value = null;
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
                    <div class="flex items-center gap-3 mt-2">
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
                            <DialogTitle>Tambah Rekening ke Kertas Kerja ({{ props.rbaViewType === 'rinci' ? 'Rinci' : 'Gelondongan' }})</DialogTitle>
                            <DialogDescription>
                                Cari dan pilih rekening yang akan disusun anggarannya sebagai RBA {{ props.rbaViewType === 'rinci' ? 'Rinci' : 'Gelondongan' }}.
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
                            <div v-if="documentForm.rba_type === 'rinci'" class="space-y-2">
                                <Label for="mapped_to_rba_id">Induk Gelondongan <span class="text-destructive">*</span></Label>
                                <Select v-model="documentForm.mapped_to_rba_id" required>
                                    <SelectTrigger id="mapped_to_rba_id">
                                        <SelectValue placeholder="Pilih Rekening Gelondongan Induk" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="doc in gelondonganDocs" :key="doc.id" :value="doc.id.toString()">
                                                {{ doc.code }} - {{ doc.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p v-if="documentForm.errors.mapped_to_rba_id" class="text-[10px] text-destructive">{{ documentForm.errors.mapped_to_rba_id }}</p>
                            </div>
                            
                            <DialogFooter class="mt-6 pt-4 border-t">
                                <Button type="button" variant="outline" @click="isSetupDialogOpen = false; isAddDialogOpen = true" :disabled="documentForm.processing">Kembali</Button>
                                <Button type="submit" variant="default" :disabled="documentForm.processing || !documentForm.funding_source_id || !documentForm.pptk_id || (documentForm.rba_type === 'rinci' && !documentForm.mapped_to_rba_id)">
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

                <!-- Dialog Edit Document -->
                <Dialog v-model:open="isEditDialogOpen">
                    <DialogContent class="sm:max-w-[550px] max-h-[85vh] flex flex-col">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2">
                                <Pencil class="w-4 h-4 text-primary" />
                                Edit Dokumen RBA
                            </DialogTitle>
                            <DialogDescription>
                                Ubah kode rekening atau pengaturan dokumen RBA tanpa mengubah rincian belanja yang sudah ada.
                            </DialogDescription>
                        </DialogHeader>

                        <!-- If Account Picker is active: display search & selection table -->
                        <div v-if="showAccountPicker" class="flex-1 flex flex-col min-h-0 space-y-3 py-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Pilih Rekening Pengganti</span>
                                <Button type="button" variant="ghost" size="sm" class="h-7 text-xs" @click="showAccountPicker = false">
                                    Batal Cari
                                </Button>
                            </div>
                            
                            <div class="relative">
                                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input v-model="accountSearchQuery" placeholder="Cari kode atau nama rekening pengganti..." class="pl-9 h-9 text-sm" />
                            </div>

                            <div class="flex-1 overflow-y-auto border rounded-md max-h-[300px]">
                                <Table>
                                    <TableHeader class="sticky top-0 bg-background z-10 shadow-sm">
                                        <TableRow>
                                            <TableHead>Kode</TableHead>
                                            <TableHead>Nama Rekening</TableHead>
                                            <TableHead class="w-[80px]"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="acc in filteredLeavesForEdit.slice(0, 100)" :key="acc.id">
                                            <TableCell class="font-mono text-xs font-medium">{{ acc.code }}</TableCell>
                                            <TableCell class="text-xs">{{ acc.name }}</TableCell>
                                            <TableCell class="text-right">
                                                <Button type="button" size="sm" class="h-7 text-xs" @click="selectReplacementAccount(acc)">
                                                    Pilih
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="filteredLeavesForEdit.length === 0">
                                            <TableCell colspan="3" class="h-20 text-center text-xs text-muted-foreground">
                                                Tidak ada rekening rincian yang cocok.
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-else-if="filteredLeavesForEdit.length > 100">
                                            <TableCell colspan="3" class="text-center text-xs text-muted-foreground py-2 bg-muted/30">
                                                Menampilkan 100 dari {{ filteredLeavesForEdit.length }} hasil. Ketik lebih spesifik untuk menyaring.
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>

                        <!-- Form Mode -->
                        <form v-else @submit.prevent="submitEditDocument" class="space-y-4 py-2">
                            <!-- Rekening Information & Switcher -->
                            <div class="grid gap-1.5">
                                <Label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                    Kode & Rekening Anggaran
                                </Label>
                                <div class="p-3.5 border rounded-xl bg-muted/30 space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="space-y-0.5">
                                            <span class="text-[10px] font-semibold text-muted-foreground uppercase">Rekening Saat Ini</span>
                                            <p class="font-mono text-xs font-bold text-secondary dark:text-foreground">{{ editingRow?.code }}</p>
                                            <p class="text-xs text-muted-foreground leading-snug">{{ editingRow?.name }}</p>
                                        </div>
                                        <Button type="button" variant="outline" size="sm" class="h-8 text-xs shrink-0" @click="showAccountPicker = true">
                                            <ArrowLeftRight class="w-3.5 h-3.5 mr-1" />
                                            Ganti Rekening
                                        </Button>
                                    </div>

                                    <!-- If New Account Selected -->
                                    <div v-if="selectedNewAccount" class="pt-3 border-t border-dashed border-primary/40">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="space-y-0.5">
                                                <span class="text-[10px] font-semibold text-primary uppercase">Rekening Pengganti Baru</span>
                                                <p class="font-mono text-xs font-bold text-primary">{{ selectedNewAccount.code }}</p>
                                                <p class="text-xs text-muted-foreground leading-snug">{{ selectedNewAccount.name }}</p>
                                            </div>
                                            <Button type="button" variant="ghost" size="sm" class="h-7 text-xs text-destructive hover:bg-destructive/10 shrink-0" @click="cancelAccountChange">
                                                Batal Ganti
                                            </Button>
                                        </div>
                                        <div class="mt-2.5 p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs flex items-center gap-1.5">
                                            <CheckCircle2 class="w-4 h-4 shrink-0" />
                                            <span>Seluruh rincian belanja (RBA Details) akan tetap tersimpan dan otomatis dialihkan ke rekening ini.</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="editForm.errors.account_code_id" class="text-[11px] text-destructive">{{ editForm.errors.account_code_id }}</p>
                            </div>

                            <!-- Sumber Dana -->
                            <div class="grid gap-1.5">
                                <Label for="edit_funding_source_id" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                    Sumber Dana <span class="text-destructive">*</span>
                                </Label>
                                <Select v-model="editForm.funding_source_id" required>
                                    <SelectTrigger id="edit_funding_source_id">
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
                                <p v-if="editForm.errors.funding_source_id" class="text-[11px] text-destructive">{{ editForm.errors.funding_source_id }}</p>
                            </div>

                            <!-- PPTK -->
                            <div class="grid gap-1.5">
                                <Label for="edit_pptk_id" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                    Penanggung Jawab (PPTK) <span class="text-destructive">*</span>
                                </Label>
                                <Select v-model="editForm.pptk_id" required>
                                    <SelectTrigger id="edit_pptk_id">
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
                                <p v-if="editForm.errors.pptk_id" class="text-[11px] text-destructive">{{ editForm.errors.pptk_id }}</p>
                            </div>

                            <DialogFooter class="mt-6 pt-4 border-t">
                                <Button type="button" variant="outline" @click="isEditDialogOpen = false" :disabled="editForm.processing">Batal</Button>
                                <Button type="submit" variant="default" :disabled="editForm.processing || !editForm.funding_source_id || !editForm.pptk_id">
                                    <span v-if="editForm.processing">Menyimpan...</span>
                                    <span v-else>Simpan Perubahan</span>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Tabs Navigasi RBA Gelondongan / Rinci -->
            <div class="flex items-center gap-2">
                <Link 
                    :href="`/rba/${props.rbaType.toLowerCase()}?rba_view_type=gelondongan`" 
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 border',
                        props.rbaViewType === 'gelondongan' 
                            ? 'bg-primary text-primary-foreground border-primary shadow-sm' 
                            : 'bg-card text-muted-foreground hover:text-foreground hover:bg-muted/50 border-border/80'
                    ]"
                >
                    RBA Gelondongan
                </Link>
                <Link 
                    :href="`/rba/${props.rbaType.toLowerCase()}?rba_view_type=rinci`" 
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 border',
                        props.rbaViewType === 'rinci' 
                            ? 'bg-primary text-primary-foreground border-primary shadow-sm' 
                            : 'bg-card text-muted-foreground hover:text-foreground hover:bg-muted/50 border-border/80'
                    ]"
                >
                    RBA Rinci
                </Link>
            </div>

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
                                    <TableHead class="text-right w-[200px]">Realisasi</TableHead>
                                    <TableHead class="text-right w-[200px]">Sisa Pagu</TableHead>
                                    <TableHead class="text-right w-[180px]">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="activeTree.length > 0">
                                    <AccountTreeRow 
                                        v-for="node in activeTree" 
                                        :key="node.id" 
                                        :row="node" 
                                        :level="0" 
                                        @edit-document="openEditDocument"
                                        @delete-document="confirmDeleteDocument"
                                    />
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
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
