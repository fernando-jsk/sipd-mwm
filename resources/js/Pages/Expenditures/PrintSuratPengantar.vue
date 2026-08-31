<script setup>
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Printer, ArrowLeft } from '@lucide/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    expenditure: Object,
    totalDpa: [Number, String]
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
    <Head :title="`Cetak Surat Pengantar: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                <Printer class="w-4 h-4 mr-2" /> Cetak Surat Pengantar
            </Button>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl print:shadow-none print:rounded-none print:w-full print:max-w-none font-sans">
            
            <!-- HEADER -->
            <div class="flex items-center relative mb-4 border-b-2 border-black pb-3">
                <img src="/images/logo-minahasa-utara.png" alt="Logo Minahasa Utara" class="h-20 w-auto absolute left-0 top-0" />
                <div class="w-full text-center">
                    <h1 class="text-sm font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-base font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                    <h3 class="text-xs">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                    <p class="text-xs">Situs Web: rsudmwmaramis.minut.go.id, Email: mwmaramis@gmail.com</p>
                </div>
            </div>

            <!-- SUB HEADER -->
            <div class="text-center mb-6">
                <h2 class="text-sm font-bold uppercase">SURAT PERMINTAAN PEMBAYARAN LANGSUNG BARANG DAN JASA</h2>
                <h3 class="text-sm font-bold uppercase mb-2">(SPP-LS BARANG DAN JASA)</h3>
                <p class="text-sm font-bold">Nomor : {{ expenditure.document_number }}</p>
                <h2 class="text-base font-bold uppercase mt-6 tracking-wide underline">SURAT PENGANTAR</h2>
            </div>

            <!-- RECIPIENT -->
            <div class="text-xs mb-8 space-y-1">
                <p>Kepada Yth.</p>
                <p>Pengguna anggaran / kuasa pengguna anggaran</p>
                <p>SKPD BLUD RSUD Maria Walanda Maramis</p>
                <p>Di tempat</p>
            </div>

            <!-- CONTENT LIST -->
            <div class="text-xs space-y-4 mb-16">
                <div class="flex">
                    <div class="w-64">a. Urusan Permintaan</div>
                    <div class="w-4">:</div>
                    <div class="flex-1">1.02 Urusan Wajib Pelayanan Dasar Kesehatan</div>
                </div>

                <div class="flex">
                    <div class="w-64">b. SKPD</div>
                    <div class="w-4">:</div>
                    <div class="flex-1">1.02.02.01 BLUD RSUD Maria Walanda Maramis</div>
                </div>

                <div class="flex">
                    <div class="w-64">c. Tahun Anggaran</div>
                    <div class="w-4">:</div>
                    <div class="flex-1">{{ expenditure.date ? new Date(expenditure.date).getFullYear() : '-' }}</div>
                </div>

                <div class="flex">
                    <div class="w-64">d. Dasar pengeluaran SPD Nomor</div>
                    <div class="w-4">:</div>
                    <div class="flex-1">{{ expenditure.spd_number || '-' }}</div>
                </div>

                <div class="flex flex-col space-y-1">
                    <div class="flex">
                        <div class="w-64">e. Jumlah sisa dana</div>
                        <div class="w-4">:</div>
                        <div class="flex-1 font-bold font-mono">Rp. {{ formatCurrency(totalDpa || 0) }}</div>
                    </div>
                    <div class="pl-68 italic capitalize text-muted-foreground">
                        {{ terbilang(totalDpa || 0) }} Rupiah
                    </div>
                </div>

                <div class="flex">
                    <div class="w-64">f. Nama Bendahara Pengeluaran</div>
                    <div class="w-4">:</div>
                    <div class="flex-1">{{ expenditure.treasurer?.name || 'Saskia Paraso, SKM' }}</div>
                </div>

                <div class="flex flex-col space-y-1">
                    <div class="flex">
                        <div class="w-64">g. Jumlah Pembayaran yang Diminta</div>
                        <div class="w-4">:</div>
                        <div class="flex-1 font-bold font-mono">Rp. {{ formatCurrency(totalAmount) }}</div>
                    </div>
                    <div class="pl-68 italic capitalize text-muted-foreground">
                        {{ terbilang(totalAmount) }} Rupiah
                    </div>
                </div>
            </div>

            <!-- SIGNATURES -->
            <div class="flex justify-between text-xs mt-20 px-8">
                <div class="w-64 flex flex-col items-center">
                    <p class="mb-24 font-semibold">PEJABAT PELAKSANA TEKNIS KEGIATAN</p>
                    <p class="font-bold underline uppercase">{{ expenditure.ptk?.name || 'STEVY ROTIKAN, Amd.Farm' }}</p>
                    <p>NIP. {{ expenditure.ptk?.nip || '197909122008021001' }}</p>
                </div>
                <div class="w-64 flex flex-col items-center">
                    <p class="mb-1">Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                    <p class="mb-24 font-semibold">BENDAHARA PENGELUARAN</p>
                    <p class="font-bold underline uppercase">{{ expenditure.treasurer?.name || 'SASKIA PARASO, SKM' }}</p>
                    <p>NIP. {{ expenditure.treasurer?.nip || '199810142022032012' }}</p>
                </div>
            </div>
            
        </div>
    </div>
</template>
