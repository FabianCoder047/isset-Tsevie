<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/log_config.php';
require_once 'db.php';

// Initialisation du log
app_log('Initialisation de la classe Auth');

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Vérifier si l'utilisateur est connecté
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Vérifier si l'utilisateur a changé son mot de passe
    public function hasChangedPassword($userId) {
        $stmt = $this->db->prepare("SELECT first_login FROM utilisateurs WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user && $user['first_login'] == 0;
    }
    
    // Connecter l'utilisateur
    public function login($email, $password) {
        app_log("Tentative de connexion", ['email' => $email]);
        
        try {
            $stmt = $this->db->prepare("SELECT id, nom, password, role, first_login FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                app_log("Utilisateur trouvé", [
                    'user_id' => $user['id'],
                    'role' => $user['role'],
                    'first_login' => $user['first_login']
                ]);
                
                $passwordMatch = password_verify($password, $user['password']);
                
                if ($passwordMatch) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nom'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['first_login'] = (int)$user['first_login'];
                    
                    app_log("Connexion réussie", [
                        'user_id' => $user['id'],
                        'session' => $_SESSION
                    ]);
                    
                    // Mettre à jour la date de dernière connexion
                    $this->updateLastLogin($user['id']);
                    
                    // Forcer la redirection après connexion réussie
                    app_log("Redirection après connexion réussie", [
                        'user_id' => $user['id'],
                        'role' => $user['role'],
                        'first_login' => $user['first_login']
                    ]);
                    
                    // Déterminer la page de destination
                    $role_pages = [
                        'professeur' => 'professeur/index.php',
                        'secretaire' => 'secretaire/index.php',
                        'directeur' => 'directeur/index.php'
                    ];
                    
                    $target_page = $role_pages[$user['role']] ?? 'index.php';
                    
                    app_log("Redirection vers: " . $target_page);
                    $this->redirectTo($target_page);
                    exit();
                } else {
                    app_log("Échec de la connexion: mot de passe incorrect", ['email' => $email]);
                }
            } else {
                app_log("Échec de la connexion: utilisateur non trouvé", ['email' => $email]);
            }
        } catch (Exception $e) {
            app_log("Erreur lors de la tentative de connexion", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return false;
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE utilisateurs SET password = ?, first_login = 0 WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    // Mettre à jour la date de dernière connexion
    private function updateLastLogin($userId) {
        // Pas de champ last_login dans la table, on pourrait l'ajouter si nécessaire
        // $stmt = $this->db->prepare("UPDATE utilisateurs SET last_login = NOW() WHERE id = ?");
        // $stmt->execute([$userId]);
    }
    
    // Déconnecter l'utilisateur
    public function logout() {
        session_destroy();
        session_unset();
    }
    
    // Rediriger l'utilisateur selon son rôle
    // @param bool $force_redirect Forcer la redirection même depuis une page d'authentification
    public function redirectUser($force_redirect = false) {
        app_log("Méthode redirectUser appelée", [
            'script_name' => $_SERVER['SCRIPT_NAME'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'session' => $_SESSION,
            'force_redirect' => $force_redirect
        ]);
        
        // Si on est sur la page de login et qu'on force la redirection, on continue
        if ($force_redirect) {
            app_log("Redirection forcée depuis la page de connexion");
            // On ne return pas, on continue pour la redirection
        } 
        // Sinon, on vérifie si on est sur une page d'authentification
        else {
            $current_script = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH));
            if (in_array($current_script, ['login.php', 'change-password.php', 'logout.php'])) {
                app_log("Redirection annulée - déjà sur une page d'authentification");
                return;
            }
        }

        if (!$this->isLoggedIn()) {
            $this->redirectTo('login.php');
            return;
        }
        
        // Vérifier si c'est la première connexion
        if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1) {
            $current_script = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH));
            if ($current_script !== 'change-password.php') {
                // Utiliser un chemin absolu pour éviter les problèmes de répertoire
                $this->redirectTo('/isset/change-password.php');
                return;
            }
            return;
        }
        
        // Récupérer le rôle de l'utilisateur
        if (!isset($_SESSION['user_role'])) {
            app_log("Redirection vers index.php - rôle utilisateur non défini");
            $this->redirectTo('index.php');
            return;
        }
        
        // Déterminer la page cible en fonction du rôle
        $role_pages = [
            'professeur' => 'professeur/index.php',
            'secretaire' => 'secretaire/index.php',
            'directeur' => 'directeur/index.php'
        ];
        
        $target_page = $role_pages[$_SESSION['user_role']] ?? 'index.php';
        
        // Vérifier si nous sommes déjà sur la bonne page
        $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $target_path = trim($target_page, '/');
        
        // Si nous ne sommes pas déjà sur la page cible, on redirige
        if (strpos($current_path, $target_path) === false) {
            app_log("Redirection vers la page cible", [
                'current_path' => $current_path,
                'target_path' => $target_path,
                'target_page' => $target_page
            ]);
            $this->redirectTo($target_page);
        } else {
            app_log("Aucune redirection nécessaire - déjà sur la page cible", [
                'current_path' => $current_path,
                'target_path' => $target_path
            ]);
        }
    }
    
    // Méthode utilitaire pour gérer les redirections
    private function redirectTo($path) {
        // Si le chemin commence par /, c'est un chemin absolu
        if (strpos($path, '/') === 0) {
            // C'est déjà un chemin absolu, on l'utilise tel quel
            $full_url = $path;
        } 
        // Si c'est une URL complète, on l'utilise telle quelle
        elseif (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            $full_url = $path;
        } 
        // Sinon, on construit l'URL complète
        else {
            // Déterminer le protocole (http ou https)
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            
            // Déterminer l'hôte
            $host = $_SERVER['HTTP_HOST'];
            
            // Construire le chemin de base
            $base_path = '';
            
            // Si on est dans un sous-dossier, on l'ajoute au chemin
            $script_name = dirname($_SERVER['SCRIPT_NAME']);
            if ($script_name !== '/') {
                $base_path = rtrim($script_name, '/');
            }
            
            // Construire l'URL complète
            $full_url = rtrim($protocol . $host . $base_path, '/') . '/' . ltrim($path, '/');
        }
        
        // Nettoyer les doubles slashes sauf après le protocole
        $full_url = preg_replace('/([^:])(\/\/)/', '$1/', $full_url);
        
        // URL actuelle
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
                      $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // Normaliser les URL pour la comparaison
        $normalized_current = rtrim(strtolower($current_url), '/');
        $normalized_target = rtrim(strtolower($full_url), '/');
        
        // Log des informations de débogage
        app_log("Tentative de redirection", [
            'current_url' => $current_url,
            'target_url' => $full_url,
            'normalized_current' => $normalized_current,
            'normalized_target' => $normalized_target,
            'path_param' => $path,
            'server' => [
                'HTTPS' => $_SERVER['HTTPS'] ?? 'non défini',
                'HTTP_HOST' => $_SERVER['HTTP_HOST'],
                'REQUEST_URI' => $_SERVER['REQUEST_URI'],
                'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME']
            ],
            'session' => $_SESSION
        ]);
        
        // Vérifier si nous ne sommes pas déjà sur cette URL
        if ($normalized_current !== $normalized_target) {
            app_log("Redirection vers: " . $full_url);
            
            // Vérifier si les en-têtes ont déjà été envoyés
            if (headers_sent($filename, $linenum)) {
                app_log("Erreur: Les en-têtes ont déjà été envoyés dans $filename à la ligne $linenum");
                echo "<script>window.location.href='$full_url';</script>";
                exit();
            } else {
                header('Location: ' . $full_url, true, 302);
                exit();
            }
        } else {
            app_log("Aucune redirection nécessaire - déjà sur la bonne URL");
        }
    }
    
    // Récupérer le nom de l'utilisateur connecté
    public function getUserName() {
        return $_SESSION['user_name'] ?? '';
    }
    
    // Vérifier si l'utilisateur a un rôle spécifique
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

// Initialisation de l'authentification
$auth = new Auth($db);

// Gestion de la déconnexion
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: login.php');
    exit();
}

// Redirection automatique si l'utilisateur est déjà connecté
$current_page = basename($_SERVER['PHP_SELF']);
$login_pages = ['login.php', 'change-password.php'];

if ($auth->isLoggedIn() && !in_array($current_page, $login_pages)) {
    if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1) {
        if ($current_page != 'change-password.php') {
            header('Location: change-password.php');
            exit();
        }
    } else {
        // Vérifier que l'utilisateur est bien dans le bon répertoire selon son rôle
        $role_dir = $_SESSION['user_role'] . '/';
        $current_dir = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Si l'utilisateur est dans un répertoire qui ne correspond pas à son rôle
        if (!str_contains($current_dir, $role_dir) && $current_page != 'index.php') {
            $auth->redirectUser();
        }
    }
}