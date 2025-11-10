<?php
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Vérifier les classes disponibles
    $stmt = $db->query("SELECT * FROM classes");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($classes)) {
        echo "Aucune classe trouvée dans la base de données.\n";
        echo "C'est probablement la cause de l'erreur de contrainte de clé étrangère.\n";
        echo "Veuillez d'abord créer des classes avant d'ajouter des élèves.\n";
    } else {
        echo "Classes disponibles :\n";
        echo str_repeat("-", 50) . "\n";
        echo str_pad("ID", 5) . " | " . str_pad("Nom", 15) . " | Niveau\n";
        echo str_repeat("-", 50) . "\n";
        
        foreach ($classes as $classe) {
            echo str_pad($classe['id'], 5) . " | " . 
                 str_pad($classe['nom'], 15) . " | " . 
                 ($classe['niveau'] ?? 'N/A') . "\n";
        }
    }
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
