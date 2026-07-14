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
        Schema::table('rba_documents', function (Blueprint $table) {
            $table->foreignId('funding_source_id')->nullable()->after('account_code_id')->constrained('funding_sources')->restrictOnDelete();
            $table->foreignId('pptk_id')->nullable()->after('funding_source_id')->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rba_documents', function (Blueprint $table) {
            $table->dropForeign(['funding_source_id']);
            $table->dropColumn('funding_source_id');
            $table->dropForeign(['pptk_id']);
            $table->dropColumn('pptk_id');
        });
    }
};
