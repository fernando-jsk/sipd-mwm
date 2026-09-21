<script setup>
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Printer, ArrowLeft } from '@lucide/vue';
import { Button } from '@/Components/ui/button';
import { terbilang } from '@/lib/utils';

const props = defineProps({
    receipt: Object,
    pptk: Object,
    treasurer: Object,
});

const printPage = () => {
    window.print();
};

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/expenditure-receipts';
    }
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
};

const receiptDateFormatted = props.receipt.date ? format(new Date(props.receipt.date), 'dd MMMM yyyy', { locale: id }) : '-';
const receiptYear = props.receipt.date ? new Date(props.receipt.date).getFullYear() : new Date().getFullYear();

const grossAmount = Number(props.receipt.amount || 0);
const taxAmount = Number(props.receipt.tax_amount || 0);
const netAmount = Math.max(0, grossAmount - taxAmount);
</script>

<template>
    <Head :title="`Cetak Kuitansi: ${receipt.receipt_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0 print:m-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-3xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground hidden sm:inline">Ukuran Kertas: A4 / F4 (1 Halaman)</span>
                <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                    <Printer class="w-4 h-4 mr-2" /> Cetak Kuitansi
                </Button>
            </div>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-3xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl font-sans print:shadow-none print:rounded-none print:w-full print:max-w-none print:p-0 print:m-0 border border-gray-200 print:border-none">
            
            <!-- HEADER DINAS -->
            <div class="flex items-center mb-4 relative border-b-2 border-black pb-3">
                <img src="/images/logo-minahasa-utara.png" alt="Logo Minahasa Utara" class="h-16 w-auto absolute left-0 top-0" />
                <div class="w-full text-center">
                    <h1 class="text-xs font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-base font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                    <h3 class="text-xs">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                    <p class="text-[10px]">Situs Web: rsudmwmaramis.minut.go.id, Email: mwmaramis@gmail.com</p>
                </div>
            </div>

            <!-- TITLE & METADATA -->
            <div class="text-center mb-6">
                <h2 class="text-sm font-bold uppercase tracking-wide underline">KUITANSI / BUKTI PENGELUARAN KAS</h2>
                <div class="flex justify-between items-center text-xs mt-2 px-2 font-mono">
                    <div>Tahun Anggaran : <strong>{{ receiptYear }}</strong></div>
                    <div>Nomor Bukti : <strong>{{ receipt.receipt_number }}</strong></div>
                </div>
            </div>

            <!-- ISI KUITANSI -->
            <table class="w-full text-xs mb-6 border-collapse">
                <tbody>
                    <tr class="align-top">
                        <td class="w-44 py-1.5 font-medium">Sudah Terima Dari</td>
                        <td class="w-4 py-1.5 text-center">:</td>
                        <td class="py-1.5 font-semibold">Bendahara Pengeluaran BLUD RSUD Maria Walanda Maramis</td>
                    </tr>
                    <tr class="align-top">
                        <td class="py-1.5 font-medium">Jumlah Uang</td>
                        <td class="py-1.5 text-center">:</td>
                        <td class="py-1.5 font-mono font-bold text-sm bg-gray-100 px-2 py-1 rounded">
                            Rp {{ formatCurrency(grossAmount) }}
                        </td>
                    </tr>
                    <tr class="align-top">
                        <td class="py-1.5 font-medium">Terbilang</td>
                        <td class="py-1.5 text-center">:</td>
                        <td class="py-1.5 italic font-semibold capitalize bg-gray-50 px-2 py-1 rounded">
                            {{ terbilang(grossAmount) }} Rupiah
                        </td>
                    </tr>
                    <tr class="align-top">
                        <td class="py-1.5 font-medium">Untuk Pembayaran</td>
                        <td class="py-1.5 text-center">:</td>
                        <td class="py-1.5 leading-relaxed">{{ receipt.description }}</td>
                    </tr>
                    <tr class="align-top">
                        <td class="py-1.5 font-medium">Kode Rekening Belanja</td>
                        <td class="py-1.5 text-center">:</td>
                        <td class="py-1.5 font-mono">
                            <strong>{{ receipt.account_code?.code }}</strong> - {{ receipt.account_code?.name }}
                        </td>
                    </tr>
                    <tr class="align-top">
                        <td class="py-1.5 font-medium">Penerima Pembayaran</td>
                        <td class="py-1.5 text-center">:</td>
                        <td class="py-1.5 font-semibold">{{ receipt.recipient_name }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- TABEL RINCIAN PAJAK (JIKA ADA POTONGAN) -->
            <div v-if="taxAmount > 0" class="mb-6">
                <table class="w-full text-xs border-collapse border border-black">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-black px-2 py-1 text-left">Uraian Transaksi</th>
                            <th class="border border-black px-2 py-1 text-right w-36">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-black px-2 py-1">Jumlah Pembayaran Bruto</td>
                            <td class="border border-black px-2 py-1 text-right font-mono">Rp {{ formatCurrency(grossAmount) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-black px-2 py-1">
                                Potongan {{ receipt.tax_type }}
                                <span v-if="receipt.billing_code" class="text-[11px] text-gray-600 font-mono">(Billing: {{ receipt.billing_code }})</span>
                            </td>
                            <td class="border border-black px-2 py-1 text-right font-mono">Rp {{ formatCurrency(taxAmount) }}</td>
                        </tr>
                        <tr class="font-bold bg-gray-50">
                            <td class="border border-black px-2 py-1">Jumlah Bersih yang Diterima (Netto)</td>
                            <td class="border border-black px-2 py-1 text-right font-mono">Rp {{ formatCurrency(netAmount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TANGGAL & TANDA TANGAN -->
            <div class="mt-8 text-xs">
                <div class="text-right mb-6 mr-4">
                    Airmadidi, {{ receiptDateFormatted }}
                </div>

                <div class="grid grid-cols-3 gap-4 text-center">
                    <!-- PTK / PPTK -->
                    <div class="flex flex-col justify-between h-36">
                        <div>
                            <p class="font-bold">Mengetahui / Menyetujui:</p>
                            <p>Pejabat Teknis Kegiatan (PTK/PPTK)</p>
                        </div>
                        <div>
                            <p class="font-bold underline uppercase">{{ pptk?.name || '........................................' }}</p>
                            <p class="font-mono text-[11px]">NIP. {{ pptk?.nip || '........................................' }}</p>
                        </div>
                    </div>

                    <!-- Bendahara Pengeluaran -->
                    <div class="flex flex-col justify-between h-36">
                        <div>
                            <p class="font-bold">Lunas Dibayar:</p>
                            <p>Bendahara Pengeluaran</p>
                        </div>
                        <div>
                            <p class="font-bold underline uppercase">{{ treasurer?.name || '........................................' }}</p>
                            <p class="font-mono text-[11px]">NIP. {{ treasurer?.nip || '........................................' }}</p>
                        </div>
                    </div>

                    <!-- Yang Menerima Uang -->
                    <div class="flex flex-col justify-between h-36">
                        <div>
                            <p class="font-bold">Yang Menerima Pembayaran:</p>
                            <p class="text-[11px] text-gray-600">(Penerima / Toko)</p>
                        </div>
                        <div>
                            <p class="font-bold underline uppercase">{{ receipt.recipient_name || '........................................' }}</p>
                            <p class="text-[11px] text-gray-500">Tanda Tangan &amp; Cap</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CATATAN KAKI -->
            <div class="mt-12 pt-2 border-t border-dashed border-gray-300 text-[10px] text-gray-500 flex justify-between">
                <span>Dokumen dicetak dari Sistem Informasi SIPD MWM - Penatausahaan Belanja Kas UP</span>
                <span>ID: {{ receipt.id }} / {{ receipt.receipt_number }}</span>
            </div>
        </div>
    </div>
</template>
