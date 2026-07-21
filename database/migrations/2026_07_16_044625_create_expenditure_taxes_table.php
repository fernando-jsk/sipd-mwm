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
        Schema::create('expenditure_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expenditure_id')->constrained()->cascadeOnDelete();
            $table->enum('tax_type', ['PPN', 'PPh 21', 'PPh 22', 'PPh 23']);
            $table->string('billing_code')->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenditure_taxes');
    }
};
