<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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

        // Remplace 'ton_token_de_verification' par celui que tu configures sur Meta
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

        // Sécurité : vérifier si on a bien un message
        if (!isset($data['entry'][0]['changes'][0]['value']['messages'])) {
            return response()->json(['status' => 'no message received'], 200);
        }

        $incomingMessage = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $contact = $incomingMessage['from']; // Numéro de téléphone
        $messageType = $incomingMessage['type'];
        $customerName = $data['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'] ?? $contact;

        // 1. Trouver ou créer le client
        $customer = Customer::firstOrCreate(
            ['phone' => $contact],
            ['name' => $customerName]
        );

        // 2. Filtrer les types non supportés
        if ($messageType !== 'text') {
            $this->sendWhatsappMessage($contact, "Désolé, je suis une IA spécialisée dans le texte. Veuillez écrire votre demande s'il vous plaît. 🙏");
            return response()->json(['status' => 'type rejected'], 200);
        }

        $messageContent = $incomingMessage['text']['body'];

        // 3. Traiter le message texte
        $this->handleTextMessage($customer, $messageContent);

        return response()->json(['status' => 'message processed'], 200);
    }

    /**
     * Gère la logique principale pour un message texte
     */
    private function handleTextMessage(Customer $customer, string $messageContent)
    {
        try {
            // 1. Enregistrer le message de l'utilisateur
            $userMessage = Message::create([
                'customer_id' => $customer->id,
                'type' => 'text',
                'message' => $messageContent,
            ]);

            // 2. Reconstituer l'historique (les 20 derniers messages)
            $historyLimit = 20;
            $customerMessages = $customer->messages()
                ->with('aiReply')
                ->orderBy('created_at', 'desc')
                ->limit($historyLimit)
                ->get()
                ->reverse();

            $chatHistory = [];
            foreach ($customerMessages as $msg) {
                if ($msg->id === $userMessage->id) continue;

                if (!empty(trim($msg->message))) {
                    $chatHistory[] = ['role' => 'user', 'part' => trim($msg->message)];
                }
                if ($msg->aiReply && !empty(trim($msg->aiReply->response))) {
                    // On extrait juste le texte de la réponse envoyée au client pour l'historique
                    $aiData = json_decode($msg->aiReply->response, true);
                    $aiText = $aiData['reply_to_user_on_whatsapp'] ?? '';
                    if ($aiText) {
                        $chatHistory[] = ['role' => 'model', 'part' => $aiText];
                    }
                }
            }

            // 3. Obtenir la réponse de l'IA (Gemini)
            $aiDecision = $this->geminiService->generateResponse($messageContent, $chatHistory);

            // 4. Enregistrer la réponse brute de l'IA pour traçabilité
            AiReplyCustomer::create([
                'message_id' => $userMessage->id,
                'response' => json_encode($aiDecision),
            ]);

            // 5. Exécuter l'action dictée par l'IA
            $this->processAiAction($customer, $aiDecision);

        } catch (Exception $e) {
            Log::error("Erreur Traitement Message WhatsApp: " . $e->getMessage());
            $this->sendWhatsappMessage($customer->phone, "Oups, une erreur interne est survenue. Veuillez réessayer dans un instant.");
        }
    }

    /**
     * Traite les instructions JSON retournées par Gemini
     */
    private function processAiAction(Customer $customer, array $aiDecision)
    {
        // A. Mise à jour de l'e-mail si l'IA l'a détecté
        if (!empty($aiDecision['extracted_email'])) {
            $customer->update(['email' => strtolower(trim($aiDecision['extracted_email']))]);
        }

        $actionType = $aiDecision['next_action_type'] ?? 'CONTINUE_CONVERSATION';
        $replyText = $aiDecision['reply_to_user_on_whatsapp'] ?? "Je ne suis pas sûr de comprendre.";

        // B. Exécution des actions
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

                // Initialisation LomoPay
                try {
                    $reference = 'TX-' . strtoupper(Str::random(8)) . '-' . time();

                    $paymentData = $this->lomoPayService->initializePayment([
                        'amount' => $document->price,
                        'currency' => $document->currency,
                        'reference' => $reference,
                        'description' => "Achat du document: " . $document->title,
                    ]);

                    // Création de la transaction en base
                    Transaction::create([
                        'customer_id' => $customer->id,
                        'document_id' => $document->id,
                        'reference' => $reference,
                        'amount' => $document->price,
                        'currency' => $document->currency,
                        'payment_url' => $paymentData['payment_url'] ?? '',
                        'status' => 'pending',
                    ]);

                    $messageToUser = $replyText . "\n\n🔗 *Voici votre lien de paiement sécurisé* :\n" . ($paymentData['payment_url'] ?? '') . "\n\n_Une fois le paiement effectué, écrivez-moi simplement « J'ai payé »._";
                    $this->sendWhatsappMessage($customer->phone, $messageToUser);

                } catch (Exception $e) {
                    $this->sendWhatsappMessage($customer->phone, "Impossible de générer le lien de paiement pour le moment. Réessayez plus tard.");
                }
                break;

            case 'CHECK_PAYMENT':
                // Trouver la dernière transaction en attente de ce client
                $transaction = Transaction::where('customer_id', $customer->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                if (!$transaction) {
                    $this->sendWhatsappMessage($customer->phone, "Je ne trouve aucune transaction en attente pour vous. Avez-vous déjà généré un lien de paiement ?");
                    return;
                }

                try {
                    // Vérifier sur LomoPay
                    $statusData = $this->lomoPayService->verifyTransaction($transaction->reference);

                    // Supposons que l'API LomoPay renvoie 'status' => 'success'
                    if (isset($statusData['status']) && strtolower($statusData['status']) === 'success') {

                        $transaction->update(['status' => 'success']);

                        // Partage Google Drive Automatique !
                        $document = $transaction->document;
                        $this->driveService->shareDocument($document, $customer->email);

                        $driveLink = "https://drive.google.com/file/d/{$document->drive_file_id}/view";
                        $transaction->update(['shared_drive_link' => $driveLink]);

                        $successMsg = "🎉 *Paiement confirmé !* Merci pour votre achat.\n\nLe document *{$document->title}* vient d'être partagé à votre adresse e-mail : {$customer->email}.\n\n📁 *Voici votre lien d'accès direct* :\n{$driveLink}";
                        $this->sendWhatsappMessage($customer->phone, $successMsg);

                    } else {
                        // Paiement toujours en attente
                        $this->sendWhatsappMessage($customer->phone, "Votre paiement est toujours en attente ou n'a pas encore été validé par le réseau. Veuillez vérifier votre solde ou patienter quelques minutes.");
                    }

                } catch (Exception $e) {
                    $this->sendWhatsappMessage($customer->phone, "Je n'ai pas pu vérifier votre paiement à l'instant. Ne vous inquiétez pas, notre système revérifie automatiquement.");
                }
                break;

            case 'CONTINUE_CONVERSATION':
            default:
                // Simple discussion
                $this->sendWhatsappMessage($customer->phone, $replyText);
                break;
        }
    }

    /**
     * Envoie un message texte via l'API WhatsApp Business (Meta)
     */
    private function sendWhatsappMessage(string $to, string $message): bool
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');

        if (empty($token) || empty($phoneId)) {
            Log::error('Configuration WhatsApp manquante dans config/services.php');
            return false;
        }

        $url = "https://graph.facebook.com/v22.0/{$phoneId}/messages";

        $response = Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $message
            ]
        ]);

        if ($response->failed()) {
            Log::error("Échec d'envoi WhatsApp à {$to}", ['response' => $response->body()]);
            return false;
        }

        return true;
    }
}
