<?php
// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/includes/db.php';

// Récupérer les matières depuis la base de données
$query = "SELECT * FROM matieres ORDER BY nom";
$matieres = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Renvoyer les données en JSON
header('Content-Type: application/json');
echo json_encode($matieres);
?>
