<?php

namespace App\Services;

use App\Models\Document;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\Permission;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    /**
     * Partage un document Drive à une adresse email spécifique
     */
    public function shareDocument(Document $document, string $customerEmail): bool
    {
        // Récupérer le compte associé à ce document
        $account = $document->googleAccount;

        if (!$account || !$account->is_active) {
            throw new Exception("Aucun compte Google actif associé à ce document.");
        }

        if (empty($account->service_account_json)) {
            throw new Exception("Les identifiants du compte de service sont manquants pour ce compte.");
        }

        try {
            $client = new Client();
            
            // On injecte le tableau d'identifiants déchiffré depuis la BDD
            $client->setAuthConfig($account->service_account_json);
            $client->addScope(Drive::DRIVE);

            $driveService = new Drive($client);

            // Définir la permission de lecture (reader)
            $permission = new Permission([
                'type' => 'user',
                'role' => 'reader',
                'emailAddress' => $customerEmail,
            ]);

            // Appliquer le partage sur Google Drive
            $driveService->permissions->create($document->drive_file_id, $permission, [
                'sendNotificationEmail' => true,
                'emailMessage' => 'Bonjour, voici l’accès à votre document.'
            ]);

            return true;
            
        } catch (Exception $e) {
            Log::error('Erreur partage Google Drive Multi-Comptes', [
                'file_id' => $document->drive_file_id,
                'email' => $customerEmail,
                'account' => $account->email,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Impossible de partager le fichier Drive via le compte de service : " . $e->getMessage());
        }
    }
}