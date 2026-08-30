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
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
};

const totalAmount = props.expenditure.details?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
const totalTaxes = props.expenditure.taxes?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
const netAmount = totalAmount - totalTaxes;

const terbilang = (angka) => {
    const huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    let hasil = "";
    if (angka < 12) {
        hasil = huruf[Math.floor(angka)];
    } else if (angka < 20) {
        hasil = terbilang(angka - 10) + " Belas";
    } else if (angka < 100) {
        hasil = terbilang(Math.floor(angka / 10)) + " Puluh " + terbilang(angka % 10);
    } else if (angka < 200) {
        hasil = "Seratus " + terbilang(angka - 100);
    } else if (angka < 1000) {
        hasil = terbilang(Math.floor(angka / 100)) + " Ratus " + terbilang(angka % 100);
    } else if (angka < 2000) {
        hasil = "Seribu " + terbilang(angka - 1000);
    } else if (angka < 1000000) {
        hasil = terbilang(Math.floor(angka / 1000)) + " Ribu " + terbilang(angka % 1000);
    } else if (angka < 1000000000) {
        hasil = terbilang(Math.floor(angka / 1000000)) + " Juta " + terbilang(angka % 1000000);
    } else if (angka < 1000000000000) {
        hasil = terbilang(Math.floor(angka / 1000000000)) + " Milyar " + terbilang(angka % 1000000000);
    } else if (angka < 1000000000000000) {
        hasil = terbilang(Math.floor(angka / 1000000000000)) + " Triliun " + terbilang(angka % 1000000000000);
    }
    return hasil.trim();
};
</script>

<template>
    <Head :title="`Cetak SPM: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                <Printer class="w-4 h-4 mr-2" /> Cetak SPM
            </Button>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl print:shadow-none print:rounded-none print:w-full print:max-w-none font-sans">
            
            <!-- HEADER -->
            <div class="flex items-center mb-6 relative">
                <img src="/images/logo-mwm.png" alt="Logo" class="h-20 w-auto absolute left-0 top-0" />
                <div class="w-full text-center pt-2">
                    <h1 class="text-sm font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-base font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                    <h3 class="text-xs">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                    <p class="text-xs">Website: rsudmwmaramis.com, Email: mwmaramis@gmail.com</p>
                </div>
            </div>

            <table class="w-full text-[11px] border-collapse border border-black mb-0">
                <tbody>
                    <!-- Row 1: RSUD MARIA WALANDA MARAMIS & SPM Title -->
                    <tr>
                        <td class="w-1/2 border border-black font-bold text-center py-1">RSUD MARIA WALANDA MARAMIS</td>
                        <td class="w-1/2 border border-black font-bold text-center py-1">SURAT PERINTAH MEMBAYAR (SPM) UP/GU/TU/LS</td>
                    </tr>
                </tbody>
            </table>

            <table class="w-full text-[11px] border-collapse border-x border-b border-black mb-0">
                <tbody>
                    <!-- Info Block -->
                    <tr>
                        <td class="w-[15%] px-2 py-1 align-top font-bold">NOMOR SPP</td>
                        <td class="w-[35%] px-2 py-1 align-top border-r border-black">: {{ expenditure.document_number }}</td>
                        <td class="w-[15%] px-2 py-1 align-top font-bold">NOMOR</td>
                        <td class="w-[35%] px-2 py-1 align-top">: {{ expenditure.document_number.replace('SPP', 'SPM') }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top font-bold">TANGGAL</td>
                        <td class="px-2 py-1 align-top border-r border-black">: {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</td>
                        <td class="px-2 py-1 align-top font-bold">TANGGAL</td>
                        <td class="px-2 py-1 align-top">: {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</td>
                    </tr>
                    <tr class="border-b border-black">
                        <td class="px-2 py-1 align-top font-bold">UNIT KERJA</td>
                        <td class="px-2 py-1 align-top border-r border-black">: BLUD RSUD MARIA WALANDA MARAMIS</td>
                        <td class="px-2 py-1 align-top font-bold">TAHUN ANGGARAN</td>
                        <td class="px-2 py-1 align-top">: {{ expenditure.date ? new Date(expenditure.date).getFullYear() : '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Detail Payee Block -->
            <table class="w-full text-[11px] border-collapse border-x border-b border-black mb-0">
                <tbody>
                    <tr>
                        <td class="w-[30%] px-2 py-1 align-top font-bold">Supaya membayar/memindahbukukan dari rekening</td>
                        <td class="w-[70%] px-2 py-1 align-top">: RSUD MARIA WALANDA MARAMIS / BSI 7261385206</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top font-bold">Kepada pihak ketiga</td>
                        <td class="px-2 py-1 align-top">: {{ expenditure.vendor?.name || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top font-bold">Nomor rekening bank</td>
                        <td class="px-2 py-1 align-top">: {{ expenditure.bank_account_number || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top font-bold">Nama Bank</td>
                        <td class="px-2 py-1 align-top">: {{ expenditure.bank_name || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top font-bold">NPWP</td>
                        <td class="px-2 py-1 align-top">: {{ expenditure.vendor?.npwp || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top font-bold">Dasar pembayaran</td>
                        <td class="px-2 py-1 align-top">: DPA BLUD RSUD Maria Walanda Maramis</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top font-bold">Keperluan untuk</td>
                        <td class="px-2 py-1 align-top pb-4">: {{ expenditure.description || '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Detail Belanja -->
            <table class="w-full text-[11px] border-collapse border border-black mb-0">
                <thead>
                    <tr>
                        <th class="border border-black px-2 py-1 w-8 text-center font-bold">No.</th>
                        <th class="border border-black px-2 py-1 text-center font-bold">URAIAN</th>
                        <th class="border border-black px-2 py-1 w-48 text-center font-bold">JUMLAH ( Rp )</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(detail, idx) in expenditure.details" :key="detail.id">
                        <td class="border border-black px-2 py-1 text-center align-top">{{ idx + 1 }}</td>
                        <td class="border border-black px-2 py-1 align-top">
                            {{ detail.account_code?.code }} - {{ detail.account_code?.name }}
                        </td>
                        <td class="border border-black px-2 py-1 text-right align-top font-mono">Rp{{ formatCurrency(detail.amount) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="border border-black px-2 py-1 text-right font-bold">Jumlah:</td>
                        <td class="border border-black px-2 py-1 text-right font-bold font-mono">Rp{{ formatCurrency(totalAmount) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Potongan-potongan -->
            <table class="w-full text-[11px] border-collapse border-x border-b border-black mb-0">
                <thead>
                    <tr>
                        <th colspan="4" class="border-b border-black px-2 py-1 text-center font-bold">POTONGAN-POTONGAN</th>
                    </tr>
                    <tr>
                        <th class="border-b border-r border-black px-2 py-1 w-8 text-center font-bold">No.</th>
                        <th class="border-b border-r border-black px-2 py-1 text-center font-bold">URAIAN</th>
                        <th class="border-b border-r border-black px-2 py-1 w-48 text-center font-bold">JUMLAH (Rp)</th>
                        <th class="border-b border-black px-2 py-1 w-48 text-center font-bold">CODE BILLING</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(tax, idx) in expenditure.taxes" :key="tax.id">
                        <td class="border-r border-b border-black px-2 py-1 text-center">{{ idx + 1 }}</td>
                        <td class="border-r border-b border-black px-2 py-1">{{ tax.name }}</td>
                        <td class="border-r border-b border-black px-2 py-1 text-right font-mono">Rp{{ formatCurrency(tax.amount) }}</td>
                        <td class="border-b border-black px-2 py-1 text-center">{{ tax.billing_code || '-' }}</td>
                    </tr>
                    <tr v-if="!expenditure.taxes?.length">
                        <td class="border-r border-b border-black px-2 py-1 text-center">1</td>
                        <td class="border-r border-b border-black px-2 py-1">-</td>
                        <td class="border-r border-b border-black px-2 py-1 text-right font-mono">Rp0,00</td>
                        <td class="border-b border-black px-2 py-1 text-center">-</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="border-r border-black px-2 py-1 text-right font-bold">Jumlah:</td>
                        <td class="border-r border-black px-2 py-1 text-right font-bold font-mono">Rp{{ formatCurrency(totalTaxes) }}</td>
                        <td class="px-2 py-1 text-center"></td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary -->
            <table class="w-full text-[11px] border-collapse border border-black mb-0">
                <thead>
                    <tr>
                        <th colspan="2" class="border-b border-black px-2 py-1 text-center font-bold bg-gray-50">SPM YANG DIBAYARKAN</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border-r border-black px-2 py-1 font-bold w-[70%] pl-8">Jumlah <span class="pl-4">Yang diminta (Bruto)</span></td>
                        <td class="px-2 py-1 text-right font-bold font-mono">Rp{{ formatCurrency(totalAmount) }}</td>
                    </tr>
                    <tr>
                        <td class="border-r border-black px-2 py-1 font-bold pl-8">Jumlah <span class="pl-4">Potongan</span></td>
                        <td class="px-2 py-1 text-right font-bold font-mono">Rp{{ formatCurrency(totalTaxes) }}</td>
                    </tr>
                    <tr>
                        <td class="border-r border-black px-2 py-1 font-bold pl-8">Jumlah <span class="pl-4">Netto</span></td>
                        <td class="px-2 py-1 text-right font-bold font-mono">Rp{{ formatCurrency(netAmount) }}</td>
                    </tr>
                    <tr class="border-b border-black">
                        <td class="border-r border-black px-2 py-1 font-bold pl-8">Jumlah <span class="pl-4">Yang dibayarkan</span></td>
                        <td class="px-2 py-1 text-right font-bold font-mono">Rp{{ formatCurrency(totalAmount) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Terbilang -->
            <div class="border-x border-b border-black px-2 py-2 text-[11px] min-h-[40px] flex">
                <span class="w-32">Uang sejumlah</span>
                <span>: </span>
                <span class="italic capitalize flex-1"> {{ terbilang(totalAmount) }} Rupiah</span>
            </div>

            <!-- Signatures -->
            <div class="flex justify-between text-[11px] mt-2 border-x border-b border-black px-2 py-4 print:break-inside-avoid">
                <div class="w-1/3 flex flex-col justify-end text-xs">
                    <p>Lembar 1 : Bendahara Pengeluaran</p>
                    <p>Lembar 2 : PK</p>
                    <p>Lembar 3 : Pihak Ketiga</p>
                </div>
                <div class="w-1/3 text-center flex flex-col items-center justify-between">
                    <div>
                        <p>Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                        <p class="font-bold">BLUD RSUD MARIA WALANDA MARAMIS</p>
                        <p class="font-bold">KUASA PENGGUNA ANGGARAN</p>
                    </div>
                    <div class="mt-20">
                        <p class="font-bold underline">{{ expenditure.kpa?.name || 'dr. ALAIN VINCENT BEYAH' }}</p>
                        <p>NIP. {{ expenditure.kpa?.nip || '198201292009031001' }}</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm 15mm;
    }
    html, body {
        background-color: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
