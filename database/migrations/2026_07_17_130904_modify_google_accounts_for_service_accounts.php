<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_accounts', function (Blueprint $table) {
            // Supprimer les colonnes OAuth devenues inutiles
            $table->dropColumn(['refresh_token', 'access_token', 'expires_at']);
            
            // Stocker le contenu JSON complet du compte de service (un long texte)
            $table->text('service_account_json')->after('email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('google_accounts', function (Blueprint $table) {
            $table->string('refresh_token')->nullable();
            $table->string('access_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->dropColumn('service_account_json');
        });
    }
};