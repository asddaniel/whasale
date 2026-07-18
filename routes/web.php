<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Admin\GoogleAccountController;

// La page de retour après paiement
Route::get('/payment/success/{reference}', [PaymentController::class, 'success'])->name('payment.success');
/*
|--------------------------------------------------------------------------
| Routes d'Authentification Administrateur
|--------------------------------------------------------------------------
*/
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login']);
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Routes d'Administration (SÉCURISÉES)
|--------------------------------------------------------------------------
*/
// Le middleware 'auth' garantit que seul un utilisateur connecté peut y accéder
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        // Routes pour la gestion des Comptes de Service Google
    Route::resource('google-accounts', GoogleAccountController::class)->only(['index', 'store', 'destroy']);

    // Tu pourras ajouter d'autres routes admin ici (ex: gestion des transactions)
});

Route::get('/', function () {
    return view('welcome');
});
