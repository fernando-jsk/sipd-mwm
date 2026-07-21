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
        Schema::create('expenditures', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->date('date');
            $table->enum('type', ['UP', 'GU', 'TU', 'LS']);
            $table->text('description');
            $table->foreignId('treasurer_id')->constrained('users');
            $table->foreignId('kpa_id')->constrained('users');
            $table->foreignId('ptk_id')->constrained('users');
            $table->date('activity_date');
            $table->text('activity_description');
            $table->enum('payment_method', ['rekanan', 'pegawai', 'ls_bendahara', 'terlampir']);
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('contract_number')->nullable();
            $table->enum('status', ['draft', 'submitted', 'authorized', 'verified', 'disbursed', 'rejected'])->default('draft');
            $table->text('rejection_note')->nullable();
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
        Schema::dropIfExists('expenditures');
    }
};
