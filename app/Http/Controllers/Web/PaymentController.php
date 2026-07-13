<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\LomoPayService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Affiche la page de succès/vérification
     */
    public function success($reference)
    {
        $transaction = Transaction::with('document')->where('reference', $reference)->firstOrFail();

        return view('payment.success', compact('transaction'));
    }

    /**
     * API appelée par le Javascript de la page pour vérifier le statut en temps réel
     */
    public function checkStatus($reference, LomoPayService $lomoPayService)
    {
        $transaction = Transaction::where('reference', $reference)->firstOrFail();

        // Si c'est toujours en attente en base, on force une vérification chez LomoPay
        if ($transaction->status === 'pending') {
            try {
                $lomopayStatus = $lomoPayService->verifyTransaction($reference);

                if (isset($lomopayStatus['status']) && strtolower($lomopayStatus['status']) === 'success') {
                    // On laisse le webhook s'occuper du partage Drive, ou on peut le faire ici si on veut.
                    // Pour éviter les doublons, on informe juste le frontend.
                    // Le rafraîchissement se fera tout seul.
                }
            } catch (\Exception $e) {
                // Erreur silencieuse pour l'API
            }
        }

        return response()->json([
            'status' => $transaction->status,
            'drive_link' => $transaction->shared_drive_link
        ]);
    }
}
