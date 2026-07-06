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

        // =====================================================
        // DEFINE ROLES
        // =====================================================

        // 1. Super Admin — akses penuh ke semua fitur (Via Gate::before)
        $superAdmin = Role::create(['name' => 'super-admin']);
        // super-admin tidak perlu syncPermissions lagi karena sudah bypass via Gate::before

        // 2. Tim Perencanaan — akses ke Master Data & Perencanaan RBA
        $timPerencanaan = Role::create(['name' => 'tim-perencanaan']);
        $timPerencanaan->syncPermissions([
            'view master data',
            'manage master data',
            'view rba',
            'manage rba',
        ]);

        // 3. Viewer — hanya dapat melihat data (read-only)
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

        // User 3: Viewer / Direksi
        $direksi = User::create([
            'name'     => 'Direktur',
            'username' => 'direksi',
            'password' => Hash::make('password'),
        ]);
        $direksi->assignRole('viewer');
    }
}
