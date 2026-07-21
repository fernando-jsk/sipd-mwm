<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE expenditures MODIFY type ENUM('UP', 'GU', 'TU', 'LS', 'LS_Pegawai', 'LS_Barang_Jasa_Modal') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE expenditures MODIFY type ENUM('UP', 'GU', 'TU', 'LS') NOT NULL");
    }
};
