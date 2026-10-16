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
        Schema::create('rba_cash_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_document_id')->constrained('rba_documents')->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1 = Januari s.d. 12 = Desember
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['rba_document_id', 'month']);
            $table->index(['rba_document_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_cash_allocations');
    }
};
