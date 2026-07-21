<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenditures', function (Blueprint $table) {
            // Ubah tipe status menjadi string agar lebih fleksibel untuk status baru
            $table->string('status')->default('draft')->change();
            
            // Tahap OPD (Otorisasi Pengeluaran Direktur)
            $table->string('opd_number')->nullable()->after('status');
            $table->date('opd_date')->nullable()->after('opd_number');
            $table->text('opd_notes')->nullable()->after('opd_date');
            $table->foreignId('opd_authorized_by')->nullable()->after('opd_notes')->constrained('users');
            
            // Tahap SPD (Surat Pencairan Dana - Kabag Keuangan)
            $table->string('spd_number')->nullable()->after('opd_authorized_by');
            $table->date('spd_date')->nullable()->after('spd_number');
            $table->foreignId('spd_disbursed_by')->nullable()->after('spd_date')->constrained('users');
            $table->string('payment_source_bank')->nullable()->after('spd_disbursed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenditures', function (Blueprint $table) {
            $table->dropForeign(['opd_authorized_by']);
            $table->dropForeign(['spd_disbursed_by']);
            $table->dropColumn([
                'opd_number', 'opd_date', 'opd_notes', 'opd_authorized_by',
                'spd_number', 'spd_date', 'spd_disbursed_by', 'payment_source_bank'
            ]);
        });
    }
};
