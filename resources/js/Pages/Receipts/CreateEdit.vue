<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ref, computed, watch } from 'vue';
import { Trash2, Plus, FileText, ArrowLeft, Save } from '@lucide/vue';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';

const props = defineProps({
    receipt: Object,
    receiptTypes: Array,
    accountCodes: Array,
    fundingSources: Array,
});

const isEditing = !!props.receipt;

const form = useForm({
    document_number: props.receipt?.document_number || '',
    date: props.receipt?.date || new Date().toISOString().split('T')[0],
    receipt_type_id: props.receipt?.receipt_type_id?.toString() || '',
    receipt_sub_type_id: props.receipt?.receipt_sub_type_id?.toString() || '',
    description: props.receipt?.description || '',
    payer_name: props.receipt?.payer_name || '',
    payment_method: props.receipt?.payment_method || 'tunai',
    bank_name: props.receipt?.bank_name || '',
    bank_account_number: props.receipt?.bank_account_number || '',
    attachment: null,
    
    details: props.receipt?.details?.length ? props.receipt.details.map(d => ({
        id: d.id,
        account_code_id: d.account_code_id?.toString(),
        funding_source_id: d.funding_source_id?.toString() || '',
        amount: parseFloat(d.amount)
    })) : [
        { id: null, account_code_id: '', funding_source_id: '', amount: 0 }
    ],
});

const availableSubTypes = computed(() => {
    if (!form.receipt_type_id) return [];
    const parent = props.receiptTypes.find(t => t.id.toString() === form.receipt_type_id);
    return parent?.children || [];
});

watch(() => form.receipt_type_id, () => {
    if (form.receipt_sub_type_id) {
        const isValid = availableSubTypes.value.some(s => s.id.toString() === form.receipt_sub_type_id);
        if (!isValid) form.receipt_sub_type_id = '';
    }
});

const addDetailRow = () => {
    form.details.push({ id: null, account_code_id: '', funding_source_id: '', amount: 0 });
};

const removeDetailRow = (index) => {
    if (form.details.length > 1) {
        form.details.splice(index, 1);
    }
};

const totalAmount = computed(() => {
    return form.details.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const submit = () => {
    if (isEditing) {
        // use POST with _method=PUT to handle file uploads properly in Laravel Inertia
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(`/receipts/${props.receipt.id}`);
    } else {
        form.post('/receipts');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Tanda Bukti Penerimaan' : 'Buat Tanda Bukti Penerimaan'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <Link href="/receipts" class="text-xs text-muted-foreground hover:text-foreground transition-colors">Penerimaan</Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">{{ isEditing ? 'Edit' : 'Buat' }} Tanda Bukti</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        {{ isEditing ? 'Edit' : 'Buat' }} Tanda Bukti Penerimaan
                    </h2>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="$inertia.visit('/receipts')">Batal</Button>
                    <Button @click="submit" :disabled="form.processing">
                        <Save class="size-4 mr-2" />
                        Simpan Draft
                    </Button>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="pb-10">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Left Card: Main Info -->
                <div class="xl:col-span-1 space-y-6">
                    <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-border/80 bg-muted/10">
                            <h3 class="font-semibold text-secondary flex items-center gap-2">
                                <FileText class="size-4 text-primary" />
                                Informasi Utama
                            </h3>
                        </div>
                        <div class="p-5 space-y-4 text-sm">
                            <div class="space-y-2">
                                <Label for="document_number">Nomor TBP / STS <span class="text-muted-foreground font-normal">(Opsional)</span></Label>
                                <Input id="document_number" v-model="form.document_number" placeholder="Contoh: TBP-001/2026" />
                                <span class="text-xs text-red-500" v-if="form.errors.document_number">{{ form.errors.document_number }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="date">Tanggal Transaksi <span class="text-red-500">*</span></Label>
                                <Input id="date" type="date" v-model="form.date" />
                                <span class="text-xs text-red-500" v-if="form.errors.date">{{ form.errors.date }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="receipt_type_id">Jenis Penerimaan <span class="text-red-500">*</span></Label>
                                <Select v-model="form.receipt_type_id">
                                    <SelectTrigger>
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
                                <span class="text-xs text-red-500" v-if="form.errors.receipt_type_id">{{ form.errors.receipt_type_id }}</span>
                            </div>

                            <div class="space-y-2" v-if="availableSubTypes.length > 0">
                                <Label for="receipt_sub_type_id">Sub-Jenis Penerimaan <span class="text-muted-foreground font-normal">(Opsional)</span></Label>
                                <Select v-model="form.receipt_sub_type_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Sub-Jenis (Jika ada)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="">-- Kosongkan --</SelectItem>
                                            <SelectItem v-for="type in availableSubTypes" :key="type.id" :value="type.id.toString()">
                                                {{ type.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <span class="text-xs text-red-500" v-if="form.errors.receipt_sub_type_id">{{ form.errors.receipt_sub_type_id }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="payer_name">Nama Penyetor / Wajib Bayar <span class="text-red-500">*</span></Label>
                                <Input id="payer_name" v-model="form.payer_name" placeholder="Nama instansi atau perorangan" />
                                <span class="text-xs text-red-500" v-if="form.errors.payer_name">{{ form.errors.payer_name }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Uraian Penerimaan <span class="text-red-500">*</span></Label>
                                <Textarea id="description" v-model="form.description" placeholder="Jelaskan rincian penerimaan ini" class="min-h-24" />
                                <span class="text-xs text-red-500" v-if="form.errors.description">{{ form.errors.description }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
                        <div class="px-5 py-4 border-b border-border/80 bg-muted/10">
                            <h3 class="font-semibold text-secondary">Detail Pembayaran & Lampiran</h3>
                        </div>
                        <div class="p-5 space-y-4 text-sm">
                            <div class="space-y-2">
                                <Label for="payment_method">Metode Pembayaran <span class="text-red-500">*</span></Label>
                                <Select v-model="form.payment_method">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Metode" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="tunai">Tunai</SelectItem>
                                            <SelectItem value="transfer">Transfer Bank</SelectItem>
                                            <SelectItem value="giro">Giro</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <span class="text-xs text-red-500" v-if="form.errors.payment_method">{{ form.errors.payment_method }}</span>
                            </div>

                            <template v-if="form.payment_method === 'transfer'">
                                <div class="space-y-2">
                                    <Label for="bank_name">Nama Bank Tujuan</Label>
                                    <Input id="bank_name" v-model="form.bank_name" placeholder="Contoh: Bank Jatim" />
                                    <span class="text-xs text-red-500" v-if="form.errors.bank_name">{{ form.errors.bank_name }}</span>
                                </div>
                                <div class="space-y-2">
                                    <Label for="bank_account_number">Nomor Rekening Tujuan</Label>
                                    <Input id="bank_account_number" v-model="form.bank_account_number" placeholder="Nomor rekening" />
                                    <span class="text-xs text-red-500" v-if="form.errors.bank_account_number">{{ form.errors.bank_account_number }}</span>
                                </div>
                            </template>

                            <div class="space-y-2">
                                <Label for="attachment">Bukti Transfer / Dokumen Pendukung</Label>
                                <Input id="attachment" type="file" @input="form.attachment = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" />
                                <span class="text-xs text-muted-foreground block mt-1">Format: PDF, JPG, PNG (Maks: 5MB)</span>
                                <span class="text-xs text-red-500" v-if="form.errors.attachment">{{ form.errors.attachment }}</span>
                                <div v-if="isEditing && receipt.attachment_path" class="mt-2 text-sm">
                                    <a :href="`/storage/${receipt.attachment_path}`" target="_blank" class="text-primary hover:underline">Lihat Lampiran Saat Ini</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Card: Details -->
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm flex flex-col h-full">
                        <div class="px-5 py-4 border-b border-border/80 bg-muted/10 flex justify-between items-center">
                            <h3 class="font-semibold text-secondary">Rincian Pendapatan</h3>
                            <Button type="button" variant="outline" size="sm" @click="addDetailRow" class="h-8">
                                <Plus class="size-3.5 mr-1.5" /> Tambah Baris
                            </Button>
                        </div>
                        
                        <div class="flex-1 overflow-x-auto">
                            <Table class="min-w-[800px]">
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent">
                                        <TableHead class="w-12 text-center">#</TableHead>
                                        <TableHead class="w-1/2">Kode Rekening Pendapatan</TableHead>
                                        <TableHead class="w-1/4">Sumber Dana (Opsional)</TableHead>
                                        <TableHead class="w-1/4">Nominal (Rp)</TableHead>
                                        <TableHead class="w-16 text-center"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(detail, index) in form.details" :key="index" class="group">
                                        <TableCell class="text-center font-medium text-muted-foreground">{{ index + 1 }}</TableCell>
                                        <TableCell class="align-top">
                                            <Select v-model="form.details[index].account_code_id">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Pilih Rekening" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <div class="max-h-60 overflow-y-auto">
                                                        <SelectGroup>
                                                            <SelectItem v-for="acc in accountCodes" :key="acc.id" :value="acc.id.toString()">
                                                                <span class="font-mono text-xs mr-2">{{ acc.code }}</span>
                                                                {{ acc.name }}
                                                            </SelectItem>
                                                        </SelectGroup>
                                                    </div>
                                                </SelectContent>
                                            </Select>
                                            <span class="text-xs text-red-500 mt-1 block" v-if="form.errors[`details.${index}.account_code_id`]">{{ form.errors[`details.${index}.account_code_id`] }}</span>
                                        </TableCell>
                                        <TableCell class="align-top">
                                            <Select v-model="form.details[index].funding_source_id">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Pilih Sumber" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem v-for="fs in fundingSources" :key="fs.id" :value="fs.id.toString()">
                                                            {{ fs.name }}
                                                        </SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell class="align-top">
                                            <Input type="number" v-model="form.details[index].amount" class="text-right" min="0" step="1" />
                                            <span class="text-xs text-red-500 mt-1 block" v-if="form.errors[`details.${index}.amount`]">{{ form.errors[`details.${index}.amount`] }}</span>
                                        </TableCell>
                                        <TableCell class="align-top text-center">
                                            <Button 
                                                type="button" 
                                                variant="ghost" 
                                                size="icon" 
                                                class="text-destructive hover:bg-destructive/10 hover:text-destructive h-9 w-9" 
                                                @click="removeDetailRow(index)"
                                                :disabled="form.details.length === 1"
                                            >
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        
                        <!-- Total Sum Up -->
                        <div class="mt-auto border-t border-border/80 bg-primary/5 p-6 rounded-b-xl transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-semibold text-secondary/80 dark:text-foreground/80 uppercase tracking-widest">Total Penerimaan</span>
                                <span class="text-4xl font-black text-primary tabular-nums tracking-tight animate-in fade-in zoom-in duration-300" :key="totalAmount">
                                    {{ formatCurrency(totalAmount) }}
                                </span>
                            </div>
                            <span class="text-xs text-red-500 mt-2 block" v-if="form.errors.details">{{ form.errors.details }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
