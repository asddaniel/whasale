<?php

namespace App\Services;

use App\Models\Document;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiAgentService
{
    /**
     * Traite le message entrant et retourne une action structurée
     */
    public function generateResponse(string $userMessage, array $chatHistory = []): array
    {
        // 1. Récupération des documents (catalogue)
        $documents = Document::all(['id', 'title', 'price', 'currency', 'description']);
        $catalog = json_encode($documents->toArray());

        // 2. Le Prompt Système (Directives)
        $prompt = "Tu es un assistant virtuel commercial sur WhatsApp. Ton rôle est de vendre nos documents numériques (hébergés sur Google Drive).

        CATALOGUE DES DOCUMENTS DISPONIBLES :
        {$catalog}

        RÈGLES DE CONVERSATION :
        1. Sois poli, concis et utilise des emojis (WhatsApp oblige).
        2. Si le client veut acheter, tu DOIS lui demander son adresse e-mail (nécessaire pour le partage Google Drive).
        3. Si le client a fourni son e-mail et choisi un document, passe l'action à 'INITIATE_PAYMENT'.
        4. Si le client revient dire qu'il a payé, passe l'action à 'CHECK_PAYMENT'.
        5. L'utilisateur actuel vient d'envoyer le message suivant. Analyse l'historique et son message pour déterminer la prochaine action.
        ";

        // 3. Construction des parties d'historique pour l'API Gemini
        $parts = [];
        $parts[] = ['text' => $prompt];

        foreach ($chatHistory as $history) {
            // L'historique doit être re-formatté si nécessaire (dépend de la logique de ton contrôleur)
            $parts[] = ['text' => "{$history['role']}: {$history['part']}"];
        }
        $parts[] = ['text' => "user: {$userMessage}"];

        // 4. Schéma de réponse JSON Strict
        $responseSchema = new Schema(
            type: DataType::OBJECT,
            properties: [
                'reply_to_user_on_whatsapp' => new Schema(
                    type: DataType::STRING,
                    description: "Le texte à envoyer au client sur WhatsApp."
                ),
                'next_action_type' => new Schema(
                    type: DataType::STRING,
                    description: "Doit être l'une des valeurs: CONTINUE_CONVERSATION, INITIATE_PAYMENT, CHECK_PAYMENT"
                ),
                'extracted_email' => new Schema(
                    type: DataType::STRING,
                    description: "L'e-mail du client s'il l'a fourni dans la conversation. Sinon null."
                ),
                'selected_document_id' => new Schema(
                    type: DataType::INTEGER,
                    description: "L'ID du document choisi par le client (tiré du catalogue). Null s'il n'a pas encore choisi."
                ),
            ],
            required: ['reply_to_user_on_whatsapp', 'next_action_type']
        );

        $generationConfig = new GenerationConfig(
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: $responseSchema,
            temperature: 0.2 // Très faible pour éviter les hallucinations
        );

        try {
            // Appel à Gemini
            $response = Gemini::generativeModel(model: 'gemini-1.5-flash')
                ->withGenerationConfig($generationConfig)
                ->generateContent(implode("\n", array_column($parts, 'text'))); // Concaténation simple pour l'exemple

            $generatedText = $response->text();

            if (!$generatedText) {
                throw new Exception("Réponse vide de l'IA.");
            }

            $data = json_decode($generatedText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("L'IA n'a pas retourné un JSON valide.");
            }

            return $data;

        } catch (Exception $e) {
            Log::error('Gemini Error: ' . $e->getMessage());
            // Fallback de sécurité
            return [
                'reply_to_user_on_whatsapp' => "Désolé, je rencontre un petit problème technique. Veuillez patienter ou reformuler.",
                'next_action_type' => 'CONTINUE_CONVERSATION',
                'extracted_email' => null,
                'selected_document_id' => null,
            ];
        }
    }
}
