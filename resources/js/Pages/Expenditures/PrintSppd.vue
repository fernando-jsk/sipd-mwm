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
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const totalAmount = props.expenditure.details?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
const totalTaxes = props.expenditure.taxes?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
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
        <div class="max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl print:shadow-none print:rounded-none print:w-full print:max-w-none">
            <!-- Kop Surat -->
            <div class="border-b-2 border-black pb-4 mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <img src="/images/logo-mwm.png" alt="Logo MWM" class="h-16 w-auto" />
                    <div>
                        <h1 class="text-xl font-bold uppercase tracking-wide">PERUMDA AIR MINUM TIRTA MUBA</h1>
                        <p class="text-xs text-slate-600">Jl. Kolonel Wahid Udin, Sekayu, Musi Banyuasin, Sumatera Selatan</p>
                        <p class="text-xs text-slate-600">Telepon: (0714) 321xxx | Email: info@tirtamuba.co.id</p>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center mb-8">
                <h2 class="text-lg font-bold uppercase underline">SURAT PERMINTAAN PENCAIRAN DANA (SPPD)</h2>
                <p class="text-sm font-mono mt-1">Nomor: {{ expenditure.document_number }}</p>
            </div>

            <!-- Informasi SPPD -->
            <table class="w-full text-sm mb-6 border-collapse">
                <tbody>
                    <tr>
                        <td class="py-1.5 w-40 font-medium">Tanggal SPPD</td>
                        <td class="py-1.5 w-4">:</td>
                        <td class="py-1.5">{{ format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium">Jenis Pengeluaran</td>
                        <td class="py-1.5">:</td>
                        <td class="py-1.5 font-semibold">{{ expenditure.type }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium">Uraian / Keperluan</td>
                        <td class="py-1.5">:</td>
                        <td class="py-1.5">{{ expenditure.description }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium">Metode Pembayaran</td>
                        <td class="py-1.5">:</td>
                        <td class="py-1.5 capitalize">{{ expenditure.payment_method }} <span v-if="expenditure.vendor">({{ expenditure.vendor.name }})</span></td>
                    </tr>
                </tbody>
            </table>

            <!-- Rincian Rekening -->
            <h3 class="font-bold text-sm mb-2 uppercase">Rincian Penggunaan Anggaran:</h3>
            <table class="w-full text-xs border border-black mb-6 border-collapse">
                <thead>
                    <tr class="bg-slate-100 print:bg-slate-200">
                        <th class="border border-black p-2 text-left w-12">No</th>
                        <th class="border border-black p-2 text-left w-36">Kode Rekening</th>
                        <th class="border border-black p-2 text-left">Nama Rekening</th>
                        <th class="border border-black p-2 text-right w-36">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(detail, idx) in expenditure.details" :key="detail.id">
                        <td class="border border-black p-2 text-center">{{ idx + 1 }}</td>
                        <td class="border border-black p-2 font-mono">{{ detail.account_code?.code }}</td>
                        <td class="border border-black p-2">{{ detail.account_code?.name }}</td>
                        <td class="border border-black p-2 text-right font-mono">{{ formatCurrency(detail.amount) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-bold bg-slate-50">
                        <td colspan="3" class="border border-black p-2 text-right">TOTAL PENGELUARAN (KOTOR)</td>
                        <td class="border border-black p-2 text-right font-mono">{{ formatCurrency(totalAmount) }}</td>
                    </tr>
                    <tr v-if="totalTaxes > 0" class="text-slate-700">
                        <td colspan="3" class="border border-black p-2 text-right">POTONGAN PAJAK</td>
                        <td class="border border-black p-2 text-right font-mono text-red-600">-{{ formatCurrency(totalTaxes) }}</td>
                    </tr>
                    <tr v-if="totalTaxes > 0" class="font-bold bg-slate-100">
                        <td colspan="3" class="border border-black p-2 text-right">TOTAL BERSIH (NETTO)</td>
                        <td class="border border-black p-2 text-right font-mono text-emerald-700">{{ formatCurrency(totalAmount - totalTaxes) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Tanda Tangan -->
            <div class="mt-12 grid grid-cols-2 gap-8 text-center text-xs">
                <div>
                    <p class="mb-16">Diajukan Oleh,<br><strong>Bendahara Pengeluaran</strong></p>
                    <p class="font-bold underline">{{ expenditure.treasurer?.name || '( .................................... )' }}</p>
                </div>
                <div>
                    <p class="mb-16">Mengetahui,<br><strong>Pejabat Teknis Kegiatan (PTK)</strong></p>
                    <p class="font-bold underline">{{ expenditure.ptk?.name || '( .................................... )' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
