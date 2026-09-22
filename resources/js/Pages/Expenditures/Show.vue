<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { 
    Download, FileText, CheckCircle, XCircle, Send, ArrowRight, UserCheck, ShieldCheck, 
    Printer, Receipt, ExternalLink, Image as ImageIcon, FileSearch, ArrowLeft, Trash2,
    Calendar, User, Building, Paperclip, CreditCard, Clock, AlertCircle, CheckCircle2,
    FileCheck2, Hash, Layers
} from '@lucide/vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ref, computed } from 'vue';
import { terbilang } from '@/lib/utils';

const props = defineProps({
    expenditure: Object,
    activities: Array,
});

const page = usePage();
const can = (permission) => {
    if (page.props.auth?.roles?.includes('super-admin')) return true;
    return page.props.auth?.permissions?.includes(permission) ?? false;
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const totalAmount = computed(() => {
    return (props.expenditure.details || []).reduce((sum, item) => sum + Number(item.amount || 0), 0);
});

const totalTaxes = computed(() => {
    return (props.expenditure.taxes || []).reduce((sum, item) => sum + Number(item.amount || 0), 0);
});

const netAmount = computed(() => {
    return Math.max(0, totalAmount.value - totalTaxes.value);
});

const totalReceiptsAmount = computed(() => {
    return (props.expenditure.receipts || []).reduce((sum, r) => sum + Number(r.amount || 0), 0);
});

const totalReceiptsTax = computed(() => {
    return (props.expenditure.receipts || []).reduce((sum, r) => sum + Number(r.tax_amount || 0), 0);
});

const getStatusColor = (status) => {
    switch (status) {
        case 'draft': return 'secondary';
        case 'submitted': return 'default';
        case 'authorized': return 'warning';
        case 'disbursed': return 'success';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'draft': return 'Draft SPPD';
        case 'submitted': return 'Diajukan (Menunggu OPD Direktur)';
        case 'authorized': return 'Diotorisasi (Menunggu SPD Kabag)';
        case 'disbursed': return 'Dana Cair (SPD Terbit)';
        case 'rejected': return 'Ditolak';
        default: return status;
    }
};

// Form and dialog logic for status updates
const statusForm = useForm({
    status: '',
    rejection_note: '',
    opd_number: '',
    opd_notes: '',
    spd_number: ''
});

const isStatusDialogOpen = ref(false);
const dialogAction = ref(''); // 'authorize', 'disburse', 'reject', 'submit'

const openStatusDialog = (action) => {
    dialogAction.value = action;
    
    switch (action) {
        case 'submit': statusForm.status = 'submitted'; break;
        case 'authorize': statusForm.status = 'authorized'; break;
        case 'disburse': statusForm.status = 'disbursed'; break;
        case 'reject': statusForm.status = 'rejected'; break;
    }
    
    statusForm.rejection_note = '';
    statusForm.opd_number = props.expenditure.opd_number || '';
    statusForm.opd_notes = props.expenditure.opd_notes || '';
    statusForm.spd_number = props.expenditure.spd_number || '';
    
    isStatusDialogOpen.value = true;
};

const updateStatus = () => {
    statusForm.patch(`/expenditures/${props.expenditure.id}/status`, {
        onSuccess: () => {
            isStatusDialogOpen.value = false;
        }
    });
};

// Delete logic
const isDeleteDialogOpen = ref(false);
const deleteForm = useForm({});
const deleteExpenditure = () => {
    deleteForm.delete(`/expenditures/${props.expenditure.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
        }
    });
};

</script>

<template>
    <Head :title="`Detail SPPD: ${expenditure.document_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Pengeluaran</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <Link href="/expenditures/sppd" class="text-xs text-muted-foreground hover:text-foreground transition-colors">Pengeluaran (SPPD)</Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Detail Dokumen</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                            {{ expenditure.document_number }}
                        </h2>
                        <Badge v-if="expenditure.type === 'UP'" variant="outline" class="bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/30 text-xs font-semibold">
                            Uang Persediaan (UP)
                        </Badge>
                        <Badge v-else-if="expenditure.type === 'GU'" variant="outline" class="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30 text-xs font-semibold">
                            Ganti Uang (GU)
                        </Badge>
                        <Badge :variant="getStatusColor(expenditure.status)" class="text-xs uppercase tracking-wider font-semibold" :class="expenditure.status === 'disbursed' ? 'bg-emerald-600 hover:bg-emerald-700 text-white border-transparent' : ''">
                            {{ getStatusLabel(expenditure.status) }}
                        </Badge>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <Link href="/expenditures/sppd">
                        <Button variant="outline" size="sm" class="h-9">
                            <ArrowLeft class="w-4 h-4 mr-1.5" /> Kembali
                        </Button>
                    </Link>
                    <Link v-if="expenditure.status === 'draft' || expenditure.status === 'rejected'" :href="`/expenditures/${expenditure.id}/edit`">
                        <Button variant="outline" size="sm" class="h-9">Edit Pengajuan</Button>
                    </Link>
                    <Button 
                        v-if="(expenditure.status === 'draft' || expenditure.status === 'rejected') && can('manage sppd')" 
                        variant="destructive" 
                        size="sm" 
                        class="h-9" 
                        @click="isDeleteDialogOpen = true"
                    >
                        <Trash2 class="w-4 h-4 mr-1.5" /> Hapus
                    </Button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.message" class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-2 shadow-sm" role="alert">
                <CheckCircle2 class="w-5 h-5 shrink-0" />
                <span class="text-sm font-medium">{{ $page.props.flash.message }}</span>
            </div>

            <!-- Rejected Banner -->
            <div v-if="expenditure.status === 'rejected'" class="bg-red-500/10 border border-red-500/20 text-red-700 p-4 rounded-xl shadow-sm flex items-start gap-3">
                <XCircle class="w-5 h-5 shrink-0 mt-0.5" />
                <div>
                    <h4 class="font-bold text-sm mb-0.5">Dokumen Ditolak / Perlu Revisi</h4>
                    <p class="text-xs leading-relaxed">Alasan Penolakan: <strong class="font-semibold">{{ expenditure.rejection_note || 'Tidak ada catatan penolakan spesifik.' }}</strong></p>
                </div>
            </div>


            <!-- 2. Metric Cards: Financial Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-card text-card-foreground border rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-muted-foreground text-xs font-medium uppercase tracking-wider mb-2">
                        <span>Total Pengajuan (Bruto)</span>
                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold font-mono text-xs">Rp</div>
                    </div>
                    <div class="text-xl font-bold font-mono text-foreground">{{ formatCurrency(totalAmount) }}</div>
                    <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                        <Layers class="w-3.5 h-3.5" /> {{ expenditure.details?.length || 0 }} Rincian Akun Belanja
                    </p>
                </div>

                <div class="bg-card text-card-foreground border rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-muted-foreground text-xs font-medium uppercase tracking-wider mb-2">
                        <span>Potongan Pajak</span>
                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 flex items-center justify-center font-bold font-mono text-xs">%</div>
                    </div>
                    <div class="text-xl font-bold font-mono text-rose-600">{{ formatCurrency(totalTaxes) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ expenditure.taxes?.length ? `${expenditure.taxes.length} Jenis Pajak Terpotong` : 'Tanpa Potongan Pajak' }}
                    </p>
                </div>

                <div class="bg-card text-card-foreground border rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-muted-foreground text-xs font-medium uppercase tracking-wider mb-2">
                        <span>Total Bersih (Netto)</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-700 flex items-center justify-center font-bold text-xs">
                            <CheckCircle2 class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-xl font-bold font-mono text-emerald-700 dark:text-emerald-400">{{ formatCurrency(netAmount) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">Nominal Yang Dibayarkan</p>
                </div>

                <div class="bg-card text-card-foreground border rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-muted-foreground text-xs font-medium uppercase tracking-wider mb-2">
                        <span>Sifat &amp; Penyaluran</span>
                        <div class="w-8 h-8 rounded-lg bg-muted text-muted-foreground flex items-center justify-center text-xs">
                            <CreditCard class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-sm font-bold text-secondary dark:text-foreground capitalize truncate">
                        {{ expenditure.payment_method.replace(/_/g, ' ') }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1 font-mono">
                        Tipe: {{ expenditure.type }}
                    </p>
                </div>
            </div>

            <!-- 3. Main Grid Layout (2 Columns) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Kartu: Informasi Dokumen & Pejabat Penandatangan -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex items-center gap-2">
                            <UserCheck class="w-4 h-4 text-primary" />
                            Informasi Dokumen &amp; Pejabat Penandatangan
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <div class="space-y-4">
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Nomor SPPD / Tanggal</div>
                                    <div class="font-semibold font-mono text-foreground">{{ expenditure.document_number }}</div>
                                    <div class="text-xs text-muted-foreground">{{ format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) }}</div>
                                </div>
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Jenis Transaksi &amp; Sifat Dana</div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ expenditure.type === 'UP' ? 'Uang Persediaan (UP)' : expenditure.type }}</span>
                                        <Badge v-if="expenditure.type === 'UP'" variant="outline" class="text-[10px] bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-500/30">
                                            Non-Pagu / Uang Muka
                                        </Badge>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Dibuat Oleh</div>
                                    <div class="font-medium text-foreground">{{ expenditure.created_by?.name || 'Sistem' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ expenditure.created_at ? format(new Date(expenditure.created_at), 'dd MMM yyyy HH:mm', { locale: id }) : '-' }}</div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- KPA -->
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Kuasa Pengguna Anggaran (KPA)</div>
                                    <div class="font-medium text-foreground">{{ expenditure.kpa?.name || '-' }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">NIP. {{ expenditure.kpa?.nip || '-' }}</div>
                                </div>
                                <!-- PPTK -->
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Pejabat Teknis Kegiatan (PTK / PPTK)</div>
                                    <div class="font-medium text-foreground">{{ expenditure.ptk?.name || '-' }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">NIP. {{ expenditure.ptk?.nip || '-' }}</div>
                                </div>
                                <!-- Bendahara -->
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Bendahara Pengeluaran BLUD</div>
                                    <div class="font-medium text-foreground">{{ expenditure.treasurer?.name || '-' }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">NIP. {{ expenditure.treasurer?.nip || '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu: Pelaksanaan Kegiatan & Uraian -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex items-center gap-2">
                            <Calendar class="w-4 h-4 text-primary" />
                            Pelaksanaan Kegiatan &amp; Uraian
                        </div>
                        <div class="p-6 space-y-4 text-sm">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Waktu Pelaksanaan</div>
                                    <div class="font-medium font-mono text-foreground">
                                        {{ expenditure.activity_date ? format(new Date(expenditure.activity_date), 'dd MMMM yyyy', { locale: id }) : '-' }}
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Uraian / Tujuan Pembayaran</div>
                                    <div class="font-medium text-foreground leading-relaxed">{{ expenditure.description || '-' }}</div>
                                </div>
                            </div>
                            <div v-if="expenditure.activity_description" class="pt-2 border-t">
                                <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Deskripsi Spesifik Pekerjaan</div>
                                <div class="font-normal text-muted-foreground leading-relaxed text-xs">{{ expenditure.activity_description }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu: Otorisasi OPD & Pencairan SPD -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex items-center gap-2">
                            <ShieldCheck class="w-4 h-4 text-primary" />
                            Status Otorisasi OPD &amp; Pencairan SPD
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <!-- OPD Section -->
                            <div class="p-4 rounded-xl border bg-muted/10 space-y-2">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <span class="font-bold text-xs uppercase text-amber-700 dark:text-amber-400">Surat Otorisasi OPD</span>
                                    <Badge :variant="expenditure.opd_number ? 'default' : 'outline'" class="text-[10px]">
                                        {{ expenditure.opd_number ? 'Diotorisasi' : 'Belum Terbit' }}
                                    </Badge>
                                </div>
                                <div>
                                    <div class="text-[11px] text-muted-foreground">Nomor Surat:</div>
                                    <div class="font-semibold font-mono text-foreground">{{ expenditure.opd_number || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] text-muted-foreground">Tanggal Otorisasi:</div>
                                    <div class="font-medium text-xs">{{ expenditure.opd_date ? format(new Date(expenditure.opd_date), 'dd MMMM yyyy', { locale: id }) : '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] text-muted-foreground">Pejabat Pengotorisasi (Direktur):</div>
                                    <div class="font-medium text-xs text-foreground">{{ expenditure.opd_authorized_by?.name || (expenditure.opd_number ? (expenditure.kpa?.name || 'Direktur RSUD') : '-') }}</div>
                                    <div v-if="expenditure.opd_authorized_by?.nip" class="text-[10px] text-muted-foreground font-mono">NIP. {{ expenditure.opd_authorized_by.nip }}</div>
                                </div>
                                <div v-if="expenditure.opd_notes" class="pt-1 border-t text-xs italic text-muted-foreground">
                                    Catatan: "{{ expenditure.opd_notes }}"
                                </div>
                            </div>

                            <!-- SPD Section -->
                            <div class="p-4 rounded-xl border bg-muted/10 space-y-2">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <span class="font-bold text-xs uppercase text-emerald-700 dark:text-emerald-400">Surat Pencairan SPD</span>
                                    <Badge :variant="expenditure.spd_number ? 'default' : 'outline'" class="text-[10px]" :class="expenditure.spd_number ? 'bg-emerald-600 text-white' : ''">
                                        {{ expenditure.spd_number ? 'Dicairkan' : 'Belum Terbit' }}
                                    </Badge>
                                </div>
                                <div>
                                    <div class="text-[11px] text-muted-foreground">Nomor Surat:</div>
                                    <div class="font-semibold font-mono text-foreground">{{ expenditure.spd_number || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] text-muted-foreground">Tanggal Pencairan:</div>
                                    <div class="font-medium text-xs">{{ expenditure.spd_date ? format(new Date(expenditure.spd_date), 'dd MMMM yyyy', { locale: id }) : '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] text-muted-foreground">Pejabat Pencair (Kabag Keuangan):</div>
                                    <div class="font-medium text-xs text-foreground">{{ expenditure.spd_disbursed_by?.name || (expenditure.spd_number ? 'Kabag Keuangan' : '-') }}</div>
                                    <div v-if="expenditure.spd_disbursed_by?.nip" class="text-[10px] text-muted-foreground font-mono">NIP. {{ expenditure.spd_disbursed_by.nip }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu: Informasi Pembayaran & Penerima (Vendor) -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex justify-between items-center">
                            <span class="flex items-center gap-2">
                                <Building class="w-4 h-4 text-primary" />
                                Informasi Pembayaran &amp; Penerima
                            </span>
                            <Badge variant="outline" class="capitalize font-mono text-xs">{{ expenditure.payment_method.replace(/_/g, ' ') }}</Badge>
                        </div>
                        <div class="p-6 text-sm">
                            <template v-if="expenditure.payment_method === 'rekanan'">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Nama Rekanan / Perusahaan</div>
                                        <div class="font-semibold text-foreground">{{ expenditure.vendor?.name || '-' }}</div>
                                        <div class="text-xs text-muted-foreground">{{ expenditure.vendor?.type || 'Pihak Ketiga' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Pimpinan / Direktur Rekanan</div>
                                        <div class="font-medium text-foreground">{{ expenditure.vendor?.director_name || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">NPWP Rekanan</div>
                                        <div class="font-mono text-xs">{{ expenditure.vendor?.npwp || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Nomor Kontrak / SPK</div>
                                        <div class="font-mono text-xs font-medium">{{ expenditure.contract_number || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Bank &amp; No. Rekening Tujuan</div>
                                        <div class="font-medium">{{ expenditure.bank_name || expenditure.vendor?.bank_name || '-' }}</div>
                                        <div class="font-mono text-xs text-muted-foreground">{{ expenditure.bank_account_number || expenditure.vendor?.bank_account_number || '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Alamat Rekanan</div>
                                        <div class="text-xs text-muted-foreground leading-relaxed">{{ expenditure.vendor?.address || '-' }}</div>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div class="p-4 bg-muted/20 rounded-xl border text-xs text-muted-foreground leading-relaxed flex items-start gap-2.5">
                                    <CreditCard class="w-4 h-4 text-primary shrink-0 mt-0.5" />
                                    <div>
                                        <p v-if="expenditure.type === 'UP'" class="font-medium text-foreground">
                                            Penyaluran Uang Persediaan (UP):
                                        </p>
                                        <p v-else class="font-medium text-foreground">
                                            Penyaluran Non-Rekanan:
                                        </p>
                                        <p class="mt-0.5">
                                            {{ expenditure.type === 'UP' 
                                                ? 'Dana dicairkan langsung dari Rekening Operasional Kas BLUD ke Rekening Kas Bendahara Pengeluaran untuk dikelola sebagai uang muka kerja.' 
                                                : 'Pembayaran disalurkan secara langsung kepada pegawai atau sesuai daftar terlampir.' }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Kartu: Berkas Lampiran Pendukung SPPD -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <Paperclip class="w-4 h-4 text-primary" />
                                Berkas Lampiran Pendukung SPPD
                            </span>
                            <Badge v-if="expenditure.attachment_path" variant="outline" class="text-xs bg-emerald-500/10 text-emerald-700 border-emerald-500/30">
                                Berkas Terlampir
                            </Badge>
                        </div>
                        <div class="p-6">
                            <div v-if="expenditure.attachment_path" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-muted/20 rounded-xl border gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <FileText class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div class="font-semibold text-xs text-foreground break-all">
                                            {{ expenditure.attachment_path.split('/').pop() }}
                                        </div>
                                        <div class="text-[11px] text-muted-foreground mt-0.5">Berkas lampiran fisik surat pengajuan SPPD</div>
                                    </div>
                                </div>
                                <a 
                                    :href="`/storage/${expenditure.attachment_path}`" 
                                    target="_blank" 
                                    class="inline-flex"
                                >
                                    <Button variant="outline" size="sm" class="w-full sm:w-auto text-xs">
                                        <ExternalLink class="w-3.5 h-3.5 mr-1.5" /> Buka / Unduh Berkas
                                    </Button>
                                </a>
                            </div>
                            <div v-else class="text-center py-6 text-xs text-muted-foreground italic">
                                <Paperclip class="w-6 h-6 mx-auto mb-2 opacity-40" />
                                Tidak ada berkas lampiran pendukung fisik yang diunggah untuk SPPD ini.
                            </div>
                        </div>
                    </div>

                    <!-- Khusus GU: Seksi Kwitansi Belanja Kas UP Terlampir (SPJ) -->
                    <div v-if="expenditure.type === 'GU'" class="bg-card text-card-foreground border-2 border-primary/20 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex justify-between items-center">
                            <span class="flex items-center gap-2">
                                <Receipt class="w-5 h-5 text-primary" />
                                Daftar Kwitansi Belanja Kas UP Terlampir (SPJ)
                            </span>
                            <Badge variant="outline" class="text-xs font-semibold bg-primary/10 text-primary border-primary/30">
                                {{ expenditure.receipts?.length || 0 }} Berkas Kwitansi
                            </Badge>
                        </div>

                        <div v-if="expenditure.receipts && expenditure.receipts.length > 0" class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-muted/30">
                                        <TableHead>No. Kwitansi</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Rekening Belanja</TableHead>
                                        <TableHead>Penerima / Toko</TableHead>
                                        <TableHead class="text-right">Bruto (Rp)</TableHead>
                                        <TableHead class="text-right">Pajak (Rp)</TableHead>
                                        <TableHead class="text-center w-24">Bukti / Nota</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="r in expenditure.receipts" :key="r.id" class="text-xs">
                                        <TableCell class="font-mono font-medium">
                                            <a :href="`/expenditure-receipts/${r.id}/print`" target="_blank" class="text-primary hover:underline flex items-center gap-1">
                                                {{ r.receipt_number }}
                                                <ExternalLink class="w-3 h-3 inline opacity-70" />
                                            </a>
                                        </TableCell>
                                        <TableCell class="text-muted-foreground whitespace-nowrap">{{ r.date ? format(new Date(r.date), 'dd MMM yyyy', { locale: id }) : '-' }}</TableCell>
                                        <TableCell>
                                            <div class="font-medium text-foreground">{{ r.account_code?.code }}</div>
                                            <div class="text-[11px] text-muted-foreground line-clamp-1">{{ r.account_code?.name }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="font-medium">{{ r.recipient_name }}</div>
                                            <div class="text-[11px] text-muted-foreground line-clamp-1">{{ r.description }}</div>
                                        </TableCell>
                                        <TableCell class="text-right font-mono font-bold">{{ formatCurrency(r.amount) }}</TableCell>
                                        <TableCell class="text-right font-mono text-muted-foreground">
                                            <div>{{ formatCurrency(r.tax_amount || 0) }}</div>
                                            <div v-if="r.tax_type && r.tax_type !== 'none'" class="text-[10px] text-destructive">
                                                {{ r.tax_type }} <span v-if="r.billing_code">({{ r.billing_code }})</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <a 
                                                v-if="r.attachment_path" 
                                                :href="`/storage/${r.attachment_path}`" 
                                                target="_blank" 
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded bg-muted hover:bg-muted/80 text-[11px] font-medium transition-colors"
                                            >
                                                <ImageIcon class="w-3 h-3 text-primary" />
                                                Nota
                                            </a>
                                            <span v-else class="text-muted-foreground text-[11px]">-</span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                                <tfoot class="bg-muted/30 text-xs border-t">
                                    <tr>
                                        <td colspan="4" class="p-3 text-right font-semibold text-muted-foreground">
                                            Total Belanja Bruto:
                                        </td>
                                        <td class="p-3 text-right font-bold text-foreground font-mono">{{ formatCurrency(totalReceiptsAmount) }}</td>
                                        <td class="p-3 text-right font-bold text-destructive font-mono">{{ formatCurrency(totalReceiptsTax) }}</td>
                                        <td></td>
                                    </tr>
                                    <tr class="border-t">
                                        <td colspan="4" class="p-3 text-right font-bold text-secondary">
                                            Netto Penggantian Kas UP:
                                        </td>
                                        <td colspan="2" class="p-3 text-right font-bold text-primary font-mono text-sm">
                                            {{ formatCurrency(Math.max(0, totalReceiptsAmount - totalReceiptsTax)) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </Table>
                        </div>
                        <div v-else class="p-6 text-center text-muted-foreground text-sm">
                            <Receipt class="w-8 h-8 mx-auto text-muted-foreground/40 mb-2" />
                            <p>Dokumen SPPD-GU ini belum menautkan kuitansi kas UP secara spesifik.</p>
                        </div>
                    </div>

                    <!-- Rincian Anggaran / Rekening Belanja Card -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex justify-between items-center">
                            <span>{{ expenditure.type === 'UP' ? 'Rincian Pencairan Uang Persediaan (Non-Anggaran)' : 'Rincian Penggunaan Anggaran' }}</span>
                            <Badge v-if="expenditure.type === 'UP'" variant="outline" class="text-xs font-normal text-muted-foreground bg-background">
                                Mutasi Kas: Kas BLUD ➔ Kas Bendahara
                            </Badge>
                        </div>
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-transparent hover:bg-transparent">
                                        <TableHead class="w-48">Kode Rekening</TableHead>
                                        <TableHead>Nama Rekening Belanja</TableHead>
                                        <TableHead class="text-right w-44">Nominal</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="detail in expenditure.details" :key="detail.id">
                                        <TableCell class="font-mono text-xs font-semibold">{{ detail.account_code?.code }}</TableCell>
                                        <TableCell class="font-medium text-xs">
                                            {{ detail.account_code?.name }}
                                            <span v-if="expenditure.type === 'UP'" class="ml-2 text-xs text-blue-600 dark:text-blue-400 font-normal">(Kas Bendahara Pengeluaran)</span>
                                        </TableCell>
                                        <TableCell class="text-right font-mono text-xs font-semibold">{{ formatCurrency(detail.amount) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                                <tfoot class="bg-muted/30">
                                    <tr>
                                        <td colspan="2" class="p-4 text-right font-semibold text-muted-foreground">
                                            {{ expenditure.type === 'UP' ? 'Total Nilai Pencairan UP:' : 'Total Pengajuan (Kotor):' }}
                                        </td>
                                        <td class="p-4 text-right font-bold text-primary font-mono text-lg">{{ formatCurrency(totalAmount) }}</td>
                                    </tr>
                                    <tr v-if="totalAmount > 0">
                                        <td colspan="3" class="px-4 py-2.5 bg-muted/20 border-t border-border/50 text-xs text-muted-foreground italic">
                                            Terbilang: <strong class="text-foreground not-italic font-medium">{{ terbilang(totalAmount) }} Rupiah</strong>
                                        </td>
                                    </tr>
                                    <template v-if="expenditure.taxes && expenditure.taxes.length > 0">
                                        <tr>
                                            <td colspan="3" class="px-4 py-2 bg-muted/10 font-semibold text-xs text-secondary uppercase tracking-wider">Potongan Pajak:</td>
                                        </tr>
                                        <tr v-for="tax in expenditure.taxes" :key="tax.id" class="text-sm">
                                            <td class="px-4 py-1 text-right text-muted-foreground">{{ tax.tax_type }} <span v-if="tax.billing_code" class="text-xs">(Billing: {{ tax.billing_code }})</span></td>
                                            <td class="px-4 py-1 text-right"></td>
                                            <td class="px-4 py-1 text-right font-mono text-destructive">-{{ formatCurrency(tax.amount) }}</td>
                                        </tr>
                                        <tr class="border-t">
                                            <td colspan="2" class="p-4 text-right font-semibold text-muted-foreground">Total Bersih (Netto):</td>
                                            <td class="p-4 text-right font-bold text-emerald-700 font-mono text-lg">{{ formatCurrency(netAmount) }}</td>
                                        </tr>
                                        <tr v-if="netAmount > 0">
                                            <td colspan="3" class="px-4 py-2.5 bg-muted/20 border-t border-border/50 text-xs text-muted-foreground italic">
                                                Terbilang Bersih: <strong class="text-foreground not-italic font-medium">{{ terbilang(netAmount) }} Rupiah</strong>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else-if="expenditure.type === 'UP'">
                                        <tr>
                                            <td colspan="3" class="px-4 py-2.5 bg-emerald-500/10 border-t border-emerald-500/20 text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                                                ✓ Bebas Potongan Pajak (PPN/PPh). Pemungutan pajak dilakukan saat uang persediaan dibelanjakan oleh bendahara (SPJ / GU).
                                            </td>
                                        </tr>
                                    </template>
                                </tfoot>
                            </Table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Action & Print Buttons -->
                <div class="space-y-6">
                    <!-- Action Card -->
                    <div class="bg-card text-card-foreground border border-primary/20 rounded-xl shadow-md overflow-hidden relative">
                        <div class="h-1 w-full bg-primary absolute top-0 left-0"></div>
                        
                        <div class="p-6">
                            <h3 class="font-semibold text-lg mb-2">Tindakan Persetujuan</h3>
                            <p class="text-sm text-muted-foreground mb-6">Status saat ini: <strong class="text-foreground">{{ getStatusLabel(expenditure.status) }}</strong></p>
                            
                            <div class="space-y-3">
                                <template v-if="expenditure.status === 'draft'">
                                    <Button v-if="can('manage sppd')" class="w-full" @click="openStatusDialog('submit')">
                                        <Send class="w-4 h-4 mr-2"/> Ajukan SPPD Ke Direktur
                                    </Button>
                                </template>
                                
                                <template v-if="expenditure.status === 'submitted'">
                                    <Button v-if="can('authorize opd')" class="w-full bg-amber-500 hover:bg-amber-600 text-white" @click="openStatusDialog('authorize')">
                                        <ShieldCheck class="w-4 h-4 mr-2"/> Otorisasi OPD (Direktur)
                                    </Button>
                                    <Button v-if="can('authorize opd')" class="w-full" variant="outline" @click="openStatusDialog('reject')">Tolak Pengajuan</Button>
                                </template>

                                <template v-if="expenditure.status === 'authorized'">
                                    <Button v-if="can('disburse spd')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/30 shadow-lg" @click="openStatusDialog('disburse')">
                                        <CheckCircle class="w-4 h-4 mr-2"/> Verifikasi &amp; Cairkan SPD
                                    </Button>
                                    <Button v-if="can('disburse spd')" class="w-full" variant="outline" @click="openStatusDialog('reject')">Tolak Pengajuan</Button>
                                </template>

                                <template v-if="expenditure.status === 'disbursed'">
                                    <div class="bg-emerald-500/10 text-emerald-700 p-4 rounded-xl flex items-center justify-center gap-2 font-semibold text-sm">
                                        <CheckCircle class="w-5 h-5"/> Dana Telah Cair &amp; SPD Terbit
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Print Documents Card (10 Dokumen Kedinasan) -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-secondary mb-4 border-b pb-2 flex items-center gap-2">
                            <Printer class="w-4 h-4 text-primary" /> Cetak Dokumen Resmi
                        </h3>
                        
                        <div class="space-y-2">
                            <!-- 1. SPPD -->
                            <a :href="`/expenditures/${expenditure.id}/print-sppd`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-blue-600" />
                                    1. SPPD (Permintaan Pencairan)
                                </Button>
                            </a>

                            <!-- 2. SPM -->
                            <a :href="`/expenditures/${expenditure.id}/print-spm`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-purple-600" />
                                    2. SPM (Perintah Membayar)
                                </Button>
                            </a>

                            <!-- 3. Ringkasan -->
                            <a :href="`/expenditures/${expenditure.id}/print-ringkasan`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-indigo-600" />
                                    3. Ringkasan Kegiatan
                                </Button>
                            </a>

                            <!-- 4. Lembar Penelitian -->
                            <a :href="`/expenditures/${expenditure.id}/print-lembar-peneliti`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-emerald-600" />
                                    4. Lembar Peneliti Dokumen
                                </Button>
                            </a>

                            <!-- 5. Surat Pengantar -->
                            <a :href="`/expenditures/${expenditure.id}/print-surat-pengantar`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-sky-600" />
                                    5. Surat Pengantar
                                </Button>
                            </a>

                            <!-- 6. Surat Pernyataan -->
                            <a :href="`/expenditures/${expenditure.id}/print-surat-pernyataan`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-rose-600" />
                                    6. Surat Pernyataan (2 Hal)
                                </Button>
                            </a>

                            <!-- 7. Surat Verifikasi -->
                            <a :href="`/expenditures/${expenditure.id}/print-surat-verifikasi`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-teal-600" />
                                    7. Surat Verifikasi Keabsahan
                                </Button>
                            </a>

                            <!-- 8. Kwitansi -->
                            <a :href="`/expenditures/${expenditure.id}/print-kwitansi`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-orange-600" />
                                    8. Lembar Kwitansi
                                </Button>
                            </a>

                            <!-- 9. Surat OPD -->
                            <a v-if="expenditure.status === 'authorized' || expenditure.status === 'disbursed'" :href="`/expenditures/${expenditure.id}/print-opd`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-amber-600" />
                                    9. Surat OPD (Otorisasi Direktur)
                                </Button>
                            </a>
                            <div v-else class="text-[11px] text-muted-foreground italic px-2.5 py-1.5 bg-muted/40 rounded-lg">
                                🔒 Surat OPD terbuka setelah diotorisasi Direktur.
                            </div>

                            <!-- 10. Surat SPD -->
                            <a v-if="expenditure.status === 'disbursed'" :href="`/expenditures/${expenditure.id}/print-spd`" target="_blank" class="block">
                                <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                    <FileText class="w-4 h-4 mr-2 text-emerald-600" />
                                    10. Surat SPD (Pencairan Dana)
                                </Button>
                            </a>
                            <div v-else class="text-[11px] text-muted-foreground italic px-2.5 py-1.5 bg-muted/40 rounded-lg">
                                🔒 Surat SPD terbuka setelah dicairkan Kabag Keuangan.
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Log (Jejak Aktivitas Spatie) -->
                    <div class="bg-card text-card-foreground border rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-secondary mb-6 border-b pb-2 flex items-center gap-2">
                            <Clock class="w-4 h-4 text-primary" /> Jejak Riwayat Dokumen
                        </h3>
                        
                        <div v-if="activities && activities.length > 0" class="space-y-5 relative before:absolute before:inset-0 before:ml-2.5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-muted before:to-transparent">
                            <div v-for="activity in activities" :key="activity.id" class="relative flex gap-3.5">
                                <div class="flex items-center justify-center w-5 h-5 rounded-full border-2 border-background bg-primary shadow shrink-0 z-10 mt-1">
                                </div>
                                <div class="flex-1 p-3 rounded-xl border bg-muted/10 shadow-xs overflow-hidden">
                                    <div class="flex flex-col gap-0.5 mb-1.5 border-b pb-1.5">
                                        <div class="font-semibold text-xs text-foreground break-words">{{ activity.description }}</div>
                                        <div class="text-[10px] font-mono text-muted-foreground">{{ format(new Date(activity.created_at), 'dd MMM yyyy HH:mm', { locale: id }) }}</div>
                                    </div>
                                    <div class="text-[11px] text-muted-foreground">Oleh: <span class="font-medium text-foreground">{{ activity.causer?.name || 'Sistem' }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-muted-foreground text-center py-4 italic">
                            Belum ada riwayat tercatat.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Update Dialog -->
        <Dialog :open="isStatusDialogOpen" @update:open="isStatusDialogOpen = $event">
            <DialogContent class="sm:max-w-[450px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <template v-if="dialogAction === 'reject'"><XCircle class="text-destructive w-5 h-5"/> Konfirmasi Penolakan</template>
                        <template v-if="dialogAction === 'submit'"><Send class="text-primary w-5 h-5"/> Konfirmasi Pengajuan SPPD</template>
                        <template v-if="dialogAction === 'authorize'"><ShieldCheck class="text-amber-500 w-5 h-5"/> Otorisasi Surat OPD (Direktur)</template>
                        <template v-if="dialogAction === 'disburse'"><CheckCircle class="text-emerald-500 w-5 h-5"/> Verifikasi &amp; Pencairan SPD</template>
                    </DialogTitle>
                    <DialogDescription class="pt-2">
                        <template v-if="dialogAction === 'reject'">
                            Silakan masukkan alasan penolakan. Dokumen akan dikembalikan ke status revisi/ditolak.
                        </template>
                        <template v-else-if="dialogAction === 'authorize'">
                            Silakan isi Nomor Surat Otorisasi Direktur (OPD) untuk menyetujui pengajuan pencairan ini.
                        </template>
                        <template v-else-if="dialogAction === 'disburse'">
                            Silakan isi Nomor Surat Pencairan Dana (SPD) untuk menyelesaikan proses pencairan.
                        </template>
                        <template v-else>
                            Apakah Anda yakin ingin mengajukan SPPD ini ke Direktur?
                        </template>
                    </DialogDescription>
                </DialogHeader>
                
                <!-- Form Inputs for Authorize (OPD) -->
                <div v-if="dialogAction === 'authorize'" class="space-y-4 py-2">
                    <div>
                        <Label for="opd_number" class="mb-1 block text-xs font-semibold">Nomor Surat OPD <span class="text-destructive">*</span></Label>
                        <Input id="opd_number" v-model="statusForm.opd_number" placeholder="Contoh: 001/OPD/DIR/2026" class="font-mono text-sm" />
                        <p v-if="statusForm.errors.opd_number" class="text-xs text-destructive mt-1">{{ statusForm.errors.opd_number }}</p>
                    </div>
                    <div>
                        <Label for="opd_notes" class="mb-1 block text-xs font-semibold">Catatan Otorisasi Direktur (Opsional)</Label>
                        <Textarea id="opd_notes" v-model="statusForm.opd_notes" rows="2" placeholder="Catatan persetujuan..."/>
                    </div>
                </div>

                <!-- Form Inputs for Disburse (SPD) -->
                <div v-if="dialogAction === 'disburse'" class="space-y-4 py-2">
                    <div>
                        <Label for="spd_number" class="mb-1 block text-xs font-semibold">Nomor Surat SPD <span class="text-destructive">*</span></Label>
                        <Input id="spd_number" v-model="statusForm.spd_number" placeholder="Contoh: 001/SPD/KABAG/2026" class="font-mono text-sm" />
                        <p v-if="statusForm.errors.spd_number" class="text-xs text-destructive mt-1">{{ statusForm.errors.spd_number }}</p>
                    </div>
                </div>

                <!-- Form Inputs for Reject -->
                <div v-if="dialogAction === 'reject'" class="py-2">
                    <Label for="rejection_note" class="mb-1 block text-xs font-semibold">Catatan Revisi / Penolakan <span class="text-destructive">*</span></Label>
                    <Textarea id="rejection_note" v-model="statusForm.rejection_note" rows="3" placeholder="Masukkan alasan penolakan..."/>
                    <p v-if="statusForm.errors.rejection_note" class="text-xs text-destructive mt-1">{{ statusForm.errors.rejection_note }}</p>
                </div>

                <DialogFooter class="mt-4 flex sm:justify-end gap-2">
                    <Button variant="outline" @click="isStatusDialogOpen = false" :disabled="statusForm.processing">
                        Batal
                    </Button>
                    <Button 
                        :variant="dialogAction === 'reject' ? 'destructive' : 'default'" 
                        :class="dialogAction === 'disburse' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''"
                        @click="updateStatus" 
                        :disabled="statusForm.processing || (dialogAction === 'reject' && !statusForm.rejection_note) || (dialogAction === 'authorize' && !statusForm.opd_number) || (dialogAction === 'disburse' && !statusForm.spd_number)"
                    >
                        {{ statusForm.processing ? 'Memproses...' : 'Konfirmasi' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent class="sm:max-w-[420px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-destructive">
                        <Trash2 class="w-5 h-5" /> Hapus Dokumen SPPD
                    </DialogTitle>
                    <DialogDescription class="pt-2 text-xs leading-relaxed">
                        Apakah Anda yakin ingin menghapus dokumen SPPD <strong>{{ expenditure.document_number }}</strong>? Tindakan ini tidak dapat dibatalkan dan seluruh rincian belanja terkait akan dihapus.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-4 flex sm:justify-end gap-2">
                    <Button variant="outline" size="sm" @click="isDeleteDialogOpen = false" :disabled="deleteForm.processing">
                        Batal
                    </Button>
                    <Button 
                        variant="destructive" 
                        size="sm" 
                        @click="deleteExpenditure" 
                        :disabled="deleteForm.processing"
                    >
                        {{ deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus Dokumen' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
