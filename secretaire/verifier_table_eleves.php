<?php
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Vérifier si la table eleves existe
    $tableExists = $db->query("SHOW TABLES LIKE 'eleves'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "La table 'eleves' n'existe pas. Création en cours...\n";
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS eleves (
                id INT AUTO_INCREMENT PRIMARY KEY,
                matricule VARCHAR(20) NOT NULL UNIQUE,
                nom VARCHAR(50) NOT NULL,
                prenom VARCHAR(100) NOT NULL,
                date_naissance DATE NOT NULL,
                lieu_naissance VARCHAR(100) NOT NULL,
                sexe ENUM('M', 'F') NOT NULL,
                classe_id INT NOT NULL,
                contact_parent VARCHAR(50) NOT NULL,
                date_inscription DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        echo "Table 'eleves' créée avec succès.\n";
    } else {
        echo "La table 'eleves' existe déjà.\n";
        
        // Afficher la structure de la table
        $stmt = $db->query("DESCRIBE eleves");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nStructure de la table 'eleves' :\n";
        echo str_repeat("-", 100) . "\n";
        echo str_pad("Champ", 20) . " | " . 
             str_pad("Type", 25) . " | " . 
             str_pad("Null", 5) . " | " . 
             str_pad("Clé", 5) . " | " . 
             "Valeur par défaut\n";
        echo str_repeat("-", 100) . "\n";
        
        foreach ($columns as $column) {
            echo str_pad($column['Field'], 20) . " | " . 
                 str_pad($column['Type'], 25) . " | " . 
                 str_pad($column['Null'], 5) . " | " . 
                 str_pad($column['Key'], 5) . " | " . 
                 ($column['Default'] ?? 'NULL') . "\n";
        }
        
        // Vérifier les contraintes de clé étrangère
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
                TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'eleves'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($foreignKeys)) {
            echo "\nAucune contrainte de clé étrangère trouvée sur la table 'eleves'.\n";
        } else {
            echo "\nContraintes de clé étrangère sur la table 'eleves' :\n";
            echo str_repeat("-", 100) . "\n";
            echo str_pad("Colonne", 20) . " | " . 
                 str_pad("Contrainte", 30) . " | " . 
                 "Table référencée (colonne)" . "\n";
            echo str_repeat("-", 100) . "\n";
            
            foreach ($foreignKeys as $fk) {
                echo str_pad($fk['COLUMN_NAME'], 20) . " | " . 
                     str_pad($fk['CONSTRAINT_NAME'], 30) . " | " . 
                     $fk['REFERENCED_TABLE_NAME'] . " (" . $fk['REFERENCED_COLUMN_NAME'] . ")\n";
            }
        }
    }
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
