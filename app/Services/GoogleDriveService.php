<?php

namespace App\Services;

use App\Models\GoogleAccount;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleDriveService
{
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        // À définir dans config/services.php sous 'google'
        $this->clientId = config('services.google.client_id');
        $this->clientSecret = config('services.google.client_secret');
    }

    /**
     * Partage un document Drive à une adresse email spécifique
     */
    public function shareDocument(Document $document, string $customerEmail): bool
    {
        $account = $document->googleAccount;

        if (!$account || !$account->is_active) {
            throw new Exception("Aucun compte Google actif associé à ce document.");
        }

        $accessToken = $this->getValidAccessToken($account);

        $response = Http::withToken($accessToken)
            ->post("https://www.googleapis.com/drive/v3/files/{$document->drive_file_id}/permissions", [
                'role' => 'reader', // Permission de lecture (téléchargement)
                'type' => 'user',
                'emailAddress' => $customerEmail,
            ]);

        if ($response->failed()) {
            Log::error('Erreur partage Google Drive', [
                'file_id' => $document->drive_file_id,
                'email' => $customerEmail,
                'response' => $response->json()
            ]);
            throw new Exception("Impossible de partager le fichier Drive.");
        }

        return true; // Partage réussi
    }

    /**
     * Récupère un token valide, le rafraîchit si nécessaire.
     */
    private function getValidAccessToken(GoogleAccount $account): string
    {
        // Si le token est valide pour encore au moins 5 minutes
        if ($account->access_token && $account->expires_at && $account->expires_at->gt(now()->addMinutes(5))) {
            return $account->access_token;
        }

        // Sinon, on rafraîchit via l'API Google OAuth2
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('Erreur rafraîchissement token Google', $response->json());
            throw new Exception("Erreur de connexion au compte Google ({$account->email}).");
        }

        $data = $response->json();

        // Mise à jour en base de données
        $account->update([
            'access_token' => $data['access_token'],
            'expires_at' => now()->addSeconds($data['expires_in']),
        ]);

        return $data['access_token'];
    }
}
