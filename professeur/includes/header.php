<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Initialiser l'authentification
$auth = new Auth($db);

// Vérifier si l'utilisateur est connecté et est un professeur
if (!$auth->isLoggedIn() || !$auth->hasRole('professeur')) {
    header('Location: /isset/login.php');
    exit();
}

// Récupérer les informations de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $auth->getUserName();

// Définir le chemin de base pour les liens
$base_url = '/isset';
$professeur_url = $base_url . '/professeur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professeur - ISSET Tsévié</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .active {
            background-color: #3b82f6;
            color: white;
        }
        .active:hover {
            background-color: #2563eb;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0.375rem;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-blue-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="<?php echo $base_url; ?>/images/logo.jpeg" alt="Logo" class="h-8 w-auto ml-20 mr-2">
                        <span class="text-xl font-bold">ISSET Tsévié</span>
                    </div>
                    <div class="hidden md:ml-10 md:flex md:space-x-4">
                        <!-- Lien vers le tableau de bord -->
                        <a href="<?php echo $professeur_url; ?>/index.php" 
                           class="px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                            Tableau de bord
                        </a>
                        
                        <!-- Lien vers la saisie des notes -->
                        <a href="<?php echo $professeur_url; ?>/saisie_notes.php" 
                           class="px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'saisie_notes.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                            Saisie des notes
                        </a>
                    </div>
                </div>
                
                <!-- Avatar et bouton de déconnexion -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-blue-700 flex items-center justify-center text-white mr-2">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <span class="hidden md:inline text-sm font-medium">
                            <?php echo htmlspecialchars($user_name); ?>
                        </span>
                    </div>
                    <a href="<?php echo $base_url; ?>/logout.php" 
                       class="hidden md:inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-700 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                    </a>
                </div>
                
                <!-- Bouton menu mobile -->
                <div class="-mr-2 flex items-center md:hidden">
                    <button type="button" 
                            class="inline-flex items-center justify-center p-2 rounded-md text-blue-200 hover:text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-blue-800 focus:ring-white" 
                            id="mobile-menu-button" 
                            aria-expanded="false" 
                            aria-haspopup="true">
                        <span class="sr-only">Ouvrir le menu principal</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu mobile -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="<?php echo $professeur_url; ?>/index.php" 
                   class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-900' : ''; ?>">
                    Tableau de bord
                </a>
                <a href="<?php echo $professeur_url; ?>/saisie_notes.php" 
                   class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'saisie_notes.php' ? 'bg-blue-900' : ''; ?>">
                    Saisie des notes
                </a>
            </div>
            <div class="pt-4 pb-3 border-t border-blue-700">
                <div class="flex items-center px-5">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-blue-700 flex items-center justify-center text-white">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-white"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="text-sm font-medium text-blue-300"><?php echo ucfirst($user_role); ?></div>
                    </div>
                </div>
                <div class="mt-3 px-2 space-y-1">
                    <a href="<?php echo $base_url; ?>/change-password.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-blue-200 hover:text-white hover:bg-blue-700">
                        <i class="fas fa-key mr-2"></i> Changer le mot de passe
                    </a>
                    <a href="<?php echo $base_url; ?>/logout.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-blue-200 hover:text-white hover:bg-blue-700">
                        <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
