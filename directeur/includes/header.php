<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Initialiser l'authentification
$auth = new Auth($db);

// Vérifier si l'utilisateur est connecté et est un directeur
if (!$auth->isLoggedIn() || !$auth->hasRole('directeur')) {
    // Si l'utilisateur n'est pas connecté ou n'est pas directeur, on le redirige vers la page de connexion
    $auth->redirectTo('login.php');
    exit();
}

// Récupérer les informations de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
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
    <title>Directeur - ISSET Tsévié</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Fonction pour initialiser les menus déroulants
        function initDropdowns() {
            console.log('Initialisation des menus déroulants...');
            
            // Fermer tous les menus sauf celui spécifié
            function closeAllMenus(except = null) {
                document.querySelectorAll('.dropdown-content').forEach(menu => {
                    if (menu !== except) {
                        menu.classList.add('hidden');
                    }
                });
            }
            
            // Gestion du menu mobile
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                // Supprimer les anciens écouteurs s'ils existent
                if (window.mobileMenuInitialized) {
                    const newButton = mobileMenuButton.cloneNode(true);
                    mobileMenuButton.parentNode.replaceChild(newButton, mobileMenuButton);
                    newButton.addEventListener('click', handleMobileMenuClick);
                } else {
                    mobileMenuButton.addEventListener('click', handleMobileMenuClick);
                }
                
                window.mobileMenuInitialized = true;
            }
            
            function handleMobileMenuClick(e) {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = mobileMenu.classList.toggle('hidden');
                // Fermer tous les autres menus quand le menu mobile s'ouvre
                if (!isHidden) {
                    closeAllMenus();
                }
            }

            // Gestion des menus déroulants
            document.querySelectorAll('.group > button, .group > a').forEach(button => {
                // Ne pas réinitialiser si déjà initialisé
                if (button.getAttribute('data-dropdown-initialized') === 'true') return;
                
                const menu = button.nextElementSibling;
                if (!menu || !menu.classList.contains('dropdown-content')) return;
                
                // Marquer comme initialisé
                button.setAttribute('data-dropdown-initialized', 'true');
                
                // Gestion du clic sur le bouton
                button.addEventListener('click', function(e) {
                    // Si c'est un lien avec href='#', on empêche le comportement par défaut
                    if (button.tagName === 'A' && button.getAttribute('href') === '#') {
                        e.preventDefault();
                    }
                    e.stopPropagation();
                    
                    // Fermer tous les autres menus
                    closeAllMenus(menu);
                    
                    // Basculer le menu actuel
                    menu.classList.toggle('hidden');
                });
                
                // Gestion du survol sur desktop
                if (window.innerWidth > 768) {
                    button.addEventListener('mouseenter', function() {
                        closeAllMenus(menu);
                        menu.classList.remove('hidden');
                    });
                    
                    // Garder le menu ouvert quand la souris est dessus
                    button.addEventListener('mouseleave', function() {
                        setTimeout(() => {
                            if (!menu.matches(':hover')) {
                                menu.classList.add('hidden');
                            }
                        }, 200);
                    });
                    
                    menu.addEventListener('mouseleave', function() {
                        this.classList.add('hidden');
                    });
                }
            });
            
            // Fermer les menus quand on clique ailleurs
            document.addEventListener('click', function() {
                closeAllMenus();
            });
            
            // Fermer les menus au scroll
            let scrollTimer;
            window.addEventListener('scroll', function() {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(function() {
                    closeAllMenus();
                }, 100);
            }, { passive: true });
            
            console.log('Initialisation des menus terminée');
        }
        
        // Fonction pour initialiser avec un léger délai
        function initializePage() {
            // Attendre que le DOM soit complètement chargé
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    // Petit délai supplémentaire pour s'assurer que tout est chargé
                    setTimeout(initDropdowns, 100);
                });
            } else {
                // Le DOM est déjà chargé, initialiser avec un léger délai
                setTimeout(initDropdowns, 100);
            }
        }
        
        // Réinitialiser les menus lors du redimensionnement de la fenêtre
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                initDropdowns();
            }, 250);
        });
        
        // Démarrer l'initialisation
        initializePage();
        
        // Réinitialiser les menus si la page est chargée via AJAX
        if (window.jQuery) {
            $(document).ajaxComplete(function() {
                console.log('Chargement AJAX détecté, réinitialisation des menus...');
                setTimeout(initDropdowns, 300);
            });
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-blue-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="<?php echo $directeur_url; ?>../../images/logo.jpeg" alt="Logo" class="h-8 w-auto ml-20 mr-2">
                        <span class="text-xl font-bold">ISSET Tsévié</span>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-4">
                        <a href="<?php echo $directeur_url; ?>/index.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?> flex items-center">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Tableau de bord
                        </a>
                        <div class="relative group">
                            <button class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 flex items-center">
                                <i class="fas fa-book mr-2"></i>
                                Gestion Pédagogique
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="absolute left-0 mt-2 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden dropdown-content z-50">
                                <div class="py-1">
                                    <a href="<?php echo $directeur_url; ?>/matieres.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-book-open mr-2 text-blue-600"></i>
                                        Matières et coefficients
                                    </a>
                                    <a href="<?php echo $directeur_url; ?>/bulletins.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-file-alt mr-2 text-blue-600"></i>
                                        Bulletins de notes
                                    </a>
                                    <a href="<?php echo $directeur_url; ?>/proclamations.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-scroll mr-2 text-blue-600"></i>
                                        Fiches de proclamation
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="relative group">
                            <button class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 flex items-center">
                                <i class="fas fa-users-cog mr-2"></i>
                                Administration
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="absolute left-0 mt-2 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden dropdown-content z-50">
                                <div class="py-1">
                                    <a href="<?php echo $directeur_url; ?>/comptes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-user-plus mr-2 text-blue-600"></i>
                                        Gestion des comptes
                                    </a>
                                    <a href="<?php echo $directeur_url; ?>/affectations.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-chalkboard-teacher mr-2 text-blue-600"></i>
                                        Affectations professeurs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="hidden md:ml-4 md:flex-shrink-0 md:flex md:items-center">
                        <div class="relative">
                            <div class="flex items-center text-sm rounded-full text-white focus:outline-none">
                                <i class="fas fa-user-circle text-2xl mr-2"></i>
                                <span class="mr-2"><?php echo htmlspecialchars($user_name ?? 'Directeur'); ?></span>
                                <a href="<?php echo $base_url; ?>/logout.php" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium bg-red-600 hover:bg-red-700">
                                    <i class="fas fa-sign-out-alt mr-1"></i>
                                    Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-blue-700 focus:outline-none" id="mobile-menu-button">
                            <span class="sr-only">Ouvrir le menu</span>
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Mobile menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="<?php echo $directeur_url; ?>/index.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Tableau de bord
                </a>
                <a href="<?php echo $directeur_url; ?>/matieres.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    <i class="fas fa-book mr-2"></i>
                    Matières et coefficients
                </a>
                <a href="<?php echo $directeur_url; ?>/bulletins.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    <i class="fas fa-file-alt mr-2"></i>
                    Bulletins de notes
                </a>
                <a href="<?php echo $directeur_url; ?>/proclamations.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    <i class="fas fa-scroll mr-2"></i>
                    Fiches de proclamation
                </a>
                <a href="<?php echo $directeur_url; ?>/comptes.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    <i class="fas fa-users-cog mr-2"></i>
                    Gestion des comptes
                </a>
                <a href="<?php echo $directeur_url; ?>/affectations.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    Affectations professeurs
                </a>
                <a href="<?php echo $base_url; ?>/logout.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-red-600">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white shadow rounded-lg p-6">
            <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
