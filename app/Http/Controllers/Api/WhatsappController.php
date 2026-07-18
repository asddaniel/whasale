<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Message;
use App\Models\AiReplyCustomer;
use App\Models\Document;
use App\Models\Transaction;
use App\Services\GeminiAgentService;
use App\Services\LomoPayService;
use App\Services\GoogleDriveService;
use Exception;

class WhatsappController extends Controller
{
    protected GeminiAgentService $geminiService;
    protected LomoPayService $lomoPayService;
    protected GoogleDriveService $driveService;

    public function __construct(
        GeminiAgentService $geminiService,
        LomoPayService $lomoPayService,
        GoogleDriveService $driveService
    ) {
        $this->geminiService = $geminiService;
        $this->lomoPayService = $lomoPayService;
        $this->driveService = $driveService;
    }

    /**
     * Validation initiale du Webhook (Demandée par Meta/Facebook)
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Invalid token', 403);
    }

    /**
     * Réception des messages WhatsApp (Le Webhook principal)
     */
    public function onMessage(Request $request)
    {
        $data = $request->all();

        // Ignorer les accusés de réception et les statuts (sent, delivered, read)
        if (!isset($data['entry'][0]['changes'][0]['value']['messages'])) {
            return response()->json(['status' => 'not a message'], 200);
        }

        $incomingMessage = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $contact = $incomingMessage['from'];
        $messageType = $incomingMessage['type'];
        $customerName = $data['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'] ?? $contact;

        $customer = Customer::firstOrCreate(
            ['phone' => $contact],
            ['name' => $customerName]
        );

        $messageContent = '';
        $mediaData = null;
        $mediaPath = null;

        try {
            if ($messageType === 'text') {
                $messageContent = $incomingMessage['text']['body'];
            }
            elseif (in_array($messageType, ['audio', 'image', 'document'])) {
                $mediaId = $incomingMessage[$messageType]['id'];
                $mimeType = $incomingMessage[$messageType]['mime_type'];

                // Si le client a mis une légende sous l'image ou le document
                $messageContent = $incomingMessage[$messageType]['caption'] ?? "";

                // Télécharger le média depuis WhatsApp
                $mediaData = $this->downloadWhatsappMedia($mediaId);

                if (!$mediaData) {
                    $this->sendWhatsappMessage($contact, "Désolé, je n'ai pas pu télécharger votre fichier. Pouvez-vous réessayer ?");
                    return response()->json(['status' => 'media download failed'], 200);
                }

                // Nettoyage du mime_type (Ex: "audio/ogg; codecs=opus" -> "audio/ogg")
                $cleanMimeType = explode(';', $mimeType)[0];
                $mediaData['mime_type'] = $cleanMimeType;

                // Enregistrement local du fichier
                $extensionMap = ['audio/ogg' => 'ogg', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
                $ext = $extensionMap[$cleanMimeType] ?? 'bin';
                $filename = 'whatsapp_media/' . $customer->id . '_' . time() . '_' . Str::random(5) . '.' . $ext;

                Storage::disk('public')->put($filename, $mediaData['bytes']);
                $mediaPath = $filename; // On stocke le chemin pour la BDD
            }
            else {
                $this->sendWhatsappMessage($contact, "Désolé, je ne supporte pas ce type de message (ex: stickers, localisation).");
                return response()->json(['status' => 'type rejected'], 200);
            }

            // Traitement principal
            $this->handleMessage($customer, $messageContent, $messageType, $mediaData, $mediaPath);

        } catch (Exception $e) {
            Log::error("Erreur générale onMessage: " . $e->getMessage());
        }

        return response()->json(['status' => 'message processed'], 200);
    }

    /**
     * Gère la logique pour le texte et les médias
     */
    private function handleMessage(Customer $customer, string $messageContent, string $messageType, ?array $mediaData, ?string $mediaPath)
    {
        try {
            // 1. Enregistrer le message de l'utilisateur (le texte sera mis à jour avec le portrait robot si c'est un média)
            $userMessage = Message::create([
                'customer_id' => $customer->id,
                'type' => $messageType,
                'message' => $messageContent,
                'media_path' => $mediaPath,
            ]);

            // 2. Reconstituer l'historique de la conversation (uniquement du texte !)
            $historyLimit = 20;
            $customerMessages = $customer->messages()
                ->with('aiReply')
                ->orderBy('created_at', 'desc')
                ->limit($historyLimit)
                ->get()
                ->reverse();

            $chatHistory = [];
            foreach ($customerMessages as $msg) {
                if ($msg->id === $userMessage->id) continue; // Ignorer le message actuel

                if (!empty(trim($msg->message))) {
                    $chatHistory[] = ['role' => 'user', 'part' => trim($msg->message)];
                }
                if ($msg->aiReply && !empty(trim($msg->aiReply->response))) {
                    $aiData = json_decode($msg->aiReply->response, true);
                    $aiText = $aiData['reply_to_user_on_whatsapp'] ?? '';
                    if ($aiText) {
                        $chatHistory[] = ['role' => 'model', 'part' => $aiText];
                    }
                }
            }

            // 3. Obtenir la réponse de l'IA. Si $mediaData est présent, l'IA l'analysera cette unique fois.
            $aiDecision = $this->geminiService->generateResponse($messageContent, $chatHistory, $mediaData);

            // 4. Mettre à jour la base de données avec le "Portrait Robot" si c'était un média
            // Ainsi, les prochaines fois, on n'enverra QUE ce texte à l'IA.
            if (!empty($aiDecision['media_description'])) {
                $portraitRobot = "[MÉDIA REÇU - Type: {$messageType}] Description: " . $aiDecision['media_description'];
                if (!empty($messageContent)) {
                    $portraitRobot .= "\n[Légende/Texte d'accompagnement]: " . $messageContent;
                }

                $userMessage->update(['message' => $portraitRobot]);
            }

            // 5. Enregistrer la réponse brute de l'IA
            AiReplyCustomer::create([
                'message_id' => $userMessage->id,
                'response' => json_encode($aiDecision),
            ]);

            // 6. Exécuter l'action dictée par l'IA
            $this->processAiAction($customer, $aiDecision);

        } catch (Exception $e) {
            Log::error("Erreur Traitement Message: " . $e->getMessage());
            $this->sendWhatsappMessage($customer->phone, "Oups, une erreur interne est survenue. Veuillez réessayer dans un instant.");
        }
    }

    /**
     * Télécharge le fichier depuis les serveurs sécurisés de WhatsApp
     */
    private function downloadWhatsappMedia(string $mediaId): ?array
    {
        $token = config('services.whatsapp.token');

        // Obtenir l'URL temporaire
        $response = Http::withToken($token)->get("https://graph.facebook.com/v22.0/{$mediaId}");

        if ($response->failed()) return null;
        $url = $response->json('url');

        // Télécharger les bytes
        $fileResponse = Http::withToken($token)->get($url);

        if ($fileResponse->failed()) return null;

        return ['bytes' => $fileResponse->body()];
    }

    /**
     * Traite les instructions retournées par Gemini
     */
    private function processAiAction(Customer $customer, array $aiDecision)
    {
        if (!empty($aiDecision['extracted_email'])) {
            $customer->update(['email' => strtolower(trim($aiDecision['extracted_email']))]);
        }

        $actionType = $aiDecision['next_action_type'] ?? 'CONTINUE_CONVERSATION';
        $replyText = $aiDecision['reply_to_user_on_whatsapp'] ?? "Je ne suis pas sûr de comprendre.";

        switch ($actionType) {
            case 'INITIATE_PAYMENT':
                if (empty($aiDecision['selected_document_id'])) {
                    $this->sendWhatsappMessage($customer->phone, "Désolé, je n'ai pas pu identifier le document que vous souhaitez acheter. Pouvez-vous préciser ?");
                    return;
                }

                $document = Document::find($aiDecision['selected_document_id']);
                if (!$document) {
                    $this->sendWhatsappMessage($customer->phone, "Le document sélectionné n'est plus disponible.");
                    return;
                }

                if (empty($customer->email)) {
                    $this->sendWhatsappMessage($customer->phone, "Avant de lancer le paiement pour *{$document->title}*, j'ai besoin de votre adresse e-mail Gmail pour vous partager le fichier après paiement.");
                    return;
                }

                try {
                    $reference = 'TX-' . strtoupper(Str::random(8)) . '-' . time();
                    $paymentData = $this->lomoPayService->initializePayment([
                        'amount' => $document->price,
                        'currency' => $document->currency,
                        'reference' => $reference,
                        'description' => "Achat du document: " . $document->title,
                    ]);
                    Log::alert($paymentData);

                    Transaction::create([
                        'customer_id' => $customer->id,
                        'document_id' => $document->id,
                        'reference' => $paymentData['data']['id'],
                        'amount' => $document->price,
                        'currency' => $document->currency,
                        'payment_url' => $paymentData['data']['checkout_url'] ?? '',
                        'status' => 'pending',
                    ]);

                    $messageToUser = $replyText . "\n\n🔗 *Lien de paiement* :\n" . ($paymentData['data']['checkout_url'] ?? '') . "\n\n_Une fois le paiement effectué, écrivez-moi « J'ai payé »._";
                    $this->sendWhatsappMessage($customer->phone, $messageToUser);

                } catch (Exception $e) {
                    Log::alert($e);
                    Log::error($e);
                    $this->sendWhatsappMessage($customer->phone, "Impossible de générer le lien de paiement pour le moment. Réessayez plus tard.");
                }
                break;

            case 'CHECK_PAYMENT':
                $transaction = Transaction::where('customer_id', $customer->id)->where('status', 'pending')->latest()->first();
                if (!$transaction) {
                    $this->sendWhatsappMessage($customer->phone, "Je ne trouve aucune transaction en attente pour vous.");
                    return;
                }

                try {
                    $statusData = $this->lomoPayService->verifyTransaction($transaction->reference);
                    if (isset($statusData['status']) && strtolower($statusData['status']) === 'success') {
                        $transaction->update(['status' => 'success']);

                        $document = $transaction->document;
                        $this->driveService->shareDocument($document, $customer->email);

                        $driveLink = "https://drive.google.com/file/d/{$document->drive_file_id}/view";
                        $transaction->update(['shared_drive_link' => $driveLink]);

                        $successMsg = "🎉 *Paiement confirmé !* Merci.\n\nDocument partagé sur : {$customer->email}.\n📁 *Lien* :\n{$driveLink}";
                        $this->sendWhatsappMessage($customer->phone, $successMsg);
                    } else {
                        $this->sendWhatsappMessage($customer->phone, "Votre paiement est toujours en attente de validation.");
                    }
                } catch (Exception $e) {
                    $this->sendWhatsappMessage($customer->phone, "Je n'ai pas pu vérifier votre paiement à l'instant, réessayez svp.");
                }
                break;

            case 'CONTINUE_CONVERSATION':
            default:
                $this->sendWhatsappMessage($customer->phone, $replyText);
                break;
        }
    }

    /**
     * Envoie un message texte via l'API WhatsApp Business
     */
    private function sendWhatsappMessage(string $to, string $message): bool
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');

        $url = "https://graph.facebook.com/v22.0/{$phoneId}/messages";
        $response = Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => true, 'body' => $message]
        ]);

        if ($response->failed()) {
            Log::error("Échec d'envoi WhatsApp", ['res' => $response->body()]);
            return false;
        }
        return true;
    }
}
