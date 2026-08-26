<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Plus, Search, Eye, Edit, Trash2, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    adjustments: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

let debounceTimeout;
const fetchAdjustments = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        router.get('/adjustments', {
            search: search.value,
            status: status.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch([search, status], () => {
    fetchAdjustments();
});

const deleteAdjustment = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus jurnal penyesuaian ini?')) {
        router.delete(`/adjustments/${id}`);
    }
};
</script>

<template>
    <Head title="Jurnal Penyesuaian" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight">
                        Jurnal Penyesuaian
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Kelola pencatatan akrual (penyusutan, persediaan, dll) pada akhir periode.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/adjustments/create">
                        <Button variant="default" size="sm">
                            <Plus class="w-4 h-4 mr-2" />
                            Tambah Penyesuaian
                        </Button>
                    </Link>
                </div>
            </div>
        </template>

        <Card>
            <CardHeader class="border-b border-border/50">
                <CardTitle class="text-sm font-semibold flex items-center gap-2">
                    <Search class="w-4 h-4 text-muted-foreground" />
                    Filter Data
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="w-full md:w-1/2">
                        <Input v-model="search" placeholder="Cari No. Referensi atau Keterangan..." class="w-full" />
                    </div>
                    <div class="w-full md:w-1/4">
                        <Select v-model="status">
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="posted">Posted</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class="mt-4 p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-muted/50 text-muted-foreground font-semibold text-xs uppercase tracking-wider border-b border-border">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">No. Referensi</th>
                            <th class="px-4 py-3 font-medium">Keterangan</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!adjustments.data.length" class="border-b border-border/50">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                Tidak ada data jurnal penyesuaian ditemukan.
                            </td>
                        </tr>
                        <tr v-for="adjustment in adjustments.data" :key="adjustment.id" class="border-b border-border/50 hover:bg-muted/20">
                            <td class="px-4 py-3">{{ adjustment.date }}</td>
                            <td class="px-4 py-3 font-medium">{{ adjustment.reference_no }}</td>
                            <td class="px-4 py-3 truncate max-w-xs">{{ adjustment.description || '-' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="adjustment.status === 'posted'" class="px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full dark:bg-emerald-900/30 dark:text-emerald-300 flex items-center w-fit">
                                    <CheckCircle class="w-3 h-3 mr-1" /> Posted
                                </span>
                                <span v-else class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/30 dark:text-amber-300">
                                    Draft
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/adjustments/${adjustment.id}`">
                                        <Button variant="outline" size="icon" class="h-8 w-8 text-blue-600">
                                            <Eye class="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <template v-if="adjustment.status === 'draft'">
                                        <Link :href="`/adjustments/${adjustment.id}/edit`">
                                            <Button variant="outline" size="icon" class="h-8 w-8 text-amber-600">
                                                <Edit class="w-4 h-4" />
                                            </Button>
                                        </Link>
                                        <Button variant="outline" size="icon" class="h-8 w-8 text-rose-600" @click="deleteAdjustment(adjustment.id)">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-4 py-3 flex items-center justify-between border-t border-border/50 bg-muted/10">
                <div class="text-sm text-muted-foreground">
                    Menampilkan <span class="font-medium text-foreground">{{ adjustments.from || 0 }}</span> sampai <span class="font-medium text-foreground">{{ adjustments.to || 0 }}</span> dari <span class="font-medium text-foreground">{{ adjustments.total }}</span> hasil
                </div>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, index) in adjustments.links"
                        :key="index"
                        :href="link.url || '#'"
                        class="px-3 py-1 text-sm border rounded-md transition-colors"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted text-muted-foreground',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    ></Link>
                </div>
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
