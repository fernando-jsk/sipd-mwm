<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { LogOut, Calendar } from '@lucide/vue';
import { ref, computed, onMounted } from 'vue';
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

// Helper: cek apakah user memiliki permission tertentu
const can = (permission) => {
    if (page.props.auth?.roles?.includes('super-admin')) return true;
    return page.props.auth?.permissions?.includes(permission) ?? false;
};

// Computed: visibilitas per section
const showUserManagement = computed(() =>
    can('manage users') || can('manage roles') || can('view activity logs') || can('manage settings')
);
const showMasterData = computed(() => can('view master data') || can('manage master data'));
const showPerencanaan = computed(() => can('view rba') || can('manage rba'));

const changeYear = (year) => {
    router.post('/settings/budget-year', {
        year: year
    }, {
        preserveScroll: true,
        onSuccess: () => {
            activeYear.value = year;
        }
    });
};

const navRef = ref(null);

const handleScroll = (e) => {
    sessionStorage.setItem('sidebar-scroll', e.target.scrollTop);
};

onMounted(() => {
    if (navRef.value) {
        const savedScroll = sessionStorage.getItem('sidebar-scroll');
        if (savedScroll) {
            navRef.value.scrollTop = parseInt(savedScroll, 10);
            
            // Double check inside requestAnimationFrame/timeout to ensure layout has settled
            requestAnimationFrame(() => {
                if (navRef.value) {
                    navRef.value.scrollTop = parseInt(savedScroll, 10);
                }
            });
        }
    }
});
</script>

<template>
    <aside class="w-64 bg-card border-r border-border/80 hidden md:flex flex-col h-full shadow-sm">
        <!-- App Title -->
        <div class="h-16 flex items-center px-6 border-b border-border/80 gap-3">
            <img src="/images/logo-mwm.png" alt="Logo MWM" class="h-8 w-auto shrink-0" />
            <span class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">SIPD MWM</span>
        </div>

        <!-- Navigation -->
        <nav ref="navRef" class="flex-1 overflow-y-auto p-4 space-y-1" @scroll="handleScroll">
            <Link href="/dashboard" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Dashboard
            </Link>

            <!-- Master Data -->
            <template v-if="showMasterData">
                <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                    Master Data
                </div>
                <Link href="/account-codes" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/account-codes') }">
                    Kode Rekening
                </Link>
                <Link href="/vendors" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/vendors') }">
                    Data Rekanan
                </Link>
            </template>

            <!-- Perencanaan -->
            <template v-if="showPerencanaan">
                <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                    Perencanaan
                </div>
                <Link href="/rba/pendapatan" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/rba/pendapatan') }">
                    RBA Pendapatan
                </Link>
                <Link href="/rba/belanja" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/rba/belanja') }">
                    RBA Belanja
                </Link>
            </template>

            <!-- Bendahara -->
            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                Bendahara &amp; Pengeluaran
            </div>
            <Link v-if="can('manage sppd')" href="/expenditures/sppd" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/expenditures/sppd') || $page.url === '/expenditures' }">
                1. Pengajuan SPPD
            </Link>
            <Link v-if="can('authorize opd')" href="/expenditures/opd" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/expenditures/opd') }">
                2. Otorisasi OPD (Direktur)
            </Link>
            <Link v-if="can('disburse spd')" href="/expenditures/spd" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/expenditures/spd') }">
                3. Pencairan SPD (Kabag)
            </Link>

            <!-- Akuntansi (modul belum aktif) -->
            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                Akuntansi
            </div>
            <a href="#" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Jurnal &amp; Laporan
            </a>

            <!-- Pengaturan (hanya super-admin) -->
            <template v-if="showUserManagement">
                <div class="pt-4 pb-2 px-3 text-xs font-semibold text-primary uppercase tracking-wider">
                    Pengaturan
                </div>
                <Link v-if="can('manage users')" href="/users" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/users') }">
                    Manajemen User
                </Link>
                <Link v-if="can('manage roles')" href="/roles" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/roles') }">
                    Manajemen Role
                </Link>
                <Link v-if="can('manage settings')" href="/settings" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/settings') }">
                    Pengaturan Sistem
                </Link>
                <Link v-if="can('view activity logs')" href="/activity-logs" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors" :class="{ 'bg-muted text-foreground': $page.url.startsWith('/activity-logs') }">
                    Log Aktivitas
                </Link>
            </template>
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
                    <p class="text-[11px] text-muted-foreground truncate">{{ $page.props.auth?.user?.username || 'admin' }}</p>
                </div>
            </div>
            <Link href="/logout" method="post" as="button" class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-destructive hover:bg-destructive/10 rounded-md transition-colors">
                <LogOut class="size-4" />
                Log Out
            </Link>
        </div>
    </aside>
</template>
