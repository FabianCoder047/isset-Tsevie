<?php
// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/includes/db.php';

// Vérifier si l'ID de la classe est fourni
if (!isset($_GET['classe_id']) || !is_numeric($_GET['classe_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de classe invalide']);
    exit;
}

$classe_id = (int)$_GET['classe_id'];

try {
    // Récupérer les matières de la classe spécifiée
    $query = "SELECT id, nom FROM matieres WHERE classe_id = ? ORDER BY nom";
    $stmt = $db->prepare($query);
    $stmt->execute([$classe_id]);
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Renvoyer les données en JSON
    header('Content-Type: application/json');
    echo json_encode($matieres);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des matières']);
}
?>
