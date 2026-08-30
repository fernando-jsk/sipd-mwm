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
    <Head :title="`Cetak Surat Pernyataan: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0 print:m-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                <Printer class="w-4 h-4 mr-2" /> Cetak Surat Pernyataan
            </Button>
        </div>

        <div class="space-y-8 print:space-y-0">
            <!-- ================= PAGE 1: SURAT PERNYATAAN PENCAIRAN SPM-LS ================= -->
            <div class="page-container max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl font-sans print:shadow-none print:rounded-none print:w-full print:max-w-none print:p-0 print:m-0 print:break-after-page">
                <!-- HEADER -->
                <div class="flex items-center relative mb-4 border-b-2 border-black pb-3">
                    <img src="/images/logo-mwm.png" alt="Logo" class="h-16 w-auto absolute left-0 top-0" />
                    <div class="w-full text-center">
                        <h1 class="text-sm font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                        <h2 class="text-base font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                        <h3 class="text-xs">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                        <p class="text-xs">Website: rsudmwmaramis.com, Email: mwmaramis@gmail.com</p>
                    </div>
                </div>

                <!-- SUB HEADER -->
                <div class="text-center mb-6">
                    <h2 class="text-sm font-bold uppercase underline tracking-wider">SURAT PERNYATAAN PENCAIRAN SPM-LS</h2>
                    <p class="text-xs font-bold mt-1">Nomor: {{ expenditure.document_number ? expenditure.document_number.replace('SPP', 'SPM') : '-' }}</p>
                </div>

                <!-- STATEMENT BODY -->
                <div class="text-xs space-y-4 text-justify leading-relaxed px-1">
                    <p>
                        Sehubungan dengan Surat Perintah Membayar Langsung (SPM-LS) Barang dan Jasa Nomor: <strong>{{ expenditure.document_number ? expenditure.document_number.replace('SPP', 'SPM') : '-' }}</strong> Tanggal <strong>{{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</strong> Sebesar <strong>Rp. {{ formatCurrency(totalAmount) }}</strong> (<em>{{ terbilang(totalAmount) }} Rupiah</em>) untuk keperluan <strong>{{ expenditure.description || '-' }}</strong>.
                    </p>

                    <p>
                        Demikian surat pernyataan ini dibuat untuk melengkapi persyaratan pengajuan SPM-LS BJ dan apabila dikemudian hari terdapat kesalahan dan atau penyimpangan penggunaaan akan kami pertanggung jawabkan sesuai dengan peraturan perundangan yang berlaku.
                    </p>
                </div>

                <!-- SIGNATURE SECTION -->
                <div class="flex justify-end text-xs mt-12 px-2 print:break-inside-avoid">
                    <div class="w-72 text-center flex flex-col items-center">
                        <p>Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                        <p class="font-bold">Kuasa Pengguna Anggaran</p>
                        <p class="font-bold">BLUD RSUD M. W. Maramis</p>
                        <p class="font-bold">Kabupaten Minahasa Utara</p>
                        <div class="h-20"></div>
                        <p class="font-bold underline uppercase">{{ expenditure.kpa?.name || 'dr. ALAIN VINCENT BEYAH' }}</p>
                        <p>NIP. {{ expenditure.kpa?.nip || '198201292009031001' }}</p>
                    </div>
                </div>
            </div>

            <!-- ================= PAGE 2: SURAT PERNYATAAN PENGAJUAN SPP-LS ================= -->
            <div class="page-container max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl font-sans print:shadow-none print:rounded-none print:w-full print:max-w-none print:p-0 print:m-0 print:break-before-page">
                <!-- HEADER -->
                <div class="flex items-center relative mb-4 border-b-2 border-black pb-3">
                    <img src="/images/logo-mwm.png" alt="Logo" class="h-16 w-auto absolute left-0 top-0" />
                    <div class="w-full text-center">
                        <h1 class="text-sm font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                        <h2 class="text-base font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                        <h3 class="text-xs">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                        <p class="text-xs">Website: rsudmwmaramis.com, Email: mwmaramis@gmail.com</p>
                    </div>
                </div>

                <!-- SUB HEADER -->
                <div class="text-center mb-6">
                    <h2 class="text-sm font-bold uppercase underline tracking-wider">SURAT PERNYATAAN PENGAJUAN SPP-LS</h2>
                    <p class="text-xs font-bold mt-1">Nomor: {{ expenditure.document_number || '-' }}</p>
                </div>

                <!-- STATEMENT BODY -->
                <div class="text-xs space-y-4 text-justify leading-relaxed px-1">
                    <p>
                        Sehubungan dengan Surat Permintaan Pembayaran Langsung (SPP-LS) Barang dan Jasa Nomor: <strong>{{ expenditure.document_number || '-' }}</strong> Tanggal <strong>{{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</strong> Sebesar <strong>Rp. {{ formatCurrency(totalAmount) }}</strong> (<em>{{ terbilang(totalAmount) }} Rupiah</em>) untuk keperluan <strong>{{ expenditure.description || '-' }}</strong>.
                    </p>

                    <ol class="list-decimal list-outside pl-5 space-y-2">
                        <li>Jumlah tersebut diatas akan dipergunakan untuk keperluan guna membiayai kegiatan yang akan kami laksanakan sesuai RBA-BLUD.</li>
                        <li>Jumlah tersebut akan digunakan untuk membiayai pengeluaran-pengeluaran yang menurut ketentuan yang berlaku harus digunakan dengan pembayaran LS.</li>
                    </ol>

                    <p>
                        Demikian surat pernyataan ini dibuat untuk melengkapi persyaratan pengajuan SPP kami.
                    </p>
                </div>

                <!-- SIGNATURE SECTION -->
                <div class="flex justify-end text-xs mt-12 px-2 print:break-inside-avoid">
                    <div class="w-72 text-center flex flex-col items-center">
                        <p>Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                        <p class="font-bold">Pejabat Pelaksana Teknis Kegiatan</p>
                        <p class="font-bold">(PPTK)</p>
                        <div class="h-20"></div>
                        <p class="font-bold underline uppercase">{{ expenditure.ptk?.name || 'STEVY ROTIKAN, Amd.Farm' }}</p>
                        <p>NIP. {{ expenditure.ptk?.nip || '197909122008021001' }}</p>
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
        margin: 15mm 20mm;
    }
    html, body {
        background-color: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .page-container {
        page-break-inside: avoid;
        break-inside: avoid;
    }
}
</style>
