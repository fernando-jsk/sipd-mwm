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
        Schema::create('rba_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_document_id')->constrained('rba_documents')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('rba_details')->cascadeOnDelete();
            
            $table->enum('type', ['header', 'item']);
            $table->string('uraian');
            
            // Kolom untuk Item
            $table->decimal('vol_1', 14, 2)->nullable();
            $table->string('satuan_1')->nullable();
            $table->decimal('vol_2', 14, 2)->nullable();
            $table->string('satuan_2')->nullable();
            $table->decimal('vol_3', 14, 2)->nullable();
            $table->string('satuan_3')->nullable();
            $table->decimal('vol_4', 14, 2)->nullable();
            $table->string('satuan_4')->nullable();
            
            // Kolom Hasil Kalkulasi/Final
            $table->decimal('koefisien', 14, 2)->nullable();
            $table->string('satuan')->nullable();
            $table->decimal('harga', 18, 2)->nullable(); // Nilai uang bisa besar
            $table->decimal('jumlah', 18, 2)->nullable(); // Total (Koefisien * Harga)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_details');
    }
};
