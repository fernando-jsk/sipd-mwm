<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Printer, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    adjustment: Object,
});

const totalDebit = computed(() => {
    return props.adjustment.details.reduce((sum, row) => sum + parseFloat(row.debit), 0);
});

const totalCredit = computed(() => {
    return props.adjustment.details.reduce((sum, row) => sum + parseFloat(row.credit), 0);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
};

const postAdjustment = () => {
    if (confirm('Apakah Anda yakin ingin mem-posting jurnal penyesuaian ini? Setelah di-posting, data tidak dapat diubah lagi.')) {
        router.post(`/adjustments/${props.adjustment.id}/post`, {}, {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head :title="`Detail Penyesuaian - ${adjustment.reference_no}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4">
                    <Link href="/adjustments">
                        <Button variant="outline" size="icon">
                            <ArrowLeft class="w-4 h-4" />
                        </Button>
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight flex items-center gap-2">
                            Detail Jurnal Penyesuaian
                            <span v-if="adjustment.status === 'posted'" class="px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full dark:bg-emerald-900/30 dark:text-emerald-300 flex items-center w-fit">
                                <CheckCircle class="w-3 h-3 mr-1" /> Posted
                            </span>
                            <span v-else class="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/30 dark:text-amber-300">
                                Draft
                            </span>
                        </h2>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="adjustment.status === 'draft'" @click="postAdjustment" variant="default" class="bg-emerald-600 hover:bg-emerald-700">
                        <CheckCircle class="w-4 h-4 mr-2" />
                        Posting Jurnal
                    </Button>
                    <!-- Future enhancement: Print -->
                    <Button variant="outline">
                        <Printer class="w-4 h-4 mr-2" />
                        Cetak Bukti
                    </Button>
                </div>
            </div>
        </template>

        <Card class="max-w-4xl mx-auto">
            <CardHeader class="border-b border-border/50 text-center pb-6 pt-8">
                <h1 class="text-2xl font-bold uppercase tracking-wider text-secondary">BUKTI JURNAL PENYESUAIAN</h1>
            </CardHeader>
            <CardContent class="pt-6">
                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
                    <div>
                        <div class="flex mb-1">
                            <span class="w-32 font-semibold text-muted-foreground">No. Referensi</span>
                            <span class="font-medium">: {{ adjustment.reference_no }}</span>
                        </div>
                        <div class="flex mb-1">
                            <span class="w-32 font-semibold text-muted-foreground">Tanggal</span>
                            <span>: {{ adjustment.date }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex mb-1">
                            <span class="w-32 font-semibold text-muted-foreground">Dibuat Oleh</span>
                            <span>: {{ adjustment.created_by?.name || 'Sistem' }}</span>
                        </div>
                        <div class="flex mb-1">
                            <span class="w-32 font-semibold text-muted-foreground">Keterangan</span>
                            <span>: {{ adjustment.description || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <Card class="p-0 overflow-hidden mt-8 border rounded-md">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted/50 text-muted-foreground font-semibold text-xs uppercase tracking-wider border-b border-border">
                            <tr>
                                <th class="px-4 py-3 text-left">Kode Rekening</th>
                                <th class="px-4 py-3 text-left">Uraian / Akun</th>
                                <th class="px-4 py-3 text-right w-1/4">Debit (Rp)</th>
                                <th class="px-4 py-3 text-right w-1/4">Kredit (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr v-for="detail in adjustment.details" :key="detail.id">
                                <td class="px-4 py-3 align-top">{{ detail.account_code?.code }}</td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium" :class="{'ml-6': detail.credit > 0}">{{ detail.account_code?.name }}</div>
                                    <div v-if="detail.description" class="text-xs text-muted-foreground mt-1" :class="{'ml-6': detail.credit > 0}">{{ detail.description }}</div>
                                </td>
                                <td class="px-4 py-3 align-top text-right">{{ detail.debit > 0 ? formatCurrency(detail.debit) : '-' }}</td>
                                <td class="px-4 py-3 align-top text-right">{{ detail.credit > 0 ? formatCurrency(detail.credit) : '-' }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-muted/50 font-semibold border-t-2 border-border">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-right">TOTAL</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totalDebit) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totalCredit) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
                </Card>
            </CardContent>
        </Card>
    </AuthenticatedLayout>
</template>
