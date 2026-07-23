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

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
};

const totalAmount = props.expenditure.details?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
const totalTaxes = props.expenditure.taxes?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;

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
    <Head :title="`Cetak SPPD: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="$window.history.back()">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                <Printer class="w-4 h-4 mr-2" /> Cetak SPPD
            </Button>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl print:shadow-none print:rounded-none print:w-full print:max-w-none font-sans">
            
            <!-- HEADER -->
            <div class="flex items-center mb-6 relative">
                <img src="/images/logo-mwm.png" alt="Logo" class="h-20 w-auto absolute left-0 top-0" />
                <div class="w-full text-center">
                    <h1 class="text-base font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-sm font-bold uppercase tracking-wide">SURAT PERMINTAAN PENCAIRAN DANA LANGSUNG</h2>
                    <h3 class="text-xs font-semibold uppercase">(SURAT-PPD-LS)</h3>
                    <p class="text-xs mt-1">Nomor : {{ expenditure.document_number }}</p>
                </div>
            </div>

            <!-- TABLE RINGKASAN KEGIATAN -->
            <table class="w-full text-[11px] border-collapse border border-black mb-2">
                <tbody>
                    <tr>
                        <td colspan="4" class="border border-black font-bold text-center py-1">RINGKASAN KEGIATAN</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">1</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Nama Perusahaan</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">{{ expenditure.vendor?.name || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">2</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Bentuk Perusahaan</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">-</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">3</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Alamat Perusahaan</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">{{ expenditure.vendor?.address || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">4</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Nama Pimpinan Perusahaan</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">{{ expenditure.vendor?.director_name || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">5</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">NPWP Perusahaan</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">{{ expenditure.vendor?.npwp || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">6</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Nama dan No. Rekening Bank / No. VA</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">{{ expenditure.bank_name ? (expenditure.bank_name + ' / ' + expenditure.bank_account_number) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">7</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Nomor Kontrak</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">{{ expenditure.contract_number || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">8</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Kegiatan Lanjutan</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">Tidak</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 align-top text-center">9</td>
                        <td class="w-56 border-r border-black px-2 py-1 align-top">Waktu Pelaksanaan Kegiatan</td>
                        <td class="w-4 text-center py-1 align-top">:</td>
                        <td class="border-r border-black px-2 py-1 align-top">{{ expenditure.activity_date ? format(new Date(expenditure.activity_date), 'dd MMMM yyyy', { locale: id }) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="w-6 border-l border-r border-black px-2 py-1 border-b align-top text-center">10</td>
                        <td class="w-56 border-r border-black border-b px-2 py-1 align-top">Deskripsi Pekerjaan</td>
                        <td class="w-4 border-b text-center py-1 align-top">:</td>
                        <td class="border-r border-black border-b px-2 py-1 align-top">{{ expenditure.activity_description || expenditure.description || '-' }}</td>
                    </tr>



                    <!-- Ringkasan Belanja -->
                    <tr>
                        <td colspan="4" class="border border-black font-bold text-center py-1">RINGKASAN BELANJA</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border border-black px-2 py-1 text-center">Belanja UP/GU</td>
                        <td class="border border-black px-2 py-1 text-right font-mono">0,00</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border border-black px-2 py-1 text-center">Belanja LS</td>
                        <td class="border border-black px-2 py-1 text-right font-mono">{{ formatCurrency(totalAmount) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border border-black px-2 py-1 font-bold text-right">Jumlah (III)</td>
                        <td class="border border-black px-2 py-1 font-bold text-right font-mono">{{ formatCurrency(totalAmount) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border border-black px-2 py-1 font-bold text-left">Sisa Anggaran Kas Triwulan berkenaan dan sebelumnya yang belum dibelanjakan (II-III)<br><div class="text-right w-full">Rp.</div></td>
                        <td class="border border-black px-2 py-1 font-bold text-right font-mono">-</td>
                    </tr>

                    <!-- Rincian Rencana Penggunaan -->
                    <tr>
                        <td colspan="4" class="border border-black font-bold text-center py-1 italic">RINCIAN RENCANA PENGGUNAAN</td>
                    </tr>
                    <tr>
                        <td class="border border-black px-2 py-1 font-bold text-center">No</td>
                        <td class="border border-black px-2 py-1 font-bold text-center">Kode Rekening</td>
                        <td class="border border-black px-2 py-1 font-bold text-center">Uraian</td>
                        <td class="border border-black px-2 py-1 font-bold text-center">Jumlah</td>
                    </tr>
                    <tr v-for="(detail, idx) in expenditure.details" :key="detail.id">
                        <td class="border border-black px-2 py-1 text-center">{{ idx + 1 }}</td>
                        <td class="border border-black px-2 py-1 text-center">{{ detail.account_code?.code }}</td>
                        <td class="border border-black px-2 py-1">{{ detail.account_code?.name }}</td>
                        <td class="border border-black px-2 py-1 text-right font-mono">{{ formatCurrency(detail.amount) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border border-black px-2 py-1 font-bold text-center">Total</td>
                        <td class="border border-black px-2 py-1 font-bold text-right font-mono">{{ formatCurrency(totalAmount) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Terbilang -->
            <div class="flex text-[11px] mb-8 mt-2">
                <div class="w-20 font-bold">Terbilang</div>
                <div class="italic capitalize">: {{ terbilang(totalAmount) }} Rupiah</div>
            </div>

            <!-- Signatures -->
            <div class="flex justify-between text-[11px] text-center mt-6">
                <div class="w-64 flex flex-col items-center">
                    <p class="mb-1">Mengetahui,</p>
                    <p class="mb-16">Pejabat Teknis Kegiatan</p>
                    <p class="font-bold underline">{{ expenditure.ptk?.name || '( .................................... )' }}</p>
                    <p>NIP : -</p>
                </div>
                <div class="w-64 flex flex-col items-center">
                    <p class="mb-1">Minahasa Utara, {{ format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) }}</p>
                    <p class="mb-16">Bendahara Pengeluaran BLUD</p>
                    <p class="font-bold underline">{{ expenditure.treasurer?.name || '( .................................... )' }}</p>
                    <p>NIP : -</p>
                </div>
            </div>
            
        </div>
    </div>
</template>
