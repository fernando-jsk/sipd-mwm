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
        Schema::create('rba_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_code_id')->constrained('account_codes')->restrictOnDelete();
            $table->string('budget_year', 4);
            $table->integer('version')->default(0); // 0 = Induk, 1 = Pergeseran 1, dst
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->decimal('total_budget', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_documents');
    }
};
