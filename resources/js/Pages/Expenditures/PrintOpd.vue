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
    <Head :title="`Cetak Surat OPD: ${expenditure.opd_number || expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0 print:m-0">
        <!-- Floating Toolbar -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground hidden sm:inline">Ukuran Kertas: A4 / F4 (1 Halaman)</span>
                <Button size="sm" @click="printPage" class="bg-amber-600 text-white hover:bg-amber-700">
                    <Printer class="w-4 h-4 mr-2" /> Cetak Surat OPD
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
                <h2 class="text-sm font-bold uppercase underline">SURAT OTORISASI PENCAIRAN DANA (OPD)</h2>
                <p class="text-xs font-mono mt-1">Nomor OPD: {{ expenditure.opd_number || '-' }}</p>
                <p class="text-[11px] text-slate-500 font-mono">Referensi SPPD No: {{ expenditure.document_number }}</p>
            </div>

            <!-- Pernyataan Otorisasi -->
            <div class="mb-4 text-xs leading-relaxed border p-3 rounded-lg bg-amber-500/5 border-amber-500/20 print:border-black print:bg-transparent">
                <p>Mengingat dan menimbang pengajuan SPPD Nomor <strong>{{ expenditure.document_number }}</strong> tanggal <strong>{{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</strong>, dengan ini Direktur memberikan <strong>OTORISASI</strong> pengeluaran dana untuk pelaksanaan kegiatan berikut:</p>
            </div>

            <!-- Detail Informasi -->
            <table class="w-full text-xs mb-6 border-collapse">
                <tbody>
                    <tr>
                        <td class="py-1 w-44 font-medium">Tanggal Otorisasi</td>
                        <td class="py-1 w-4">:</td>
                        <td class="py-1">{{ expenditure.opd_date ? format(new Date(expenditure.opd_date), 'dd MMMM yyyy', { locale: id }) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 font-medium">Uraian Pekerjaan</td>
                        <td class="py-1">:</td>
                        <td class="py-1">{{ expenditure.description }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 font-medium">Jumlah Diotorisasi</td>
                        <td class="py-1">:</td>
                        <td class="py-1 font-bold font-mono text-sm">Rp {{ formatCurrency(totalAmount - totalTaxes) }}</td>
                    </tr>
                    <tr v-if="expenditure.opd_notes">
                        <td class="py-1 font-medium">Catatan Direktur</td>
                        <td class="py-1">:</td>
                        <td class="py-1 italic">{{ expenditure.opd_notes }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Tanda Tangan Direktur -->
            <div class="mt-10 flex justify-end text-center text-xs print:break-inside-avoid">
                <div class="w-64">
                    <p class="mb-1">Airmadidi, {{ expenditure.opd_date ? format(new Date(expenditure.opd_date), 'dd MMMM yyyy', { locale: id }) : '......................' }}</p>
                    <p class="font-bold mb-16">Direktur,</p>
                    <p class="font-bold underline uppercase">{{ expenditure.opd_authorized_by?.name || expenditure.kpa?.name || '( .................................... )' }}</p>
                    <p>NIP. {{ expenditure.opd_authorized_by?.nip || expenditure.kpa?.nip || '-' }}</p>
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
