<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statut de votre commande</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center" id="status-card">

        <!-- En Attente (Affiché par défaut si pending) -->
        <div id="pending-ui" class="{{ $transaction->status === 'pending' ? 'block' : 'hidden' }}">
            <div class="animate-spin inline-block w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full mb-4"></div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Vérification en cours...</h2>
            <p class="text-gray-500 mb-4">Nous attendons la confirmation de votre paiement mobile money. Ne fermez pas cette page.</p>
        </div>

        <!-- Succès (Affiché si success) -->
        <div id="success-ui" class="{{ $transaction->status === 'success' ? 'block' : 'hidden' }}">
            <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Paiement Réussi !</h2>
            <p class="text-gray-600 mb-4">Merci pour l'achat de <strong>{{ $transaction->document->title }}</strong>.</p>

            <div class="bg-blue-50 p-4 rounded-lg mb-6 text-sm text-blue-800 text-left">
                Le fichier a été partagé automatiquement avec l'adresse : <br>
                <strong>{{ $transaction->customer->email }}</strong>
            </div>

            <a id="drive-btn" href="{{ $transaction->shared_drive_link ?? '#' }}" target="_blank"
               class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md">
                Ouvrir le fichier Google Drive
            </a>

            <a href="https://wa.me/{{ config('services.whatsapp.bot_number') }}"
               class="inline-block w-full mt-3 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md">
                Retourner sur WhatsApp
            </a>
        </div>

        <!-- Échec -->
        <div id="failed-ui" class="{{ $transaction->status === 'failed' ? 'block' : 'hidden' }}">
            <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Paiement Échoué</h2>
            <p class="text-gray-500 mb-6">La transaction n'a pas pu aboutir. Veuillez réessayer ou contacter le support.</p>
            <a href="https://wa.me/{{ config('services.whatsapp.bot_number') }}" class="inline-block bg-gray-800 text-white py-2 px-6 rounded-lg">Retour sur WhatsApp</a>
        </div>

    </div>

    <!-- Logique Javascript pour le temps réel -->
    <script>
        const reference = "{{ $transaction->reference }}";
        let currentStatus = "{{ $transaction->status }}";

        if (currentStatus === 'pending') {
            const checkInterval = setInterval(() => {
                fetch(`/api/payment/status/${reference}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            clearInterval(checkInterval);

                            // Mettre à jour l'UI
                            document.getElementById('pending-ui').classList.add('hidden');
                            document.getElementById('success-ui').classList.remove('hidden');
                            document.getElementById('success-ui').classList.add('block');

                            // Mettre à jour le lien Drive
                            const driveBtn = document.getElementById('drive-btn');
                            driveBtn.href = data.drive_link;
                        } else if (data.status === 'failed') {
                            clearInterval(checkInterval);
                            document.getElementById('pending-ui').classList.add('hidden');
                            document.getElementById('failed-ui').classList.remove('hidden');
                            document.getElementById('failed-ui').classList.add('block');
                        }
                    })
                    .catch(err => console.error(err));
            }, 5000); // Vérifie toutes les 5 secondes
        }
    </script>
</body>
</html>
