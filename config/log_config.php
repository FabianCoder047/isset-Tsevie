<?php
// Configuration des logs
$logFile = __DIR__ . '/../logs/app_' . date('Y-m-d') . '.log';

// Définir le gestionnaire d'erreurs personnalisé
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    global $logFile;
    
    $logMessage = date('[Y-m-d H:i:s]') . " [$errno] $errstr in $errfile on line $errline" . PHP_EOL;
    
    // Écrire dans le fichier de log
    error_log($logMessage, 3, $logFile);
    
    // Ne pas exécuter le gestionnaire interne d'erreurs de PHP
    return true;
}

// Définir la fonction de log personnalisée
function app_log($message, $data = null) {
    global $logFile;
    
    $logMessage = date('[Y-m-d H:i:s]') . ' ' . $message . PHP_EOL;
    
    if ($data !== null) {
        $logMessage .= 'Data: ' . print_r($data, true) . PHP_EOL;
    }
    
    // Écrire dans le fichier de log
    error_log($logMessage, 3, $logFile);
}

// Configuration des paramètres PHP
ini_set('log_errors', 1);
ini_set('error_log', $logFile);
ini_set('display_errors', 0);

error_reporting(E_ALL);
set_error_handler('customErrorHandler');

// Fonction pour logger les requêtes
function log_request() {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'post_data' => $_POST
    ];
    
    app_log('Requête reçue', $request);
}

// Fonction pour logger les redirections
function log_redirect($from, $to) {
    app_log("Redirection de $from vers $to");
}

// Démarrer le logging des requêtes
log_request();
