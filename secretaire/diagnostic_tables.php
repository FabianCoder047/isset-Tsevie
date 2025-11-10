<?php
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "=== Vérification des tables ===\n\n";
    
    // Vérifier la table classes
    echo "1. Structure de la table 'classes':\n";
    $stmt = $db->query("SHOW CREATE TABLE classes");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n\n";
    
    // Vérifier la table eleves
    echo "2. Structure de la table 'eleves':\n";
    $stmt = $db->query("SHOW CREATE TABLE eleves");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n\n";
    
    // Vérifier les contraintes de clé étrangère
    echo "3. Contraintes de clé étrangère pour 'eleves':\n";
    $stmt = $db->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE 
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME = 'classes'
            AND TABLE_NAME = 'eleves'
    ");
    
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($foreignKeys)) {
        echo "Aucune contrainte de clé étrangère trouvée.\n";
    } else {
        print_r($foreignKeys);
    }
    
    // Vérifier les données dans la table classes
    echo "\n4. Liste des classes disponibles :\n";
    $stmt = $db->query("SELECT id, nom, niveau FROM classes");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($classes)) {
        echo "Aucune classe trouvée dans la base de données.\n";
    } else {
        foreach ($classes as $classe) {
            echo "- ID: " . $classe['id'] . ", Classe: " . $classe['nom'] . " " . $classe['niveau'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la vérification des tables : " . $e->getMessage());
}
?>
