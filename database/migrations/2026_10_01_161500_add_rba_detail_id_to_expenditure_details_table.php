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
        Schema::table('expenditure_details', function (Blueprint $table) {
            $table->foreignId('rba_detail_id')
                ->nullable()
                ->after('account_code_id')
                ->constrained('rba_details')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenditure_details', function (Blueprint $table) {
            $table->dropForeign(['rba_detail_id']);
            $table->dropColumn('rba_detail_id');
        });
    }
};
