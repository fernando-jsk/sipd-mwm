<script setup>
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Printer, ArrowLeft } from '@lucide/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    expenditure: Object,
    sppList: Array,
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

</script>

<template>
    <Head :title="`Cetak Ringkasan Kegiatan: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                <Printer class="w-4 h-4 mr-2" /> Cetak Ringkasan
            </Button>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-8 sm:p-12 shadow-md rounded-xl print:shadow-none print:rounded-none print:w-full print:max-w-none font-sans">
            
            <!-- HEADER -->
            <div class="flex items-center relative mb-4 border-b-2 border-black pb-4">
                <img src="/images/logo-mwm.png" alt="Logo" class="h-20 w-auto absolute left-0 top-0" />
                <div class="w-full text-center">
                    <h1 class="text-base font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                    <h2 class="text-lg font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                    <h3 class="text-sm">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                    <p class="text-sm">Website: rsudmwmaramis.com, Email: mwmaramis@gmail.com</p>
                </div>
            </div>

            <!-- TITLE -->
            <div class="text-center mb-4">
                <h2 class="text-sm font-bold uppercase">SURAT PERMINTAAN PEMBAYARAN LANGSUNG BARANG DAN JASA</h2>
                <h3 class="text-sm font-bold uppercase mb-2">(SPP-LS BARANG DAN JASA)</h3>
                <p class="text-sm font-bold">Nomor : {{ expenditure.document_number }}</p>
                <p class="text-sm font-bold mt-2">RINGKASAN</p>
            </div>

            <!-- TABLE 1: RINGKASAN KEGIATAN -->
            <table class="w-full text-[11px] border-collapse border border-black mb-4">
                <tbody>
                    <tr>
                        <td colspan="3" class="border-b border-black font-bold text-center py-1 bg-gray-50">RINGKASAN KEGIATAN</td>
                    </tr>
                    <tr>
                        <td class="w-72 px-2 py-1 align-top border-r-0">1. Program</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">Upaya Kegiatan Masyarakat</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">2. Nama Kegiatan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">Peningkatan mutu pelayanan BLUD</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">3. Nomor dan Tanggal DPA-/DPPA-/DPAL-SKPD</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">-</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">4. Nama Perusahaan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">{{ expenditure.vendor?.name || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">5. Bentuk Perusahaan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">PT(Perseroan Terbatas)</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">6. Alamat Perusahaan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">{{ expenditure.vendor?.address || '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-2 py-1 align-top border-r-0 text-transparent">.</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">7. Nama Pimpinan Perusahaan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">{{ expenditure.vendor?.director_name || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">8. Nama dan No. Rekening Bank</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">{{ expenditure.bank_name ? (expenditure.bank_name + ' / ' + expenditure.bank_account_number) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">9. No. Dan Tanggal Kontrak</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">{{ expenditure.contract_number || '-' }}</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">10. Kegiatan Lanjutan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">-</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0">11. Waktu Pelaksanaan Kegiatan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top">{{ expenditure.activity_date ? format(new Date(expenditure.activity_date), 'dd MMMM yyyy', { locale: id }) : '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-2 py-1 align-top border-r-0 text-transparent">.</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 align-top border-r-0 pb-4">13. Deskripsi Pekerjaan</td>
                        <td class="w-4 py-1 align-top text-center">:</td>
                        <td class="px-2 py-1 align-top pb-4">{{ expenditure.description || '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border-t border-black font-bold text-center py-1 bg-gray-50">RINGKASAN DPA-SKPD/DPPA-SKPD/DPAL-SKPD</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="border-t border-black px-2 py-1">Jumlah Dana DPA-SKPD/DPPA-SKPD/DPAL-SKPD</td>
                        <td class="border-t border-black px-2 py-1 text-right font-mono">{{ formatCurrency(totalDpa || 0) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- TABLE 2: RINGKASAN SPP -->
            <table class="w-full text-[11px] border-collapse border border-black mb-8">
                <thead>
                    <tr>
                        <th colspan="4" class="border border-black font-bold text-center py-1 bg-gray-50">RINGKASAN SPP</th>
                    </tr>
                    <tr>
                        <th class="border border-black px-2 py-1 text-center font-bold w-16">No. Urut</th>
                        <th class="border border-black px-2 py-1 text-center font-bold">Nomor SPP</th>
                        <th class="border border-black px-2 py-1 text-center font-bold w-32">Tanggal SPP</th>
                        <th class="border border-black px-2 py-1 text-center font-bold w-48">Jumlah Dana</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(spp, idx) in sppList" :key="spp.id">
                        <td class="border border-black px-2 py-1 text-center font-bold">{{ idx + 1 }}</td>
                        <td class="border border-black px-2 py-1">{{ spp.document_number }}</td>
                        <td class="border border-black px-2 py-1 text-center">{{ spp.date ? format(new Date(spp.date), 'dd/MM/yyyy', { locale: id }) : '-' }}</td>
                        <td class="border border-black px-2 py-1 text-right font-bold font-mono">Rp{{ formatCurrency(spp.total_amount) }}</td>
                    </tr>
                    <tr v-if="sppList.length === 0">
                        <td colspan="4" class="border border-black px-2 py-4 text-center italic text-gray-500">Belum ada data SPP</td>
                    </tr>
                </tbody>
            </table>

            <!-- Signatures -->
            <div class="flex justify-between text-[11px] mt-12 px-8">
                <div class="w-64 flex flex-col items-center">
                    <p class="mb-16">PEJABAT PELAKSANA TEKNIS KEGIATAN</p>
                    <p class="font-bold underline uppercase">{{ expenditure.ptk?.name || 'STEVY ROTIKAN, Amd.Farm' }}</p>
                    <p>NIP. {{ expenditure.ptk?.nip || '197909122008021001' }}</p>
                </div>
                <div class="w-64 flex flex-col items-center">
                    <p class="mb-1">Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                    <p class="mb-16">BENDAHARA PENGELUARAN</p>
                    <p class="font-bold underline uppercase">{{ expenditure.treasurer?.name || 'SASKIA PARASO, SKM' }}</p>
                    <p>NIP. {{ expenditure.treasurer?.nip || '199810142022032012' }}</p>
                </div>
            </div>
            
        </div>
    </div>
</template>
