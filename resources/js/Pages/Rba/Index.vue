<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogTrigger } from '@/Components/ui/dialog';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { ArrowRight, FolderOpen, Plus, Search } from 'lucide-vue-next';
import AccountTreeRow from '@/Components/AccountTreeRow.vue';

const props = defineProps({
    activeTree: Array,
    leafAccounts: Array,
});

const isAddDialogOpen = ref(false);
const searchQuery = ref('');

const filteredLeaves = computed(() => {
    if (!searchQuery.value) return props.leafAccounts;
    const lowerQuery = searchQuery.value.toLowerCase();
    return props.leafAccounts.filter(acc => 
        acc.code.toLowerCase().includes(lowerQuery) || 
        acc.name.toLowerCase().includes(lowerQuery)
    );
});

const createDocument = (accountCodeId) => {
    router.post('/rba/documents', { account_code_id: accountCodeId }, {
        onSuccess: () => {
            isAddDialogOpen.value = false;
            searchQuery.value = '';
        }
    });
};
</script>

<template>
    <Head title="Kertas Kerja RBA" />

    <AuthenticatedLayout>
        <template #header>
            <div class="w-full flex justify-between items-center">
                <div>
                    <Breadcrumb class="mb-1">
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child class="text-xs text-muted-foreground">
                                    <span class="cursor-default">Perencanaan</span>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage class="text-xs">RBA</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                    <h2 class="font-semibold text-xl text-secondary leading-tight">Perencanaan RBA</h2>
                </div>
                <Dialog v-model:open="isAddDialogOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="w-4 h-4 mr-2" />
                            Tambah Rekening RBA
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[600px] max-h-[85vh] flex flex-col">
                        <DialogHeader>
                            <DialogTitle>Tambah Rekening ke Kertas Kerja</DialogTitle>
                            <DialogDescription>
                                Cari dan pilih rekening rincian (daun) yang akan disusun anggarannya.
                            </DialogDescription>
                        </DialogHeader>
                        
                        <div class="relative mt-2">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input v-model="searchQuery" placeholder="Cari kode atau nama rekening..." class="pl-9" />
                        </div>

                        <div class="flex-1 overflow-y-auto mt-4 border rounded-md min-h-[300px]">
                            <Table>
                                <TableHeader class="sticky top-0 bg-background z-10 shadow-sm">
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama Rekening</TableHead>
                                        <TableHead class="w-[100px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="acc in filteredLeaves.slice(0, 100)" :key="acc.id">
                                        <TableCell class="font-medium">{{ acc.code }}</TableCell>
                                        <TableCell>{{ acc.name }}</TableCell>
                                        <TableCell class="text-right">
                                            <Button @click="createDocument(acc.id)" variant="outline" size="sm">
                                                Tambah
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="filteredLeaves.length === 0">
                                        <TableCell colspan="3" class="h-24 text-center text-muted-foreground">
                                            Tidak ada rekening yang cocok dengan pencarian.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-else-if="filteredLeaves.length > 100">
                                        <TableCell colspan="3" class="text-center text-xs text-muted-foreground py-2 bg-muted/30">
                                            Menampilkan 100 dari {{ filteredLeaves.length }} hasil. Ketik lebih spesifik untuk menyaring.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FolderOpen class="w-5 h-5" /> Dokumen RBA Aktif
                    </CardTitle>
                    <CardDescription>
                        Daftar rekening yang telah dianggarkan pada tahun aktif. Klik tombol Tambah di pojok kanan atas untuk memasukkan rekening baru.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[250px]">Kode</TableHead>
                                    <TableHead>Nama Rekening</TableHead>
                                    <TableHead class="text-right w-[200px]">Total Anggaran</TableHead>
                                    <TableHead class="text-right w-[150px]">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="activeTree.length > 0">
                                    <AccountTreeRow 
                                        v-for="node in activeTree" 
                                        :key="node.id" 
                                        :row="node" 
                                        :level="0" 
                                    />
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="4" class="h-32 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center justify-center">
                                            <FolderOpen class="w-10 h-10 mb-2 text-muted-foreground/50" />
                                            <p>Belum ada dokumen RBA yang disusun di tahun ini.</p>
                                            <p class="text-sm">Klik "Tambah Rekening RBA" untuk memulai.</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
