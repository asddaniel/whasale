<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comptes de Service - IA-Drive</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts (Outfit) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght=300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Configuration Tailwind personnalisée -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        ai: {
                            400: '#9f7aea',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            900: '#4c1d95',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans antialiased min-h-screen relative overflow-x-hidden selection:bg-ai-500 selection:text-white">

    <!-- Effets de lumière en arrière-plan (Glow) -->
    <div class="fixed top-0 left-1/4 w-96 h-96 bg-ai-600 rounded-full mix-blend-screen filter blur-[150px] opacity-20 pointer-events-none"></div>
    <div class="fixed bottom-0 right-1/4 w-96 h-96 bg-blue-600 rounded-full mix-blend-screen filter blur-[150px] opacity-20 pointer-events-none"></div>

    <!-- Navbar d'Administration Flottante -->
    <nav class="pt-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-50">
        <div class="glass-panel rounded-2xl px-6 py-4 shadow-2xl flex justify-between items-center transition-all">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="bg-gradient-to-br from-ai-400 to-blue-500 p-2 rounded-xl shadow-lg shadow-ai-500/20">
                        <i class="ph ph-robot text-2xl text-white"></i>
                    </div>
                    <div class="font-bold text-xl tracking-wide text-white">
                        IA-DRIVE <span class="text-ai-400 font-medium">ADMIN</span>
                    </div>
                </div>
                
                <!-- Menu de Navigation Principal -->
                <div class="hidden md:flex items-center gap-1 bg-slate-900/40 p-1 rounded-xl border border-slate-800">
                    <a href="{{ route('admin.documents.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-slate-200 transition-colors">
                        <i class="ph ph-files inline mr-1"></i> Documents
                    </a>
                    <a href="{{ route('admin.google-accounts.index') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-ai-600/25 text-ai-400 border border-ai-500/20 transition-all">
                        <i class="ph ph-key inline mr-1"></i> Comptes de Service
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="hidden sm:flex items-center gap-2 text-sm text-slate-400 bg-slate-900/50 py-1.5 px-4 rounded-full border border-slate-800">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    Admin : <strong class="text-slate-200">{{ Auth::user()->name }}</strong>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="group flex items-center gap-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300">
                        <i class="ph ph-sign-out text-lg group-hover:-translate-x-1 transition-transform"></i>
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Contenu Principal -->
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 relative z-10 mt-4">

        <!-- En-tête de page -->
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400 mb-2">
                    Comptes de Service Google
                </h1>
                <p class="text-slate-400 flex items-center gap-2">
                    <i class="ph ph-shield-check text-blue-400"></i> Configurez et révoquez les identifiants d'accès robotisés.
                </p>
            </div>
        </div>

        <!-- Alertes de notifications -->
        @if(session('success'))
            <div class="glass-panel border-l-4 border-l-green-500 text-green-400 px-6 py-4 rounded-xl mb-8 flex items-center gap-3 animate-[fade-in_0.5s_ease-out]">
                <i class="ph-fill ph-check-circle text-2xl"></i>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="glass-panel border-l-4 border-l-red-500 text-red-400 px-6 py-4 rounded-xl mb-8 flex items-start gap-3">
                <i class="ph-fill ph-warning-circle text-2xl mt-0.5"></i>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li class="font-medium">- {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <!-- Formulaire d'ajout (Colonnes 1 à 4) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Carte d'ajout -->
                <div class="glass-panel p-6 rounded-3xl shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl -mr-10 -mt-10"></div>

                    <h2 class="text-lg font-bold mb-5 flex items-center gap-3 text-white">
                        <i class="ph-fill ph-key text-blue-400 text-xl"></i>
                        Nouveau Robot Drive
                    </h2>

                    <form action="{{ route('admin.google-accounts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Adresse Email du Robot</label>
                            <input type="email" name="email" required class="w-full bg-slate-900/50 border border-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 transition-all outline-none" placeholder="nom-robot@projet...gserviceaccount.com">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Fichier JSON Google Cloud</label>
                            <div class="relative bg-slate-900/50 border border-slate-700 rounded-xl hover:border-blue-500 transition-all">
                                <label class="flex flex-col items-center justify-center py-5 px-4 cursor-pointer text-slate-400 hover:text-slate-200">
                                    <i class="ph ph-upload-simple text-3xl mb-1 text-blue-400"></i>
                                    <span class="text-xs font-semibold">Choisir le fichier de clés</span>
                                    <input type="file" name="service_account_file" accept=".json" required class="hidden" id="json_file_selector">
                                </label>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2" id="file_selected_name">Sélectionnez le fichier .json téléchargé depuis GCP.</p>
                        </div>

                        <button type="submit" class="w-full mt-2 group relative inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-ai-600 hover:from-blue-500 hover:to-ai-500 text-white font-bold py-3.5 px-6 rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all duration-300">
                            <i class="ph-bold ph-shield-check text-base"></i>
                            Configurer le compte
                        </button>
                    </form>
                </div>

                <!-- Guide d'Aide Google Drive -->
                <div class="bg-slate-900/40 border border-slate-800 p-6 rounded-3xl relative overflow-hidden">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2 mb-3">
                        <i class="ph-bold ph-info text-blue-400"></i> Rappel important
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Pour que ce robot fonctionne, vous devez impérativement aller sur votre Google Drive personnel et <strong>partager</strong> vos dossiers de documents avec l'adresse email de ce robot en lui attribuant le rôle d'<strong>Éditeur</strong>.
                    </p>
                </div>

            </div>

            <!-- Liste des Comptes (Colonnes 5 à 12) -->
            <div class="lg:col-span-8">
                <div class="glass-panel p-6 rounded-3xl shadow-2xl flex flex-col h-full">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-lg font-bold text-white flex items-center gap-3">
                            <i class="ph-fill ph-users text-ai-400 text-xl"></i>
                            Robots de service enregistrés
                        </h2>
                        <span class="bg-blue-500/10 text-blue-400 border border-blue-500/20 px-3 py-1 rounded-full text-xs font-bold">
                            {{ count($googleAccounts ?? []) }} Comptes actifs
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-700/50">
                        <table class="min-w-full divide-y divide-slate-700/50">
                            <thead class="bg-slate-900/50 backdrop-blur-sm">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Compte de Service</th>
                                    <th scope="col" class="px-5 py-3 class text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Sécurité</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Statut</th>
                                    <th scope="col" class="px-5 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 bg-slate-800/20">
                                @forelse($googleAccounts as $account)
                                <tr class="hover:bg-slate-700/30 transition-colors duration-200 group">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-slate-800 rounded-lg text-blue-400 group-hover:bg-blue-500/20 transition-colors">
                                                <i class="ph-fill ph-robot text-xl"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-slate-200">{{ $account->email }}</div>
                                                <div class="text-[10px] text-slate-500">Ajouté le {{ $account->created_at->format('d/m/Y') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 text-[11px] text-emerald-400 font-mono bg-emerald-500/5 border border-emerald-500/20 px-2 py-0.5 rounded-md">
                                            <i class="ph ph-lock-key"></i> Clé Chiffrée (AES-256)
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/20 text-xs font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Opérationnel
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-right">
                                        <form action="{{ route('admin.google-accounts.destroy', $account->id) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment révoquer et supprimer ce compte de service ? Toutes les associations de fichiers Drive associées seront rompues.')" class="m-0 inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 rounded-lg text-sm transition-all">
                                                <i class="ph ph-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-500">
                                            <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center mb-3 border border-slate-700">
                                                <i class="ph-ghost ph-key text-2xl"></i>
                                            </div>
                                            <p class="text-xs font-medium">Aucun compte de service Google.</p>
                                            <p class="text-[10px] mt-1 text-slate-600">Importez votre première clé JSON pour activer le Drive automatique.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Script JS minimaliste pour afficher le nom du fichier JSON sélectionné -->
    <script>
        const fileSelector = document.getElementById('json_file_selector');
        const fileNameField = document.getElementById('file_selected_name');

        if (fileSelector && fileNameField) {
            fileSelector.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    fileNameField.textContent = 'Sélection : ' + e.target.files[0].name;
                    fileNameField.classList.remove('text-slate-500');
                    fileNameField.classList.add('text-green-400', 'font-medium');
                } else {
                    fileNameField.textContent = 'Sélectionnez le fichier .json téléchargé depuis GCP.';
                    fileNameField.classList.remove('text-green-400', 'font-medium');
                    fileNameField.classList.add('text-slate-500');
                }
            });
        }
    </script>
</body>
</html>