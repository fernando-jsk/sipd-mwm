<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, User, Calendar } from '@lucide/vue';
import { ref } from 'vue';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

const page = usePage();
const activeYear = ref(page.props.active_budget_year || new Date().getFullYear().toString());

const changeYear = (year) => {
    // Logic to save the selected budget year to backend/session will be handled here
    activeYear.value = year;
    console.log('Tahun Anggaran diubah ke:', year);
};
</script>

<template>
    <aside class="w-64 bg-card border-r border-border/80 hidden md:flex flex-col h-full shadow-sm">
        <!-- App Title -->
        <div class="h-16 flex items-center px-6 border-b border-border/80">
            <span class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">SIPD MWM</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            <Link href="/dashboard" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Dashboard
            </Link>

            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                Master Data
            </div>
            <Link href="/account-codes" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Kode Rekening
            </Link>

            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                Perencanaan
            </div>
            <a href="#" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Rencana Anggaran
            </a>
            
            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                Bendahara
            </div>
            <a href="#" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Penerimaan
            </a>
            <a href="#" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Pengeluaran
            </a>

            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                Akuntansi
            </div>
            <a href="#" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Jurnal & Laporan
            </a>
            
            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                Pengaturan
            </div>
            <Link href="/users" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Manajemen User
            </Link>
            <Link href="/roles" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Manajemen Role
            </Link>
            <Link href="/settings" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Pengaturan Sistem
            </Link>
            <Link v-if="$page.props.auth.permissions?.includes('view activity logs')" href="/activity-logs" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Log Aktivitas
            </Link>
        </nav>

        <!-- Budget Year Selector -->
        <div class="px-4 py-3 border-t border-border/80 bg-background/50">
            <div class="flex items-center gap-1.5 mb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                <Calendar class="w-3.5 h-3.5" />
                <span>Tahun Anggaran</span>
            </div>
            <Select v-model="activeYear" @update:modelValue="changeYear">
                <SelectTrigger class="w-full h-8 text-xs font-medium">
                    <SelectValue placeholder="Pilih Tahun" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem value="2025">Tahun 2025</SelectItem>
                        <SelectItem value="2026">Tahun 2026</SelectItem>
                        <SelectItem value="2027">Tahun 2027</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>

        <!-- User Info & Logout (Footer) -->
        <div class="p-4 border-t border-border/80 bg-muted/20 mt-auto">
            <div class="flex items-center gap-3 mb-3">
                <div class="size-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                    {{ $page.props.auth?.user?.name ? $page.props.auth.user.name.charAt(0).toUpperCase() : 'U' }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-secondary dark:text-foreground truncate">{{ $page.props.auth?.user?.name || 'Administrator' }}</p>
                    <p class="text-[11px] text-muted-foreground truncate">{{ $page.props.auth?.user?.email || 'admin@mwm.go.id' }}</p>
                </div>
            </div>
            <Link href="/logout" method="post" as="button" class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-destructive hover:bg-destructive/10 rounded-md transition-colors">
                <LogOut class="size-4" />
                Log Out
            </Link>
        </div>
    </aside>
</template>
