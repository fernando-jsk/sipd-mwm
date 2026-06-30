<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps({
    activities: Object,
});

const isDetailsOpen = ref(false);
const selectedActivity = ref(null);

const viewDetails = (activity) => {
    selectedActivity.value = activity;
    isDetailsOpen.value = true;
};
</script>

<template>
    <Head title="Log Aktivitas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Log Aktivitas (Audit Trail)
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Waktu</TableHead>
                                    <TableHead>Aktor (Pelaku)</TableHead>
                                    <TableHead>Aksi</TableHead>
                                    <TableHead>Modul/Target</TableHead>
                                    <TableHead class="text-right">Detail</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="activity in activities.data" :key="activity.id">
                                    <TableCell class="font-medium whitespace-nowrap">
                                        {{ new Date(activity.created_at).toLocaleString('id-ID') }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-semibold">{{ activity.causer?.name || 'Sistem' }}</span>
                                            <span class="text-xs text-gray-500">(@{{ activity.causer?.username || '-' }})</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                              :class="{
                                                  'bg-green-50 text-green-700 ring-green-600/20': activity.event === 'created',
                                                  'bg-blue-50 text-blue-700 ring-blue-600/20': activity.event === 'updated',
                                                  'bg-red-50 text-red-700 ring-red-600/20': activity.event === 'deleted',
                                                  'bg-gray-50 text-gray-700 ring-gray-600/20': !['created','updated','deleted'].includes(activity.event)
                                              }">
                                            {{ activity.event }}
                                        </span>
                                        <span class="ml-2 text-sm text-gray-600">{{ activity.description }}</span>
                                    </TableCell>
                                    <TableCell class="text-sm text-gray-500">
                                        {{ activity.subject_type ? activity.subject_type.split('\\').pop() : '-' }} #{{ activity.subject_id || '-' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button variant="outline" size="sm" @click="viewDetails(activity)">Lihat Perubahan</Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="activities.data.length === 0">
                                    <TableCell colspan="5" class="h-24 text-center">
                                        Belum ada data log aktivitas.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </div>

        <Dialog :open="isDetailsOpen" @update:open="isDetailsOpen = $event">
            <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Detail Perubahan (Log ID: {{ selectedActivity?.id }})</DialogTitle>
                    <DialogDescription>
                        Aktivitas: <strong>{{ selectedActivity?.description }}</strong> oleh <strong>{{ selectedActivity?.causer?.name || 'Sistem' }}</strong>
                    </DialogDescription>
                </DialogHeader>
                
                <div v-if="selectedActivity?.properties" class="mt-4 space-y-4">
                    <!-- Tampilan Data Baru (Attributes) -->
                    <div v-if="selectedActivity.properties.attributes" class="space-y-2">
                        <h4 class="font-semibold text-sm text-green-700">Data Baru / Setelah Perubahan:</h4>
                        <div class="bg-gray-50 p-3 rounded border text-sm overflow-x-auto">
                            <pre>{{ JSON.stringify(selectedActivity.properties.attributes, null, 2) }}</pre>
                        </div>
                    </div>
                    
                    <!-- Tampilan Data Lama (Old) -->
                    <div v-if="selectedActivity.properties.old" class="space-y-2">
                        <h4 class="font-semibold text-sm text-red-700">Data Lama / Sebelum Perubahan:</h4>
                        <div class="bg-gray-50 p-3 rounded border text-sm overflow-x-auto">
                            <pre>{{ JSON.stringify(selectedActivity.properties.old, null, 2) }}</pre>
                        </div>
                    </div>

                    <div v-if="!selectedActivity.properties.attributes && !selectedActivity.properties.old" class="text-sm text-gray-500 text-center py-4">
                        Tidak ada detail perubahan data yang dicatat untuk aktivitas ini.
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
