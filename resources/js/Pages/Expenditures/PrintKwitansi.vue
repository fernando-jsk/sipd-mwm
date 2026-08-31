<script setup>
import { computed } from 'vue';
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

const totalAmount = computed(() => {
    return props.expenditure.details?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
});

const totalTaxes = computed(() => {
    return props.expenditure.taxes?.reduce((sum, item) => sum + Number(item.amount || 0), 0) || 0;
});

const netAmount = computed(() => {
    return totalAmount.value - totalTaxes.value;
});

const ppnAmount = computed(() => {
    return props.expenditure.taxes?.filter(t => t.tax_type?.toLowerCase().includes('ppn'))
        .reduce((sum, t) => sum + Number(t.amount || 0), 0) || 0;
});

const pph21Amount = computed(() => {
    return props.expenditure.taxes?.filter(t => t.tax_type?.toLowerCase().includes('21'))
        .reduce((sum, t) => sum + Number(t.amount || 0), 0) || 0;
});

const pph22Amount = computed(() => {
    return props.expenditure.taxes?.filter(t => t.tax_type?.toLowerCase().includes('22'))
        .reduce((sum, t) => sum + Number(t.amount || 0), 0) || 0;
});

const pph23Amount = computed(() => {
    return props.expenditure.taxes?.filter(t => t.tax_type?.toLowerCase().includes('23'))
        .reduce((sum, t) => sum + Number(t.amount || 0), 0) || 0;
});

const accountCodes = computed(() => {
    const codes = props.expenditure.details?.map(d => d.account_code?.code).filter(Boolean) || [];
    return [...new Set(codes)];
});

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
    <Head :title="`Cetak Kwitansi: ${expenditure.document_number}`" />

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 print:bg-white print:p-0 print:m-0">
        <!-- Floating Toolbar (Hide on Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center print:hidden">
            <Button variant="outline" size="sm" @click="goBack">
                <ArrowLeft class="w-4 h-4 mr-2" /> Kembali
            </Button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground hidden sm:inline">Ukuran Kertas: A4 / F4 (1 Halaman)</span>
                <Button size="sm" @click="printPage" class="bg-primary text-primary-foreground">
                    <Printer class="w-4 h-4 mr-2" /> Cetak Kwitansi
                </Button>
            </div>
        </div>

        <!-- Printable Document Canvas -->
        <div class="max-w-4xl mx-auto bg-white text-black p-6 sm:p-10 shadow-md rounded-xl font-sans print:shadow-none print:rounded-none print:w-full print:max-w-none print:p-0 print:m-0">
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
            <div class="text-center my-4">
                <h2 class="text-xs font-bold uppercase underline tracking-wider">KWITANSI</h2>
                <p class="text-[10px] font-mono mt-0.5">
                    No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/KWT /1.02.99/ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/{{ expenditure.date ? new Date(expenditure.date).getFullYear() : '-' }}
                </p>
            </div>

            <!-- RECEIPT DETAILS -->
            <div class="text-[10.5px] space-y-2 mb-6 px-2 leading-relaxed">
                <div class="flex items-baseline">
                    <div class="w-36 font-normal">Sudah Terima Dari</div>
                    <div class="w-4 text-center">:</div>
                    <div class="flex-1 font-bold uppercase">BENDAHARA PENGELUARAN BLUD RSUD M.W. MARAMIS</div>
                </div>

                <div class="flex items-baseline">
                    <div class="w-36 font-normal">Sejumlah Uang</div>
                    <div class="w-4 text-center">:</div>
                    <div class="flex-1 font-bold font-mono text-xs">
                        Rp {{ formatCurrency(totalAmount) }}
                    </div>
                </div>

                <div class="flex items-baseline">
                    <div class="w-36 font-normal">Terbilang</div>
                    <div class="w-4 text-center">:</div>
                    <div class="flex-1 italic capitalize text-[10px]">
                        {{ terbilang(totalAmount) }} Rupiah
                    </div>
                </div>

                <div class="flex items-baseline">
                    <div class="w-36 font-normal">Untuk Keperluan</div>
                    <div class="w-4 text-center">:</div>
                    <div class="flex-1 leading-normal">
                        {{ expenditure.description || '-' }}
                    </div>
                </div>
            </div>

            <!-- MIDDLE SIGNATURES -->
            <div class="flex justify-between text-[10px] my-6 px-6 print:break-inside-avoid">
                <div class="w-64 text-center flex flex-col items-center">
                    <p>Mengetahui,</p>
                    <p class="font-bold">Direktur RSUD M.W.Maramis</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline uppercase">{{ expenditure.kpa?.name || 'dr. ALAIN VINCENT BEYAH' }}</p>
                    <p>NIP. {{ expenditure.kpa?.nip || '198201292009031001' }}</p>
                </div>

                <div class="w-64 text-center flex flex-col items-center">
                    <p>Airmadidi, {{ expenditure.date ? format(new Date(expenditure.date), 'dd MMMM yyyy', { locale: id }) : '-' }}</p>
                    <p class="font-bold">Yang Menerima</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline uppercase">{{ expenditure.vendor?.director_name || expenditure.vendor?.name || 'DEWI SARTIKA GOSAL, Amd.Kep' }}</p>
                </div>
            </div>

            <!-- BOTTOM TABLE -->
            <div class="border border-black text-[10px] mt-6 print:break-inside-avoid">
                <div class="grid grid-cols-4 border-b border-black font-bold text-center bg-gray-50/50">
                    <div class="p-1.5 border-r border-black">Bendahara Pengeluaran BLUD</div>
                    <div class="p-1.5 border-r border-black">PPTK</div>
                    <div class="p-1.5 border-r border-black">Kode Rekening</div>
                    <div class="p-1.5 text-left pl-3">Rincian Perhitungan</div>
                </div>

                <div class="grid grid-cols-4 min-h-[110px]">
                    <!-- Col 1: Bendahara -->
                    <div class="p-1.5 border-r border-black flex flex-col justify-end text-center">
                        <p class="font-bold underline uppercase">{{ expenditure.treasurer?.name || 'SASKIA PARASO, SKM' }}</p>
                        <p>NIP. {{ expenditure.treasurer?.nip || '199810142022032012' }}</p>
                    </div>

                    <!-- Col 2: PPTK -->
                    <div class="p-1.5 border-r border-black flex flex-col justify-end text-center">
                        <p class="font-bold underline uppercase">{{ expenditure.ptk?.name || 'STEVY ROTIKAN, Amd.Farm' }}</p>
                        <p>NIP. {{ expenditure.ptk?.nip || '197909122008021001' }}</p>
                    </div>

                    <!-- Col 3: Kode Rekening -->
                    <div class="p-1.5 border-r border-black flex items-center justify-center text-center font-mono text-[9.5px]">
                        <div>
                            <div v-for="code in accountCodes" :key="code">
                                {{ code }}
                            </div>
                            <div v-if="accountCodes.length === 0">-</div>
                        </div>
                    </div>

                    <!-- Col 4: Tax Calculation Breakdown -->
                    <div class="p-1.5 space-y-0.5 font-mono text-[9.5px]">
                        <div class="flex justify-between border-b pb-0.5 font-bold">
                            <span>Total :</span>
                            <span>Rp{{ formatCurrency(totalAmount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>PPn :</span>
                            <span>Rp{{ formatCurrency(ppnAmount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>PPh 21 :</span>
                            <span>Rp{{ formatCurrency(pph21Amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>PPh 22 :</span>
                            <span>Rp{{ formatCurrency(pph22Amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>PPh 23 :</span>
                            <span>Rp{{ formatCurrency(pph23Amount) }}</span>
                        </div>
                        <div class="flex justify-between border-t pt-0.5 font-bold">
                            <span>Jmlh Pajak :</span>
                            <span>Rp{{ formatCurrency(totalTaxes) }}</span>
                        </div>
                        <div class="flex justify-between border-t pt-0.5 font-bold text-[10px]">
                            <span>Jmlh Bersih :</span>
                            <span>Rp{{ formatCurrency(netAmount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: auto;
        margin: 6mm 10mm;
    }
    html, body {
        background-color: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
