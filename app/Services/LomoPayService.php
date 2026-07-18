<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class LomoPayService
{
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.lomopay.secret_key');
        $this->baseUrl = config('services.lomopay.base_url', 'https://api.lomopay.net/v1'); // Vérifie l'URL de base exacte
    }

    public function initializePayment(array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl . '/payments', [
            'amount' => $payload['amount'],
            'currency' => $payload['currency'] ?? 'CDF',
            'reference' => $payload['reference'],
            'description' => $payload['description'],
            'callback_url' => route('lomopay.webhook'), // Route que nous allons créer
            'return_url' => route('payment.success', ['reference' => $payload['reference']]), // Page Tailwind
        ]);

        if ($response->failed()) {
            Log::error('Erreur Initialisation LomoPay', ['body' => $response->json()]);
            throw new Exception("Échec de l'initialisation du paiement.");
        }

        return $response->json(); // Retourne ['payment_url' => '...', 'transaction_token' => '...']
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Accept' => 'application/json',
        ])->get($this->baseUrl . "/payments/verify/{$reference}");

        if ($response->failed()) {
            throw new Exception("Impossible de vérifier la transaction.");
        }

        return $response->json(); // Retourne le statut réel
    }
}
