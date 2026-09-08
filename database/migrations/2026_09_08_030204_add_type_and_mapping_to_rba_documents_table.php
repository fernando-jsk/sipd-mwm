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
            $table->enum('rba_type', ['gelondongan', 'rinci'])->default('gelondongan')->after('status');
            $table->foreignId('mapped_to_rba_id')->nullable()->constrained('rba_documents')->nullOnDelete()->after('rba_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rba_documents', function (Blueprint $table) {
            $table->dropForeign(['mapped_to_rba_id']);
            $table->dropColumn(['mapped_to_rba_id', 'rba_type']);
        });
    }
};
