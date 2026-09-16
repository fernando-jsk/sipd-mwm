<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    Printer,
    Download,
    CheckCircle2,
    Clock,
    FileText,
    CreditCard,
    Coins,
    Pencil,
    Send,
    Trash2,
    BookOpen,
    ExternalLink,
    AlertTriangle,
    Paperclip,
    UserCheck,
    Building
} from 'lucide-vue-next';

const props = defineProps({
    receipt: Object,
});

const totalAmount = computed(() => {
    const total = props.receipt.details?.reduce((sum, item) => sum + (Number(item.amount) || 0), 0) || 0;
    return Math.round((total + Number.EPSILON) * 100) / 100;
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

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const deleteForm = useForm({});
const isDeleteDialogOpen = ref(false);

const deleteItem = () => {
    deleteForm.delete(`/receipts/${props.receipt.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
        }
    });
};

const statusForm = useForm({
    status: 'submitted'
});
const isSubmitDialogOpen = ref(false);

const submitStatus = () => {
    statusForm.patch(`/receipts/${props.receipt.id}/status`, {
        onSuccess: () => {
            isSubmitDialogOpen.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Rekap Penerimaan - ${receipt.document_number || 'Detail'}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 w-full">
                <!-- Breadcrumb & Document Title -->
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <Link href="/receipts" class="text-xs text-muted-foreground hover:text-foreground transition-colors">Penerimaan</Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Detail Tanda Bukti</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                            {{ receipt.document_number || 'Tanpa Nomor Dokumen' }}
                        </h2>

                        <!-- Status Badge -->
                        <span
                            v-if="receipt.status === 'draft'"
                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20 flex items-center gap-1.5 uppercase tracking-wider"
                        >
                            <Clock class="size-3.5" /> DRAFT
                        </span>
                        <span
                            v-else
                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20 flex items-center gap-1.5 uppercase tracking-wider"
                        >
                            <CheckCircle2 class="size-3.5" /> SUBMITTED
                        </span>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" as-child>
                        <Link href="/receipts">
                            <ArrowLeft class="size-4 mr-1.5" /> Kembali
                        </Link>
                    </Button>

                    <template v-if="receipt.status === 'draft'">
                        <Button variant="outline" as-child>
                            <Link :href="`/receipts/${receipt.id}/edit`">
                                <Pencil class="size-4 mr-1.5" /> Edit
                            </Link>
                        </Button>
                        <Button variant="default" @click="isSubmitDialogOpen = true">
                            <Send class="size-4 mr-1.5" /> Ajukan Dokumen
                        </Button>
                        <Button variant="destructive" @click="isDeleteDialogOpen = true">
                            <Trash2 class="size-4 mr-1.5" /> Hapus
                        </Button>
                    </template>
                    
                    <Button variant="outline" as-child>
                        <a :href="`/receipts/${receipt.id}/print`" target="_blank">
                            <Printer class="size-4 mr-1.5" /> Cetak TBP
                        </a>
                    </Button>

                    <Button v-if="receipt.attachment_path" variant="secondary" as-child>
                        <a :href="`/storage/${receipt.attachment_path}`" target="_blank">
                            <Download class="size-4 mr-1.5" /> Unduh Lampiran
                        </a>
                    </Button>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- Hero Stat Card: Nominal & Quick Overview -->
            <Card class="overflow-hidden border-primary/25 bg-gradient-to-br from-card via-card to-primary/[0.03] shadow-xs">
                <CardContent class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                <Coins class="size-4 text-primary" />
                                <span>Total Nominal Penerimaan</span>
                            </div>
                            <div class="text-3xl sm:text-4xl font-black text-primary font-mono tabular-nums tracking-tight">
                                {{ formatCurrency(totalAmount) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Disetor pada <span class="font-medium text-foreground">{{ formatDate(receipt.date) }}</span> melalui metode
                                <span class="font-semibold text-foreground uppercase">{{ receipt.payment_method }}</span>
                            </p>
                        </div>

                        <!-- Quick Badges & Attributes -->
                        <div class="flex flex-wrap items-center gap-2 md:self-center">
                            <div class="px-3 py-1.5 rounded-lg bg-muted/60 border border-border/80 text-xs">
                                <span class="text-muted-foreground block text-[10px] uppercase font-semibold">Jenis</span>
                                <span class="font-semibold text-foreground">{{ receipt.type?.name }}</span>
                                <span v-if="receipt.sub_type" class="text-muted-foreground ml-1">/ {{ receipt.sub_type.name }}</span>
                            </div>

                            <div v-if="receipt.payment_method === 'transfer' && receipt.bank_name" class="px-3 py-1.5 rounded-lg bg-muted/60 border border-border/80 text-xs">
                                <span class="text-muted-foreground block text-[10px] uppercase font-semibold">Bank Tujuan</span>
                                <span class="font-semibold text-foreground font-mono">{{ receipt.bank_name }} - {{ receipt.bank_account_number }}</span>
                            </div>

                            <div class="px-3 py-1.5 rounded-lg bg-muted/60 border border-border/80 text-xs">
                                <span class="text-muted-foreground block text-[10px] uppercase font-semibold">Penyetor</span>
                                <span class="font-semibold text-foreground uppercase">{{ receipt.payer_name }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Accounting Traceability Banner -->
            <div v-if="receipt.journal" class="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                        <BookOpen class="size-5" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Jurnal Otomatis Terbit</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium">Posted</span>
                        </div>
                        <p class="text-sm font-semibold font-mono text-foreground">
                            {{ receipt.journal.reference_no }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Dokumen ini telah dibukukan otomatis ke dalam Jurnal Umum per tanggal {{ formatDate(receipt.journal.date) }}.
                        </p>
                    </div>
                </div>
                <Button variant="outline" size="sm" as-child class="shrink-0 bg-background/80 hover:bg-background">
                    <Link :href="`/journals?search=${receipt.journal.reference_no}`">
                        <span>Buka Buku Jurnal</span>
                        <ExternalLink class="size-3.5 ml-1.5" />
                    </Link>
                </Button>
            </div>

            <div v-else-if="receipt.status === 'draft'" class="p-4 rounded-xl border border-amber-500/20 bg-amber-500/5 flex items-start gap-3">
                <div class="p-2 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 shrink-0">
                    <Clock class="size-5" />
                </div>
                <div>
                    <span class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Status Draft</span>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Dokumen tanda bukti ini belum diajukan. Saat Anda mengklik <strong>"Ajukan Dokumen"</strong>, sistem akan secara otomatis menerbitkan Jurnal Umum pendapatan dan membukukannya ke kode rekening terkait.
                    </p>
                </div>
            </div>

            <!-- Detail Grid: 2 Kolom -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Card 1: Informasi Umum & Dokumen -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <FileText class="size-4 text-primary" />
                            <CardTitle>Informasi Transaksi</CardTitle>
                        </div>
                        <CardDescription>Rincian identitas dan klasifikasi penerimaan pendapatan</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3.5 text-sm">
                        <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                            <span class="text-muted-foreground text-xs">Nomor Dokumen / TBP</span>
                            <span class="font-medium font-mono text-foreground">{{ receipt.document_number || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                            <span class="text-muted-foreground text-xs">Tanggal Transaksi</span>
                            <span class="font-medium text-foreground">{{ formatDate(receipt.date) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                            <span class="text-muted-foreground text-xs">Jenis Penerimaan</span>
                            <span class="font-semibold text-foreground text-right">
                                {{ receipt.type?.name }}
                                <span v-if="receipt.sub_type" class="text-muted-foreground font-normal block sm:inline sm:ml-1">
                                    &rsaquo; {{ receipt.sub_type.name }}
                                </span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                            <span class="text-muted-foreground text-xs">Bendahara Penerimaan</span>
                            <span class="font-medium text-foreground flex items-center gap-1.5">
                                <UserCheck class="size-3.5 text-primary" />
                                {{ receipt.treasurer?.name || '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-muted-foreground text-xs">Diinput Oleh</span>
                            <span class="font-medium text-muted-foreground">{{ receipt.creator?.name || 'Sistem' }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 2: Detail Pembayaran & Penyetor -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <CreditCard class="size-4 text-primary" />
                            <CardTitle>Detail Penyetor & Kanal</CardTitle>
                        </div>
                        <CardDescription>Informasi penyetor dan rekening penyetoran</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3.5 text-sm">
                        <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                            <span class="text-muted-foreground text-xs">Nama Penyetor / Wajib Bayar</span>
                            <span class="font-bold text-foreground uppercase text-right">{{ receipt.payer_name }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                            <span class="text-muted-foreground text-xs">Metode Pembayaran</span>
                            <span class="font-semibold text-foreground capitalize">{{ receipt.payment_method }}</span>
                        </div>
                        <template v-if="receipt.payment_method === 'transfer'">
                            <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                                <span class="text-muted-foreground text-xs">Bank Tujuan</span>
                                <span class="font-medium text-foreground">{{ receipt.bank_name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-1.5 border-b border-border/50">
                                <span class="text-muted-foreground text-xs">No. Rekening Tujuan</span>
                                <span class="font-medium font-mono text-foreground">{{ receipt.bank_account_number || '-' }}</span>
                            </div>
                        </template>
                        <div class="py-1.5 border-b border-border/50">
                            <span class="text-muted-foreground text-xs block mb-1">Uraian Penerimaan:</span>
                            <p class="font-medium text-foreground text-xs leading-relaxed bg-muted/30 p-2.5 rounded-lg">
                                {{ receipt.description }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-muted-foreground text-xs">Berkas Lampiran</span>
                            <div v-if="receipt.attachment_path">
                                <a :href="`/storage/${receipt.attachment_path}`" target="_blank" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1">
                                    <Paperclip class="size-3.5" /> Buka Lampiran
                                </a>
                            </div>
                            <span v-else class="text-xs text-muted-foreground">Tidak ada berkas</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabel Rincian Kode Rekening Pendapatan (Shadcn Table) -->
            <Card class="p-0 overflow-hidden shadow-xs">
                <CardHeader class="p-5 border-b border-border/80 bg-muted/10">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <CardTitle class="text-base font-semibold text-secondary dark:text-foreground">
                                Rincian Kode Rekening Pendapatan
                            </CardTitle>
                            <CardDescription class="text-xs mt-0.5">
                                Pemetaan akun rekening yang menjadi dasar pencatatan Laporan Realisasi Anggaran (LRA) dan Laporan Operasional (LO)
                            </CardDescription>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary/10 text-primary self-start sm:self-auto">
                            {{ receipt.details?.length || 0 }} Rincian
                        </span>
                    </div>
                </CardHeader>

                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted/40">
                            <TableRow>
                                <TableHead class="w-12 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground">#</TableHead>
                                <TableHead class="w-48 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Kode Rekening</TableHead>
                                <TableHead class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Uraian Rekening Pendapatan</TableHead>
                                <TableHead class="w-48 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Sumber Dana</TableHead>
                                <TableHead class="w-48 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Nominal (Rp)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(detail, index) in receipt.details" :key="detail.id" class="hover:bg-muted/30 transition-colors">
                                <TableCell class="text-center font-medium text-muted-foreground text-xs">{{ index + 1 }}</TableCell>
                                <TableCell>
                                    <span class="font-mono text-xs px-2.5 py-1 rounded-md bg-muted font-bold text-foreground border border-border/70 inline-block">
                                        {{ detail.account_code?.code || '-' }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="font-semibold text-sm text-foreground">
                                        {{ detail.account_code?.name || 'Tanpa Nama Rekening' }}
                                    </div>
                                    <span class="text-[11px] text-muted-foreground">
                                        Terekam otomatis dari jenis {{ receipt.type?.name }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span v-if="detail.funding_source" class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20 inline-block">
                                        {{ detail.funding_source.name }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </TableCell>
                                <TableCell class="text-right font-mono font-bold text-base tabular-nums text-foreground">
                                    {{ formatCurrency(detail.amount) }}
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="!receipt.details || receipt.details.length === 0">
                                <TableCell colspan="5" class="py-8 text-center text-muted-foreground text-sm">
                                    Belum ada rincian rekening tercatat pada dokumen ini.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                        <TableFooter class="bg-muted/30 border-t-2 border-border/80">
                            <TableRow>
                                <TableCell colspan="4" class="text-right font-bold text-xs uppercase tracking-wider text-muted-foreground py-4">
                                    Total Penerimaan
                                </TableCell>
                                <TableCell class="text-right font-black font-mono text-lg tabular-nums text-primary py-4">
                                    {{ formatCurrency(totalAmount) }}
                                </TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                </div>
            </Card>
        </div>

        <!-- Dialog Konfirmasi Hapus -->
        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full bg-destructive/10 text-destructive flex items-center justify-center shrink-0">
                            <AlertTriangle class="size-5" />
                        </div>
                        <div>
                            <DialogTitle>Hapus Rekap Penerimaan?</DialogTitle>
                            <DialogDescription class="mt-1">
                                Dokumen <strong>{{ receipt.document_number || 'ini' }}</strong> dengan nominal <strong>{{ formatCurrency(totalAmount) }}</strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>
                <DialogFooter class="mt-4">
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteItem" :disabled="deleteForm.processing">
                        Ya, Hapus Dokumen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Dialog Konfirmasi Pengajuan -->
        <Dialog :open="isSubmitDialogOpen" @update:open="isSubmitDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <Send class="size-5" />
                        </div>
                        <div>
                            <DialogTitle>Ajukan Rekap Penerimaan?</DialogTitle>
                            <DialogDescription class="mt-1">
                                Anda akan mengajukan dokumen <strong>{{ receipt.document_number || 'ini' }}</strong>. Setelah diajukan:
                                <ul class="list-disc pl-5 mt-2 space-y-1 text-xs text-muted-foreground">
                                    <li>Status berubah menjadi <strong>Submitted</strong> (Terkunci dari pengeditan).</li>
                                    <li>Sistem secara otomatis menerbitkan <strong>Jurnal Umum</strong> pendapatan BLUD.</li>
                                </ul>
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>
                <DialogFooter class="mt-4">
                    <Button variant="outline" @click="isSubmitDialogOpen = false">Batal</Button>
                    <Button variant="default" @click="submitStatus" :disabled="statusForm.processing">
                        Ya, Ajukan Sekarang
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
