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
            $table->string('uraian', 500)
                ->nullable()
                ->after('rba_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenditure_details', function (Blueprint $table) {
            $table->dropColumn('uraian');
        });
    }
};
