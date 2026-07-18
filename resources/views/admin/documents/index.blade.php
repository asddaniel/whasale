<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des Documents IA</title>

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

        /* Glass effect pour la table et les cartes */
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
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-br from-ai-400 to-blue-500 p-2 rounded-xl shadow-lg shadow-ai-500/20">
                    <i class="ph ph-robot text-2xl text-white"></i>
                </div>
                <div class="font-bold text-xl tracking-wide text-white">
                    IA-DRIVE <span class="text-ai-400 font-medium">ADMIN</span>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="hidden sm:flex items-center gap-2 text-sm text-slate-400 bg-slate-900/50 py-1.5 px-4 rounded-full border border-slate-800">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    Connecté : <strong class="text-slate-200">{{ Auth::user()->name }}</strong>
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
                    Gestion du Drive Multi-Comptes
                </h1>
                <p class="text-slate-400 flex items-center gap-2">
                    <i class="ph ph-brain text-ai-400"></i> Automatisez l'accès à vos fichiers Drive via vos comptes de service.
                </p>
            </div>
        </div>

        <!-- Alertes de succès ou d'erreurs -->
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

            <!-- COLONNE GAUCHE : Formulaires (Colonnes 1 à 4) -->
            <div class="lg:col-span-4 space-y-8">

                <!-- FORMULAIRE : Nouveau Document -->
                <div class="glass-panel p-6 rounded-3xl shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-ai-500/10 rounded-full blur-2xl -mr-10 -mt-10"></div>

                    <h2 class="text-lg font-bold mb-5 flex items-center gap-3 text-white">
                        <i class="ph-fill ph-plus-circle text-ai-400 text-xl"></i>
                        Nouveau Document
                    </h2>

                    <form action="{{ route('admin.documents.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Titre du document</label>
                            <input type="text" name="title" required class="w-full bg-slate-900/50 border border-slate-700/80 focus:border-ai-500 focus:ring-1 focus:ring-ai-500 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 transition-all outline-none" placeholder="Ex: Formation IA 2024">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Description</label>
                            <textarea name="description" rows="2" class="w-full bg-slate-900/50 border border-slate-700/80 focus:border-ai-500 focus:ring-1 focus:ring-ai-500 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 transition-all outline-none resize-none" placeholder="Brève description..."></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">ID du Fichier Google Drive</label>
                            <div class="relative">
                                <i class="ph ph-google-drive-logo absolute left-3.5 top-3 text-slate-400 text-base"></i>
                                <input type="text" name="drive_file_id" required placeholder="1A2b3C4d5E6f..." class="w-full bg-slate-900/50 border border-slate-700/80 focus:border-ai-500 focus:ring-1 focus:ring-ai-500 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-200 placeholder-slate-500 transition-all outline-none font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Prix</label>
                                <input type="number" step="0.01" name="price" required placeholder="0.00" class="w-full bg-slate-900/50 border border-slate-700/80 focus:border-ai-500 focus:ring-1 focus:ring-ai-500 rounded-xl px-4 py-2.5 text-sm text-slate-200 transition-all outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Devise</label>
                                <select name="currency" class="w-full bg-slate-900/50 border border-slate-700/80 focus:border-ai-500 focus:ring-1 focus:ring-ai-500 rounded-xl px-4 py-2.5 text-sm text-slate-200 transition-all outline-none appearance-none">
                                    <option value="USD">USD ($)</option>
                                    <option value="CDF">CDF (FC)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Compte de Service Propriétaire</label>
                            <div class="relative">
                                <select name="google_account_id" required class="w-full bg-slate-900/50 border border-slate-700/80 focus:border-ai-500 focus:ring-1 focus:ring-ai-500 rounded-xl px-4 py-2.5 text-sm text-slate-200 transition-all outline-none appearance-none">
                                    <option value="" class="text-slate-500">Sélectionner un compte robot</option>
                                    @foreach($googleAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->email }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-2 group relative inline-flex items-center justify-center gap-2 bg-gradient-to-r from-ai-600 to-blue-600 hover:from-ai-500 hover:to-blue-500 text-white font-bold py-3 px-5 rounded-xl shadow-[0_0_15px_rgba(124,58,237,0.2)] hover:shadow-[0_0_25px_rgba(124,58,237,0.4)] transition-all duration-300">
                            <i class="ph-bold ph-floppy-disk text-base"></i>
                            Enregistrer le document
                        </button>
                    </form>
                </div>

                <!-- FORMULAIRE : Nouveau Compte de Service Google -->
                <div class="glass-panel p-6 rounded-3xl shadow-2xl relative overflow-hidden border border-slate-800">
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl -ml-10 -mb-10"></div>

                    <h2 class="text-lg font-bold mb-5 flex items-center gap-3 text-white">
                        <i class="ph-fill ph-key text-blue-400 text-xl"></i>
                        Ajouter un Compte de Service
                    </h2>

                    <!-- Note: Cette route correspond à la création de comptes de service décrits dans le contrôleur bonus -->
                    <form action="{{ route('admin.google-accounts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Adresse Email du Robot</label>
                            <input type="email" name="email" required class="w-full bg-slate-900/50 border border-slate-700/80 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 transition-all outline-none" placeholder="Ex: drive-robot@project...gserviceaccount.com">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Fichier de Clé JSON Google</label>
                            <div class="relative bg-slate-900/50 border border-slate-700/80 rounded-xl hover:border-blue-500 transition-all">
                                <label class="flex flex-col items-center justify-center py-4 px-4 cursor-pointer text-slate-400 hover:text-slate-200">
                                    <i class="ph ph-upload-simple text-2xl mb-1.5 text-blue-400"></i>
                                    <span class="text-xs font-medium">Uploader le fichier .json</span>
                                    <input type="file" name="service_account_file" accept=".json" required class="hidden" id="json_upload_input">
                                </label>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1.5" id="file_selected_name">Aucun fichier sélectionné (JSON Google Cloud requis).</p>
                        </div>

                        <button type="submit" class="w-full mt-2 group relative inline-flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold py-3 px-5 rounded-xl border border-slate-700 transition-all duration-300">
                            <i class="ph-bold ph-shield-check text-base text-blue-400"></i>
                            Enregistrer le robot
                        </button>
                    </form>
                </div>

            </div>

            <!-- COLONNE DROITE : Listes (Colonnes 5 à 12) -->
            <div class="lg:col-span-8 space-y-8">

                <!-- SECTION 1 : Base de données des Documents -->
                <div class="glass-panel p-6 rounded-3xl shadow-2xl flex flex-col">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-lg font-bold text-white flex items-center gap-3">
                            <i class="ph-fill ph-database text-ai-400 text-xl"></i>
                            Base de données IA (Fichiers)
                        </h2>
                        <span class="bg-ai-500/10 text-ai-400 border border-ai-500/20 px-3 py-1 rounded-full text-xs font-bold">
                            {{ count($documents ?? []) }} Fichiers synchronisés
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-700/50">
                        <table class="min-w-full divide-y divide-slate-700/50">
                            <thead class="bg-slate-900/50 backdrop-blur-sm">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Document</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Prix</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Compte Robot Source</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">ID Drive</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 bg-slate-800/20">
                                @forelse($documents as $doc)
                                <tr class="hover:bg-slate-700/30 transition-colors duration-200 group">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-slate-800 rounded-lg group-hover:bg-ai-500/20 group-hover:text-ai-400 transition-colors">
                                                <i class="ph-fill ph-file-text text-lg text-slate-400 group-hover:text-ai-400"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-200">{{ $doc->title }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold">
                                            <i class="ph-bold ph-tag"></i> {{ $doc->price }} {{ $doc->currency }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-2 text-xs text-slate-400">
                                            <i class="ph ph-robot text-slate-500"></i>
                                            {{ $doc->googleAccount->email ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-[11px] text-slate-500 bg-slate-900/80 px-2 py-0.5 rounded-md border border-slate-700">
                                                {{ Str::limit($doc->drive_file_id, 16) }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-500">
                                            <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center mb-3 border border-slate-700">
                                                <i class="ph-ghost ph-files text-2xl"></i>
                                            </div>
                                            <p class="text-xs font-medium">Aucun document n'est disponible.</p>
                                            <p class="text-[10px] mt-1 text-slate-600">Ajoutez-en un depuis le formulaire pour commencer.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECTION 2 : Base des Comptes de Service Google Configurés -->
                <div class="glass-panel p-6 rounded-3xl shadow-2xl flex flex-col">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-lg font-bold text-white flex items-center gap-3">
                            <i class="ph-fill ph-shield-check text-blue-400 text-xl"></i>
                            Comptes de Service Connectés
                        </h2>
                        <span class="bg-blue-500/10 text-blue-400 border border-blue-500/20 px-3 py-1 rounded-full text-xs font-bold">
                            {{ count($googleAccounts ?? []) }} Robots Actifs
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-700/50">
                        <table class="min-w-full divide-y divide-slate-700/50">
                            <thead class="bg-slate-900/50 backdrop-blur-sm">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Identifiant (Email Google Cloud)</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Type de compte</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 bg-slate-800/20">
                                @forelse($googleAccounts as $account)
                                <tr class="hover:bg-slate-700/30 transition-colors duration-200">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-slate-800 rounded-lg text-blue-400">
                                                <i class="ph-fill ph-key text-lg"></i>
                                            </div>
                                            <div class="text-sm font-medium text-slate-200">{{ $account->email }}</div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="text-xs text-slate-400 font-mono">Service Account (JSON)</span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/20 text-xs font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Actif
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-500">
                                            <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center mb-3 border border-slate-700">
                                                <i class="ph-ghost ph-users text-2xl"></i>
                                            </div>
                                            <p class="text-xs font-medium">Aucun compte de service enregistré.</p>
                                            <p class="text-[10px] mt-1 text-slate-600">Importez un fichier JSON Google Cloud pour configurer votre premier robot.</p>
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
        const uploadInput = document.getElementById('json_upload_input');
        const fileNameDisplay = document.getElementById('file_selected_name');

        if (uploadInput && fileNameDisplay) {
            uploadInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    fileNameDisplay.textContent = 'Fichier sélectionné : ' + e.target.files[0].name;
                    fileNameDisplay.classList.remove('text-slate-500');
                    fileNameDisplay.classList.add('text-green-400', 'font-medium');
                } else {
                    fileNameDisplay.textContent = 'Aucun fichier sélectionné (JSON Google Cloud requis).';
                    fileNameDisplay.classList.remove('text-green-400', 'font-medium');
                    fileNameDisplay.classList.add('text-slate-500');
                }
            });
        }
    </script>
</body>
</html>