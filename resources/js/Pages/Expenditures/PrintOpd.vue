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

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0">
        <!-- Floating Toolbar -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <Button size="sm" @click="printPage" class="bg-amber-600 text-white hover:bg-amber-700">
                <Printer class="w-4 h-4 mr-2" /> Cetak Surat OPD
            </Button>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl print:shadow-none print:rounded-none print:w-full print:max-w-none">
            <!-- Kop Surat -->
            <div class="border-b-2 border-black pb-3 mb-6 flex items-center relative">
                <img src="/images/logo-minahasa-utara.png" alt="Logo Minahasa Utara" class="h-16 w-auto absolute left-0 top-0" />
                <div class="w-full text-center">
                    <h1 class="text-sm font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-base font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                    <h3 class="text-xs">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                    <p class="text-xs">Situs Web: rsudmwmaramis.minut.go.id, Email: mwmaramis@gmail.com</p>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center mb-8">
                <h2 class="text-lg font-bold uppercase underline">SURAT OTORISASI Pencairan Dana (OPD)</h2>
                <p class="text-sm font-mono mt-1">Nomor OPD: {{ expenditure.opd_number || '-' }}</p>
                <p class="text-xs text-slate-500 font-mono">Referensi SPPD No: {{ expenditure.document_number }}</p>
            </div>

            <!-- Pernyataan Otorisasi -->
            <div class="mb-6 text-sm leading-relaxed border p-4 rounded-lg bg-amber-500/5 border-amber-500/20 print:border-black print:bg-transparent">
                <p>Mengingat dan menimbang pengajuan SPPD Nomor <strong>{{ expenditure.document_number }}</strong> tanggal <strong>{{ format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) }}</strong>, dengan ini Direktur Utama memberikan **OTORISASI** pengeluaran dana untuk pelaksanaan kegiatan berikut:</p>
            </div>

            <!-- Detail Informasi -->
            <table class="w-full text-sm mb-6 border-collapse">
                <tbody>
                    <tr>
                        <td class="py-1.5 w-44 font-medium">Tanggal Otorisasi</td>
                        <td class="py-1.5 w-4">:</td>
                        <td class="py-1.5">{{ expenditure.opd_date ? format(new Date(expenditure.opd_date), 'dd MMMM yyyy', { locale: id }) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium">Uraian Pekerjaan</td>
                        <td class="py-1.5">:</td>
                        <td class="py-1.5">{{ expenditure.description }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium">Jumlah Diotorisasi</td>
                        <td class="py-1.5">:</td>
                        <td class="py-1.5 font-bold font-mono text-base">{{ formatCurrency(totalAmount - totalTaxes) }}</td>
                    </tr>
                    <tr v-if="expenditure.opd_notes">
                        <td class="py-1.5 font-medium">Catatan Direktur</td>
                        <td class="py-1.5">:</td>
                        <td class="py-1.5 italic">{{ expenditure.opd_notes }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Tanda Tangan Direktur -->
            <div class="mt-16 flex justify-end text-center text-xs">
                <div class="w-64">
                    <p class="mb-2">Sekayu, {{ expenditure.opd_date ? format(new Date(expenditure.opd_date), 'dd MMMM yyyy', { locale: id }) : '......................' }}</p>
                    <p class="mb-20">Direktur Utama,</p>
                    <p class="font-bold underline text-sm">{{ expenditure.opd_authorized_by?.name || expenditure.kpa?.name || '( .................................... )' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
