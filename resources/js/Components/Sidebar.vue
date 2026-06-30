<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, User } from '@lucide/vue';

const page = usePage();
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
                Keuangan
            </div>
            
            <a href="#" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Transaksi
            </a>
            <a href="#" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Laporan
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
            <Link v-if="$page.props.auth.permissions?.includes('view activity logs')" href="/activity-logs" class="block px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                Log Aktivitas
            </Link>
        </nav>

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
