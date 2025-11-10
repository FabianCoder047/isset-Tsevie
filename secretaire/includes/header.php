<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Initialiser l'authentification
$auth = new Auth($db);

// Vérifier si l'utilisateur est connecté et est une secrétaire
if (!$auth->isLoggedIn() || !$auth->hasRole('secretaire')) {
    // Si l'utilisateur n'est pas connecté ou n'est pas secrétaire, on le redirige vers la page de connexion
    header('Location: /isset/login.php');
    exit();
}

// Récupérer les informations de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $auth->getUserName();

// Définir le chemin de base pour les liens
$base_url = '/isset';
$directeur_url = $base_url . '/directeur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secrétaire - ISSET Tsévié</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        // Fonction pour initialiser les menus déroulants
        function initDropdowns() {
            // Fermer tous les menus sauf celui spécifié
            function closeAllMenus(except = null) {
                document.querySelectorAll('.dropdown-content').forEach(menu => {
                    if (menu !== except) {
                        menu.classList.add('hidden');
                    }
                });
            }
            
            // Gestionnaire d'événements pour les boutons de menu
            document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
                const menu = document.getElementById(button.getAttribute('data-dropdown-toggle'));
                if (!menu) return;
                
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !menu.classList.contains('hidden');
                    closeAllMenus();
                    if (!isOpen) {
                        menu.classList.remove('hidden');
                    }
                });
            });
            
            // Fermer les menus quand on clique ailleurs
            document.addEventListener('click', () => closeAllMenus());
        }
        
        // Initialiser les menus au chargement de la page
        document.addEventListener('DOMContentLoaded', initDropdowns);
    </script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-blue-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="<?php echo $secretaire_url; ?>../../images/logo.jpeg" alt="Logo" class="h-8 w-auto ml-20 mr-2">
                        <span class="text-xl font-bold">ISSET Tsévié</span>
                    </div>
                    <div class="hidden md:ml-10 md:flex md:space-x-4">
                        <!-- Lien vers le tableau de bord -->
                        <a href="<?php echo $secretaire_url; ?>/index.php" 
                           class="px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                            Tableau de bord
                        </a>
                        
                        <!-- Menu déroulant Inscription -->
                        <div class="relative group">
                            <button type="button" 
                                    onclick="toggleDropdown('inscription-menu')" 
                                    class="px-3 py-2 rounded-md text-sm font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'inscription.php' || basename($_SERVER['PHP_SELF']) == 'eleves.php') ? 'bg-blue-900' : 'hover:bg-blue-700'; ?> flex items-center">
                                Inscription
                                <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div id="inscription-menu" 
                                 class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                <div class="py-1" role="menu" aria-orientation="vertical">
                                    <a href="<?php echo $secretaire_url; ?>/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                        Nouvelle inscription
                                    </a>
                                    <a href="<?php echo $secretaire_url; ?>/eleves.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                        Liste des élèves
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                
                    </div>
                </div>
                
                <!-- Avatar et bouton de déconnexion -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-blue-700 flex items-center justify-center text-white mr-2">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <span class="text-white text-sm font-medium"><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                    <a href="<?php echo $base_url; ?>/logout.php" class="ml-4 px-3 py-2 rounded-md text-sm font-medium text-white bg-red-700 hover:bg-red-600">
                        <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                    </a>
                    
                    <!-- Bouton menu mobile -->
                    <div class="-mr-2 flex items-center md:hidden">
                        <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-blue-200 hover:text-white hover:bg-blue-700 focus:outline-none" id="mobile-menu-button">
                            <span class="sr-only">Ouvrir le menu principal</span>
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu mobile -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="<?php echo $secretaire_url; ?>/index.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    Tableau de bord
                </a>
                <a href="<?php echo $secretaire_url; ?>/inscription.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    Inscription
                </a>
                <a href="<?php echo $secretaire_url; ?>/eleves.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    Liste des élèves
                </a>
                <a href="<?php echo $secretaire_url; ?>/statistiques.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    Statistiques
                </a>
                <div class="border-t border-blue-700 pt-4 pb-3">
                    <div class="flex items-center px-5">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle text-2xl text-blue-300"></i>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-white"><?php echo htmlspecialchars($user_name); ?></div>
                            <div class="text-sm font-medium text-blue-300">Secrétaire</div>
                        </div>
                    </div>
                    <div class="mt-3 px-2 space-y-1">
                        <a href="<?php echo $base_url; ?>/change-password.php" class="block px-3 py-2 rounded-md text-base font-medium text-blue-200 hover:text-white hover:bg-blue-700">
                            Changer le mot de passe
                        </a>
                        <a href="<?php echo $base_url; ?>/logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-blue-200 hover:text-white hover:bg-blue-700">
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Script pour les menus déroulants -->
    <script>
        // Fonction pour basculer l'affichage d'un menu déroulant
        function toggleDropdown(menuId) {
            const menu = document.getElementById(menuId);
            if (!menu) return;
            
            // Fermer tous les autres menus
            document.querySelectorAll('.group > div[id$="-menu"]').forEach(m => {
                if (m.id !== menuId) m.classList.add('hidden');
            });
            
            // Basculer le menu actuel
            menu.classList.toggle('hidden');
            
            // Fermer le menu si on clique à nouveau sur le bouton
            const button = document.querySelector(`[onclick*="${menuId}"]`);
            if (button && button.getAttribute('aria-expanded') === 'true' && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
            }
            
            // Mettre à jour l'attribut aria-expanded
            if (button) {
                button.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
            }
        }

        // Fermer les menus quand on clique à l'extérieur
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.group')) {
                document.querySelectorAll('.group > div[id$="-menu"]').forEach(menu => {
                    menu.classList.add('hidden');
                    const button = document.querySelector(`[onclick*="${menu.id}"]`);
                    if (button) button.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // Menu mobile
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('hidden');
                this.setAttribute('aria-expanded', mobileMenu.classList.contains('hidden') ? 'false' : 'true');
            }
        });
    </script>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white shadow rounded-lg p-6">
