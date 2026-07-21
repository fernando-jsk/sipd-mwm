<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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
import { Download, FileText, CheckCircle, XCircle, Send, ArrowRight, UserCheck, ShieldCheck, Printer } from '@lucide/vue';
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
    return props.expenditure.details.reduce((sum, item) => sum + Number(item.amount || 0), 0);
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

</script>

<template>
    <Head :title="`Detail SPPD: ${expenditure.document_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
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
                                <BreadcrumbPage class="text-xs">Detail SPPD</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground flex items-center gap-3">
                        {{ expenditure.document_number }}
                        <Badge :variant="getStatusColor(expenditure.status)" class="text-xs uppercase tracking-wider" :class="expenditure.status === 'disbursed' ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-transparent' : ''">
                            {{ getStatusLabel(expenditure.status) }}
                        </Badge>
                    </h2>
                </div>
                
                <div class="flex gap-2">
                    <Link v-if="expenditure.status === 'draft' || expenditure.status === 'rejected'" :href="`/expenditures/${expenditure.id}/edit`">
                        <Button variant="outline">Edit Pengajuan</Button>
                    </Link>
                </div>
            </div>
        </template>

        <div v-if="$page.props.flash?.message" class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline text-sm font-medium">{{ $page.props.flash.message }}</span>
        </div>
        <div v-if="expenditure.status === 'rejected'" class="mb-6 bg-red-500/10 border border-red-500/20 text-red-700 p-4 rounded-xl relative shadow-sm">
            <h4 class="font-bold flex items-center gap-2 mb-1 text-sm"><XCircle class="w-4 h-4"/> Dokumen Ditolak</h4>
            <p class="text-sm">Alasan Penolakan: <strong>{{ expenditure.rejection_note }}</strong></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Umum Card -->
                <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary">
                        Informasi Dokumen &amp; Kegiatan
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div class="space-y-3">
                            <div>
                                <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">No. SPPD / Tanggal</div>
                                <div class="font-semibold font-mono">{{ expenditure.document_number }}</div>
                                <div class="text-xs text-muted-foreground">{{ format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) }}</div>
                            </div>
                            <div>
                                <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">No. Surat OPD (Direktur)</div>
                                <div v-if="expenditure.opd_number" class="font-semibold font-mono text-amber-700">{{ expenditure.opd_number }}</div>
                                <div v-else class="text-xs text-muted-foreground italic">Belum diotorisasi OPD</div>
                                <div v-if="expenditure.opd_date" class="text-xs text-muted-foreground">{{ format(new Date(expenditure.opd_date), 'dd MMMM yyyy', { locale: id }) }}</div>
                            </div>
                            <div>
                                <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">No. Surat SPD (Kabag)</div>
                                <div v-if="expenditure.spd_number" class="font-semibold font-mono text-emerald-700">{{ expenditure.spd_number }}</div>
                                <div v-else class="text-xs text-muted-foreground italic">Belum dicairkan SPD</div>
                                <div v-if="expenditure.spd_date" class="text-xs text-muted-foreground">{{ format(new Date(expenditure.spd_date), 'dd MMMM yyyy', { locale: id }) }}</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Jenis &amp; Metode Pembayaran</div>
                                <div class="font-medium capitalize">{{ expenditure.type }} - {{ expenditure.payment_method.replace('_', ' ') }}</div>
                            </div>
                            <div>
                                <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Bendahara Pengeluaran</div>
                                <div class="font-medium">{{ expenditure.treasurer?.name || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-muted-foreground text-xs uppercase tracking-wider mb-1">Uraian Pembayaran</div>
                                <div class="font-medium">{{ expenditure.description }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Pembayaran Card -->
                <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary flex justify-between items-center">
                        Informasi Pembayaran &amp; Penerima
                        <Badge variant="outline" class="capitalize">{{ expenditure.payment_method.replace('_', ' ') }}</Badge>
                    </div>
                    <div class="p-6 text-sm">
                        <template v-if="expenditure.payment_method === 'rekanan'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase mb-1">Nama Vendor</div>
                                    <div class="font-medium">{{ expenditure.vendor?.name || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase mb-1">No Kontrak</div>
                                    <div class="font-medium font-mono">{{ expenditure.contract_number || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase mb-1">Bank Penerima</div>
                                    <div class="font-medium">{{ expenditure.bank_name || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted-foreground text-xs uppercase mb-1">No Rekening</div>
                                    <div class="font-medium font-mono">{{ expenditure.bank_account_number || '-' }}</div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <p class="text-muted-foreground italic">Detail pembayaran disesuaikan dengan daftar terlampir atau dibayar langsung kepada pegawai terkait.</p>
                        </template>
                    </div>
                </div>

                <!-- Rincian Anggaran -->
                <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary">
                        Rincian Penggunaan Anggaran
                    </div>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-transparent hover:bg-transparent">
                                    <TableHead>Kode Rekening</TableHead>
                                    <TableHead>Nama Rekening</TableHead>
                                    <TableHead class="text-right">Nominal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="detail in expenditure.details" :key="detail.id">
                                    <TableCell class="font-mono text-sm">{{ detail.account_code?.code }}</TableCell>
                                    <TableCell class="font-medium">{{ detail.account_code?.name }}</TableCell>
                                    <TableCell class="text-right font-mono">{{ formatCurrency(detail.amount) }}</TableCell>
                                </TableRow>
                            </TableBody>
                            <tfoot class="bg-muted/30">
                                <tr>
                                    <td colspan="2" class="p-4 text-right font-semibold text-muted-foreground">Total Pengajuan (Kotor):</td>
                                    <td class="p-4 text-right font-bold text-primary font-mono text-lg">{{ formatCurrency(totalAmount) }}</td>
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
                                        <td class="p-4 text-right font-bold text-emerald-600 font-mono text-lg">{{ formatCurrency(totalAmount - expenditure.taxes.reduce((sum, item) => sum + Number(item.amount), 0)) }}</td>
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
                                <div class="bg-emerald-500/10 text-emerald-700 p-4 rounded-lg flex items-center justify-center gap-2 font-semibold text-sm">
                                    <CheckCircle class="w-5 h-5"/> Dana Telah Cair &amp; SPD Terbit
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Print Documents Card -->
                <div class="bg-card text-card-foreground border rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-secondary mb-4 border-b pb-2 flex items-center gap-2">
                        <Printer class="w-4 h-4 text-primary" /> Cetak Dokumen Resmi
                    </h3>
                    
                    <div class="space-y-2.5">
                        <!-- 1. Cetak SPPD (Selalu Aktif) -->
                        <a :href="`/expenditures/${expenditure.id}/print-sppd`" target="_blank" class="block">
                            <Button variant="outline" class="w-full justify-start text-xs font-normal">
                                <FileText class="w-4 h-4 mr-2 text-blue-600" />
                                Cetak SPPD (Permintaan Pencairan)
                            </Button>
                        </a>

                        <!-- 2. Cetak OPD (Aktif jika sudah diotorisasi OPD) -->
                        <a v-if="expenditure.status === 'authorized' || expenditure.status === 'disbursed'" :href="`/expenditures/${expenditure.id}/print-opd`" target="_blank" class="block">
                            <Button variant="outline" class="w-full justify-start text-xs font-semibold text-amber-700 border-amber-300 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/20">
                                <Printer class="w-4 h-4 mr-2 text-amber-600" />
                                Cetak Surat OPD (Otorisasi Direktur)
                            </Button>
                        </a>
                        <div v-else class="text-[11px] text-muted-foreground italic px-2 py-1 bg-muted/40 rounded">
                            🔒 Cetak OPD terbuka setelah diotorisasi Direktur.
                        </div>

                        <!-- 3. Cetak SPD (Aktif jika sudah dicairkan SPD) -->
                        <a v-if="expenditure.status === 'disbursed'" :href="`/expenditures/${expenditure.id}/print-spd`" target="_blank" class="block">
                            <Button variant="outline" class="w-full justify-start text-xs font-semibold text-emerald-700 border-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/20">
                                <CheckCircle class="w-4 h-4 mr-2 text-emerald-600" />
                                Cetak Surat SPD (Pencairan Dana)
                            </Button>
                        </a>
                        <div v-else class="text-[11px] text-muted-foreground italic px-2 py-1 bg-muted/40 rounded">
                            🔒 Cetak SPD terbuka setelah dicairkan Kabag Keuangan.
                        </div>
                    </div>
                </div>

                <!-- Timeline Log -->
                <div class="bg-card text-card-foreground border rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-secondary mb-6 border-b pb-2">Jejak Aktivitas</h3>
                    
                    <div class="space-y-6 relative before:absolute before:inset-0 before:ml-2.5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-muted before:to-transparent">
                        <div v-for="activity in activities" :key="activity.id" class="relative flex gap-4">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full border-2 border-background bg-primary shadow shrink-0 z-10 mt-1">
                            </div>
                            <div class="flex-1 p-3 rounded-xl border bg-card shadow-sm overflow-hidden">
                                <div class="flex flex-col gap-1 mb-2 border-b pb-2">
                                    <div class="font-semibold text-sm text-foreground break-words">{{ activity.description }}</div>
                                    <div class="text-[10px] font-mono text-muted-foreground">{{ format(new Date(activity.created_at), 'dd MMM yyyy HH:mm') }}</div>
                                </div>
                                <div class="text-xs text-muted-foreground">Oleh: <span class="font-medium text-foreground">{{ activity.causer?.name || 'Sistem' }}</span></div>
                            </div>
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
                            Silakan masukkan alasan penolakan. Dokumen akan dikembalikan ke status revisi/rejection.
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
                        <Label for="opd_number" class="mb-1 block">Nomor Surat OPD <span class="text-destructive">*</span></Label>
                        <Input id="opd_number" v-model="statusForm.opd_number" placeholder="Contoh: 001/OPD/DIR/2026" class="font-mono text-sm" />
                        <p v-if="statusForm.errors.opd_number" class="text-xs text-destructive mt-1">{{ statusForm.errors.opd_number }}</p>
                    </div>
                    <div>
                        <Label for="opd_notes" class="mb-1 block">Catatan Otorisasi Direktur (Opsional)</Label>
                        <Textarea id="opd_notes" v-model="statusForm.opd_notes" rows="2" placeholder="Catatan persetujuan..."/>
                    </div>
                </div>

                <!-- Form Inputs for Disburse (SPD) -->
                <div v-if="dialogAction === 'disburse'" class="space-y-4 py-2">
                    <div>
                        <Label for="spd_number" class="mb-1 block">Nomor Surat SPD <span class="text-destructive">*</span></Label>
                        <Input id="spd_number" v-model="statusForm.spd_number" placeholder="Contoh: 001/SPD/KABAG/2026" class="font-mono text-sm" />
                        <p v-if="statusForm.errors.spd_number" class="text-xs text-destructive mt-1">{{ statusForm.errors.spd_number }}</p>
                    </div>
                </div>

                <!-- Form Inputs for Reject -->
                <div v-if="dialogAction === 'reject'" class="py-2">
                    <Label for="rejection_note" class="mb-1 block">Catatan Revisi / Penolakan <span class="text-destructive">*</span></Label>
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
    </AuthenticatedLayout>
</template>
