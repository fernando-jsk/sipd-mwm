<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import {
    Calendar,
    Search,
    CheckCircle2,
    AlertTriangle,
    SlidersHorizontal,
    Wallet,
    Layers,
    FileSpreadsheet,
    CalendarDays,
} from 'lucide-vue-next';
import RbaCashAllocationModal from '@/Components/RbaCashAllocationModal.vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    budgetYear: {
        type: [String, Number],
        default: () => new Date().getFullYear(),
    },
    currentVersion: {
        type: Number,
        default: 0,
    },
    currentVersionName: {
        type: String,
        default: 'Induk',
    },
    rbaViewType: {
        type: String,
        default: 'gelondongan',
    },
    summary: {
        type: Object,
        default: () => ({
            total_pagu: 0,
            total_kas: 0,
            total_selisih: 0,
            total_rekening: 0,
            rekening_seimbang: 0,
        }),
    },
});

const searchQuery = ref('');
const statusFilter = ref('all'); // 'all', 'balanced', 'unbalanced'
const selectedDocForModal = ref(null);
const isModalOpen = ref(false);

const filteredItems = computed(() => {
    let list = props.items;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        list = list.filter(item =>
            item.account_code.toLowerCase().includes(query) ||
            item.account_name.toLowerCase().includes(query) ||
            (item.pptk_name && item.pptk_name.toLowerCase().includes(query))
        );
    }

    if (statusFilter.value === 'balanced') {
        list = list.filter(item => item.is_balanced);
    } else if (statusFilter.value === 'unbalanced') {
        list = list.filter(item => !item.is_balanced);
    }

    return list;
});

const formatCurrency = (value, hideDecimals = false) => {
    if (value == null || isNaN(value)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: hideDecimals ? 0 : 2,
        maximumFractionDigits: hideDecimals ? 0 : 2,
    }).format(value);
};

const openAllocationModal = (item) => {
    selectedDocForModal.value = item;
    isModalOpen.value = true;
};

const handleViewTypeChange = (type) => {
    router.get(
        route('rba.cash-allocations.index'),
        { rba_view_type: type },
        { preserveState: true, preserveScroll: true }
    );
};

const monthHeaders = [
    { num: 1, label: 'Jan' },
    { num: 2, label: 'Feb' },
    { num: 3, label: 'Mar' },
    { num: 4, label: 'Apr' },
    { num: 5, label: 'Mei' },
    { num: 6, label: 'Jun' },
    { num: 7, label: 'Jul' },
    { num: 8, label: 'Agu' },
    { num: 9, label: 'Sep' },
    { num: 10, label: 'Okt' },
    { num: 11, label: 'Nov' },
    { num: 12, label: 'Des' },
];

const percentAllocated = computed(() => {
    if (!props.summary.total_pagu || props.summary.total_pagu <= 0) return 0;
    return Math.min(100, Math.round((props.summary.total_kas / props.summary.total_pagu) * 100));
});
</script>

<template>
    <Head title="Anggaran Kas Belanja (RAK)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <Breadcrumb class="mb-1 select-none">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child class="text-xs text-muted-foreground">
                                    <Link href="/dashboard">Dashboard</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground font-medium">Perencanaan</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Anggaran Kas Belanja (RAK)</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <div class="flex items-center gap-3">
                        <h2 class="font-semibold text-xl text-secondary dark:text-foreground leading-tight flex items-center gap-2">
                            <CalendarDays class="size-5 text-primary" />
                            Anggaran Kas Belanja (RAK)
                        </h2>
                        <div class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                            Versi: {{ currentVersionName }} (v{{ currentVersion }}) &bull; TA {{ budgetYear }}
                        </div>
                    </div>
                </div>

                <!-- View Type Switcher (Gelondongan vs Rinci) -->
                <div class="flex items-center gap-1.5 p-1 bg-muted/60 rounded-xl border border-border/80 shrink-0">
                    <Button
                        type="button"
                        size="sm"
                        :variant="rbaViewType === 'gelondongan' ? 'default' : 'ghost'"
                        @click="handleViewTypeChange('gelondongan')"
                        class="h-8 text-xs font-semibold px-3 transition-all cursor-pointer"
                        :class="rbaViewType === 'gelondongan' ? 'bg-background text-foreground shadow-2xs' : 'text-muted-foreground hover:text-foreground'"
                    >
                        Pagu Gelondongan
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="rbaViewType === 'rinci' ? 'default' : 'ghost'"
                        @click="handleViewTypeChange('rinci')"
                        class="h-8 text-xs font-semibold px-3 transition-all cursor-pointer"
                        :class="rbaViewType === 'rinci' ? 'bg-background text-foreground shadow-2xs' : 'text-muted-foreground hover:text-foreground'"
                    >
                        Pagu Rinci
                    </Button>
                </div>
            </div>
        </template>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card class="rounded-xl border border-border/80 shadow-2xs">
                <CardHeader class="pb-2">
                    <div class="flex items-center justify-between">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Total Pagu Belanja (RBA)
                        </CardDescription>
                        <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                            <Wallet class="size-4" />
                        </div>
                    </div>
                    <CardTitle class="text-2xl font-bold tracking-tight text-foreground mt-1">
                        {{ formatCurrency(summary.total_pagu, true) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="text-xs text-muted-foreground flex items-center gap-1.5">
                        <span>Jumlah Rekening:</span>
                        <strong class="text-foreground">{{ summary.total_rekening }} Rekening</strong>
                    </div>
                </CardContent>
            </Card>

            <Card class="rounded-xl border border-border/80 shadow-2xs">
                <CardHeader class="pb-2">
                    <div class="flex items-center justify-between">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Kas Teralokasi (12 Bulan)
                        </CardDescription>
                        <div class="size-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                            <CheckCircle2 class="size-4" />
                        </div>
                    </div>
                    <CardTitle class="text-2xl font-bold tracking-tight text-primary mt-1">
                        {{ formatCurrency(summary.total_kas, true) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="pt-0 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Persentase Teralokasi:</span>
                        <span class="font-bold text-foreground">{{ percentAllocated }}%</span>
                    </div>
                    <!-- Mini Progress Bar -->
                    <div class="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                        <div
                            class="bg-primary h-full transition-all duration-300 rounded-full"
                            :style="{ width: `${percentAllocated}%` }"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card class="rounded-xl border border-border/80 shadow-2xs">
                <CardHeader class="pb-2">
                    <div class="flex items-center justify-between">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Keseimbangan Pagu & Kas
                        </CardDescription>
                        <div class="size-8 rounded-lg flex items-center justify-center" :class="summary.total_selisih === 0 && summary.total_pagu > 0 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600'">
                            <SlidersHorizontal class="size-4" />
                        </div>
                    </div>
                    <CardTitle class="text-2xl font-bold tracking-tight mt-1" :class="summary.total_selisih === 0 && summary.total_pagu > 0 ? 'text-emerald-600' : 'text-amber-600'">
                        {{ summary.rekening_seimbang }} / {{ summary.total_rekening }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="text-xs flex items-center gap-1.5">
                        <span v-if="summary.total_selisih === 0 && summary.total_pagu > 0" class="text-emerald-600 font-semibold flex items-center gap-1">
                            <CheckCircle2 class="size-3.5" />
                            Seluruh rekening seimbang 100%
                        </span>
                        <span v-else class="text-amber-600 font-semibold flex items-center gap-1">
                            <AlertTriangle class="size-3.5" />
                            Sisa belum teralokasi: {{ formatCurrency(summary.total_selisih, true) }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filter & Search Toolbar (Compact) -->
        <div class="bg-card rounded-xl border border-border/80 shadow-2xs px-4 py-2.5">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari kode atau nama rekening..."
                        class="pl-9 h-8 text-xs focus-visible:ring-primary"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <span class="text-xs text-muted-foreground font-medium">Filter Status:</span>
                    <Button
                        type="button"
                        size="sm"
                        :variant="statusFilter === 'all' ? 'default' : 'outline'"
                        @click="statusFilter = 'all'"
                        class="h-7 text-xs px-2.5 cursor-pointer"
                    >
                        Semua ({{ items.length }})
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="statusFilter === 'balanced' ? 'default' : 'outline'"
                        @click="statusFilter = 'balanced'"
                        class="h-7 text-xs px-2.5 cursor-pointer"
                    >
                        Seimbang ({{ summary.rekening_seimbang }})
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="statusFilter === 'unbalanced' ? 'default' : 'outline'"
                        @click="statusFilter = 'unbalanced'"
                        class="h-7 text-xs px-2.5 cursor-pointer"
                    >
                        Belum Pas ({{ summary.total_rekening - summary.rekening_seimbang }})
                    </Button>
                </div>
            </div>
        </div>

        <!-- Matriks Spreadsheet Table Card -->
        <Card class="p-0 overflow-hidden rounded-xl border border-border/80 shadow-2xs">
            <div class="overflow-x-auto max-h-[70vh]">
                <Table class="w-full text-xs">
                    <TableHeader class="bg-muted sticky top-0 z-20 shadow-2xs">
                        <TableRow>
                            <TableHead class="w-12 text-center font-bold">No</TableHead>
                            <TableHead class="min-w-44 sticky left-0 z-30 bg-muted font-bold shadow-[4px_0_6px_-2px_rgba(0,0,0,0.05)] border-r border-border/80">
                                Kode Rekening
                            </TableHead>
                            <TableHead class="min-w-64 font-bold">Uraian Rekening</TableHead>
                            <TableHead class="min-w-36 text-right font-bold text-foreground">
                                Pagu RBA
                            </TableHead>
                            
                            <!-- 12 Months Columns -->
                            <TableHead 
                                v-for="m in monthHeaders" 
                                :key="m.num" 
                                class="w-24 text-right font-semibold text-muted-foreground"
                            >
                                {{ m.label }}
                            </TableHead>

                            <TableHead class="min-w-36 text-right font-bold text-primary">
                                Total Kas
                            </TableHead>
                            <TableHead class="w-28 text-center font-bold">Status</TableHead>
                            <TableHead class="w-24 text-center font-bold sticky right-0 z-30 bg-muted shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] border-l border-border/80">
                                Aksi
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow 
                            v-for="(item, idx) in filteredItems" 
                            :key="item.id"
                            class="hover:bg-muted/30 transition-colors group"
                        >
                            <TableCell class="text-center text-muted-foreground">{{ idx + 1 }}</TableCell>
                            
                            <!-- Sticky Left Column: Kode Rekening (Solid Opaque) -->
                            <TableCell class="font-mono font-semibold text-foreground sticky left-0 z-10 bg-card shadow-[4px_0_6px_-2px_rgba(0,0,0,0.05)] border-r border-border/80">
                                {{ item.account_code }}
                            </TableCell>

                            <TableCell class="max-w-xs truncate" :title="item.account_name">
                                <div class="font-medium text-foreground truncate">{{ item.account_name }}</div>
                                <div v-if="item.pptk_name" class="text-[11px] text-muted-foreground truncate">
                                    PPTK: {{ item.pptk_name }}
                                </div>
                            </TableCell>

                            <TableCell class="text-right font-semibold text-foreground font-mono">
                                {{ formatCurrency(item.total_budget) }}
                            </TableCell>

                            <!-- 12 Months Numbers -->
                            <TableCell 
                                v-for="m in monthHeaders" 
                                :key="m.num" 
                                class="text-right font-mono text-[11px]"
                                :class="item.months[m.num] > 0 ? 'text-foreground' : 'text-muted-foreground/40'"
                            >
                                {{ item.months[m.num] > 0 ? formatCurrency(item.months[m.num]) : '-' }}
                            </TableCell>

                            <TableCell class="text-right font-mono font-bold text-primary">
                                {{ formatCurrency(item.total_cash) }}
                            </TableCell>

                            <TableCell class="text-center">
                                <Badge 
                                    v-if="item.is_balanced" 
                                    variant="outline" 
                                    class="bg-emerald-500/10 text-emerald-600 border-emerald-500/20 text-[10px] font-semibold py-0.5"
                                >
                                    Seimbang
                                </Badge>
                                <Badge 
                                    v-else-if="item.difference > 0" 
                                    variant="outline" 
                                    class="bg-amber-500/10 text-amber-600 border-amber-500/20 text-[10px] font-semibold py-0.5"
                                    :title="`Kurang ${formatCurrency(item.difference)}`"
                                >
                                    -{{ formatCurrency(item.difference) }}
                                </Badge>
                                <Badge 
                                    v-else 
                                    variant="destructive" 
                                    class="text-[10px] font-semibold py-0.5"
                                    :title="`Lebih ${formatCurrency(Math.abs(item.difference))}`"
                                >
                                    +{{ formatCurrency(Math.abs(item.difference)) }}
                                </Badge>
                            </TableCell>

                            <!-- Sticky Right Column: Aksi (Solid Opaque) -->
                            <TableCell class="text-center sticky right-0 z-10 bg-card shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] border-l border-border/80">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="openAllocationModal(item)"
                                    class="h-7 text-xs font-medium px-2.5 cursor-pointer bg-card hover:bg-muted hover:text-foreground transition-colors shadow-2xs"
                                >
                                    Atur Kas
                                </Button>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="filteredItems.length === 0">
                            <TableCell colspan="18" class="text-center py-12 text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <FileSpreadsheet class="size-8 text-muted-foreground/40" />
                                    <p class="font-medium text-sm">Tidak ada rekening belanja yang ditemukan.</p>
                                    <p class="text-xs">Pastikan dokumen RBA belanja sudah dibuat untuk tahun anggaran aktif.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </Card>

        <!-- Edit Modal Component -->
        <RbaCashAllocationModal
            v-if="selectedDocForModal"
            v-model:open="isModalOpen"
            :rba-document="selectedDocForModal"
            @saved="router.reload({ preserveScroll: true })"
        />
    </AuthenticatedLayout>
</template>
