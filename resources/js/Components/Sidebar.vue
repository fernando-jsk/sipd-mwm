<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <aside class="w-64 bg-white border-r border-gray-100 hidden md:block min-h-screen">
        <div class="h-16 flex items-center justify-center border-b border-gray-100">
            <span class="text-xl font-bold text-gray-800">KeuanganKu</span>
        </div>

        <nav class="p-4 space-y-2">
            <Link href="/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                Dashboard
            </Link>

            <!-- Roles and Permissions can be checked here later to conditionally render links -->
            <div class="pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Keuangan
            </div>
            
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                Transaksi
            </a>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                Laporan
            </a>
            
            <div class="pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Pengaturan
            </div>
            <!-- Idealnya, Anda dapat menambahkan permission check di sini. Karena kita belum share permissions ke frontend, kita akan buat log aktivitas muncul untuk sekarang. Untuk implementasi terbaik, tambahkan v-if. -->
            <Link href="/users" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                Manajemen User
            </Link>
            <Link href="/roles" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                Manajemen Role
            </Link>
            <Link v-if="$page.props.auth.permissions?.includes('view activity logs')" href="/activity-logs" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                Log Aktivitas
            </Link>
        </nav>
    </aside>
</template>
