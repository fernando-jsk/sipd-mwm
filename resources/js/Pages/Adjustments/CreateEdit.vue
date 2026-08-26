<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardFooter,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Plus, Trash2, ArrowLeft, Save } from 'lucide-vue-next';

const props = defineProps({
    adjustment: Object,
    accountCodes: Array,
    defaultRef: String
});

const isEdit = !!props.adjustment;

const form = useForm({
    date: props.adjustment?.date || new Date().toISOString().split('T')[0],
    reference_no: props.adjustment?.reference_no || props.defaultRef || '',
    description: props.adjustment?.description || '',
    type: 'adjustment', // Hardcoded
    details: props.adjustment?.details?.length ? props.adjustment.details.map(d => ({
        id: d.id,
        account_code_id: d.account_code_id.toString(),
        description: d.description || '',
        debit: d.debit || 0,
        credit: d.credit || 0
    })) : [
        { account_code_id: '', description: '', debit: 0, credit: 0 },
        { account_code_id: '', description: '', debit: 0, credit: 0 }
    ]
});

const addRow = () => {
    form.details.push({ account_code_id: '', description: '', debit: 0, credit: 0 });
};

const removeRow = (index) => {
    if (form.details.length > 2) {
        form.details.splice(index, 1);
    }
};

const totalDebit = computed(() => {
    return form.details.reduce((sum, row) => sum + (parseFloat(row.debit) || 0), 0);
});

const totalCredit = computed(() => {
    return form.details.reduce((sum, row) => sum + (parseFloat(row.credit) || 0), 0);
});

const difference = computed(() => {
    return Math.abs(totalDebit.value - totalCredit.value);
});

const isBalanced = computed(() => {
    return difference.value === 0 && totalDebit.value > 0;
});

const submit = () => {
    if (!isBalanced.value) {
        alert('Jurnal tidak seimbang! Pastikan Total Debit sama dengan Total Kredit.');
        return;
    }
    
    if (isEdit) {
        form.put(`/adjustments/${props.adjustment.id}`);
    } else {
        form.post('/adjustments');
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Jurnal Penyesuaian' : 'Tambah Jurnal Penyesuaian'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/adjustments">
                    <Button variant="outline" size="icon">
                        <ArrowLeft class="w-4 h-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        {{ isEdit ? 'Edit Jurnal Penyesuaian' : 'Tambah Jurnal Penyesuaian' }}
                    </h2>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Header Jurnal -->
            <Card>
                <CardHeader class="border-b border-border/50">
                    <CardTitle class="text-base">Informasi Utama</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <Label for="date">Tanggal Transaksi <span class="text-destructive">*</span></Label>
                        <Input id="date" type="date" v-model="form.date" required />
                        <p class="text-xs text-destructive" v-if="form.errors.date">{{ form.errors.date }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="reference_no">No. Referensi / Bukti <span class="text-destructive">*</span></Label>
                        <Input id="reference_no" v-model="form.reference_no" required />
                        <p class="text-xs text-destructive" v-if="form.errors.reference_no">{{ form.errors.reference_no }}</p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="description">Keterangan Umum (Penyesuaian)</Label>
                        <Textarea id="description" v-model="form.description" rows="2" placeholder="Catatan transaksi penyesuaian, misal: Beban Penyusutan Kendaraan Dinas..." />
                        <p class="text-xs text-destructive" v-if="form.errors.description">{{ form.errors.description }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Rincian Jurnal -->
            <Card class="overflow-hidden">
                <CardHeader class="border-b border-border/50 flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-base">Rincian Akun (Debit/Kredit)</CardTitle>
                    <Button type="button" variant="outline" size="sm" @click="addRow">
                        <Plus class="w-4 h-4 mr-1" /> Tambah Baris
                    </Button>
                </CardHeader>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground font-semibold text-xs uppercase tracking-wider border-b border-border">
                                <tr>
                                    <th class="p-3 text-left font-medium w-1/3">Kode Rekening <span class="text-destructive">*</span></th>
                                    <th class="p-3 text-left font-medium w-1/4">Uraian</th>
                                    <th class="p-3 text-right font-medium w-1/6">Debit (Rp)</th>
                                    <th class="p-3 text-right font-medium w-1/6">Kredit (Rp)</th>
                                    <th class="p-3 text-center font-medium w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50">
                                <tr v-for="(row, index) in form.details" :key="index">
                                    <td class="p-3 align-top">
                                        <Select v-model="row.account_code_id" required>
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Pilih Akun..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectItem v-for="account in accountCodes" :key="account.id" :value="account.id.toString()">
                                                        {{ account.code }} - {{ account.name }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-destructive mt-1" v-if="form.errors[`details.${index}.account_code_id`]">Wajib diisi</p>
                                    </td>
                                    <td class="p-3 align-top">
                                        <Input v-model="row.description" placeholder="Uraian baris..." />
                                    </td>
                                    <td class="p-3 align-top">
                                        <Input type="number" v-model="row.debit" min="0" step="0.01" class="text-right" @focus="$event.target.select()" />
                                    </td>
                                    <td class="p-3 align-top">
                                        <Input type="number" v-model="row.credit" min="0" step="0.01" class="text-right" @focus="$event.target.select()" />
                                    </td>
                                    <td class="p-3 align-top text-center">
                                        <Button type="button" variant="ghost" size="icon" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50" @click="removeRow(index)" :disabled="form.details.length <= 2">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-muted/30 font-medium">
                                <tr>
                                    <td colspan="2" class="p-4 text-right">TOTAL</td>
                                    <td class="p-4 text-right text-emerald-600">{{ formatCurrency(totalDebit) }}</td>
                                    <td class="p-4 text-right text-emerald-600">{{ formatCurrency(totalCredit) }}</td>
                                    <td></td>
                                </tr>
                                <tr v-if="difference > 0">
                                    <td colspan="2" class="p-4 text-right text-rose-600 font-bold">SELISIH</td>
                                    <td colspan="2" class="p-4 text-center text-rose-600 font-bold bg-rose-50">
                                        {{ formatCurrency(difference) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <CardFooter class="bg-muted/20 border-t border-border flex justify-between mt-auto">
                    <p class="text-sm text-muted-foreground">Pastikan Total Debit dan Kredit seimbang sebelum menyimpan.</p>
                    <Button type="submit" :disabled="form.processing || !isBalanced">
                        <Save class="w-4 h-4 mr-2" />
                        Simpan Jurnal Penyesuaian
                    </Button>
                </CardFooter>
            </Card>
        </form>
    </AuthenticatedLayout>
</template>
