<?php
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer les données du formulaire
    $professeur_id = isset($_POST['professeur_id']) ? (int)$_POST['professeur_id'] : 0;
    $matiere_id = isset($_POST['matiere_id']) ? (int)$_POST['matiere_id'] : 0;

    // Validation des données
    if ($professeur_id <= 0 || $matiere_id <= 0) {
        throw new Exception('Données invalides');
    }

    // Vérifier si l'affectation existe déjà
    $stmt = $db->prepare("SELECT id FROM enseignements WHERE professeur_id = ? AND matiere_id = ?");
    $stmt->execute([$professeur_id, $matiere_id]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Cette affectation existe déjà');
    }

    // Ajouter la nouvelle affectation
    $stmt = $db->prepare("INSERT INTO enseignements (professeur_id, matiere_id) VALUES (?, ?)");
    $result = $stmt->execute([$professeur_id, $matiere_id]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erreur lors de l\'ajout de l\'affectation');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
