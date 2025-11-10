<?php
session_start();
require_once 'includes/auth.php';

// Rediriger si pas connecté ou si le mot de passe a déjà été changé
if (!$auth->isLoggedIn() || $_SESSION['first_login'] == 0) {
    $auth->redirectUser();
}

$error = '';
$success = '';

// Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (strlen($newPassword) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        // Mettre à jour le mot de passe
        if ($auth->updatePassword($_SESSION['user_id'], $newPassword)) {
            $_SESSION['first_login'] = 0;
            $success = 'Votre mot de passe a été mis à jour avec succès.';
            
            // Déterminer la page de destination en fonction du rôle
            $role_pages = [
                'professeur' => 'professeur/index.php',
                'secretaire' => 'secretaire/index.php',
                'directeur' => 'directeur/index.php'
            ];
            
            $target_page = $role_pages[$_SESSION['user_role']] ?? 'index.php';
            
            // Rediriger après 2 secondes vers la page appropriée
            header('Refresh: 2; URL=' . $target_page);
        } else {
            $error = 'Une erreur est survenue lors de la mise à jour du mot de passe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de mot de passe - ISSET Tsévié</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="text-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="mx-auto h-12 w-12 text-blue-600" 
                 fill="none" viewBox="0 0 24 24" 
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h1 class="text-2xl font-bold text-gray-900 mt-4">
                Changement de mot de passe
            </h1>
            <p class="text-gray-600 mt-2">
                Pour des raisons de sécurité, vous devez changer votre mot de passe.
            </p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
        </div>
        <?php endif; ?>

        <form action="change-password.php" method="POST" class="space-y-6">
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Nouveau mot de passe
                </label>
                <div class="relative">
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           required
                           minlength="8"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                           placeholder="Saisissez votre nouveau mot de passe">
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Le mot de passe doit contenir au moins 8 caractères.
                </p>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmer le mot de passe
                </label>
                <div class="relative">
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required
                           minlength="8"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                           placeholder="Confirmez votre nouveau mot de passe">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</body>
</html>