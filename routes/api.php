<?php

use App\Http\Controllers\Api\WhatsappController;
use App\Http\Controllers\Api\LomoPayWebhookController;
use App\Http\Controllers\Web\PaymentController;


// Webhook WhatsApp (Meta)
Route::get('/whatsapp/webhook', [WhatsappController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsappController::class, 'onMessage']);
Route::post('/whatsapp', [WhatsappController::class, 'onMessage']);
// Webhook LomoPay (Le nom de la route doit correspondre à ce qui est envoyé dans LomoPayService)
Route::post('/lomopay/webhook', [LomoPayWebhookController::class, 'handle'])->name('lomopay.webhook');

// Route API utilisée par le Javascript de la page de paiement
Route::get('/payment/status/{reference}', [PaymentController::class, 'checkStatus']);
