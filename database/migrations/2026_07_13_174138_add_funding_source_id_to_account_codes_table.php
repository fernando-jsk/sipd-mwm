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
        Schema::table('account_codes', function (Blueprint $table) {
            $table->foreignId('funding_source_id')->nullable()->after('level')->constrained('funding_sources')->nullOnDelete();
            
            // Drop old unique index and create a new compound one
            $table->dropUnique(['code']);
            $table->unique(['code', 'funding_source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->dropUnique(['code', 'funding_source_id']);
            $table->unique(['code']);
            $table->dropForeign(['funding_source_id']);
            $table->dropColumn('funding_source_id');
        });
    }
};
