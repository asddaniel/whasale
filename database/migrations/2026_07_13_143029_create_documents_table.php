<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Nom du document (ex: Formation Laravel)
            $table->text('description')->nullable();
            $table->string('drive_file_id'); // L'ID du fichier ou dossier sur Google Drive
            $table->decimal('price', 10, 2); // Prix du document
            $table->string('currency')->default('CDF'); // Devise par défaut
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
