<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { 
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter 
} from '@/Components/ui/dialog';
import { 
    Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue 
} from '@/Components/ui/select';
import { ArrowLeft, Plus } from 'lucide-vue-next';
import RbaTreeRow from '@/Components/RbaTreeRow.vue';

const props = defineProps({
    rbaDocument: Object,
    rbaDetails: Array,
});

// Convert flat list to recursive tree and calculate Header totals
const treeData = computed(() => {
    const data = JSON.parse(JSON.stringify(props.rbaDetails)); // Deep copy to avoid mutating props
    const map = {};
    const roots = [];

    data.forEach(node => {
        map[node.id] = { ...node, children: [] };
    });

    data.forEach(node => {
        if (node.parent_id !== null && map[node.parent_id]) {
            map[node.parent_id].children.push(map[node.id]);
        } else {
            roots.push(map[node.id]);
        }
    });

    const calculateTotals = (nodes) => {
        let total = 0;
        nodes.forEach(n => {
            if (n.type === 'header') {
                n.jumlah = calculateTotals(n.children);
            }
            total += Number(n.jumlah || 0);
        });
        return total;
    };
    
    calculateTotals(roots);
    return roots;
});

const grandTotal = computed(() => {
    let total = 0;
    treeData.value.forEach(node => {
        total += Number(node.jumlah || 0);
    });
    return total;
});

const formatCurrency = (value) => {
    if (value == null) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(value);
};

// Form Dialog State
const isDialogOpen = ref(false);
const dialogMode = ref('add'); // 'add' or 'edit'
const formType = ref('item'); // 'header' or 'item'
const currentId = ref(null);

const form = useForm({
    parent_id: null,
    type: 'item',
    uraian: '',
    vol_1: '', satuan_1: '',
    vol_2: '', satuan_2: '',
    vol_3: '', satuan_3: '',
    vol_4: '', satuan_4: '',
    harga: '',
});

// Real-time calculation for Form Preview
const previewKoefisien = computed(() => {
    let koef = 1;
    let hasVal = false;
    if (form.vol_1) { koef *= Number(form.vol_1); hasVal = true; }
    if (form.vol_2) { koef *= Number(form.vol_2); hasVal = true; }
    if (form.vol_3) { koef *= Number(form.vol_3); hasVal = true; }
    if (form.vol_4) { koef *= Number(form.vol_4); hasVal = true; }
    return hasVal ? koef : 0;
});

const previewSatuan = computed(() => {
    const s = [];
    if (form.satuan_1) s.push(form.satuan_1);
    if (form.satuan_2) s.push(form.satuan_2);
    if (form.satuan_3) s.push(form.satuan_3);
    if (form.satuan_4) s.push(form.satuan_4);
    return s.join('/');
});

const previewTotal = computed(() => {
    return previewKoefisien.value * (Number(form.harga) || 0);
});

// Actions
const openAddDialog = (type, parentId = null) => {
    dialogMode.value = 'add';
    formType.value = type;
    form.reset();
    form.type = type;
    form.parent_id = parentId;
    isDialogOpen.value = true;
};

const openEditDialog = (row) => {
    dialogMode.value = 'edit';
    formType.value = row.type;
    currentId.value = row.id;
    form.parent_id = row.parent_id;
    form.type = row.type;
    form.uraian = row.uraian;
    form.vol_1 = row.vol_1; form.satuan_1 = row.satuan_1;
    form.vol_2 = row.vol_2; form.satuan_2 = row.satuan_2;
    form.vol_3 = row.vol_3; form.satuan_3 = row.satuan_3;
    form.vol_4 = row.vol_4; form.satuan_4 = row.satuan_4;
    form.harga = row.harga;
    isDialogOpen.value = true;
};

// Delete Dialog State
const isDeleteDialogOpen = ref(false);
const rowToDelete = ref(null);

const confirmDeleteAction = (row) => {
    rowToDelete.value = row;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (rowToDelete.value) {
        router.delete(`/rba/details/${rowToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => { 
                isDeleteDialogOpen.value = false;
                rowToDelete.value = null;
            }
        });
    }
};

const submitForm = () => {
    if (dialogMode.value === 'add') {
        form.post(`/rba/${props.rbaDocument.id}/details`, {
            preserveScroll: true,
            onSuccess: () => { isDialogOpen.value = false; }
        });
    } else {
        form.put(`/rba/details/${currentId.value}`, {
            preserveScroll: true,
            onSuccess: () => { isDialogOpen.value = false; }
        });
    }
};
</script>

<template>
    <Head title="Builder RBA" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Button as-child variant="outline" size="icon" class="h-8 w-8">
                    <Link href="/rba">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Susun Rencana Bisnis dan Anggaran (RBA)</h2>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary text-white uppercase tracking-wider">
                            {{ rbaDocument.version_name }}
                        </span>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ rbaDocument.account_code.code }} - {{ rbaDocument.account_code.name }}</p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Rincian Anggaran</CardTitle>
                        <CardDescription>
                            Total Anggaran Rekening ini: <span class="font-bold text-primary">{{ formatCurrency(grandTotal) }}</span>
                        </CardDescription>
                    </div>
                    <div class="space-x-2">
                        <Button @click="openAddDialog('header')" variant="outline">
                            <Plus class="w-4 h-4 mr-2"/> Tambah Rincian Utama
                        </Button>
                        <Button @click="openAddDialog('item')">
                            <Plus class="w-4 h-4 mr-2"/> Tambah Sub Rincian Utama
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="border-y">
                        <Table>
                            <TableHeader class="bg-muted/50">
                                <TableRow>
                                    <TableHead class="min-w-[300px] max-w-[500px]">Uraian</TableHead>
                                    <TableHead class="w-[200px]">Volume</TableHead>
                                    <TableHead class="text-right w-[180px]">Harga</TableHead>
                                    <TableHead class="text-right w-[200px]">Jumlah</TableHead>
                                    <TableHead class="w-[80px] text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="treeData.length > 0">
                                    <RbaTreeRow
                                        v-for="node in treeData"
                                        :key="node.id"
                                        :row="node"
                                        @addHeader="openAddDialog('header', $event)"
                                        @addItem="openAddDialog('item', $event)"
                                        @edit="openEditDialog"
                                        @delete="confirmDeleteAction"
                                    />
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="5" class="h-32 text-center text-muted-foreground">
                                        Belum ada rincian. Mulailah dengan menambahkan Rincian atau Sub Rincian.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Dialog Form -->
        <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>{{ dialogMode === 'add' ? 'Tambah' : 'Edit' }} {{ formType === 'header' ? 'Rincian' : 'Sub Rincian' }}</DialogTitle>
                    <DialogDescription>
                        Isi form di bawah ini dengan teliti. Pastikan volume dan harga sudah benar.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="flex flex-col gap-4 py-4 h-full">
                    <div class="space-y-2">
                        <Label>Uraian / Nama</Label>
                        <Input v-model="form.uraian" required placeholder="Contoh: Honorarium Dokter Umum" />
                        <p class="text-[0.8rem] text-destructive" v-if="form.errors.uraian">{{ form.errors.uraian }}</p>
                    </div>

                    <!-- Input khusus Item -->
                    <template v-if="formType === 'item'">
                        <div class="space-y-2 pt-2 border-t mt-2">
                            <Label>Kalkulasi Volume (Pengali)</Label>
                            
                            <!-- Vol 1 & 2 -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex gap-2">
                                    <Input v-model="form.vol_1" type="number" step="0.01" placeholder="Vol 1 (mis. 2)" class="w-1/2" />
                                    <Input v-model="form.satuan_1" placeholder="Satuan 1 (mis. Orang)" class="w-1/2" />
                                </div>
                                <div class="flex gap-2">
                                    <Input v-model="form.vol_2" type="number" step="0.01" placeholder="Vol 2 (mis. 12)" class="w-1/2" />
                                    <Input v-model="form.satuan_2" placeholder="Satuan 2 (mis. Bulan)" class="w-1/2" />
                                </div>
                            </div>
                            
                            <!-- Vol 3 & 4 -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex gap-2">
                                    <Input v-model="form.vol_3" type="number" step="0.01" placeholder="Vol 3 (Opsional)" class="w-1/2" />
                                    <Input v-model="form.satuan_3" placeholder="Satuan 3" class="w-1/2" />
                                </div>
                                <div class="flex gap-2">
                                    <Input v-model="form.vol_4" type="number" step="0.01" placeholder="Vol 4 (Opsional)" class="w-1/2" />
                                    <Input v-model="form.satuan_4" placeholder="Satuan 4" class="w-1/2" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t mt-2">
                            <Label>Harga Satuan (Rp)</Label>
                            <Input v-model="form.harga" type="number" step="0.01" required placeholder="Misal: 100000" />
                            <p class="text-[0.8rem] text-destructive" v-if="form.errors.harga">{{ form.errors.harga }}</p>
                        </div>
                        
                        <!-- Real-time Preview Box -->
                        <div class="bg-primary/5 p-4 rounded-lg mt-2 border border-primary/20">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-muted-foreground mb-1">Total Koefisien:</p>
                                    <p class="font-medium text-lg">{{ previewKoefisien }} <span class="text-muted-foreground text-sm">{{ previewSatuan }}</span></p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground mb-1">Total Jumlah (Rp):</p>
                                    <p class="font-bold text-lg text-primary">{{ formatCurrency(previewTotal) }}</p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="mt-auto pt-4 flex justify-end space-x-2">
                        <Button type="button" variant="outline" @click="isDialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
        <!-- Dialog Konfirmasi Hapus -->
        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="text-destructive">Konfirmasi Penghapusan</DialogTitle>
                    <DialogDescription v-if="rowToDelete">
                        Apakah Anda yakin ingin menghapus <strong>"{{ rowToDelete.uraian }}"</strong>?
                        <p class="mt-2 text-destructive font-medium" v-if="rowToDelete.type === 'header'">
                            Peringatan: Menghapus Rincian ini akan ikut menghapus SEMUA sub rincian di bawahnya!
                        </p>
                        <p class="mt-2" v-else>
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </DialogDescription>
                </DialogHeader>
                <div class="flex justify-end gap-3 mt-4">
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="executeDelete">Ya, Hapus Permanen</Button>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
