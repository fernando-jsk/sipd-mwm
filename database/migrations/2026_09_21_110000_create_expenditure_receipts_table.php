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
        Schema::create('expenditure_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->date('date');
            $table->foreignId('account_code_id')->constrained('account_codes');
            $table->string('recipient_name');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('tax_type')->nullable(); // PPN, PPh 21, PPh 22, PPh 23, PPh Final
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('billing_code')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['draft', 'paid', 'in_gu', 'completed'])->default('paid');
            $table->foreignId('expenditure_id')->nullable()->constrained('expenditures')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenditure_receipts');
    }
};
