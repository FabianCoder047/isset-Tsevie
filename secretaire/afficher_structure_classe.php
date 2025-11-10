<?php
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Afficher la structure de la table classes
    $stmt = $db->query("SHOW CREATE TABLE classes");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($result['Create Table'])) {
        echo "Structure de la table 'classes' :\n";
        echo "----------------------------------------\n";
        echo $result['Create Table'] . "\n\n";
    } else {
        echo "La table 'classes' n'existe pas.\n";
    }
    
    // Afficher toutes les classes
    $stmt = $db->query("SELECT * FROM classes ORDER BY id");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($classes)) {
        echo "Aucune classe trouvée dans la base de données.\n";
    } else {
        echo "\nListe des classes (" . count($classes) . ") :\n";
        echo str_repeat("-", 50) . "\n";
        echo str_pad("ID", 5) . " | " . str_pad("Nom", 10) . " | Niveau\n";
        echo str_repeat("-", 50) . "\n";
        
        foreach ($classes as $classe) {
            echo str_pad($classe['id'], 5) . " | " . 
                 str_pad($classe['nom'], 10) . " | " . 
                 ($classe['niveau'] ?? 'N/A') . "\n";
        }
    }
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
