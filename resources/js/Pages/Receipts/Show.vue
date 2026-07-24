<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { ArrowLeft, Printer, Download, CheckCircle, FileText } from '@lucide/vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { computed, ref } from 'vue';

const props = defineProps({
    receipt: Object,
});

const totalAmount = computed(() => {
    return props.receipt.details.reduce((sum, item) => sum + parseFloat(item.amount), 0);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const printDocument = () => {
    window.print();
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
    <Head :title="`Tanda Bukti Penerimaan - ${receipt.document_number}`" />

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
                                <BreadcrumbPage class="text-xs">Detail TBP</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground flex items-center gap-3">
                        {{ receipt.document_number || 'Tanpa Nomor' }}
                        
                        <span v-if="receipt.status === 'draft'" class="px-2.5 py-0.5 rounded-full text-[10px] font-medium border bg-slate-100 text-slate-700 border-slate-200 uppercase tracking-wider">
                            DRAFT
                        </span>
                        <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-medium border bg-blue-100 text-blue-700 border-blue-200 uppercase tracking-wider flex items-center gap-1">
                            <CheckCircle class="size-3" /> SUBMITTED
                        </span>
                    </h2>
                </div>
                <div class="flex gap-2">
                    <template v-if="receipt.status === 'draft'">
                        <Link :href="`/receipts/${receipt.id}/edit`">
                            <Button variant="outline">Edit</Button>
                        </Link>
                        <Button variant="default" @click="isSubmitDialogOpen = true">Ajukan</Button>
                        <Button variant="destructive" @click="isDeleteDialogOpen = true">Hapus</Button>
                    </template>
                    
                    <a :href="`/receipts/${receipt.id}/print`" target="_blank">
                        <Button variant="outline" class="border-blue-200 text-blue-700 hover:bg-blue-50">
                            <Printer class="size-4 mr-2" />
                            Cetak
                        </Button>
                    </a>
                    <a v-if="receipt.attachment_path" :href="`/storage/${receipt.attachment_path}`" target="_blank">
                        <Button variant="secondary">
                            <Download class="size-4 mr-2" />
                            Lampiran
                        </Button>
                    </a>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Card Informasi Utama -->
                <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary">
                        Informasi Umum
                    </div>
                    <div class="p-6 space-y-4 text-sm">
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Nomor Dokumen</span>
                            <span class="col-span-2 font-medium">{{ receipt.document_number || '-' }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Tanggal</span>
                            <span class="col-span-2 font-medium">{{ formatDate(receipt.date) }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Jenis Penerimaan</span>
                            <span class="col-span-2 font-medium">
                                {{ receipt.type?.name }}
                                <template v-if="receipt.sub_type">
                                    <span class="text-muted-foreground mx-1">&rsaquo;</span> {{ receipt.sub_type.name }}
                                </template>
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Bendahara</span>
                            <span class="col-span-2 font-medium">{{ receipt.treasurer?.name || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card Info Penyetor -->
                <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary">
                        Detail Pembayaran
                    </div>
                    <div class="p-6 space-y-4 text-sm">
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Penyetor</span>
                            <span class="col-span-2 font-medium uppercase">{{ receipt.payer_name }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Metode</span>
                            <span class="col-span-2 font-medium capitalize">
                                {{ receipt.payment_method }}
                                <span v-if="receipt.payment_method === 'transfer' && receipt.bank_name" class="text-muted-foreground text-xs ml-1">
                                    ({{ receipt.bank_name }} - {{ receipt.bank_account_number }})
                                </span>
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Untuk Pembayaran</span>
                            <span class="col-span-2 font-medium">{{ receipt.description }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-muted-foreground">Total Penerimaan</span>
                            <span class="col-span-2 font-bold text-emerald-600 text-lg">{{ formatCurrency(totalAmount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Rincian -->
            <div class="bg-card text-card-foreground border rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-muted/10 font-semibold text-secondary">
                    Rincian Kode Rekening Pendapatan
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 border-b border-border/80">
                            <tr>
                                <th class="text-left font-semibold py-3 px-4 w-16 text-muted-foreground">No</th>
                                <th class="text-left font-semibold py-3 px-4 text-muted-foreground">Kode Rekening</th>
                                <th class="text-left font-semibold py-3 px-4 text-muted-foreground">Uraian Rekening</th>
                                <th class="text-right font-semibold py-3 px-4 text-muted-foreground">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr v-for="(detail, index) in receipt.details" :key="detail.id" class="hover:bg-muted/30 transition-colors">
                                <td class="py-3 px-4 text-muted-foreground">{{ index + 1 }}</td>
                                <td class="py-3 px-4 font-mono font-medium">{{ detail.account_code?.code }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-medium">{{ detail.account_code?.name }}</div>
                                    <div v-if="detail.funding_source" class="text-xs text-muted-foreground mt-1">Sumber Dana: {{ detail.funding_source.name }}</div>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-medium tabular-nums">{{ formatCurrency(detail.amount) }}</td>
                            </tr>
                            <tr v-if="!receipt.details || receipt.details.length === 0">
                                <td colspan="4" class="py-8 text-center text-muted-foreground">Belum ada rincian rekening.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-muted/20 border-t-2 border-border/80">
                            <tr>
                                <td colspan="3" class="py-4 px-4 text-right font-bold text-muted-foreground uppercase tracking-wider">Total Penerimaan</td>
                                <td class="py-4 px-4 text-right font-bold font-mono text-lg tabular-nums text-emerald-600">{{ formatCurrency(totalAmount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Penghapusan</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus dokumen <strong>{{ receipt.document_number || 'ini' }}</strong>?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteItem" :disabled="deleteForm.processing">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="isSubmitDialogOpen" @update:open="isSubmitDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Pengajuan</DialogTitle>
                    <DialogDescription>
                        Anda akan mengajukan dokumen penerimaan <strong>{{ receipt.document_number || 'ini' }}</strong>. Setelah diajukan, dokumen tidak dapat diedit lagi.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isSubmitDialogOpen = false">Batal</Button>
                    <Button variant="default" @click="submitStatus" :disabled="statusForm.processing">Ya, Ajukan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
