<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Services\GoogleDriveService;
use Exception;

class LomoPayWebhookController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Reçoit la notification serveur-à-serveur de LomoPay
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('Webhook LomoPay reçu : ', $data);

        // Selon la doc LomoPay, on récupère la référence
        $reference = $data['reference'] ?? null;
        $status = $data['status'] ?? null;

        if (!$reference || !$status) {
            return response()->json(['message' => 'Payload invalide'], 400);
        }

        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable'], 404);
        }

        // Si la transaction est déjà traitée, on s'arrête là (Idempotence)
        if ($transaction->status === 'success') {
            return response()->json(['message' => 'Déjà traité'], 200);
        }

        // Si LomoPay nous dit que c'est un succès
        if (strtolower($status) === 'success' || strtolower($status) === 'completed') {
            try {
                $transaction->update(['status' => 'success']);

                $document = $transaction->document;
                $customer = $transaction->customer;

                // 1. Partage Google Drive
                $this->driveService->shareDocument($document, $customer->email);

                $driveLink = "https://drive.google.com/file/d/{$document->drive_file_id}/view";
                $transaction->update(['shared_drive_link' => $driveLink]);

                // 2. Envoi du message WhatsApp Automatique
                $message = "🎉 *Paiement confirmé via LomoPay !*\n\nLe document *{$document->title}* vient d'être partagé à : {$customer->email}.\n\n📁 *Lien d'accès* :\n{$driveLink}";
                $this->sendWhatsappMessage($customer->phone, $message);

            } catch (Exception $e) {
                Log::error("Erreur post-paiement Webhook: " . $e->getMessage());
            }
        } elseif (strtolower($status) === 'failed') {
            $transaction->update(['status' => 'failed']);
            $this->sendWhatsappMessage($transaction->customer->phone, "⚠️ Votre tentative de paiement a échoué. N'hésitez pas à générer un nouveau lien si besoin.");
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Fonction utilitaire pour notifier le client via WhatsApp
     */
    private function sendWhatsappMessage(string $to, string $message): void
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');

        Http::withToken($token)->post("https://graph.facebook.com/v22.0/{$phoneId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message]
        ]);
    }
}
