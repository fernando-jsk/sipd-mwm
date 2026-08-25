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
        Schema::table('receipt_types', function (Blueprint $table) {
            $table->foreignId('account_code_id')->nullable()->after('is_active')->constrained('account_codes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_types', function (Blueprint $table) {
            $table->dropForeign(['account_code_id']);
            $table->dropColumn('account_code_id');
        });
    }
};
