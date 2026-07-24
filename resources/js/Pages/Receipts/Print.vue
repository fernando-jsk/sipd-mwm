<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { ArrowLeft, Printer, Download, CheckCircle, FileText } from '@lucide/vue';
import { computed } from 'vue';

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
</script>

<template>
    <Head :title="`Tanda Bukti Penerimaan - ${receipt.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0">
        <!-- Floating Toolbar -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="$window.history.back()">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <div class="flex gap-2">
                <Button size="sm" @click="printDocument" class="bg-blue-600 text-white hover:bg-blue-700">
                    <Printer class="w-4 h-4 mr-2" /> Cetak TBP
                </Button>
                <a v-if="receipt.attachment_path" :href="`/storage/${receipt.attachment_path}`" target="_blank">
                    <Button variant="secondary" size="sm">
                        <Download class="size-4 mr-2" /> Lampiran
                    </Button>
                </a>
            </div>
        </div>

        <div class="max-w-4xl mx-auto py-8 print:py-0 print:max-w-none">
            <!-- TBP Print View -->
            <div class="bg-white text-slate-900 shadow-sm border border-border/50 print:border-none print:shadow-none p-10 sm:p-14 min-h-[800px] relative">
                
                <!-- Watermark -->
                <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none print:opacity-[0.05]">
                    <img src="/images/logo-mwm.png" alt="Watermark" class="w-1/2 max-w-md grayscale" />
                </div>

                <!-- Header -->
                <div class="flex border-b-2 border-slate-800 pb-6 mb-8">
                    <div class="w-24 shrink-0 flex items-center justify-center">
                        <img src="/images/logo-mwm.png" alt="Logo Pemda" class="w-16 h-auto" />
                    </div>
                    <div class="flex-1 text-center flex flex-col justify-center px-4">
                        <h1 class="text-xl font-bold uppercase tracking-wider">PEMERINTAH DAERAH</h1>
                        <h2 class="text-2xl font-black uppercase tracking-widest mt-1 text-primary">RSUD BLUD</h2>
                        <p class="text-sm mt-2 text-slate-600">Alamat: Jl. Contoh Pemerintahan No. 123, Kota Pusat</p>
                    </div>
                    <div class="w-24 shrink-0"></div> <!-- Spacer for center alignment -->
                </div>

                <!-- Title -->
                <div class="text-center mb-10">
                    <h3 class="text-xl font-bold uppercase decoration-slate-800 underline underline-offset-4 mb-2">TANDA BUKTI PENERIMAAN / STS</h3>
                    <p class="text-sm font-medium">Nomor: {{ receipt.document_number }}</p>
                </div>

                <!-- Body / Info -->
                <div class="space-y-6 mb-10 text-sm">
                    <table class="w-full">
                        <tbody>
                            <tr>
                                <td class="py-1.5 w-48 align-top">Telah terima dari</td>
                                <td class="py-1.5 w-4 align-top">:</td>
                                <td class="py-1.5 align-top font-bold uppercase">{{ receipt.payer_name }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 align-top">Uang Sebesar</td>
                                <td class="py-1.5 align-top">:</td>
                                <td class="py-1.5 align-top">
                                    <div class="bg-slate-100 px-3 py-1.5 inline-block font-mono font-bold text-lg border border-slate-200">
                                        {{ formatCurrency(totalAmount) }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1.5 align-top">Untuk Pembayaran</td>
                                <td class="py-1.5 align-top">:</td>
                                <td class="py-1.5 align-top leading-relaxed">{{ receipt.description }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 align-top">Metode Pembayaran</td>
                                <td class="py-1.5 align-top">:</td>
                                <td class="py-1.5 align-top capitalize">
                                    {{ receipt.payment_method }}
                                    <span v-if="receipt.payment_method === 'transfer' && receipt.bank_name">
                                        ({{ receipt.bank_name }} - {{ receipt.bank_account_number }})
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1.5 align-top">Jenis Penerimaan</td>
                                <td class="py-1.5 align-top">:</td>
                                <td class="py-1.5 align-top">
                                    {{ receipt.type?.name }}
                                    <template v-if="receipt.sub_type">
                                        &rsaquo; {{ receipt.sub_type.name }}
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Rincian Table -->
                <div class="mb-12">
                    <h4 class="font-bold text-sm mb-3">Rincian Kode Rekening:</h4>
                    <table class="w-full border-collapse border border-slate-800 text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-800 py-2 px-3 text-center w-12">No</th>
                                <th class="border border-slate-800 py-2 px-3 text-left">Kode Rekening</th>
                                <th class="border border-slate-800 py-2 px-3 text-left">Uraian Rekening</th>
                                <th class="border border-slate-800 py-2 px-3 text-right">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(detail, index) in receipt.details" :key="detail.id">
                                <td class="border border-slate-800 py-2 px-3 text-center">{{ index + 1 }}</td>
                                <td class="border border-slate-800 py-2 px-3 font-mono">{{ detail.account_code?.code }}</td>
                                <td class="border border-slate-800 py-2 px-3">
                                    {{ detail.account_code?.name }}
                                    <div v-if="detail.funding_source" class="text-xs text-slate-500 mt-0.5">Sumber Dana: {{ detail.funding_source.name }}</div>
                                </td>
                                <td class="border border-slate-800 py-2 px-3 text-right tabular-nums">{{ formatCurrency(detail.amount).replace('Rp', '').trim() }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-slate-50">
                                <td colspan="3" class="border border-slate-800 py-2 px-3 text-right">TOTAL PENERIMAAN</td>
                                <td class="border border-slate-800 py-2 px-3 text-right tabular-nums">{{ formatCurrency(totalAmount).replace('Rp', '').trim() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Signatures -->
                <div class="flex justify-between items-end mt-16 pt-8 break-inside-avoid">
                    <div class="text-center w-64">
                        <p class="mb-16">Penyetor / Wajib Bayar</p>
                        <p class="font-bold underline">{{ receipt.payer_name }}</p>
                    </div>
                    
                    <div class="text-center w-64">
                        <p class="mb-1">...................., {{ formatDate(receipt.date) }}</p>
                        <p class="mb-16">Bendahara Penerimaan</p>
                        <p class="font-bold underline">{{ receipt.treasurer?.name || '____________________' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    @media print {
        @page { margin: 0; size: A4 portrait; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white !important; }
        .print\:hidden { display: none !important; }
        .print\:border-none { border: none !important; }
        .print\:shadow-none { box-shadow: none !important; }
        .print\:p-0 { padding: 0 !important; }
        .print\:m-0 { margin: 0 !important; }
        .print\:bg-white { background-color: white !important; }
        .print\:max-w-none { max-width: none !important; }
        .print\:py-0 { padding-top: 0 !important; padding-bottom: 0 !important; }
    }
    </style>
</template>
