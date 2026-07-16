<?php

namespace App\Services;

use App\Models\Document;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Data\Blob;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiAgentService
{
    /**
     * Traite le message entrant et génère l'action, ainsi que la description textuelle du média (si fourni)
     */
    public function generateResponse(string $userMessage, array $chatHistory = [], ?array $mediaData = null): array
    {
        $documents = Document::all(['id', 'title', 'price', 'currency', 'description']);
        $catalog = json_encode($documents->toArray());

        $prompt = "Tu es un assistant virtuel commercial sur WhatsApp. Ton rôle est de vendre nos documents numériques.

        CATALOGUE DES DOCUMENTS DISPONIBLES :
        {$catalog}

        RÈGLES DE CONVERSATION :
        1. Sois poli, concis et utilise des emojis.
        2. Si un fichier MEDIA t'est transmis aujourd'hui, tu vas l'analyser. Si c'est un AUDIO, retranscris-le ou résume son intention. Si c'est une IMAGE ou un PDF, décris précisément son contenu textuel/visuel.
        3. Si le client veut acheter, tu DOIS lui demander son adresse e-mail.
        4. Si le client a fourni son e-mail et choisi un document, passe l'action à 'INITIATE_PAYMENT'.
        5. Si le client revient dire qu'il a payé, passe l'action à 'CHECK_PAYMENT'.
        ";

        // Construction du payload pour Gemini
        $parts = [];
        $parts[] = $prompt;

        foreach ($chatHistory as $history) {
            $parts[] = "{$history['role']}: {$history['part']}";
        }

        // Ajout du texte de l'utilisateur (ou légende)
        $parts[] = "user: {$userMessage}";

        // Si c'est un média, on l'attache à la requête IA (Uniquement la PREMIÈRE FOIS !)
        if ($mediaData && isset($mediaData['bytes']) && isset($mediaData['mime_type'])) {
            $parts[] = new Blob(
                mimeType: $mediaData['mime_type'],
                data: base64_encode($mediaData['bytes'])
            );
        }

        // Schema JSON avec le fameux "Portrait Robot" optionnel
        $responseSchema = new Schema(
            type: DataType::OBJECT,
            properties: [
                'reply_to_user_on_whatsapp' => new Schema(
                    type: DataType::STRING,
                    description: "Le texte de réponse à renvoyer au client sur WhatsApp."
                ),
                'next_action_type' => new Schema(
                    type: DataType::STRING,
                    description: "Valeurs autorisées: CONTINUE_CONVERSATION, INITIATE_PAYMENT, CHECK_PAYMENT"
                ),
                'extracted_email' => new Schema(
                    type: DataType::STRING,
                    description: "L'e-mail du client s'il l'a fourni. Sinon null."
                ),
                'selected_document_id' => new Schema(
                    type: DataType::INTEGER,
                    description: "L'ID du document choisi par le client. Null s'il n'a pas encore choisi."
                ),
                'media_description' => new Schema(
                    type: DataType::STRING,
                    description: "OBLIGATOIRE SI et seulement si un fichier média a été transmis dans ce tour. Fournis un portrait robot textuel détaillé (transcription pour un vocal, description visuelle pour une photo, résumé pour un PDF). Null sinon."
                ),
            ],
            required: ['reply_to_user_on_whatsapp', 'next_action_type']
        );

        $generationConfig = new GenerationConfig(
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: $responseSchema,
            temperature: 0.2
        );

        try {
            $response = Gemini::generativeModel(model: 'gemini-2.5-flash')
                ->withGenerationConfig($generationConfig)
                ->generateContent($parts);

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
            return [
                'reply_to_user_on_whatsapp' => "Désolé, je n'ai pas bien compris votre fichier ou votre vocal. Pouvez-vous me l'écrire s'il vous plaît ?",
                'next_action_type' => 'CONTINUE_CONVERSATION',
                'extracted_email' => null,
                'selected_document_id' => null,
                'media_description' => null,
            ];
        }
    }
}
