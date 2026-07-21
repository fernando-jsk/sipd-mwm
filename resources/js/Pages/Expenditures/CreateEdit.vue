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
import { Trash2, Plus, UploadCloud, ChevronRight, ChevronLeft, Save, Send } from '@lucide/vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';

const props = defineProps({
    expenditure: Object,
    users: Array,
    vendors: Array,
    accountCodes: Array,
});

const isEdit = !!props.expenditure;

const form = useForm({
    document_number: props.expenditure?.document_number || '',
    date: props.expenditure?.date ? new Date(props.expenditure.date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    type: props.expenditure?.type || 'UP',
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
    details: props.expenditure?.details ? props.expenditure.details.map(d => ({ ...d, account_code_id: d.account_code_id.toString() })) : [],
    taxes: props.expenditure?.taxes || [],
});

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
                        <h3 class="text-lg font-semibold text-secondary mb-4 border-b pb-2">Informasi Pembayaran (Vendor)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="payment_method">Cara Bayar <span class="text-destructive">*</span></Label>
                                <Select v-model="form.payment_method">
                                    <SelectTrigger><SelectValue placeholder="Pilih Cara Bayar" /></SelectTrigger>
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
                    </div>
                </div>

                <!-- STEP 2: Rincian Anggaran -->
                <div v-show="currentStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="flex items-center justify-between border-b pb-2">
                        <h3 class="text-lg font-semibold text-secondary">Rincian Anggaran (Kode Rekening)</h3>
                        <Button variant="outline" size="sm" @click="addDetailRow" type="button">
                            <Plus class="w-4 h-4 mr-1" /> Tambah Baris
                        </Button>
                    </div>

                    <div class="overflow-x-auto bg-background rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50">
                                    <TableHead class="w-[50%]">Akun Anggaran (RBA)</TableHead>
                                    <TableHead class="w-[40%]">Nominal (Rp)</TableHead>
                                    <TableHead class="w-[10%] text-center">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in form.details" :key="index">
                                    <TableCell class="align-top">
                                        <Select v-model="form.details[index].account_code_id">
                                            <SelectTrigger><SelectValue placeholder="Pilih Akun" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="acc in accountCodes" :key="acc.id" :value="acc.id.toString()">
                                                    {{ acc.code }} - {{ acc.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
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
                                    </TableCell>
                                    <TableCell class="align-top">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">Rp</span>
                                            <Input type="number" step="0.01" v-model="form.details[index].amount" class="pl-8 font-mono" />
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center align-top pt-4">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:bg-destructive/10" @click="removeDetailRow(index)" type="button">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="form.details.length === 0">
                                    <TableCell colspan="3" class="text-center text-muted-foreground h-24">
                                        Belum ada rincian ditambahkan. Klik tombol "Tambah Baris".
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    
                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <h3 class="text-lg font-semibold text-secondary">Potongan Pajak (Opsional)</h3>
                            <Button type="button" variant="outline" size="sm" @click="addTaxRow">
                                <Plus class="w-4 h-4 mr-1" /> Tambah Pajak
                            </Button>
                        </div>
                        <div class="overflow-x-auto border rounded-xl">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-muted/30">
                                        <TableHead class="w-1/3">Jenis Pajak</TableHead>
                                        <TableHead class="w-1/3">Kode Billing</TableHead>
                                        <TableHead>Nominal</TableHead>
                                        <TableHead class="w-[50px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
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
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Upload & Submit -->
                <div v-show="currentStep === 3" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                    <h3 class="text-lg font-semibold text-secondary mb-4 border-b pb-2">Ringkasan & Lampiran</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Summary Card -->
                        <div class="bg-muted/30 rounded-xl p-6 border shadow-sm h-fit">
                            <h4 class="text-sm font-bold text-muted-foreground uppercase tracking-wider mb-4">Total Pengajuan (Kotor)</h4>
                            <div class="text-4xl font-bold font-mono text-primary mb-6">
                                {{ formatCurrency(totalAmount) }}
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between border-b pb-2 border-border/50">
                                    <span class="text-muted-foreground">Jenis:</span>
                                    <span class="font-medium">{{ form.type }}</span>
                                </div>
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
                                <div class="flex justify-between pb-2">
                                    <span class="text-muted-foreground">Cara Bayar:</span>
                                    <span class="font-medium capitalize">{{ form.payment_method.replace('_', ' ') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Card -->
                        <div>
                            <Label class="text-base mb-2 block">Upload Dokumen Pendukung (Invoice/Kuitansi)</Label>
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
                            <p class="text-xs text-muted-foreground mt-2">Dokumen ini akan digunakan sebagai bukti fisik saat verifikasi.</p>
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
                        <Button type="button" @click="nextStep" class="bg-secondary hover:bg-secondary/90 text-white">
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
    </AuthenticatedLayout>
</template>
