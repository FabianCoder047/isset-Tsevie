<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Vérifier la structure de la table eleves
    $stmt = $db->query("DESCRIBE eleves");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table 'eleves' :\n";
    echo str_pad("Champ", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Clé", 10) . str_pad("Valeur par défaut", 20) . "\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 20) . 
             str_pad($column['Null'], 10) .
             str_pad($column['Key'], 10) .
             str_pad($column['Default'] ?? 'NULL', 20) . "\n";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la vérification de la structure de la table : " . $e->getMessage());
}
?>
