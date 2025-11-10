<?php
require_once 'includes/auth.php';
require_once 'config/log_config.php';

$error = '';
$success = '';

// Vérifier si l'utilisateur vient de se déconnecter
if (isset($_GET['logged_out']) && $_GET['logged_out'] == 1) {
    $success = 'Vous avez été déconnecté avec succès.';
}

// Initialisation de la session si elle n'existe pas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rediriger si déjà connecté
if ($auth->isLoggedIn()) {
    app_log("Redirection depuis login.php - utilisateur déjà connecté", [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null
    ]);
    $auth->redirectUser();
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    app_log("Tentative de connexion depuis le formulaire", [
        'email' => $email,
        'has_password' => !empty($password)
    ]);
    
    if ($auth->login($email, $password)) {
        app_log("Redirection après connexion réussie", [
            'user_id' => $_SESSION['user_id'],
            'user_role' => $_SESSION['user_role'],
            'session' => $_SESSION
        ]);
        // La redirection est maintenant gérée dans la méthode login()
        exit();
    } else {
        app_log("Échec de la connexion", [
            'email' => $email,
            'error' => 'Identifiants invalides'
        ]);
        $error = 'Identifiants invalides. Veuillez réessayer.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion - ISSET Tsévié</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-100 flex items-center justify-center min-h-screen">

  <div class="flex flex-col p-8 md:mx-12 md:px-12 md:flex-row bg-white rounded-2xl shadow-xl overflow-hidden w-11/12 md:w-3/4 lg:w-full">

    <!-- Image à gauche -->
    <div class="md:w-1/2 hidden md:block">
      <img 
        src="images/home.jpg" 
        alt="Campus ISSET Tsévié" 
        class="object-cover w-full h-full"
      />
    </div>

    <!-- Formulaire à droite -->
    <div class="w-full md:w-1/2 flex items-center justify-center p-10">
      <div class="w-full max-w-md space-y-6">

        <!-- Logo et titre -->
        <div class="text-center">
          <svg xmlns="http://www.w3.org/2000/svg" 
               class="mx-auto h-12 w-12 text-blue-600" 
               fill="none" viewBox="0 0 24 24" 
               stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 14l6.16-3.422A12.083 12.083 0 0118 20.944V21l-6-3-6 3v-.056a12.083 12.083 0 01-.16-10.366L12 14z" />
          </svg>
          <h2 class="mt-4 text-2xl font-bold text-gray-900">
            Connexion - ISSET Tsévié
          </h2>
          <p class="mt-1 text-gray-500 text-sm">
            Entrez vos identifiants pour accéder à la plateforme
          </p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form action="/isset/login.php" method="POST" class="space-y-5">
          
          <!-- Email -->
          <div>
            <label class="block text-gray-700 mb-1" for="email">Adresse email</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <!-- Icône Email -->
                <svg xmlns="http://www.w3.org/2000/svg" 
                     class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M16 12H8m0 0l4-4m-4 4l4 4" />
                </svg>
              </span>
              <input type="email" id="email" name="email" required
                     class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg 
                            focus:ring-2 focus:ring-blue-500 focus:outline-none"
                     placeholder="exemple@isset-tsevie.tg">
            </div>
          </div>

          <!-- Mot de passe -->
          <div>
            <label class="block text-gray-700 mb-1" for="password">Mot de passe</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <!-- Icône mot de passe -->
                <svg xmlns="http://www.w3.org/2000/svg" 
                     class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 11c.66 0 1.2-.54 1.2-1.2S12.66 8.6 12 8.6 10.8 9.14 10.8 9.8s.54 1.2 1.2 1.2z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M17 9V7a5 5 0 00-10 0v2m10 0H7m10 0v10a2 2 0 01-2 2H9a2 2 0 01-2-2V9h10z" />
                </svg>
              </span>
              <input type="password" id="password" name="password" required
                     class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg 
                            focus:ring-2 focus:ring-blue-500 focus:outline-none"
                     placeholder="••••••••">
            </div>
          </div>

          <!-- Options -->
          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center">
              <input type="checkbox" name="remember" 
                     class="h-4 w-4 text-blue-600 border-gray-300 rounded">
              <span class="ml-2 text-gray-600">Se souvenir de moi</span>
            </label>
            <a href="#" class="text-blue-600 hover:underline">Mot de passe oublié ?</a>
          </div>

          <!-- Bouton -->
          <button type="submit"
                  class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 
                         text-white font-medium py-3 rounded-lg shadow transition">
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M17 16l4-4m0 0l-4-4m4 4H7" />
            </svg>
            Se connecter
          </button>
        </form>
      </div>
    </div>
  </div>

</body>
</html>
