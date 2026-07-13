<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique(); // L'adresse email du compte mère
            $table->text('refresh_token'); // Le token pour générer de nouveaux accès
            $table->text('access_token')->nullable(); // Le token temporaire
            $table->timestamp('expires_at')->nullable(); // Expiration de l'access token
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_accounts');
    }
};
