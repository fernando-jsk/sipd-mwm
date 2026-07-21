<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================
        // CLEANUP: Hapus semua data lama agar fresh & ID kembali ke 1
        // =====================================================
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('users')->truncate();
        
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // =====================================================
        // DEFINE PERMISSIONS (sesuai modul yang sudah aktif)
        // =====================================================

        // Kelompok: Manajemen User & Sistem
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage roles']);
        Permission::create(['name' => 'view activity logs']);
        Permission::create(['name' => 'manage settings']);

        // Kelompok: Master Data
        Permission::create(['name' => 'view master data']);
        Permission::create(['name' => 'manage master data']);

        // Kelompok: Perencanaan
        Permission::create(['name' => 'view rba']);
        Permission::create(['name' => 'manage rba']);
        Permission::create(['name' => 'manage budget revision']);

        // Kelompok: Bendahara & Pengeluaran (SPPD -> OPD -> SPD)
        Permission::create(['name' => 'manage sppd']);
        Permission::create(['name' => 'authorize opd']);
        Permission::create(['name' => 'disburse spd']);

        // =====================================================
        // DEFINE ROLES
        // =====================================================

        // 1. Super Admin — akses penuh ke semua fitur (Via Gate::before)
        $superAdmin = Role::create(['name' => 'super-admin']);

        // 2. Tim Perencanaan — akses ke Master Data & Perencanaan RBA
        $timPerencanaan = Role::create(['name' => 'tim-perencanaan']);
        $timPerencanaan->syncPermissions([
            'view master data',
            'manage master data',
            'view rba',
            'manage rba',
        ]);

        // 3. Bendahara — pengajuan SPPD
        $bendaharaRole = Role::create(['name' => 'bendahara']);
        $bendaharaRole->syncPermissions([
            'view master data',
            'view rba',
            'manage sppd',
        ]);

        // 4. Direktur — otorisasi OPD
        $direkturRole = Role::create(['name' => 'direktur']);
        $direkturRole->syncPermissions([
            'view master data',
            'view rba',
            'authorize opd',
        ]);

        // 5. Kabag Keuangan — verifikasi & pencairan SPD
        $kabagRole = Role::create(['name' => 'kabag-keuangan']);
        $kabagRole->syncPermissions([
            'view master data',
            'view rba',
            'disburse spd',
        ]);

        // 6. Viewer — hanya dapat melihat data (read-only)
        $viewer = Role::create(['name' => 'viewer']);
        $viewer->syncPermissions([
            'view master data',
            'view rba',
        ]);

        // =====================================================
        // DEMO USERS (untuk keperluan testing & development)
        // =====================================================

        // User 1: Super Administrator
        $admin = User::create([
            'name'     => 'Super Administrator',
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('super-admin');

        // User 2: Tim Perencanaan
        $perencana = User::create([
            'name'     => 'Tim Perencanaan',
            'username' => 'perencana',
            'password' => Hash::make('password'),
        ]);
        $perencana->assignRole('tim-perencanaan');

        // User 3: Bendahara
        $bendaharaUser = User::create([
            'name'     => 'Bendahara Pengeluaran',
            'username' => 'bendahara',
            'password' => Hash::make('password'),
        ]);
        $bendaharaUser->assignRole('bendahara');

        // User 4: Direktur
        $direksi = User::create([
            'name'     => 'Direktur Utama',
            'username' => 'direksi',
            'password' => Hash::make('password'),
        ]);
        $direksi->assignRole('direktur');

        // User 5: Kabag Keuangan
        $kabagUser = User::create([
            'name'     => 'Kabag Keuangan',
            'username' => 'kabag',
            'password' => Hash::make('password'),
        ]);
        $kabagUser->assignRole('kabag-keuangan');
    }
}
