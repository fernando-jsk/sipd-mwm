<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { 
    Trash2, Plus, UploadCloud, ChevronRight, ChevronLeft, Save, Send, Info, CheckCircle2, ArrowRightLeft,
    Receipt, CheckSquare, Square, Search, Filter, X, ExternalLink, Calendar, AlertCircle
} from '@lucide/vue';
import {
  Dialog,
  DialogScrollContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Badge } from '@/Components/ui/badge';
import { terbilang } from '@/lib/utils';

const props = defineProps({
    expenditure: Object,
    users: Array,
    vendors: Array,
    accountCodes: Array,
    expenditureRules: {
        type: Object,
        default: () => ({})
    },
    availableReceipts: {
        type: Array,
        default: () => []
    },
    linkedReceiptIds: {
        type: Array,
        default: () => []
    }
});

const isEdit = !!props.expenditure;

const form = useForm({
    document_number: props.expenditure?.document_number || '',
    date: props.expenditure?.date ? new Date(props.expenditure.date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    type: props.expenditure?.type || 'LS',
    description: props.expenditure?.description || '',
    treasurer_id: props.expenditure?.treasurer_id?.toString() || '',
    kpa_id: props.expenditure?.kpa_id?.toString() || '',
    ptk_id: props.expenditure?.ptk_id?.toString() || '',
    activity_date: props.expenditure?.activity_date ? new Date(props.expenditure.activity_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    activity_description: props.expenditure?.activity_description || '',
    payment_method: props.expenditure?.payment_method || 'rekanan',
    vendor_id: props.expenditure?.vendor_id?.toString() || '',
    bank_name: props.expenditure?.bank_name || '',
    bank_account_number: props.expenditure?.bank_account_number || '',
    contract_number: props.expenditure?.contract_number || '',
    status: props.expenditure?.status || 'draft',
    attachment: null,
    details: props.expenditure?.details 
        ? props.expenditure.details.map(d => ({ ...d, account_code_id: d.account_code_id.toString() })) 
        : [{ account_code_id: '', amount: '' }],
    taxes: props.expenditure?.taxes || [],
    receipt_ids: props.linkedReceiptIds ? [...props.linkedReceiptIds] : [],
});

// Otomatis atur akun debit, deskripsi kegiatan, dan metode bayar jika memilih UP
const initUpForm = () => {
    if (form.type === 'UP') {
        if (!form.description) {
            form.description = 'Penyediaan Uang Persediaan (UP) Awal Tahun Anggaran';
        }
        if (!form.activity_description) {
            const year = form.date ? new Date(form.date).getFullYear() : new Date().getFullYear();
            form.activity_description = `Penyediaan Uang Persediaan (UP) untuk keperluan operasional rutin BLUD Tahun Anggaran ${year}`;
        }
        form.payment_method = 'ls_bendahara';
        form.taxes = [];
        
        // UP hanya memerlukan 1 baris gelondongan ke akun kas bendahara
        const upDebitAccId = props.expenditureRules?.UP?.debit_account_id;
        if (form.details.length === 0) {
            form.details.push({
                account_code_id: upDebitAccId ? upDebitAccId.toString() : '',
                amount: ''
            });
        } else {
            if (form.details.length > 1) {
                form.details = [form.details[0]];
            }
            if (upDebitAccId && !form.details[0].account_code_id) {
                form.details[0].account_code_id = upDebitAccId.toString();
            }
        }
    }
};

// Pengaturan khusus untuk form jenis GU
const initGuForm = () => {
    if (form.type === 'GU') {
        form.payment_method = 'ls_bendahara';
        if (!form.treasurer_id && props.users?.length > 0) {
            const tr = props.users.find(u => u.name?.toLowerCase().includes('saskia'));
            if (tr) form.treasurer_id = tr.id.toString();
        }
        if (!form.description) {
            const year = form.date ? new Date(form.date).getFullYear() : new Date().getFullYear();
            form.description = `Penggantian Uang Persediaan (GU) atas Belanja Kas UP Tahun Anggaran ${year}`;
        }
        if (!form.activity_description) {
            form.activity_description = `Penggantian Uang Persediaan (GU) atas belanja operasional rutin kas UP BLUD`;
        }
        if (selectedReceiptIds.value.length === 0) {
            form.details = [];
            form.taxes = [];
        } else {
            syncReceiptsToForm();
        }
    }
};

// State modal pemilih kwitansi GU
const isReceiptModalOpen = ref(false);
const receiptMonthFilter = ref('all');
const receiptSearch = ref('');
const selectedReceiptIds = ref(props.linkedReceiptIds ? [...props.linkedReceiptIds] : []);

const openReceiptModal = () => {
    selectedReceiptIds.value = [...(form.receipt_ids || [])];
    isReceiptModalOpen.value = true;
};

const months = [
    { value: 'all', label: 'Semua Bulan' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const allReceiptPool = computed(() => {
    const map = new Map();
    (props.availableReceipts || []).forEach(r => map.set(r.id, r));
    (props.expenditure?.receipts || []).forEach(r => map.set(r.id, r));
    return Array.from(map.values());
});

const filteredReceipts = computed(() => {
    let list = allReceiptPool.value;
    if (receiptMonthFilter.value !== 'all') {
        list = list.filter(r => {
            if (!r.date) return false;
            const m = new Date(r.date).getMonth() + 1;
            return m.toString() === receiptMonthFilter.value.toString();
        });
    }
    const q = (receiptSearch.value || '').toString().toLowerCase().trim();
    if (q) {
        list = list.filter(r => 
            (r.receipt_number && r.receipt_number.toLowerCase().includes(q)) ||
            (r.recipient_name && r.recipient_name.toLowerCase().includes(q)) ||
            (r.description && r.description.toLowerCase().includes(q)) ||
            (r.account_code && (r.account_code.code.toLowerCase().includes(q) || r.account_code.name.toLowerCase().includes(q)))
        );
    }
    return list;
});

const selectedReceiptObjects = computed(() => {
    const map = new Map();
    allReceiptPool.value.forEach(r => map.set(r.id, r));
    return selectedReceiptIds.value.map(id => map.get(id)).filter(Boolean);
});

const totalSelectedGross = computed(() => {
    return selectedReceiptObjects.value.reduce((acc, r) => acc + Number(r.amount || 0), 0);
});

const totalSelectedTax = computed(() => {
    return selectedReceiptObjects.value.reduce((acc, r) => acc + Number(r.tax_amount || 0), 0);
});

const totalSelectedNet = computed(() => {
    return Math.max(0, totalSelectedGross.value - totalSelectedTax.value);
});

const isAllFilteredSelected = computed(() => {
    if (filteredReceipts.value.length === 0) return false;
    return filteredReceipts.value.every(r => selectedReceiptIds.value.includes(r.id));
});

const toggleSelectAllFiltered = () => {
    if (isAllFilteredSelected.value) {
        const toRemove = new Set(filteredReceipts.value.map(r => r.id));
        selectedReceiptIds.value = selectedReceiptIds.value.filter(id => !toRemove.has(id));
    } else {
        const currentSet = new Set(selectedReceiptIds.value);
        filteredReceipts.value.forEach(r => currentSet.add(r.id));
        selectedReceiptIds.value = Array.from(currentSet);
    }
};

const toggleReceiptSelection = (id) => {
    const idx = selectedReceiptIds.value.indexOf(id);
    if (idx > -1) {
        selectedReceiptIds.value.splice(idx, 1);
    } else {
        selectedReceiptIds.value.push(id);
    }
};

const removeSelectedReceipt = (id) => {
    const idx = selectedReceiptIds.value.indexOf(id);
    if (idx > -1) {
        selectedReceiptIds.value.splice(idx, 1);
        syncReceiptsToForm();
    }
};

const syncReceiptsToForm = () => {
    const receipts = selectedReceiptObjects.value;
    
    if (receipts.length === 0) {
        form.receipt_ids = [];
        form.details = [];
        form.taxes = [];
        return;
    }

    const grouped = {};
    const taxList = [];
    
    receipts.forEach(r => {
        const accId = r.account_code_id ? r.account_code_id.toString() : '';
        const amt = Number(r.amount || 0);
        grouped[accId] = (grouped[accId] || 0) + amt;

        if (r.tax_amount && Number(r.tax_amount) > 0 && r.tax_type && r.tax_type !== 'none') {
            taxList.push({
                tax_type: r.tax_type,
                billing_code: r.billing_code || '',
                amount: Number(r.tax_amount)
            });
        }
    });

    form.details = Object.entries(grouped).map(([accId, amt]) => ({
        account_code_id: accId,
        amount: amt
    }));

    form.taxes = taxList;
    form.receipt_ids = [...selectedReceiptIds.value];

    const mLabel = receiptMonthFilter.value !== 'all' 
        ? months.find(m => m.value === receiptMonthFilter.value)?.label 
        : '';
    const year = form.date ? new Date(form.date).getFullYear() : new Date().getFullYear();
    
    if (!form.description || form.description.startsWith('Penggantian Uang Persediaan')) {
        form.description = `Penggantian Uang Persediaan (GU) atas Belanja Kas UP ${mLabel ? 'Bulan ' + mLabel + ' ' : ''}Tahun ${year}`;
    }
    if (!form.activity_description || form.activity_description.startsWith('Penggantian Uang Persediaan')) {
        form.activity_description = `Penggantian Uang Persediaan (GU) atas ${selectedReceiptIds.value.length} kuitansi belanja operasional kas UP BLUD`;
    }
};

const applyReceiptSelection = () => {
    syncReceiptsToForm();
    isReceiptModalOpen.value = false;
};

watch(() => form.type, (newType, oldType) => {
    if (newType === 'UP') {
        initUpForm();
    } else if (newType === 'GU') {
        initGuForm();
    } else if (oldType === 'UP' || oldType === 'GU') {
        // Jika beralih dari UP/GU ke jenis belanja reguler
        if (form.payment_method === 'ls_bendahara') {
            form.payment_method = 'rekanan';
        }
        if (form.description?.startsWith('Penyediaan Uang Persediaan') || form.description?.startsWith('Penggantian Uang Persediaan')) {
            form.description = '';
        }
        if (form.activity_description?.startsWith('Penyediaan Uang Persediaan') || form.activity_description?.startsWith('Penggantian Uang Persediaan')) {
            form.activity_description = '';
        }
        if (form.details.length === 0) {
            form.details = [{ account_code_id: '', amount: '' }];
        }
    }
}, { immediate: true });

// For keeping track of selected vendor to auto-fill bank
watch(() => form.vendor_id, (newVendorId) => {
    if (form.payment_method === 'rekanan' && newVendorId) {
        const vendor = props.vendors.find(v => v.id.toString() === newVendorId.toString());
        if (vendor) {
            form.bank_name = vendor.bank_name || '';
            form.bank_account_number = vendor.bank_account_number || '';
        }
    }
});

const currentStep = ref(1);

const nextStep = () => {
    if (currentStep.value === 2 && form.type === 'GU' && selectedReceiptObjects.value.length === 0) {
        alert('Silakan pilih minimal 1 (satu) kuitansi belanja kas UP sebelum melanjutkan.');
        return;
    }
    if (currentStep.value < 3) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const addDetailRow = () => {
    form.details.push({
        account_code_id: '',
        amount: 0,
    });
};

const removeDetailRow = (index) => {
    form.details.splice(index, 1);
};

const addTaxRow = () => {
    form.taxes.push({
        tax_type: '',
        billing_code: '',
        amount: 0,
    });
};

const removeTaxRow = (index) => {
    form.taxes.splice(index, 1);
};

const getAccountInfo = (accountId, key) => {
    const acc = props.accountCodes.find(a => a.id === accountId);
    return acc ? acc[key] : 0;
};

const totalAmount = computed(() => {
    return form.details.reduce((sum, item) => sum + Number(item.amount || 0), 0);
});

const totalTax = computed(() => {
    return form.taxes.reduce((sum, item) => sum + Number(item.amount || 0), 0);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const fileInput = ref(null);
const fileName = ref(props.expenditure?.attachment_path ? 'Dokumen sudah terlampir' : '');

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.attachment = file;
        fileName.value = file.name;
    }
};

const submitForm = (status) => {
    form.status = status;
    if (isEdit) {
        // Inertia useForm with file upload requires POST with _method=PUT
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(`/expenditures/${props.expenditure.id}`, {
            preserveScroll: true,
            onError: (err) => {
                if (err.details) {
                    currentStep.value = 2; // Go to details step if budget error
                }
            }
        });
    } else {
        form.post('/expenditures', {
            preserveScroll: true,
            onError: (err) => {
                if (err.details) {
                    currentStep.value = 2; 
                }
            }
        });
    }
};

</script>

<template>
    <Head :title="isEdit ? 'Edit SPPD' : 'Buat SPPD Baru'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Bendahara</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <Link href="/expenditures" class="text-xs text-muted-foreground hover:text-foreground transition-colors">Pengeluaran</Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">{{ isEdit ? 'Edit SPPD' : 'Buat Baru' }}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        {{ isEdit ? 'Edit Pengajuan SPPD' : 'Form Pengajuan SPPD Baru' }}
                    </h2>
                </div>
            </div>
        </template>

        <!-- Progress Wizard -->
        <div class="mb-6">
            <div class="flex items-center justify-between w-full max-w-2xl mx-auto relative">
                <!-- Lines -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-muted -z-10 rounded-full"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-primary -z-10 rounded-full transition-all duration-300" :style="{ width: ((currentStep - 1) / 2) * 100 + '%' }"></div>
                
                <!-- Steps -->
                <div v-for="step in 3" :key="step" class="flex flex-col items-center gap-2 bg-background px-2">
                    <div :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold border-2 transition-colors duration-300',
                        currentStep >= step ? 'bg-primary border-primary text-primary-foreground' : 'bg-background border-muted text-muted-foreground'
                    ]">
                        {{ step }}
                    </div>
                    <span :class="['text-xs font-medium', currentStep >= step ? 'text-foreground' : 'text-muted-foreground']">
                        {{ step === 1 ? 'Informasi Umum' : (step === 2 ? 'Rincian Anggaran' : 'Upload & Submit') }}
                    </span>
                </div>
            </div>
        </div>

        <div v-if="$page.props.flash?.warning" class="mb-4 bg-amber-500/10 border border-amber-500/20 text-amber-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.warning }}</span>
        </div>
        
        <div v-if="Object.keys(form.errors).length > 0" class="mb-4 bg-red-500/10 border border-red-500/20 text-red-700 px-4 py-3 rounded-lg relative">
            <p class="font-bold text-sm mb-1">Terdapat kesalahan:</p>
            <ul class="list-disc pl-5 text-sm">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <div class="bg-card text-card-foreground border border-border/80 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <!-- STEP 1: Informasi Umum -->
                <div v-show="currentStep === 1" class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-300">
                    <!-- Section: Informasi Dokumen -->
                    <div>
                        <h3 class="text-lg font-semibold text-secondary mb-4 border-b pb-2">Informasi Dokumen</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="document_number">No. SPPD <span class="text-destructive">*</span></Label>
                                <Input id="document_number" v-model="form.document_number" placeholder="Contoh: SPPD/01/2026" />
                            </div>
                            <div class="space-y-2">
                                <Label for="date">Tanggal SPPD <span class="text-destructive">*</span></Label>
                                <Input id="date" type="date" v-model="form.date" />
                            </div>
                            <div class="space-y-2">
                                <Label for="type">Jenis Pengeluaran <span class="text-destructive">*</span></Label>
                                <Select v-model="form.type">
                                    <SelectTrigger><SelectValue placeholder="Pilih Jenis" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="UP">Uang Persediaan (UP)</SelectItem>
                                        <SelectItem value="GU">Ganti Uang (GU)</SelectItem>
                                        <SelectItem value="TU">Tambahan Uang (TU)</SelectItem>
                                        <SelectItem value="LS_Pegawai">LS Pegawai</SelectItem>
                                        <SelectItem value="LS_Barang_Jasa_Modal">LS Barang, Jasa dan Modal</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-2">
                                <Label for="description">Uraian / Tujuan Pembayaran <span class="text-destructive">*</span></Label>
                                <Textarea id="description" v-model="form.description" rows="2" placeholder="Jelaskan untuk pembayaran apa..." />
                            </div>
                            <div class="space-y-2">
                                <Label for="treasurer_id">Bendahara Pengeluaran <span class="text-destructive">*</span></Label>
                                <Select v-model="form.treasurer_id">
                                    <SelectTrigger><SelectValue placeholder="Pilih Bendahara" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">{{ user.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-2">
                                <Label for="kpa_id">Kuasa Pengguna Anggaran (KPA) <span class="text-destructive">*</span></Label>
                                <Select v-model="form.kpa_id">
                                    <SelectTrigger><SelectValue placeholder="Pilih KPA" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">{{ user.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Informasi Kegiatan -->
                    <div>
                        <h3 class="text-lg font-semibold text-secondary mb-4 border-b pb-2">Informasi Kegiatan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="ptk_id">Pejabat Teknis Kegiatan (PTK) <span class="text-destructive">*</span></Label>
                                <Select v-model="form.ptk_id">
                                    <SelectTrigger><SelectValue placeholder="Pilih PTK" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">{{ user.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-2">
                                <Label for="activity_date">Waktu Pelaksanaan <span class="text-destructive">*</span></Label>
                                <Input id="activity_date" type="date" v-model="form.activity_date" />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label for="activity_description">Deskripsi Pekerjaan <span class="text-destructive">*</span></Label>
                                <Textarea id="activity_description" v-model="form.activity_description" rows="2" placeholder="Deskripsi spesifik kegiatan..." />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Informasi Pembayaran / Vendor -->
                    <div>
                        <h3 class="text-lg font-semibold text-secondary mb-4 border-b pb-2">
                            {{ form.type === 'UP' ? 'Tujuan Penyaluran Kas UP' : 'Informasi Pembayaran (Vendor)' }}
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="payment_method">Cara Bayar <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.payment_method" :disabled="form.type === 'UP' || form.type === 'GU'">
                                        <SelectTrigger :disabled="form.type === 'UP' || form.type === 'GU'">
                                            <SelectValue placeholder="Pilih Cara Bayar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="rekanan">Ke Rekanan (Pihak Ketiga)</SelectItem>
                                            <SelectItem value="pegawai">Ke Pegawai</SelectItem>
                                            <SelectItem value="ls_bendahara">LS Bendahara</SelectItem>
                                            <SelectItem value="terlampir">Daftar Terlampir</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                
                                <template v-if="form.payment_method === 'rekanan'">
                                    <div class="space-y-2">
                                        <Label for="vendor_id">Nama Rekanan</Label>
                                        <Select v-model="form.vendor_id">
                                            <SelectTrigger><SelectValue placeholder="Pilih Rekanan" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="vendor in vendors" :key="vendor.id" :value="vendor.id.toString()">{{ vendor.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="bank_name">Bank Penerima</Label>
                                        <Input id="bank_name" v-model="form.bank_name" placeholder="Otomatis terisi..." />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="bank_account_number">No. Rekening</Label>
                                        <Input id="bank_account_number" v-model="form.bank_account_number" placeholder="Otomatis terisi..." />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="contract_number">No. Kontrak (SPK)</Label>
                                        <Input id="contract_number" v-model="form.contract_number" placeholder="Contoh: 027/SPK/..." />
                                    </div>
                                </template>
                            </div>

                            <!-- Banner Khusus UP: Penyaluran Kas ke Bendahara Pengeluaran -->
                            <div v-if="form.type === 'UP'" class="p-4 bg-muted/40 rounded-xl border flex items-start gap-3">
                                <Info class="w-5 h-5 text-primary shrink-0 mt-0.5" />
                                <div class="text-xs text-muted-foreground space-y-1">
                                    <p class="font-semibold text-secondary dark:text-foreground text-sm">Penyaluran Uang Muka Operasional (UP):</p>
                                    <p>Pencairan Uang Persediaan (UP) dipindahbukukan langsung dari <strong>Kas BLUD di Bank</strong> ke <strong>Rekening Kas Operasional Bendahara Pengeluaran</strong> untuk membiayai operasional rutin BLUD.</p>
                                </div>
                            </div>

                            <!-- Banner Khusus GU: Penggantian Kas Uang Persediaan ke Bendahara -->
                            <div v-else-if="form.type === 'GU'" class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-start gap-3">
                                <Info class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                                <div class="text-xs text-amber-900 dark:text-amber-200 space-y-1">
                                    <p class="font-semibold text-sm">Mekanisme Penggantian Uang Persediaan (GU):</p>
                                    <p>Pencairan SPPD-GU masuk ke <strong>Rekening Kas Bendahara Pengeluaran</strong> untuk memulihkan saldo kas operasional atas kuitansi-kuitansi belanja kas UP yang telah berstatus <strong>Cair</strong>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Rincian Anggaran -->
                <div v-show="currentStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="flex items-center justify-between border-b pb-2">
                        <div>
                            <h3 class="text-lg font-semibold text-secondary">
                                {{ form.type === 'UP' ? 'Pencairan Kas Uang Persediaan (UP)' : (form.type === 'GU' ? 'Penggantian Uang Persediaan (GU)' : 'Rincian Anggaran (Kode Rekening)') }}
                            </h3>
                            <p v-if="form.type === 'UP'" class="text-xs text-muted-foreground mt-0.5">
                                Nilai nominal uang muka kerja operasional bendahara pengeluaran.
                            </p>
                            <p v-else-if="form.type === 'GU'" class="text-xs text-muted-foreground mt-0.5">
                                Rekapitulasi belanja kuitansi kas UP yang akan diganti ke kas bendahara pengeluaran.
                            </p>
                        </div>
                        <!-- Tombol Tambah Baris hanya untuk belanja reguler non-UP dan non-GU -->
                        <div v-if="form.type !== 'UP' && form.type !== 'GU'" class="flex items-center gap-2">
                            <Button variant="outline" size="sm" @click="addDetailRow" type="button">
                                <Plus class="w-4 h-4 mr-1" /> Tambah Baris
                            </Button>
                        </div>
                    </div>

                    <!-- Banner Info jika tipe UP -->
                    <div v-if="form.type === 'UP'" class="p-3.5 bg-blue-500/10 border border-blue-500/20 rounded-xl text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2.5">
                        <Info class="w-4 h-4 shrink-0 text-blue-600 mt-0.5" />
                        <div>
                            <strong>Pengajuan Uang Persediaan (UP):</strong> Dana ini bersifat uang muka kerja operasional bendahara (non-anggaran). Tidak memotong pagu belanja RBA dan tidak menambah realisasi belanja di dashboard.
                        </div>
                    </div>

                    <!-- Khusus GU: Panel Pemilihan Multi-Kwitansi Belanja Kas UP -->
                    <div v-if="form.type === 'GU'" class="bg-card border-2 border-primary/20 rounded-xl p-5 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-base font-bold text-secondary flex items-center gap-2">
                                        <Receipt class="w-5 h-5 text-primary" />
                                        Kwitansi Belanja Kas UP yang akan di-GU
                                    </h4>
                                    <Badge v-if="selectedReceiptObjects.length > 0" variant="secondary" class="bg-primary/10 text-primary font-semibold text-xs">
                                        {{ selectedReceiptObjects.length }} Kwitansi Terpilih
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Pilih kuitansi belanja kas UP yang telah berstatus <strong>Cair</strong> untuk direkap ke dalam SPPD-GU ini.
                                </p>
                            </div>
                            <!-- Tombol Ubah/Kelola Kwitansi: HANYA tampil di pojok kanan atas jika SUDAH ADA kuitansi terpilih -->
                            <Button 
                                v-if="selectedReceiptObjects.length > 0"
                                type="button" 
                                variant="outline"
                                size="sm" 
                                @click="openReceiptModal"
                                class="border-input hover:bg-accent text-foreground shrink-0 shadow-sm font-medium"
                            >
                                <Filter class="w-4 h-4 mr-1.5 text-primary" />
                                Ubah / Kelola Kwitansi ({{ selectedReceiptObjects.length }})
                            </Button>
                        </div>

                        <!-- State 1: Summary Cards & Tabel jika ada kuitansi dipilih -->
                        <div v-if="selectedReceiptObjects.length > 0" class="space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="p-3 bg-muted/40 rounded-lg border">
                                    <span class="text-[11px] font-medium text-muted-foreground block">Jumlah Kwitansi</span>
                                    <span class="text-lg font-bold text-foreground">{{ selectedReceiptObjects.length }} berkas</span>
                                </div>
                                <div class="p-3 bg-muted/40 rounded-lg border">
                                    <span class="text-[11px] font-medium text-muted-foreground block">Total Belanja Bruto</span>
                                    <span class="text-lg font-bold font-mono text-foreground">{{ formatCurrency(totalSelectedGross) }}</span>
                                </div>
                                <div class="p-3 bg-muted/40 rounded-lg border">
                                    <span class="text-[11px] font-medium text-muted-foreground block">Total Potongan Pajak</span>
                                    <span class="text-lg font-bold font-mono text-destructive">{{ formatCurrency(totalSelectedTax) }}</span>
                                </div>
                                <div class="p-3 bg-primary/10 rounded-lg border border-primary/20">
                                    <span class="text-[11px] font-medium text-primary block">Netto Penggantian Kas</span>
                                    <span class="text-lg font-bold font-mono text-primary">{{ formatCurrency(totalSelectedNet) }}</span>
                                </div>
                            </div>

                            <!-- Pratinjau Daftar Kwitansi Terpilih -->
                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 py-2 bg-muted/40 border-b flex items-center justify-between">
                                    <span class="text-xs font-semibold text-secondary">Rincian Kwitansi Terlampir ({{ selectedReceiptObjects.length }}):</span>
                                    <span class="text-[11px] text-muted-foreground">Klik tanda silang (x) untuk melepas kuitansi</span>
                                </div>
                                <div class="max-h-56 overflow-y-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="text-xs bg-muted/20">
                                                <TableHead>No. Kwitansi</TableHead>
                                                <TableHead>Tgl</TableHead>
                                                <TableHead>Rekening Belanja</TableHead>
                                                <TableHead>Toko / Penerima</TableHead>
                                                <TableHead class="text-right">Bruto</TableHead>
                                                <TableHead class="text-right">Pajak</TableHead>
                                                <TableHead class="w-10 text-center"></TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="rc in selectedReceiptObjects" :key="'sel-'+rc.id" class="text-xs">
                                                <TableCell class="font-mono font-medium">{{ rc.receipt_number }}</TableCell>
                                                <TableCell class="text-muted-foreground">{{ rc.date }}</TableCell>
                                                <TableCell>
                                                    <span class="font-medium">{{ rc.account_code?.code }}</span>
                                                    <span class="text-muted-foreground ml-1 hidden sm:inline">- {{ rc.account_code?.name }}</span>
                                                </TableCell>
                                                <TableCell>{{ rc.recipient_name }}</TableCell>
                                                <TableCell class="text-right font-mono font-bold">{{ formatCurrency(rc.amount) }}</TableCell>
                                                <TableCell class="text-right font-mono text-muted-foreground">{{ formatCurrency(rc.tax_amount || 0) }}</TableCell>
                                                <TableCell class="text-center">
                                                    <button 
                                                        type="button" 
                                                        @click="removeSelectedReceipt(rc.id)" 
                                                        class="text-muted-foreground hover:text-destructive p-1 rounded transition-colors"
                                                        title="Lepaskan kwitansi ini"
                                                    >
                                                        <X class="w-3.5 h-3.5" />
                                                    </button>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg text-xs text-blue-900 dark:text-blue-200 flex items-center gap-2">
                                <Info class="w-4 h-4 shrink-0 text-blue-600" />
                                <span>Rincian akun anggaran dan potongan pajak di bawah ini telah diakumulasikan otomatis dari kuitansi terpilih di atas.</span>
                            </div>
                        </div>

                        <!-- State 2: Empty state jika belum ada kuitansi dipilih (HANYA ADA 1 TOMBOL UTAMA) -->
                        <div v-else class="text-center py-8 px-4 border-2 border-dashed border-primary/30 rounded-xl bg-primary/[0.02]">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 text-primary mb-3">
                                <Receipt class="w-6 h-6" />
                            </div>
                            <p class="text-sm font-bold text-secondary dark:text-foreground">Belum ada kuitansi belanja yang dipilih</p>
                            <p class="text-xs text-muted-foreground mt-1 mb-4 max-w-md mx-auto">
                                SPPD jenis GU memerlukan kuitansi belanja kas UP yang telah <strong>Cair</strong>. Klik tombol di bawah untuk memilih kuitansi yang akan di-GU.
                            </p>
                            <Button 
                                type="button" 
                                size="default" 
                                @click="openReceiptModal" 
                                class="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold shadow-sm px-6"
                            >
                                <Plus class="w-4 h-4 mr-1.5" /> Pilih Kwitansi Belanja
                            </Button>
                        </div>
                    </div>

                    <!-- Seksi Rincian Kode Rekening Belanja -->
                    <!-- KASUS A: Khusus GU jika belum ada kuitansi dipilih -->
                    <div v-if="form.type === 'GU' && selectedReceiptObjects.length === 0" class="p-6 border-2 border-dashed rounded-xl text-center bg-muted/20 text-muted-foreground">
                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-muted text-muted-foreground mb-2">
                            <Info class="w-4 h-4 text-muted-foreground" />
                        </div>
                        <p class="text-xs font-semibold text-secondary dark:text-foreground">Rincian Anggaran Masih Kosong</p>
                        <p class="text-[11px] text-muted-foreground mt-0.5 max-w-sm mx-auto">
                            Rincian rekening belanja 5.x akan otomatis terisi dan terakumulasi setelah Anda memilih kuitansi belanja di atas.
                        </p>
                    </div>

                    <!-- KASUS B: Khusus GU jika sudah ada kuitansi dipilih (Terkunci & Otomatis) -->
                    <div v-else-if="form.type === 'GU'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-secondary">Rekapitulasi Kode Rekening Belanja</h4>
                                <Badge variant="outline" class="text-[10px] bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30">
                                    Otomatis dari Kwitansi
                                </Badge>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ form.details.length }} rekening terakumulasi</span>
                        </div>

                        <div class="overflow-x-auto bg-background rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-muted/50">
                                        <TableHead class="w-[60%]">Akun Anggaran Belanja</TableHead>
                                        <TableHead class="w-[40%] text-right">Nominal (Rp)</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(item, index) in form.details" :key="'gu-det-'+index">
                                        <TableCell class="align-top">
                                            <div class="font-medium text-sm text-secondary">
                                                {{ accountCodes.find(a => a.id.toString() === item.account_code_id?.toString())?.code }} - 
                                                {{ accountCodes.find(a => a.id.toString() === item.account_code_id?.toString())?.name }}
                                            </div>
                                            <!-- Info Pagu RBA -->
                                            <div v-if="item.account_code_id" class="mt-2 text-[11px] sm:text-xs p-2 sm:p-2.5 bg-muted/30 rounded-lg border flex flex-col gap-1">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-muted-foreground">Total Pagu:</span>
                                                    <span class="font-semibold font-mono">{{ formatCurrency(getAccountInfo(Number(item.account_code_id), 'total_budget')) }}</span>
                                                </div>
                                                <div class="flex justify-between items-center border-t border-border/80 pt-1 mt-0.5">
                                                    <span class="font-semibold text-foreground">Sisa Pagu:</span>
                                                    <span class="font-bold font-mono text-primary">{{ formatCurrency(getAccountInfo(Number(item.account_code_id), 'remaining_budget')) }}</span>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="align-top text-right">
                                            <div class="font-mono text-base font-bold text-foreground">
                                                {{ formatCurrency(item.amount) }}
                                            </div>
                                            <div class="text-xs mt-1 text-muted-foreground italic">
                                                {{ terbilang(item.amount) }} Rupiah
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    <!-- KASUS C: Belanja Reguler / UP (Input Manual) -->
                    <div v-else class="overflow-x-auto bg-background rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50">
                                    <TableHead :class="form.type === 'UP' ? 'w-[55%]' : 'w-[50%]'">
                                        {{ form.type === 'UP' ? 'Akun Kas Penerima UP' : 'Akun Anggaran / Kas' }}
                                    </TableHead>
                                    <TableHead :class="form.type === 'UP' ? 'w-[45%]' : 'w-[40%]'">Nominal (Rp)</TableHead>
                                    <TableHead v-if="form.type !== 'UP'" class="w-[10%] text-center">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in form.details" :key="index">
                                    <TableCell class="align-top">
                                        <template v-if="form.type === 'UP'">
                                            <div class="space-y-1.5">
                                                <Select v-model="form.details[index].account_code_id">
                                                    <SelectTrigger><SelectValue placeholder="Pilih Akun Kas UP" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="acc in accountCodes" :key="acc.id" :value="acc.id.toString()">
                                                            {{ acc.code }} - {{ acc.name }} {{ acc.is_non_budgetary ? '(Kas UP)' : '' }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <div class="flex items-center gap-1.5 text-[11px] text-blue-700 dark:text-blue-300 font-medium">
                                                    <Badge variant="outline" class="text-[10px] bg-blue-500/10 text-blue-700 border-blue-500/30">Kas Operasional</Badge>
                                                    <span>Akun kas debet sesuai aturan jurnal sistem (Non-Anggaran).</span>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <Select v-model="form.details[index].account_code_id">
                                                <SelectTrigger><SelectValue placeholder="Pilih Akun" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="acc in accountCodes" :key="acc.id" :value="acc.id.toString()">
                                                        {{ acc.code }} - {{ acc.name }} {{ acc.is_non_budgetary ? '(Non-Anggaran / UP)' : '' }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            
                                            <!-- Info Pagu RBA untuk Belanja Riil -->
                                            <div v-if="item.account_code_id" class="mt-2 text-[11px] sm:text-xs p-2 sm:p-3 bg-muted/30 rounded-lg border flex flex-col gap-1.5 shadow-sm">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-muted-foreground">Total Pagu:</span>
                                                    <span class="font-semibold font-mono">{{ formatCurrency(getAccountInfo(Number(item.account_code_id), 'total_budget')) }}</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-muted-foreground">Jml. Pengajuan:</span>
                                                    <span class="font-medium font-mono text-amber-600">{{ formatCurrency(getAccountInfo(Number(item.account_code_id), 'submitted_amount')) }}</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-muted-foreground">Jml. Cair (SPD):</span>
                                                    <span class="font-medium font-mono text-emerald-600">{{ formatCurrency(getAccountInfo(Number(item.account_code_id), 'disbursed_amount')) }}</span>
                                                </div>
                                                <div class="flex justify-between items-center border-t border-border/80 pt-1.5 mt-0.5">
                                                    <span class="font-semibold text-foreground">Sisa Pagu:</span>
                                                    <span class="font-bold font-mono text-primary">{{ formatCurrency(getAccountInfo(Number(item.account_code_id), 'remaining_budget')) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </TableCell>
                                    <TableCell class="align-top">
                                        <div class="space-y-1.5">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-semibold">Rp</span>
                                                <Input type="number" step="0.01" v-model="form.details[index].amount" class="pl-9 font-mono text-base font-bold focus-visible:ring-primary" placeholder="0" />
                                            </div>
                                            <!-- Live Terbilang Text -->
                                            <div v-if="form.details[index].amount && Number(form.details[index].amount) > 0" class="text-xs p-2 rounded-md bg-muted/40 border text-muted-foreground font-sans leading-snug">
                                                <span class="font-semibold text-foreground">Terbilang:</span> 
                                                <span class="italic text-primary font-medium ml-1">{{ terbilang(form.details[index].amount) }} Rupiah</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell v-if="form.type !== 'UP'" class="text-center align-top pt-4">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:bg-destructive/10" @click="removeDetailRow(index)" type="button">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="form.details.length === 0">
                                    <TableCell :colspan="form.type === 'UP' ? 2 : 3" class="text-center text-muted-foreground h-24">
                                        Belum ada rincian ditambahkan. Klik tombol "Tambah Baris".
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    
                    <!-- Seksi Potongan Pajak: Khusus Non-UP -->
                    <div v-if="form.type !== 'UP'" class="mt-8">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-semibold text-secondary">Potongan Pajak</h3>
                                <Badge v-if="form.type === 'GU'" variant="outline" class="text-[10px] bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30">
                                    Otomatis dari Kwitansi
                                </Badge>
                                <span v-else class="text-xs text-muted-foreground">(Opsional)</span>
                            </div>
                            <Button v-if="form.type !== 'GU'" type="button" variant="outline" size="sm" @click="addTaxRow">
                                <Plus class="w-4 h-4 mr-1" /> Tambah Pajak
                            </Button>
                        </div>

                        <!-- Jika GU dan belum ada kuitansi -->
                        <div v-if="form.type === 'GU' && selectedReceiptObjects.length === 0" class="p-4 border border-dashed rounded-xl text-center text-xs text-muted-foreground bg-muted/10">
                            Potongan pajak akan otomatis direkap setelah kuitansi belanja dipilih di atas.
                        </div>

                        <!-- Jika GU dan kuitansi tidak memiliki pajak -->
                        <div v-else-if="form.type === 'GU' && form.taxes.length === 0" class="p-4 bg-muted/30 border rounded-xl flex items-center gap-3 text-xs text-muted-foreground">
                            <CheckCircle2 class="w-4 h-4 text-muted-foreground shrink-0" />
                            <span>Tidak ada potongan pajak pada berkas kuitansi yang dipilih.</span>
                        </div>

                        <!-- Tabel Pajak untuk GU (read-only) atau non-GU (editable) -->
                        <div v-else class="overflow-x-auto border rounded-xl">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-muted/30">
                                        <TableHead class="w-1/3">Jenis Pajak</TableHead>
                                        <TableHead class="w-1/3">Kode Billing</TableHead>
                                        <TableHead :class="form.type === 'GU' ? 'text-right' : ''">Nominal</TableHead>
                                        <TableHead v-if="form.type !== 'GU'" class="w-[50px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-if="form.type === 'GU'">
                                        <TableRow v-for="(item, index) in form.taxes" :key="'tax-'+index">
                                            <TableCell class="font-medium text-sm">{{ item.tax_type }}</TableCell>
                                            <TableCell class="font-mono text-xs text-muted-foreground">{{ item.billing_code || '-' }}</TableCell>
                                            <TableCell class="text-right font-mono font-bold">{{ formatCurrency(item.amount) }}</TableCell>
                                        </TableRow>
                                    </template>
                                    <template v-else>
                                        <TableRow v-for="(item, index) in form.taxes" :key="'tax-'+index">
                                            <TableCell class="align-top">
                                                <Select v-model="form.taxes[index].tax_type">
                                                    <SelectTrigger><SelectValue placeholder="Pilih Jenis" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="PPN">PPN</SelectItem>
                                                        <SelectItem value="PPh 21">PPh 21</SelectItem>
                                                        <SelectItem value="PPh 22">PPh 22</SelectItem>
                                                        <SelectItem value="PPh 23">PPh 23</SelectItem>
                                                        <SelectItem value="PPh Final">PPh Final</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </TableCell>
                                            <TableCell class="align-top">
                                                <Input v-model="form.taxes[index].billing_code" placeholder="Opsional" class="font-mono" />
                                            </TableCell>
                                            <TableCell class="align-top">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">Rp</span>
                                                    <Input type="number" step="0.01" v-model="form.taxes[index].amount" class="pl-8 font-mono" />
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-center align-top pt-4">
                                                <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:bg-destructive/10" @click="removeTaxRow(index)" type="button">
                                                    <Trash2 class="w-4 h-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="form.taxes.length === 0">
                                            <TableCell colspan="4" class="text-center text-muted-foreground h-16">
                                                Tidak ada potongan pajak. Klik "Tambah Pajak" jika ada.
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    <!-- Kotak Info Bebas Pajak untuk UP -->
                    <div v-else class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-start gap-3">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                        <div class="text-xs text-muted-foreground space-y-1">
                            <p class="font-semibold text-emerald-800 dark:text-emerald-300 text-sm">Bebas Potongan Pajak (Non-Pajak):</p>
                            <p>Pencairan Uang Persediaan (UP) tidak dikenakan potongan pajak (PPN/PPh). Pemungutan dan penyetoran pajak akan dilakukan saat dana UP digunakan untuk belanja riil pada pengajuan pertanggungjawaban (SPJ / Ganti Uang / GU).</p>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Upload & Submit -->
                <div v-show="currentStep === 3" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                    <h3 class="text-lg font-semibold text-secondary mb-4 border-b pb-2">
                        {{ form.type === 'UP' ? 'Ringkasan Pencairan UP & Dokumen Pendukung' : 'Ringkasan & Lampiran' }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Summary Card -->
                        <div class="bg-muted/30 rounded-xl p-6 border shadow-sm h-fit">
                            <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-2">
                                {{ form.type === 'UP' ? 'Total Nilai Pencairan UP' : 'Total Pengajuan (Kotor)' }}
                            </h4>
                            <div class="text-3xl sm:text-4xl font-bold font-mono text-primary mb-2">
                                {{ formatCurrency(totalAmount) }}
                            </div>
                            
                            <!-- Terbilang di ringkasan -->
                            <div v-if="totalAmount > 0" class="text-xs italic text-muted-foreground mb-6 pb-4 border-b border-border/50">
                                Terbilang: <strong class="text-foreground not-italic">{{ terbilang(totalAmount) }} Rupiah</strong>
                            </div>
                            
                            <div class="space-y-2.5 text-sm">
                                <div class="flex justify-between border-b pb-2 border-border/50">
                                    <span class="text-muted-foreground">Jenis Transaksi:</span>
                                    <span class="font-semibold text-secondary dark:text-foreground">
                                        {{ form.type === 'UP' ? 'Uang Persediaan (UP)' : form.type }}
                                    </span>
                                </div>

                                <!-- Baris khusus UP -->
                                <template v-if="form.type === 'UP'">
                                    <div class="flex justify-between border-b pb-2 border-border/50">
                                        <span class="text-muted-foreground">Sifat Dana:</span>
                                        <Badge variant="outline" class="bg-blue-500/10 text-blue-700 border-blue-500/30 text-xs">Uang Muka Kerja (Non-Pagu)</Badge>
                                    </div>
                                    <div class="flex justify-between border-b pb-2 border-border/50">
                                        <span class="text-muted-foreground">Mutasi Kas:</span>
                                        <span class="font-medium text-xs text-right">Kas BLUD ➔ Kas Bendahara</span>
                                    </div>
                                    <div class="flex justify-between pb-1">
                                        <span class="text-muted-foreground">Potongan Pajak:</span>
                                        <span class="font-medium text-emerald-600">Bebas Pajak (Rp 0)</span>
                                    </div>
                                </template>

                                <!-- Baris Non-UP -->
                                <template v-else>
                                    <div class="flex justify-between border-b pb-2 border-border/50">
                                        <span class="text-muted-foreground">Jumlah Akun:</span>
                                        <span class="font-medium">{{ form.details.length }} Akun</span>
                                    </div>
                                    <div class="flex justify-between border-b pb-2 border-border/50">
                                        <span class="text-muted-foreground">Potongan Pajak:</span>
                                        <span class="font-medium text-destructive">{{ form.taxes.length > 0 ? '-' + formatCurrency(totalTax) : 'Rp 0' }}</span>
                                    </div>
                                    <div class="flex justify-between border-b pb-2 border-border/50">
                                        <span class="text-muted-foreground">Total Bersih:</span>
                                        <span class="font-bold text-emerald-600">{{ formatCurrency(totalAmount - totalTax) }}</span>
                                    </div>
                                    <div class="flex justify-between pb-1">
                                        <span class="text-muted-foreground">Cara Bayar:</span>
                                        <span class="font-medium capitalize">{{ form.payment_method.replace('_', ' ') }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Upload Card -->
                        <div>
                            <Label class="text-base mb-2 block">
                                {{ form.type === 'UP' ? 'Upload Dokumen Pendukung (SK Plafon UP / Surat Permohonan)' : 'Upload Dokumen Pendukung (Invoice/Kuitansi)' }}
                            </Label>
                            <div 
                                class="border-2 border-dashed border-border hover:border-primary/50 transition-colors rounded-xl p-8 flex flex-col items-center justify-center text-center cursor-pointer bg-background"
                                @click="$refs.fileInput.click()"
                            >
                                <UploadCloud class="w-12 h-12 text-muted-foreground mb-4" />
                                <h5 class="text-sm font-semibold mb-1">Klik untuk memilih file</h5>
                                <p class="text-xs text-muted-foreground mb-4">Maksimal 5MB (PDF, JPG, PNG)</p>
                                
                                <div v-if="fileName" class="px-4 py-2 bg-primary/10 text-primary rounded-md text-sm truncate max-w-full">
                                    {{ fileName }}
                                </div>
                                
                                <input 
                                    type="file" 
                                    ref="fileInput" 
                                    class="hidden" 
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="handleFileUpload"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground mt-2">
                                {{ form.type === 'UP' ? 'Lampirkan SK Penetapan Plafon UP atau Surat Permohonan Uang Persediaan dari Bendahara Pengeluaran.' : 'Dokumen ini akan digunakan sebagai bukti fisik saat verifikasi.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wizard Footer Buttons -->
            <div class="p-4 border-t bg-muted/20 flex justify-between items-center">
                <Button 
                    type="button" 
                    variant="outline" 
                    @click="prevStep" 
                    :disabled="currentStep === 1 || form.processing"
                >
                    <ChevronLeft class="w-4 h-4 mr-2" /> Kembali
                </Button>
                
                <div class="flex gap-2">
                    <template v-if="currentStep < 3">
                        <Button 
                            type="button" 
                            @click="nextStep" 
                            :disabled="currentStep === 2 && form.type === 'GU' && selectedReceiptObjects.length === 0"
                            class="bg-secondary hover:bg-secondary/90 text-white disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Lanjut <ChevronRight class="w-4 h-4 ml-2" />
                        </Button>
                    </template>
                    <template v-else>
                        <Button 
                            type="button" 
                            variant="secondary" 
                            @click="submitForm('draft')" 
                            :disabled="form.processing || form.details.length === 0 || totalAmount <= 0"
                        >
                            <Save class="w-4 h-4 mr-2" /> Simpan Draft
                        </Button>
                        <Button 
                            type="button" 
                            @click="submitForm('submitted')" 
                            :disabled="form.processing || form.details.length === 0 || totalAmount <= 0"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white"
                        >
                            <Send class="w-4 h-4 mr-2" /> Ajukan SPPD
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Dialog Multi-Select Kwitansi Belanja Kas UP untuk GU -->
        <Dialog v-model:open="isReceiptModalOpen">
            <DialogScrollContent class="max-w-4xl max-h-[85vh] flex flex-col p-0 overflow-hidden">
                <DialogHeader class="p-6 pb-4 border-b shrink-0 bg-background">
                    <div class="flex items-center justify-between">
                        <div>
                            <DialogTitle class="text-xl font-bold flex items-center gap-2 text-secondary">
                                <Receipt class="w-5 h-5 text-primary" />
                                Pilih Kwitansi Belanja Kas UP untuk GU
                            </DialogTitle>
                            <DialogDescription class="text-xs text-muted-foreground mt-1">
                                Centang kuitansi berstatus <strong>Cair</strong> yang akan dimasukkan ke dalam berkas SPPD-GU ini.
                            </DialogDescription>
                        </div>
                    </div>
                    
                    <!-- Toolbar Filter & Search -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                        <div class="relative">
                            <Search class="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
                            <Input 
                                v-model="receiptSearch" 
                                placeholder="Cari no. kwitansi, toko, uraian..." 
                                class="pl-9 text-sm"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-muted-foreground shrink-0">Filter Bulan:</span>
                            <Select v-model="receiptMonthFilter">
                                <SelectTrigger class="text-sm">
                                    <SelectValue placeholder="Pilih Bulan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                                        {{ m.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body: Table of Receipts -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div class="flex items-center justify-between pb-1">
                        <div class="flex items-center gap-2">
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                @click="toggleSelectAllFiltered"
                                class="text-xs h-8"
                            >
                                <component :is="isAllFilteredSelected ? CheckSquare : Square" class="w-4 h-4 mr-1.5 text-primary" />
                                {{ isAllFilteredSelected ? 'Batalkan Semua (Tampil)' : 'Pilih Semua (Tampil)' }}
                            </Button>
                            <span class="text-xs text-muted-foreground">
                                Menampilkan {{ filteredReceipts.length }} kuitansi
                            </span>
                        </div>
                        <div class="text-xs font-semibold text-primary">
                            {{ selectedReceiptIds.length }} kuitansi dipilih
                        </div>
                    </div>

                    <div class="border rounded-xl overflow-hidden bg-background">
                        <Table>
                            <TableHeader class="bg-muted/50">
                                <TableRow>
                                    <TableHead class="w-10 text-center">#</TableHead>
                                    <TableHead class="w-[140px]">No. Kwitansi</TableHead>
                                    <TableHead class="w-[100px]">Tanggal</TableHead>
                                    <TableHead>Rekening Belanja</TableHead>
                                    <TableHead>Toko / Penerima</TableHead>
                                    <TableHead class="text-right">Bruto</TableHead>
                                    <TableHead class="text-right">Pajak</TableHead>
                                    <TableHead class="text-right">Netto</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="r in filteredReceipts" 
                                    :key="r.id"
                                    :class="['cursor-pointer transition-colors hover:bg-muted/40', selectedReceiptIds.includes(r.id) ? 'bg-primary/5 font-medium' : '']"
                                    @click="toggleReceiptSelection(r.id)"
                                >
                                    <TableCell class="text-center" @click.stop>
                                        <input 
                                            type="checkbox" 
                                            :checked="selectedReceiptIds.includes(r.id)" 
                                            @change="toggleReceiptSelection(r.id)"
                                            class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                                        />
                                    </TableCell>
                                    <TableCell class="font-mono font-medium text-xs">{{ r.receipt_number }}</TableCell>
                                    <TableCell class="text-xs text-muted-foreground">{{ r.date }}</TableCell>
                                    <TableCell class="text-xs">
                                        <div class="font-medium text-foreground">{{ r.account_code?.code }}</div>
                                        <div class="text-[11px] text-muted-foreground line-clamp-1">{{ r.account_code?.name }}</div>
                                    </TableCell>
                                    <TableCell class="text-xs">
                                        <div class="font-medium">{{ r.recipient_name }}</div>
                                        <div class="text-[11px] text-muted-foreground line-clamp-1">{{ r.description }}</div>
                                    </TableCell>
                                    <TableCell class="text-xs font-mono font-bold text-right">{{ formatCurrency(r.amount) }}</TableCell>
                                    <TableCell class="text-xs font-mono text-muted-foreground text-right">{{ formatCurrency(r.tax_amount || 0) }}</TableCell>
                                    <TableCell class="text-xs font-mono font-bold text-primary text-right">
                                        {{ formatCurrency(Math.max(0, Number(r.amount || 0) - Number(r.tax_amount || 0))) }}
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredReceipts.length === 0">
                                    <TableCell colspan="8" class="text-center py-12 text-muted-foreground">
                                        <Receipt class="w-10 h-10 mx-auto text-muted-foreground/40 mb-2" />
                                        <p class="font-medium text-sm">Tidak ada kuitansi kas UP yang memenuhi filter.</p>
                                        <p class="text-xs mt-1">Pastikan kuitansi telah berstatus <strong>Cair</strong> dan belum masuk ke SPPD lain.</p>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <!-- Footer: Live Summary & Apply Action -->
                <DialogFooter class="p-4 px-6 border-t bg-muted/20 flex flex-row items-center justify-between shrink-0">
                    <div class="text-xs space-y-0.5">
                        <div class="font-bold text-foreground">
                            {{ selectedReceiptIds.length }} kuitansi terpilih:
                            <span class="text-primary font-mono text-sm ml-1">{{ formatCurrency(totalSelectedGross) }}</span>
                        </div>
                        <div class="text-muted-foreground text-[11px]">
                            Pajak: {{ formatCurrency(totalSelectedTax) }} | Netto: {{ formatCurrency(totalSelectedNet) }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" size="sm" @click="isReceiptModalOpen = false">
                            Batal
                        </Button>
                        <Button 
                            type="button" 
                            size="sm" 
                            class="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold"
                            @click="applyReceiptSelection"
                        >
                            <CheckCircle2 class="w-4 h-4 mr-1.5" />
                            Gunakan {{ selectedReceiptIds.length }} Kwitansi
                        </Button>
                    </div>
                </DialogFooter>
            </DialogScrollContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
