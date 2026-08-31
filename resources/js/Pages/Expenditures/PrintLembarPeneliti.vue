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

const checklistItems = [
    "Perhitungan Pajak",
    "Surat Perintah Membayar Langsung (LS) Ganti Uang (GU) Tambahan Uang (TU) Nihil",
    "Surat Pengantar SPP-LS, GU, TU Nihil",
    "SPP-UP, TU, LS, Lemabar 1, 2, dan 3",
    "SPJ dan Pengesahan SPJ",
    "Surat pernyataan pengajuan SPP-UP,GU,TU,LS",
    "Surat pernyataan pencairan SPM-LS",
    "Salinan Surat Rekomendasi dari SKPD teknis terkait",
    "SSP disertai Faktur Pajak (PPn dan PPh) yang telah ditandatangani Wajib Pajak dan Wajib Pungut",
    "Surat Perjanjian Kerjasama Kontrak antara PA, KPA dengan Pihak Ketiga serta mencantumkan",
    "Surat Permohonan Uang Muka",
    "Rincian uang muka",
    "Berita Acara Penyelesaian Pekerjaan",
    "Berita Acara Serah Terima Barang dan Jasa",
    "Berita Acara Pembayaran",
    "Berita Acara Kemajuan Fisik Pekerjaan",
    "Laporan Kemajuan Fisik Pekerjaan (Harian, Mingguan dan Bulanan)",
    "MCA (Mutual Cek Awal dan Mutual Cek Akhir)",
    "PHO dan FHO",
    "Kwitansi bermeterai, nota Faktur yang ditandatangani Pihak ketiga dan PPTK serta Disetujui oleh PA/KPA",
    "Surat Jaminan atau dipersamakan yang dikeluarkan oleh BANK atau Lembaga Keuangan Non BANK",
    "Dokumen lain yang dipersamakan untuk Kontrak yang dananya sebagian atau seluruhnya bersumber dari penerusan Pinjaman Hibah Luar Negeri",
    "Berita Acara Pemeriksaan yang ditandatangani oleh Pihak Ketiga Rekanan serta unsur Panitia Pemeriksaan Barang berikut Lampiran Daftar Barang yang diperiksa",
    "Surat Angkutan atau Konsumen apabila pengadaan Barang dilaksanakan diluar wilayah kerja",
    "Surat Pemberitahuan Potongan Denda Keterlambatan Pekerjaan dari PPTK apabila Pek mengalami keterlambatan",
    "Foto Dokumen Tingkat Kemajuan Penyelesaian Pekerjaan",
    "Potongan Jamsostek (Potongan sesuai dengan ketentuan yang berlaku/surat pemberitahuan Jamsostek)",
    "Khusus untuk Pekerjaan Konsultasi yang perhitungan harganya menggunakan biaya personil (Billing Rate)_ penetapan waktu pekerjaan dan bukti penyewaan pembelian atau penunjang serta bukti pengeluaran lainnya berdasarkan rincian dalam surat penawaran",
    "SK, Nota Dinas, Surat Penunjukan, SPK, NPHD sebagai dasar pembayaran",
    "Surat Pernyataan, Pakta Integritas, RC",
    "Nota/Faktur ditandatanani pihak ketiga dan pengguna barang",
    "Daftar Pembayaran",
    "Dokumen Perusahaan"
];
</script>

<template>
    <Head :title="`Cetak Lembar Penelitian Dokumen: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0 print:m-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground hidden sm:inline">Ukuran Kertas: A4 / F4 (1 Halaman)</span>
                <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                    <Printer class="w-4 h-4 mr-2" /> Cetak Lembar Penelitian
                </Button>
            </div>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-6 sm:p-10 shadow-md rounded-xl print:shadow-none print:rounded-none print:w-full print:max-w-none font-sans print:p-0 print:m-0">
            
            <!-- HEADER -->
            <div class="flex items-center relative mb-2 border-b-2 border-black pb-2">
                <img src="/images/logo-minahasa-utara.png" alt="Logo Minahasa Utara" class="h-14 w-auto absolute left-0 top-0" />
                <div class="w-full text-center">
                    <h1 class="text-xs font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-sm font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                    <h3 class="text-[11px]">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                    <p class="text-[10px]">Situs Web: rsudmwmaramis.minut.go.id, Email: mwmaramis@gmail.com</p>
                </div>
            </div>

            <!-- TITLE -->
            <div class="text-center mb-2">
                <h1 class="text-xs font-bold uppercase tracking-wide underline">LEMBAR PENELITIAN DOKUMEN</h1>
            </div>

            <!-- METADATA TABLE -->
            <table class="w-full text-[9.5px] border-collapse border border-black mb-2">
                <tbody>
                    <tr>
                        <td class="w-36 px-1.5 py-0.5 align-top font-bold">NAMA SATKER</td>
                        <td class="w-4 py-0.5 align-top text-center">:</td>
                        <td class="px-1.5 py-0.5 align-top font-bold">BLUD RSUD MARIA WALANDA MARAMIS</td>
                    </tr>
                    <tr>
                        <td class="px-1.5 py-0.5 align-top font-bold">BENDAHARA</td>
                        <td class="w-4 py-0.5 align-top text-center">:</td>
                        <td class="px-1.5 py-0.5 align-top">{{ expenditure.treasurer?.name || 'Saskia Paraso, SKM' }} / BENDAHARA PENGELUARAN</td>
                    </tr>
                    <tr>
                        <td class="px-1.5 py-0.5 align-top font-bold">PIHAK KETIGA</td>
                        <td class="w-4 py-0.5 align-top text-center">:</td>
                        <td class="px-1.5 py-0.5 align-top font-bold">{{ expenditure.vendor?.name || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-1.5 py-0.5 align-top font-bold">NOMOR TGL SPM</td>
                        <td class="w-4 py-0.5 align-top text-center">:</td>
                        <td class="px-1.5 py-0.5 align-top">{{ expenditure.document_number ? expenditure.document_number.replace('SPP', 'SPM') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-1.5 py-0.5 align-top font-bold">JENIS SPM</td>
                        <td class="w-4 py-0.5 align-top text-center">:</td>
                        <td class="px-1.5 py-0.5 align-top">{{ expenditure.type ? expenditure.type.toUpperCase() : 'LS BJ' }}</td>
                    </tr>
                    <tr>
                        <td class="px-1.5 py-0.5 align-top font-bold">NILAI SPM</td>
                        <td class="w-4 py-0.5 align-top text-center">:</td>
                        <td class="px-1.5 py-0.5 align-top font-bold font-mono">Rp {{ formatCurrency(totalAmount) }}</td>
                    </tr>
                    <tr>
                        <td class="px-1.5 py-0.5 align-top font-bold pb-1">KEPERLUAN</td>
                        <td class="w-4 py-0.5 align-top text-center pb-1">:</td>
                        <td class="px-1.5 py-0.5 align-top pb-1">{{ expenditure.description || '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- CHECKLIST TABLE -->
            <table class="w-full text-[8.5px] leading-tight border-collapse border border-black mb-2">
                <thead>
                    <tr class="border-b border-black bg-gray-50">
                        <th class="border-r border-black px-1 py-0.5 w-6 text-center font-bold">NO</th>
                        <th class="border-r border-black px-1 py-0.5 w-16 text-center font-bold">PARAF</th>
                        <th class="px-1.5 py-0.5 text-center font-bold">DAFTAR CHEK LIST</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in checklistItems" :key="idx" class="border-b border-black">
                        <td class="border-r border-black px-1 py-0.25 text-center align-top">{{ idx + 1 }}</td>
                        <td class="border-r border-black px-1 py-0.25 text-center align-top">
                            <div class="w-full h-2.5"></div>
                        </td>
                        <td class="px-1.5 py-0.25 align-top">{{ item }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- SIGNATURES -->
            <div class="flex justify-between text-[9.5px] mt-2 px-6 print:break-inside-avoid">
                <div class="w-64 flex flex-col items-center">
                    <p class="font-bold mb-8">PPTK</p>
                    <p class="font-bold underline uppercase">{{ expenditure.ptk?.name || 'STEVY ROTIKAN, Amd.Farm' }}</p>
                    <p>NIP. {{ expenditure.ptk?.nip || '197909122008021001' }}</p>
                </div>
                <div class="w-64 flex flex-col items-center">
                    <p class="mb-0.5">Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                    <p class="font-bold mb-8">VERIFIKASI</p>
                    <p class="font-bold underline uppercase">( .................................................... )</p>
                    <p class="text-transparent">.</p>
                </div>
            </div>
            
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: auto;
        margin: 5mm 8mm;
    }
    html, body {
        background-color: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
