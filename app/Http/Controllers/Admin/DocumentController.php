<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\GoogleAccount;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Affiche la liste des documents et le formulaire d'ajout
     */
    public function index()
    {
        $documents = Document::with('googleAccount')->get();
        // On récupère uniquement les comptes de service actifs
        $googleAccounts = GoogleAccount::where('is_active', true)->get();

        return view('admin.documents.index', compact('documents', 'googleAccounts'));
    }

    /**
     * Enregistre un nouveau document (Lien Drive) associé à un compte de service spécifique
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'drive_file_id' => 'required|string', // L'ID extrait du lien de partage
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'google_account_id' => 'required|exists:google_accounts,id',
        ]);

        Document::create($validated);

        return redirect()->back()->with('success', 'Document Drive ajouté avec succès.');
    }
}