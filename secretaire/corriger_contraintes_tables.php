<?php
require_once dirname(__DIR__) . '/includes/db.php';

echo "Début de la correction des contraintes de tables...\n";

// Désactiver temporairement les vérifications de clés étrangères
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

// 1. Supprimer la table eleves si elle existe
try {
    $db->exec("DROP TABLE IF EXISTS eleves");
    echo "Table 'eleves' supprimée.\n";
} catch (PDOException $e) {
    echo "Erreur lors de la suppression de la table 'eleves' : " . $e->getMessage() . "\n";
}

// 2. Recréer la table eleves avec les bonnes contraintes
$create_eleves = "
CREATE TABLE IF NOT EXISTS eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    lieu_naissance VARCHAR(100) NOT NULL,
    sexe ENUM('M', 'F') NOT NULL,
    classe_id INT NOT NULL,
    contact_parent VARCHAR(20) NOT NULL,
    date_inscription DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_matricule (matricule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $db->exec($create_eleves);
    echo "Table 'eleves' recréée avec succès.\n";
    
    // Vérifier si la contrainte de clé étrangère existe
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'eleves' 
        AND CONSTRAINT_TYPE = 'FOREIGN_KEY_CONSTRAINT'
    ");
    
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        echo "Contrainte de clé étrangère vérifiée.\n";
    } else {
        echo "ATTENTION : La contrainte de clé étrangère n'a pas pu être ajoutée.\n";
        
        // Essayer d'ajouter la contrainte manuellement
        try {
            $db->exec("
                ALTER TABLE eleves
                ADD CONSTRAINT fk_eleves_classe
                FOREIGN KEY (classe_id) 
                REFERENCES classes(id) 
                ON DELETE CASCADE
            ");
            echo "Contrainte de clé étrangère ajoutée avec succès.\n";
        } catch (PDOException $e) {
            echo "Erreur lors de l'ajout de la contrainte de clé étrangère : " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Erreur lors de la recréation de la table 'eleves' : " . $e->getMessage() . "\n";
}

// Réactiver les vérifications de clés étrangères
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "Correction des contraintes terminée.\n";

// Vérifier les tables finales
echo "\nVérification finale des tables...\n";

try {
    // Vérifier la table classes
    $stmt = $db->query("SHOW TABLES LIKE 'classes'");
    if ($stmt->rowCount() > 0) {
        echo "- La table 'classes' existe.\n";
    } else {
        echo "- ERREUR : La table 'classes' n'existe pas.\n";
    }
    
    // Vérifier la table eleves
    $stmt = $db->query("SHOW TABLES LIKE 'eleves'");
    if ($stmt->rowCount() > 0) {
        echo "- La table 'eleves' existe.\n";
        
        // Vérifier la structure de la table eleves
        $stmt = $db->query("DESCRIBE eleves");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  Colonnes de la table 'eleves' : " . implode(', ', $columns) . "\n";
        
        // Vérifier les contraintes
        $stmt = $db->query("
            SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'eleves' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($constraints) > 0) {
            echo "  Contraintes de clé étrangère trouvées :\n";
            foreach ($constraints as $constraint) {
                echo "  - {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}({$constraint['REFERENCED_COLUMN_NAME']})\n";
            }
        } else {
            echo "  Aucune contrainte de clé étrangère trouvée.\n";
        }
    } else {
        echo "- ERREUR : La table 'eleves' n'existe pas.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur lors de la vérification finale : " . $e->getMessage() . "\n";
}

echo "\nVérification terminée.\n";
