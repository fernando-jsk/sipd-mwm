<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Search, Eye } from '@lucide/vue';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { ref, watch } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

const props = defineProps({
    activities: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const eventFilter = ref(props.filters?.event || 'all');

let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/activity-logs',
            { search: value, event: eventFilter.value === 'all' ? null : eventFilter.value },
            { preserveState: true, replace: true }
        );
    }, 300);
});

watch(eventFilter, (value) => {
    router.get(
        '/activity-logs',
        { search: search.value, event: value === 'all' ? null : value },
        { preserveState: true, replace: true }
    );
});

const isDetailsOpen = ref(false);
const selectedActivity = ref(null);

const viewDetails = (activity) => {
    selectedActivity.value = activity;
    isDetailsOpen.value = true;
};

const getEventType = (activity) => {
    if (activity.event) return activity.event;
    
    const desc = activity.description.toLowerCase();
    if (desc.includes('login') || desc.includes('logged in')) return 'login';
    if (desc.includes('logout') || desc.includes('logged out')) return 'logout';
    if (desc.includes('updated')) return 'updated';
    if (desc.includes('created')) return 'created';
    if (desc.includes('deleted')) return 'deleted';
    
    return 'system';
};
</script>

<template>
    <Head title="Log Aktivitas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col">
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <span class="text-xs text-muted-foreground">Pengaturan</span>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">Log Aktivitas</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
                        Log Aktivitas (Audit Trail)
                    </h2>
                </div>
            </div>
        </template>

        <div class="mb-5 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative flex w-full sm:max-w-md items-center">
                <Search class="absolute left-3 text-muted-foreground size-4" />
                <Input
                    type="text"
                    placeholder="Cari deskripsi aktivitas atau nama aktor..."
                    v-model="search"
                    class="w-full pl-9 shadow-sm bg-white dark:bg-slate-900"
                />
            </div>
            <div class="w-full sm:w-[220px]">
                <Select v-model="eventFilter">
                    <SelectTrigger class="w-full shadow-sm bg-white dark:bg-slate-900">
                        <SelectValue placeholder="Semua Aksi" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="all">Semua Aksi</SelectItem>
                            <SelectItem value="created">Created (Tambah)</SelectItem>
                            <SelectItem value="updated">Updated (Ubah)</SelectItem>
                            <SelectItem value="deleted">Deleted (Hapus)</SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
            <Table class="min-w-full">
                <TableHeader class="bg-muted/40">
                    <TableRow>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Waktu</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Aktor (Pelaku)</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Aksi</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Modul/Target</TableHead>
                        <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Detail</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                                <TableRow v-for="activity in activities.data" :key="activity.id">
                                    <TableCell class="py-3 font-medium text-foreground whitespace-nowrap">
                                        {{ new Date(activity.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }},
                                        <span class="text-xs text-muted-foreground">{{ new Date(activity.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                    </TableCell>
                                    <TableCell class="py-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-semibold text-secondary dark:text-foreground">{{ activity.causer?.name || 'Sistem' }}</span>
                                            <span class="text-xs text-muted-foreground">(@{{ activity.causer?.username || '-' }})</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider gap-1.5"
                                              :class="{
                                                  'bg-[#4ADE80]/10 text-emerald-700 dark:text-emerald-400': getEventType(activity) === 'created',
                                                  'bg-blue-500/10 text-blue-700 dark:text-blue-400': getEventType(activity) === 'updated',
                                                  'bg-destructive/10 text-destructive': getEventType(activity) === 'deleted',
                                                  'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400': getEventType(activity) === 'login',
                                                  'bg-amber-500/10 text-amber-700 dark:text-amber-400': getEventType(activity) === 'logout',
                                                  'bg-muted text-muted-foreground': getEventType(activity) === 'system'
                                              }">
                                            <span class="size-1.5 rounded-full" 
                                                  :class="{
                                                      'bg-[#4ADE80]': getEventType(activity) === 'created',
                                                      'bg-blue-500': getEventType(activity) === 'updated',
                                                      'bg-destructive': getEventType(activity) === 'deleted',
                                                      'bg-indigo-500': getEventType(activity) === 'login',
                                                      'bg-amber-500': getEventType(activity) === 'logout',
                                                      'bg-muted-foreground': getEventType(activity) === 'system'
                                                  }"></span>
                                            {{ getEventType(activity) }}
                                        </span>
                                        <span class="ml-2 text-sm text-muted-foreground">{{ activity.description }}</span>
                                    </TableCell>
                                    <TableCell class="py-3 text-sm text-muted-foreground">
                                        <template v-if="activity.subject_type">
                                            {{ activity.subject_type.split('\\').pop() }} <span class="text-xs">#{{ activity.subject_id }}</span>
                                        </template>
                                        <template v-else>
                                            <span class="text-muted-foreground/50">-</span>
                                        </template>
                                    </TableCell>
                                    <TableCell class="py-3 text-right">
                                        <Button variant="ghost" size="icon" class="size-8 text-muted-foreground hover:text-foreground" @click="viewDetails(activity)" title="Lihat Detail">
                                            <Eye class="size-4" />
                                            <span class="sr-only">Lihat Detail</span>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="activities.data.length === 0">
                                    <TableCell colspan="5" class="h-24 text-center text-muted-foreground text-sm">
                                        Belum ada data log aktivitas.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

        <Dialog :open="isDetailsOpen" @update:open="isDetailsOpen = $event">
            <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-secondary dark:text-foreground">Detail Perubahan (Log ID: {{ selectedActivity?.id }})</DialogTitle>
                    <DialogDescription class="text-muted-foreground">
                        Aktivitas: <strong class="text-secondary dark:text-foreground">{{ selectedActivity?.description }}</strong> oleh <strong class="text-secondary dark:text-foreground">{{ selectedActivity?.causer?.name || 'Sistem' }}</strong>
                    </DialogDescription>
                </DialogHeader>
                
                <div v-if="selectedActivity?.properties" class="mt-4 space-y-4">
                    <!-- Tampilan Data Baru (Attributes) -->
                    <div v-if="selectedActivity.properties.attributes" class="space-y-2">
                        <h4 class="font-semibold text-sm text-emerald-700 dark:text-emerald-400">Data Baru / Setelah Perubahan:</h4>
                        <div class="bg-muted/30 p-4 rounded-lg border border-border/80 text-sm overflow-x-auto text-secondary dark:text-foreground shadow-inner">
                            <pre>{{ JSON.stringify(selectedActivity.properties.attributes, null, 2) }}</pre>
                        </div>
                    </div>
                    
                    <!-- Tampilan Data Lama (Old) -->
                    <div v-if="selectedActivity.properties.old" class="space-y-2">
                        <h4 class="font-semibold text-sm text-destructive">Data Lama / Sebelum Perubahan:</h4>
                        <div class="bg-muted/30 p-4 rounded-lg border border-border/80 text-sm overflow-x-auto text-secondary dark:text-foreground shadow-inner">
                            <pre>{{ JSON.stringify(selectedActivity.properties.old, null, 2) }}</pre>
                        </div>
                    </div>

                    <div v-if="!selectedActivity.properties.attributes && !selectedActivity.properties.old" class="text-sm text-muted-foreground text-center py-4">
                        Tidak ada detail perubahan data yang dicatat untuk aktivitas ini.
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
