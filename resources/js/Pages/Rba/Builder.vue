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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/Components/ui/dropdown-menu';
import { Checkbox } from '@/Components/ui/checkbox';
import { ArrowLeft, Plus, Settings, MoreVertical, FolderInput, Trash2, X } from 'lucide-vue-next';
import RbaTreeRow from '@/Components/RbaTreeRow.vue';
import ParentCombobox from '@/Components/ParentCombobox.vue';

const props = defineProps({
    rbaDocument: Object,
    rbaDetails: Array,
    fundingSources: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    }
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

// High-performance eligible parents generator (only processes headers, ignores thousands of items)
const getEligibleParents = (excludeNodeId = null) => {
    // Only header nodes can be parents
    const headers = props.rbaDetails.filter(n => n.type === 'header');

    // Build children map for headers only (only ~20 items)
    const headerChildrenMap = {};
    headers.forEach(h => {
        const pId = h.parent_id ?? 'root';
        if (!headerChildrenMap[pId]) {
            headerChildrenMap[pId] = [];
        }
        headerChildrenMap[pId].push(h);
    });

    // Collect all descendant header IDs to exclude (to prevent circular references)
    const excludedIds = new Set();
    if (excludeNodeId) {
        excludedIds.add(excludeNodeId);
        const collectDescendants = (id) => {
            const children = headerChildrenMap[id] || [];
            children.forEach(c => {
                excludedIds.add(c.id);
                collectDescendants(c.id);
            });
        };
        collectDescendants(excludeNodeId);
    }

    // Build ordered, indented list starting from roots
    const result = [];
    const traverse = (pId = 'root', depth = 0) => {
        const children = headerChildrenMap[pId] || [];
        for (const child of children) {
            if (!excludedIds.has(child.id)) {
                result.push({
                    id: child.id,
                    uraian: child.uraian,
                    label: depth > 0 ? `${'— '.repeat(depth)}${child.uraian}` : child.uraian,
                });
                traverse(child.id, depth + 1);
            }
        }
    };
    traverse('root', 0);

    return result;
};

// Store pre-computed lists in reactive refs to avoid running during Vue template re-renders
const eligibleParentsForChangeParent = ref([]);
const eligibleParentsForForm = ref([]);

// Form Dialog State
const isDialogOpen = ref(false);
const dialogMode = ref('add'); // 'add' or 'edit'
const formType = ref('item'); // 'header' or 'item'
const currentId = ref(null);

const form = useForm({
    parent_id: 'root',
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
    form.parent_id = parentId ? parentId.toString() : 'root';
    eligibleParentsForForm.value = getEligibleParents(null);
    isDialogOpen.value = true;
};

const openEditDialog = (row) => {
    dialogMode.value = 'edit';
    formType.value = row.type;
    currentId.value = row.id;
    form.parent_id = row.parent_id ? row.parent_id.toString() : 'root';
    form.type = row.type;
    form.uraian = row.uraian;
    form.vol_1 = row.vol_1; form.satuan_1 = row.satuan_1;
    form.vol_2 = row.vol_2; form.satuan_2 = row.satuan_2;
    form.vol_3 = row.vol_3; form.satuan_3 = row.satuan_3;
    form.vol_4 = row.vol_4; form.satuan_4 = row.satuan_4;
    form.harga = row.harga;
    eligibleParentsForForm.value = getEligibleParents(row.id);
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
            preserveState: true,
            onSuccess: () => { 
                isDeleteDialogOpen.value = false;
                rowToDelete.value = null;
            }
        });
    }
};

const submitForm = () => {
    const formHandler = form.transform((data) => ({
        ...data,
        parent_id: data.parent_id === 'root' || !data.parent_id ? null : Number(data.parent_id),
    }));

    if (dialogMode.value === 'add') {
        formHandler.post(`/rba/${props.rbaDocument.id}/details`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { isDialogOpen.value = false; }
        });
    } else {
        formHandler.put(`/rba/details/${currentId.value}`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { isDialogOpen.value = false; }
        });
    }
};

// Quick Change Parent Dialog State
const isChangeParentDialogOpen = ref(false);
const rowToChangeParent = ref(null);
const changeParentForm = useForm({
    parent_id: 'root',
});

const openChangeParentDialog = (row) => {
    rowToChangeParent.value = row;
    changeParentForm.parent_id = row.parent_id ? row.parent_id.toString() : 'root';
    changeParentForm.clearErrors();
    eligibleParentsForChangeParent.value = getEligibleParents(row.id);
    isChangeParentDialogOpen.value = true;
};

const executeChangeParent = () => {
    if (!rowToChangeParent.value) return;

    changeParentForm
        .transform((data) => ({
            parent_id: data.parent_id === 'root' || !data.parent_id ? null : Number(data.parent_id),
        }))
        .put(`/rba/details/${rowToChangeParent.value.id}`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isChangeParentDialogOpen.value = false;
                rowToChangeParent.value = null;
            }
        });
};

// Selection State for Bulk Actions
const selectedIds = ref([]);

const isAllSelected = computed(() => {
    if (!props.rbaDetails || props.rbaDetails.length === 0) return false;
    return selectedIds.value.length === props.rbaDetails.length;
});

const masterCheckboxState = computed(() => {
    if (!props.rbaDetails || props.rbaDetails.length === 0) return false;
    if (selectedIds.value.length === 0) return false;
    if (selectedIds.value.length === props.rbaDetails.length) return true;
    return 'indeterminate';
});

const toggleSelectAll = (checked) => {
    if (checked === true || masterCheckboxState.value === 'indeterminate') {
        selectedIds.value = props.rbaDetails.map(d => d.id);
    } else {
        selectedIds.value = [];
    }
};

const toggleSelect = (id) => {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

// Bulk Change Parent State
const isBulkChangeParentDialogOpen = ref(false);
const bulkChangeParentForm = useForm({
    parent_id: 'root',
    detail_ids: [],
});
const eligibleParentsForBulkChange = ref([]);

const openBulkChangeParentDialog = () => {
    if (selectedIds.value.length === 0) return;

    // Filter eligible parents: cannot be any of selectedIds, nor any descendant of selected headers
    const headers = props.rbaDetails.filter(n => n.type === 'header');
    const headerChildrenMap = {};
    headers.forEach(h => {
        const pId = h.parent_id ?? 'root';
        if (!headerChildrenMap[pId]) headerChildrenMap[pId] = [];
        headerChildrenMap[pId].push(h);
    });

    const excludedIds = new Set(selectedIds.value);
    const collectDescendants = (id) => {
        const children = headerChildrenMap[id] || [];
        children.forEach(c => {
            excludedIds.add(c.id);
            collectDescendants(c.id);
        });
    };
    selectedIds.value.forEach(id => collectDescendants(id));

    const result = [];
    const traverse = (pId = 'root', depth = 0) => {
        const children = headerChildrenMap[pId] || [];
        for (const child of children) {
            if (!excludedIds.has(child.id)) {
                result.push({
                    id: child.id,
                    uraian: child.uraian,
                    label: depth > 0 ? `${'— '.repeat(depth)}${child.uraian}` : child.uraian,
                });
                traverse(child.id, depth + 1);
            }
        }
    };
    traverse('root', 0);

    eligibleParentsForBulkChange.value = result;
    bulkChangeParentForm.parent_id = 'root';
    bulkChangeParentForm.detail_ids = [...selectedIds.value];
    bulkChangeParentForm.clearErrors();
    isBulkChangeParentDialogOpen.value = true;
};

const executeBulkChangeParent = () => {
    bulkChangeParentForm
        .transform((data) => ({
            detail_ids: data.detail_ids,
            parent_id: data.parent_id === 'root' || !data.parent_id ? null : Number(data.parent_id),
        }))
        .post(`/rba/${props.rbaDocument.id}/details/bulk-change-parent`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isBulkChangeParentDialogOpen.value = false;
                selectedIds.value = [];
            }
        });
};

// Bulk Delete State
const isBulkDeleteDialogOpen = ref(false);
const bulkDeleteForm = useForm({
    detail_ids: [],
});

const openBulkDeleteDialog = () => {
    if (selectedIds.value.length === 0) return;
    bulkDeleteForm.detail_ids = [...selectedIds.value];
    bulkDeleteForm.clearErrors();
    isBulkDeleteDialogOpen.value = true;
};

const executeBulkDelete = () => {
    bulkDeleteForm.post(`/rba/${props.rbaDocument.id}/details/bulk-destroy`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isBulkDeleteDialogOpen.value = false;
            selectedIds.value = [];
        }
    });
};

// Document Settings State
const isSettingsDialogOpen = ref(false);
const settingsForm = useForm({
    funding_source_id: props.rbaDocument.funding_source_id ? props.rbaDocument.funding_source_id.toString() : '',
    pptk_id: props.rbaDocument.pptk_id ? props.rbaDocument.pptk_id.toString() : ''
});

const openSettingsDialog = () => {
    settingsForm.funding_source_id = props.rbaDocument.funding_source_id ? props.rbaDocument.funding_source_id.toString() : '';
    settingsForm.pptk_id = props.rbaDocument.pptk_id ? props.rbaDocument.pptk_id.toString() : '';
    settingsForm.clearErrors();
    isSettingsDialogOpen.value = true;
};

const saveSettings = () => {
    settingsForm.put(`/rba/documents/${props.rbaDocument.id}`, {
        preserveScroll: true,
        onSuccess: () => { isSettingsDialogOpen.value = false; }
    });
};
</script>

<template>
    <Head title="Builder RBA" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4 w-full">
                <Button as-child variant="outline" size="icon" class="h-8 w-8 shrink-0">
                    <Link :href="rbaDocument.account_code.code.startsWith('4') ? '/rba/pendapatan' : '/rba/belanja'">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1 flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ rbaDocument.account_code.code }} - {{ rbaDocument.account_code.name }}</h2>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary text-white uppercase tracking-wider">
                                {{ rbaDocument.version_name }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">PPTK: {{ rbaDocument.pptk ? rbaDocument.pptk.name : 'Belum Ditentukan' }}</p>
                    </div>
                    
                    <Button variant="outline" size="sm" class="gap-2 my-auto" @click="openSettingsDialog">
                        <Settings class="w-4 h-4" /> Pengaturan
                    </Button>
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
                    <div class="flex items-center space-x-2">
                        <Button @click="openAddDialog('header')" variant="outline">
                            <Plus class="w-4 h-4 mr-2"/> Tambah Rincian Utama
                        </Button>
                        <Button @click="openAddDialog('item')">
                            <Plus class="w-4 h-4 mr-2"/> Tambah Sub Rincian Utama
                        </Button>

                        <!-- Menu Aksi Masal (Titik Tiga) -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    :disabled="selectedIds.length === 0"
                                    class="relative"
                                    :class="{ 'border-primary text-primary hover:text-primary': selectedIds.length > 0 }"
                                    title="Aksi Masal Rincian Terpilih"
                                >
                                    <MoreVertical class="w-4 h-4" />
                                    <span
                                        v-if="selectedIds.length > 0"
                                        class="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-white shadow-sm"
                                    >
                                        {{ selectedIds.length }}
                                    </span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground">
                                    {{ selectedIds.length }} rincian dipilih
                                </div>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="openBulkChangeParentDialog">
                                    <FolderInput class="mr-2 h-4 w-4" />
                                    <span>Pindah Induk Terpilih</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="openBulkDeleteDialog" class="text-destructive focus:text-destructive">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    <span>Hapus Terpilih</span>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="selectedIds = []">
                                    <X class="mr-2 h-4 w-4" />
                                    <span>Batalkan Pilihan</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="border-y">
                        <Table>
                            <TableHeader class="bg-muted/50">
                                <TableRow>
                                    <TableHead class="w-[40px] px-3 text-center">
                                        <Checkbox
                                            :model-value="masterCheckboxState"
                                            @update:model-value="toggleSelectAll"
                                            title="Pilih Semua / Batal Semua"
                                        />
                                    </TableHead>
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
                                        :selected-ids="selectedIds"
                                        @toggleSelect="toggleSelect"
                                        @addHeader="openAddDialog('header', $event)"
                                        @addItem="openAddDialog('item', $event)"
                                        @edit="openEditDialog"
                                        @delete="confirmDeleteAction"
                                        @changeParent="openChangeParentDialog"
                                    />
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
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
                        <Label>Induk / Header Grup</Label>
                        <ParentCombobox
                            v-model="form.parent_id"
                            :options="eligibleParentsForForm"
                            placeholder="Pilih Induk (Header)"
                        />
                        <p class="text-[0.8rem] text-destructive" v-if="form.errors.parent_id">{{ form.errors.parent_id }}</p>
                    </div>

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

        <!-- Dialog Pindah Induk -->
        <Dialog :open="isChangeParentDialogOpen" @update:open="isChangeParentDialogOpen = $event">
            <DialogContent class="sm:max-w-[480px] overflow-hidden">
                <DialogHeader>
                    <DialogTitle>Pindah Induk (Parent)</DialogTitle>
                    <DialogDescription v-if="rowToChangeParent" class="break-words">
                        Pindahkan <strong>"{{ rowToChangeParent.uraian }}"</strong> ke grup induk yang baru.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="executeChangeParent" class="space-y-4 py-2 min-w-0">
                    <div class="space-y-2 min-w-0">
                        <Label for="new_parent_id">Pilih Induk Baru <span class="text-destructive">*</span></Label>
                        <ParentCombobox
                            v-model="changeParentForm.parent_id"
                            :options="eligibleParentsForChangeParent"
                            placeholder="Pilih Induk Baru"
                        />
                        <p v-if="changeParentForm.errors.parent_id" class="text-xs text-destructive">
                            {{ changeParentForm.errors.parent_id }}
                        </p>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button type="button" variant="outline" @click="isChangeParentDialogOpen = false" :disabled="changeParentForm.processing">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="changeParentForm.processing">
                            <span v-if="changeParentForm.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog Pindah Induk Masal -->
        <Dialog :open="isBulkChangeParentDialogOpen" @update:open="isBulkChangeParentDialogOpen = $event">
            <DialogContent class="sm:max-w-[480px] overflow-hidden">
                <DialogHeader>
                    <DialogTitle>Pindah Induk Masal</DialogTitle>
                    <DialogDescription>
                        Pindahkan <strong>{{ selectedIds.length }} rincian terpilih</strong> ke grup induk yang baru.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="executeBulkChangeParent" class="space-y-4 py-2 min-w-0">
                    <div class="space-y-2 min-w-0">
                        <Label for="bulk_new_parent_id">Pilih Induk Baru <span class="text-destructive">*</span></Label>
                        <ParentCombobox
                            v-model="bulkChangeParentForm.parent_id"
                            :options="eligibleParentsForBulkChange"
                            placeholder="Pilih Induk Baru"
                        />
                        <p v-if="bulkChangeParentForm.errors.parent_id" class="text-xs text-destructive">
                            {{ bulkChangeParentForm.errors.parent_id }}
                        </p>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button type="button" variant="outline" @click="isBulkChangeParentDialogOpen = false" :disabled="bulkChangeParentForm.processing">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="bulkChangeParentForm.processing">
                            <span v-if="bulkChangeParentForm.processing">Memindahkan...</span>
                            <span v-else>Pindahkan {{ selectedIds.length }} Rincian</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog Konfirmasi Hapus Masal -->
        <Dialog :open="isBulkDeleteDialogOpen" @update:open="isBulkDeleteDialogOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="text-destructive">Konfirmasi Hapus Masal</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus <strong>{{ selectedIds.length }} rincian terpilih</strong> secara permanen?
                        <p class="mt-2 text-destructive font-medium">
                            Peringatan: Jika ada rincian bertipe grup (header) yang dipilih, seluruh sub rincian di bawahnya juga akan ikut terhapus! Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </DialogDescription>
                </DialogHeader>
                <div class="flex justify-end gap-3 mt-4">
                    <Button variant="outline" @click="isBulkDeleteDialogOpen = false" :disabled="bulkDeleteForm.processing">Batal</Button>
                    <Button variant="destructive" @click="executeBulkDelete" :disabled="bulkDeleteForm.processing">
                        <span v-if="bulkDeleteForm.processing">Menghapus...</span>
                        <span v-else>Ya, Hapus {{ selectedIds.length }} Rincian</span>
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Dialog Pengaturan Dokumen -->
        <Dialog v-model:open="isSettingsDialogOpen">
            <DialogContent class="sm:max-w-[450px]">
                <DialogHeader>
                    <DialogTitle>Pengaturan Dokumen RBA</DialogTitle>
                    <DialogDescription>
                        Ubah pengaturan sumber dana dan penanggung jawab dokumen.
                    </DialogDescription>
                </DialogHeader>
                
                <form @submit.prevent="saveSettings" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="funding_source_id">Sumber Dana <span class="text-destructive">*</span></Label>
                        <Select v-model="settingsForm.funding_source_id" required>
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
                        <p v-if="settingsForm.errors.funding_source_id" class="text-[10px] text-destructive">{{ settingsForm.errors.funding_source_id }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="pptk_id">Penanggung Jawab (PPTK) <span class="text-destructive">*</span></Label>
                        <Select v-model="settingsForm.pptk_id" required>
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
                        <p v-if="settingsForm.errors.pptk_id" class="text-[10px] text-destructive">{{ settingsForm.errors.pptk_id }}</p>
                    </div>
                    
                    <DialogFooter class="mt-6 pt-4 border-t">
                        <Button type="button" variant="outline" @click="isSettingsDialogOpen = false" :disabled="settingsForm.processing">Batal</Button>
                        <Button type="submit" variant="default" :disabled="settingsForm.processing || !settingsForm.funding_source_id || !settingsForm.pptk_id">
                            <span v-if="settingsForm.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
