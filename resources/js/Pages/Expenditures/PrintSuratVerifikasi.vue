<script setup>
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Printer, ArrowLeft } from '@lucide/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    expenditure: Object,
    ppk: Object
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
</script>

<template>
    <Head :title="`Cetak Surat Verifikasi: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0 print:m-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground hidden sm:inline">Ukuran Kertas: A4 / F4 (1 Halaman)</span>
                <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                    <Printer class="w-4 h-4 mr-2" /> Cetak Surat Verifikasi
                </Button>
            </div>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-6 sm:p-10 shadow-md rounded-xl font-sans print:shadow-none print:rounded-none print:w-full print:max-w-none print:p-0 print:m-0">
            <div>
                <!-- HEADER -->
                <div class="flex items-center relative mb-3 border-b-2 border-black pb-2">
                    <img src="/images/logo-minahasa-utara.png" alt="Logo Minahasa Utara" class="h-14 w-auto absolute left-0 top-0" />
                    <div class="w-full text-center">
                        <h1 class="text-xs font-bold uppercase tracking-wide">PEMERINTAH KABUPATEN MINAHASA UTARA</h1>
                        <h2 class="text-sm font-bold uppercase tracking-wide">RSUD MARIA WALANDA MARAMIS</h2>
                        <h3 class="text-[11px]">JL. Arnold Mononutu Kelurahan Sarongsong II Kec. Airmadidi 95371</h3>
                        <p class="text-[10px]">Situs Web: rsudmwmaramis.minut.go.id, Email: mwmaramis@gmail.com</p>
                    </div>
                </div>

                <!-- TITLE -->
                <div class="text-center my-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider underline">VERIFIKASI KELENGKAPAN DAN KEABSAHAN</h2>
                </div>

                <!-- IDENTITY SECTION -->
                <div class="text-xs space-y-1.5 mb-6 px-2">
                    <div class="flex">
                        <div class="w-32 font-semibold">Nama</div>
                        <div class="w-4">:</div>
                        <div class="flex-1 font-semibold uppercase">{{ ppk?.name || 'MONALISA F.SUMAMPOUW,SST,M.Kes' }}</div>
                    </div>
                    <div class="flex">
                        <div class="w-32 font-semibold">NIP</div>
                        <div class="w-4">:</div>
                        <div class="flex-1 font-mono">197309152006042007</div>
                    </div>
                    <div class="flex">
                        <div class="w-32 font-semibold">Jabatan</div>
                        <div class="w-4">:</div>
                        <div class="flex-1 font-semibold">PPK SKPD</div>
                    </div>
                </div>

                <!-- STATEMENT BODY -->
                <div class="text-xs space-y-4 text-justify leading-relaxed px-2">
                    <p>
                        Menyatakan dengan sesungguhnya bahwa dokumen dan lampiran Surat Permintaan Pembayaran LS Nomor <strong>{{ expenditure.document_number }}</strong> tanggal <strong>{{ expenditure.date ? format(new Date(expenditure.date), 'dd/MM/yyyy') : '-' }}</strong> telah lengkap dan sah sesuai ketentuan peraturan perundang-undangan. Jika di kemudian hari pernyataan saya ini tidak benar, maka saya bersedia menerima sanksi sesuai peraturan yang berlaku.
                    </p>

                    <p>
                        Demikian surat ini saya buat dalam keadaan sadar dan tanpa paksaan dari pihak manapun.
                    </p>
                </div>
            </div>

            <!-- SIGNATURE SECTION -->
            <div class="flex justify-end text-xs mt-12 px-4 print:break-inside-avoid">
                <div class="w-80 text-center flex flex-col items-center">
                    <p>Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                    <p class="font-bold">PPK RSUD BLUD</p>
                    <div class="h-20"></div>
                    <p class="font-bold underline uppercase">{{ ppk?.name || 'MONALISA F.SUMAMPOUW,SST,M.Kes' }}</p>
                    <p>NIP. 197309152006042007</p>
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
