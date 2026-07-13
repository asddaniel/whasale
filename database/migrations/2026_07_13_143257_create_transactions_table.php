<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('reference')->unique(); // Référence unique pour LomoPay
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('CDF');
            $table->string('payment_url')->nullable(); // Lien LomoPay
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('shared_drive_link')->nullable(); // Le lien Drive généré une fois partagé
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
