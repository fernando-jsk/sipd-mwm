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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->date('date');
            $table->foreignId('receipt_type_id')->constrained('receipt_types');
            $table->text('description');
            $table->string('payer_name');
            $table->enum('payment_method', ['tunai', 'transfer', 'giro']);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->foreignId('treasurer_id')->constrained('users');
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
