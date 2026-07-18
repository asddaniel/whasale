<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleAccount;
use Illuminate\Http\Request;

class GoogleAccountController extends Controller
{
    /**
     * Affiche la liste des comptes de service Google et le formulaire
     */
    public function index()
    {
        $googleAccounts = GoogleAccount::all();

        return view('admin.google-accounts.index', compact('googleAccounts'));
    }

    /**
     * Enregistre un nouveau compte de service à partir d'un fichier JSON
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:google_accounts,email',
            'service_account_file' => 'required|file', // Évite un blocage strict de type mime pour le JSON
        ]);

        $jsonFile = $request->file('service_account_file');
        $jsonContent = json_decode($jsonFile->get(), true);

        // Validation de la structure du fichier de clé Google
        if (!$jsonContent || !isset($jsonContent['type']) || $jsonContent['type'] !== 'service_account') {
            return redirect()->back()->withErrors([
                'service_account_file' => 'Le fichier fourni n\'est pas un fichier JSON de compte de service Google valide.'
            ]);
        }

        // Création du compte
        GoogleAccount::create([
            'email' => $request->email,
            'service_account_json' => $jsonContent,
            'is_active' => true,
        ]);

        return redirect()->route('admin.google-accounts.index')
            ->with('success', 'Le compte de service a été configuré et chiffré en base de données.');
    }

    /**
     * Supprime définitivement un compte de service
     */
    public function destroy(GoogleAccount $googleAccount)
    {
        $googleAccount->delete();

        return redirect()->route('admin.google-accounts.index')
            ->with('success', 'Compte de service révoqué et supprimé avec succès.');
    }
}