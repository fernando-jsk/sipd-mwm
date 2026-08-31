<script setup>
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Printer, ArrowLeft } from '@lucide/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    expenditure: Object
});

const printPage = () => {
    window.print();
};

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = `/expenditures/${props.expenditure.id}`;
    }
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const totalAmount = props.expenditure.details?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
const totalTaxes = props.expenditure.taxes?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
</script>

<template>
    <Head :title="`Cetak Surat SPD: ${expenditure.spd_number || expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0 print:m-0">
        <!-- Floating Toolbar -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground hidden sm:inline">Ukuran Kertas: A4 / F4 (1 Halaman)</span>
                <Button size="sm" @click="printPage" class="bg-emerald-600 text-white hover:bg-emerald-700">
                    <Printer class="w-4 h-4 mr-2" /> Cetak Surat SPD
                </Button>
            </div>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-6 sm:p-10 shadow-md rounded-xl font-sans print:shadow-none print:rounded-none print:w-full print:max-w-none print:p-0 print:m-0">
            <!-- Kop Surat -->
            <div class="border-b-2 border-black pb-2 mb-4 flex items-center relative">
                <img src="/images/logo-minahasa-utara.png" alt="Logo Minahasa Utara" class="h-14 w-auto absolute left-0 top-0" />
                <div class="w-full text-center">
                    <h1 class="text-xs font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-sm font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                    <h3 class="text-[11px]">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                    <p class="text-[10px]">Situs Web: rsudmwmaramis.minut.go.id, Email: mwmaramis@gmail.com</p>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center mb-6">
                <h2 class="text-sm font-bold uppercase underline">SURAT PENCAIRAN DANA (SPD)</h2>
                <p class="text-xs font-mono mt-1">Nomor SPD: {{ expenditure.spd_number || '-' }}</p>
                <p class="text-[11px] text-slate-500 font-mono">Ref. OPD: {{ expenditure.opd_number || '-' }} | Ref. SPPD: {{ expenditure.document_number }}</p>
            </div>

            <!-- Pernyataan Pencairan -->
            <div class="mb-4 text-xs leading-relaxed border p-3 rounded-lg bg-emerald-500/5 border-emerald-500/20 print:border-black print:bg-transparent">
                <p>Berdasarkan Surat Otorisasi Pencairan Dana (OPD) Nomor <strong>{{ expenditure.opd_number || '-' }}</strong>, dengan ini Kabag Keuangan menerbitkan Surat Pencairan Dana (SPD) dan mentransfer sejumlah dana kepada penerima terkait:</p>
            </div>

            <!-- Detail Transfer -->
            <table class="w-full text-xs mb-6 border-collapse">
                <tbody>
                    <tr>
                        <td class="py-1 w-44 font-medium">Tanggal Pencairan</td>
                        <td class="py-1 w-4">:</td>
                        <td class="py-1">{{ expenditure.spd_date ? format(new Date(expenditure.spd_date), 'dd MMMM yyyy', { locale: id }) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 font-medium">Bank Sumber Dana</td>
                        <td class="py-1">:</td>
                        <td class="py-1 font-semibold">{{ expenditure.payment_source_bank || 'BSI' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 font-medium">Penerima / Rekanan</td>
                        <td class="py-1">:</td>
                        <td class="py-1">{{ expenditure.payment_method === 'rekanan' && expenditure.vendor ? expenditure.vendor.name : 'Pegawai Internal / Terlampir' }}</td>
                    </tr>
                    <tr v-if="expenditure.bank_name">
                        <td class="py-1 font-medium">Bank / No Rekening Tujuan</td>
                        <td class="py-1">:</td>
                        <td class="py-1 font-mono">{{ expenditure.bank_name }} - {{ expenditure.bank_account_number }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 font-medium">Jumlah Dicairkan (Netto)</td>
                        <td class="py-1">:</td>
                        <td class="py-1 font-bold font-mono text-sm text-emerald-700">Rp {{ formatCurrency(totalAmount - totalTaxes) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Tanda Tangan Kabag Keuangan -->
            <div class="mt-10 flex justify-end text-center text-xs print:break-inside-avoid">
                <div class="w-64">
                    <p class="mb-1">Airmadidi, {{ expenditure.spd_date ? format(new Date(expenditure.spd_date), 'dd MMMM yyyy', { locale: id }) : '......................' }}</p>
                    <p class="font-bold mb-16">Kepala Bagian Keuangan,</p>
                    <p class="font-bold underline uppercase">{{ expenditure.spd_disbursed_by?.name || 'Monalisa F. Sumampouw,SST, M.Kes' }}</p>
                    <p>NIP. {{ expenditure.spd_disbursed_by?.nip || '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: auto;
        margin: 8mm 12mm;
    }
    html, body {
        background-color: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
