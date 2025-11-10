<?php
// Configuration des chemins de base
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/isset/');
define('BASE_PATH', dirname(__DIR__) . '/');

// Fonction pour générer des URLs absolues
function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Fonction pour rediriger
function redirect($path) {
    $url = url($path);
    header("Location: $url");
    exit();
}
