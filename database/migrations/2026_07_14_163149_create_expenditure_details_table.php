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
        Schema::create('expenditure_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expenditure_id')->constrained('expenditures')->cascadeOnDelete();
            $table->foreignId('account_code_id')->constrained('account_codes');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenditure_details');
    }
};
