<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ref, computed, watch } from 'vue';
import { FileText, Coins, CreditCard, Save, ArrowLeft, CheckCircle2, AlertTriangle, Building2 } from 'lucide-vue-next';

const props = defineProps({
    receipt: Object,
    receiptTypes: Array,
    fundingSources: Array,
});

const isEditing = !!props.receipt;

const form = useForm({
    document_number: props.receipt?.document_number || '',
    date: props.receipt?.date || new Date().toISOString().split('T')[0],
    receipt_type_id: props.receipt?.receipt_type_id?.toString() || '',
    receipt_sub_type_id: props.receipt?.receipt_sub_type_id?.toString() || 'none',
    description: props.receipt?.description || '',
    payer_name: props.receipt?.payer_name || '',
    payment_method: props.receipt?.payment_method || 'tunai',
    bank_name: props.receipt?.bank_name || '',
    bank_account_number: props.receipt?.bank_account_number || '',
    attachment: null,
    
    // Single nominal & funding source
    amount: props.receipt?.details?.[0]?.amount ? Number(props.receipt.details[0].amount) : '',
    funding_source_id: props.receipt?.details?.[0]?.funding_source_id?.toString() || 'none',
});

// Parent Receipt Type
const selectedParentType = computed(() => {
    if (!form.receipt_type_id) return null;
    return props.receiptTypes.find(t => t.id.toString() === form.receipt_type_id);
});

// Available Sub-Types from Parent
const availableSubTypes = computed(() => {
    return selectedParentType.value?.children || [];
});

// Selected Sub-Type
const selectedSubType = computed(() => {
    if (!form.receipt_sub_type_id || form.receipt_sub_type_id === 'none') return null;
    return availableSubTypes.value.find(s => s.id.toString() === form.receipt_sub_type_id);
});

// Otomatis deteksi kode rekening dari Sub-Type, fallback ke Parent Type
const mappedAccountCode = computed(() => {
    if (selectedSubType.value?.account_code) {
        return selectedSubType.value.account_code;
    }
    if (selectedParentType.value?.account_code) {
        return selectedParentType.value.account_code;
    }
    return null;
});

// Reset sub-type jika parent type berubah dan sub-type lama tidak valid
watch(() => form.receipt_type_id, () => {
    if (form.receipt_sub_type_id && form.receipt_sub_type_id !== 'none') {
        const isValid = availableSubTypes.value.some(s => s.id.toString() === form.receipt_sub_type_id);
        if (!isValid) form.receipt_sub_type_id = 'none';
    }
});

const formatCurrency = (value) => {
    const num = Number(value) || 0;
    const hasDecimal = num % 1 !== 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: hasDecimal ? 2 : 0,
        maximumFractionDigits: 2,
    }).format(num);
};

const submit = () => {
    if (!mappedAccountCode.value) {
        alert('Jenis penerimaan yang dipilih belum memiliki pemetaan kode rekening. Silakan atur di menu Pengaturan Jenis Penerimaan terlebih dahulu.');
        return;
    }

    form.transform((data) => ({
        ...data,
        receipt_sub_type_id: data.receipt_sub_type_id === 'none' ? '' : data.receipt_sub_type_id,
        funding_source_id: data.funding_source_id === 'none' ? '' : data.funding_source_id,
        amount: data.amount,
        details: [
            {
                id: props.receipt?.details?.[0]?.id || null,
                account_code_id: mappedAccountCode.value?.id || null,
                funding_source_id: data.funding_source_id === 'none' ? null : (data.funding_source_id || null),
                amount: data.amount,
            }
        ],
        _method: isEditing ? 'PUT' : undefined,
    }));
    
    if (isEditing) {
        form.post(`/receipts/${props.receipt.id}`);
    } else {
        form.post('/receipts');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Rekap Penerimaan Harian' : 'Entri Rekap Penerimaan Harian'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <Link href="/receipts" class="text-xs text-muted-foreground hover:text-foreground transition-colors">Penerimaan</Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">{{ isEditing ? 'Edit' : 'Entri' }} Rekap Penerimaan</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        {{ isEditing ? 'Edit' : 'Entri' }} Rekap Penerimaan Harian
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" as-child>
                        <Link href="/receipts">
                            <ArrowLeft class="size-4 mr-1.5" /> Batal
                        </Link>
                    </Button>
                    <Button @click="submit" :disabled="form.processing || (form.receipt_type_id && !mappedAccountCode)">
                        <Save class="size-4 mr-1.5" />
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Draft' }}
                    </Button>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6 pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Kolom Kiri: Informasi Transaksi & Klasifikasi (7 Kolom) -->
                <div class="lg:col-span-7 space-y-6">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <FileText class="size-4 text-primary" />
                                <CardTitle>Informasi Transaksi</CardTitle>
                            </div>
                            <CardDescription>
                                Masukkan tanggal, nomor dokumen, dan klasifikasi jenis penerimaan pendapatan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Tanggal Transaksi & Nomor Dokumen -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="grid gap-1.5">
                                    <Label for="date" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Tanggal Transaksi <span class="text-destructive">*</span></Label>
                                    <Input id="date" type="date" v-model="form.date" :aria-invalid="!!form.errors.date" class="focus-visible:ring-primary" />
                                    <span class="text-[11px] text-destructive" v-if="form.errors.date">{{ form.errors.date }}</span>
                                </div>
                                <div class="grid gap-1.5">
                                    <Label for="document_number" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">No. Dokumen / TBP <span class="text-muted-foreground font-normal lowercase">(opsional)</span></Label>
                                    <Input id="document_number" v-model="form.document_number" placeholder="Contoh: TBP-001/2026" :aria-invalid="!!form.errors.document_number" class="focus-visible:ring-primary" />
                                    <span class="text-[11px] text-destructive" v-if="form.errors.document_number">{{ form.errors.document_number }}</span>
                                </div>
                            </div>

                            <!-- Jenis Penerimaan & Sub-Jenis -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="grid gap-1.5">
                                    <Label for="receipt_type_id" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Jenis Penerimaan <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.receipt_type_id">
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.receipt_type_id }">
                                            <SelectValue placeholder="Pilih Jenis Penerimaan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="type in receiptTypes" :key="type.id" :value="type.id.toString()">
                                                    {{ type.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <span class="text-[11px] text-destructive" v-if="form.errors.receipt_type_id">{{ form.errors.receipt_type_id }}</span>
                                </div>

                                <div class="grid gap-1.5" v-if="availableSubTypes.length > 0">
                                    <Label for="receipt_sub_type_id" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Sub-Jenis Penerimaan <span class="text-muted-foreground font-normal lowercase">(opsional)</span></Label>
                                    <Select v-model="form.receipt_sub_type_id">
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.receipt_sub_type_id }">
                                            <SelectValue placeholder="Pilih Sub-Jenis" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="none">-- Tanpa Sub-Jenis --</SelectItem>
                                                <SelectItem v-for="type in availableSubTypes" :key="type.id" :value="type.id.toString()">
                                                    {{ type.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <span class="text-[11px] text-destructive" v-if="form.errors.receipt_sub_type_id">{{ form.errors.receipt_sub_type_id }}</span>
                                </div>
                            </div>

                            <!-- Otomatis Terhubung ke Kode Rekening (Info Banner) -->
                            <div v-if="form.receipt_type_id" class="transition-all duration-300">
                                <div v-if="mappedAccountCode" class="p-3.5 rounded-lg border border-primary/20 bg-primary/5 flex items-start gap-3">
                                    <div class="p-1.5 rounded-md bg-primary/10 text-primary shrink-0 mt-0.5">
                                        <CheckCircle2 class="size-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] font-semibold text-primary uppercase tracking-wider">Kode Rekening Otomatis Terhubung</span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-primary/10 text-primary font-medium">Auto-mapped</span>
                                        </div>
                                        <p class="text-sm font-semibold text-foreground mt-0.5 font-mono">
                                            {{ mappedAccountCode.code }} <span class="font-sans font-medium text-muted-foreground">— {{ mappedAccountCode.name }}</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Pendapatan ini otomatis dibukukan ke rekening di atas sesuai master pengaturan.
                                        </p>
                                    </div>
                                </div>

                                <div v-else class="p-3.5 rounded-lg border border-destructive/30 bg-destructive/5 flex items-start gap-3">
                                    <div class="p-1.5 rounded-md bg-destructive/10 text-destructive shrink-0 mt-0.5">
                                        <AlertTriangle class="size-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[11px] font-semibold text-destructive uppercase tracking-wider">Peringatan: Belum Ada Pemetaan Rekening</span>
                                        <p class="text-xs text-destructive/90 mt-0.5">
                                            Jenis penerimaan yang dipilih belum dimapping ke kode rekening di master data. Silakan atur terlebih dahulu di menu <strong>Pengaturan Jenis Penerimaan</strong> agar pencatatan jurnal dapat diproses.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Nama Penyetor & Uraian -->
                            <div class="grid gap-1.5">
                                <Label for="payer_name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Nama Penyetor / Wajib Bayar <span class="text-destructive">*</span></Label>
                                <Input id="payer_name" v-model="form.payer_name" placeholder="Contoh: Pasien Umum Kasir Rawat Jalan / BPJS Kesehatan" :aria-invalid="!!form.errors.payer_name" class="focus-visible:ring-primary" />
                                <span class="text-[11px] text-destructive" v-if="form.errors.payer_name">{{ form.errors.payer_name }}</span>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="description" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Uraian Penerimaan <span class="text-destructive">*</span></Label>
                                <Textarea id="description" v-model="form.description" placeholder="Jelaskan rincian atau keterangan penerimaan harian ini..." class="min-h-24 focus-visible:ring-primary" :aria-invalid="!!form.errors.description" />
                                <span class="text-[11px] text-destructive" v-if="form.errors.description">{{ form.errors.description }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Kolom Kanan: Nominal & Kanal Pembayaran (5 Kolom) -->
                <div class="lg:col-span-5 space-y-6">
                    <!-- Card 1: Nominal & Sumber Dana -->
                    <Card class="border-primary/30 shadow-xs">
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <Coins class="size-4 text-primary" />
                                <CardTitle>Nominal Pendapatan</CardTitle>
                            </div>
                            <CardDescription>
                                Input nominal pendapatan yang disetor dan sumber dana terkait.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-1.5">
                                <Label for="amount" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Nominal Penerimaan (Rp) <span class="text-destructive">*</span></Label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-muted-foreground">Rp</span>
                                    <Input
                                        id="amount"
                                        type="number"
                                        v-model="form.amount"
                                        placeholder="0"
                                        class="pl-11 text-right font-mono text-lg font-bold tracking-tight focus-visible:ring-primary"
                                        min="0"
                                        step="0.01"
                                        :aria-invalid="!!form.errors.amount || !!form.errors['details.0.amount']"
                                    />
                                </div>
                                <span class="text-[11px] text-destructive" v-if="form.errors.amount">{{ form.errors.amount }}</span>
                                <span class="text-[11px] text-destructive" v-if="form.errors['details.0.amount']">{{ form.errors['details.0.amount'] }}</span>
                            </div>

                            <!-- Live Display Total Preview -->
                            <div class="p-4 rounded-xl bg-primary/5 border border-primary/20 flex flex-col gap-1 transition-all duration-200">
                                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Total Penerimaan Terbaca:</span>
                                <span class="text-2xl font-black text-primary font-mono tabular-nums tracking-tight">
                                    {{ formatCurrency(form.amount) }}
                                </span>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="funding_source_id" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Sumber Dana <span class="text-muted-foreground font-normal lowercase">(opsional)</span></Label>
                                <Select v-model="form.funding_source_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Sumber Dana (Opsional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="none">-- Tanpa Sumber Dana --</SelectItem>
                                            <SelectItem v-for="fs in fundingSources" :key="fs.id" :value="fs.id.toString()">
                                                {{ fs.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Card 2: Metode Pembayaran & Lampiran -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <CreditCard class="size-4 text-primary" />
                                <CardTitle>Metode Pembayaran & Lampiran</CardTitle>
                            </div>
                            <CardDescription>
                                Bukti setor fisik dan kanal transaksi penerimaan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-1.5">
                                <Label for="payment_method" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Metode Pembayaran <span class="text-destructive">*</span></Label>
                                <Select v-model="form.payment_method">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Metode" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="tunai">Tunai (Kas Langsung)</SelectItem>
                                            <SelectItem value="transfer">Transfer Bank</SelectItem>
                                            <SelectItem value="giro">Giro</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <span class="text-[11px] text-destructive" v-if="form.errors.payment_method">{{ form.errors.payment_method }}</span>
                            </div>

                            <template v-if="form.payment_method === 'transfer'">
                                <div class="grid gap-1.5">
                                    <Label for="bank_name" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Nama Bank Tujuan</Label>
                                    <Input id="bank_name" v-model="form.bank_name" placeholder="Contoh: Bank Jatim / BNI / BSI" :aria-invalid="!!form.errors.bank_name" class="focus-visible:ring-primary" />
                                    <span class="text-[11px] text-destructive" v-if="form.errors.bank_name">{{ form.errors.bank_name }}</span>
                                </div>
                                <div class="grid gap-1.5">
                                    <Label for="bank_account_number" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Nomor Rekening Tujuan</Label>
                                    <Input id="bank_account_number" v-model="form.bank_account_number" placeholder="Contoh: 0123456789" :aria-invalid="!!form.errors.bank_account_number" class="focus-visible:ring-primary" />
                                    <span class="text-[11px] text-destructive" v-if="form.errors.bank_account_number">{{ form.errors.bank_account_number }}</span>
                                </div>
                            </template>

                            <div class="grid gap-1.5">
                                <Label for="attachment" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Bukti Dokumen / Slip Setoran <span class="text-muted-foreground font-normal lowercase">(opsional)</span></Label>
                                <Input id="attachment" type="file" @input="form.attachment = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="focus-visible:ring-primary cursor-pointer text-xs" />
                                <span class="text-[11px] text-muted-foreground">Format yang didukung: PDF, JPG, PNG (Maksimal 5MB)</span>
                                <span class="text-[11px] text-destructive" v-if="form.errors.attachment">{{ form.errors.attachment }}</span>
                                <div v-if="isEditing && receipt.attachment_path" class="mt-2 text-xs">
                                    <a :href="`/storage/${receipt.attachment_path}`" target="_blank" class="text-primary hover:underline inline-flex items-center gap-1 font-medium">
                                        <FileText class="size-3.5" /> Lihat Lampiran Dokumen Saat Ini
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
