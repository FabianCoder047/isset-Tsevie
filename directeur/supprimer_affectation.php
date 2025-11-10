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
    // Récupérer l'ID de l'affectation à supprimer
    $affectation_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Validation de l'ID
    if ($affectation_id <= 0) {
        throw new Exception('ID d\'affectation invalide');
    }

    // Supprimer l'affectation
    $stmt = $db->prepare("DELETE FROM enseignements WHERE id = ?");
    $result = $stmt->execute([$affectation_id]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Aucune affectation trouvée avec cet ID');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
