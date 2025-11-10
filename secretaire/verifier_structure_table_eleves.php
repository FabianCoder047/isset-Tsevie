<?php
require_once dirname(__DIR__) . '/includes/db.php';

// Vérifier si la table eleves existe
try {
    $db->query("SELECT 1 FROM eleves LIMIT 1");
    echo "La table 'eleves' existe déjà.\n";
    
    // Vérifier les colonnes existantes
    $stmt = $db->query("DESCRIBE eleves");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colonnes existantes : " . implode(', ', $columns) . "\n";
    
    // Vérifier si les colonnes nécessaires existent
    $required_columns = [
        'id', 'matricule', 'nom', 'prenom', 'date_naissance', 
        'lieu_naissance', 'sexe', 'classe_id', 'contact_parent', 'date_inscription'
    ];
    
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        echo "Colonnes manquantes : " . implode(', ', $missing_columns) . "\n";
        
        // Ajouter les colonnes manquantes
        foreach ($missing_columns as $column) {
            $alter_query = "";
            
            switch ($column) {
                case 'id':
                    $alter_query = "ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST";
                    break;
                case 'matricule':
                    $alter_query = "ADD COLUMN matricule VARCHAR(20) NOT NULL";
                    break;
                case 'nom':
                    $alter_query = "ADD COLUMN nom VARCHAR(100) NOT NULL";
                    break;
                case 'prenom':
                    $alter_query = "ADD COLUMN prenom VARCHAR(100) NOT NULL";
                    break;
                case 'date_naissance':
                    $alter_query = "ADD COLUMN date_naissance DATE NOT NULL";
                    break;
                case 'lieu_naissance':
                    $alter_query = "ADD COLUMN lieu_naissance VARCHAR(100) NOT NULL";
                    break;
                case 'sexe':
                    $alter_query = "ADD COLUMN sexe ENUM('M', 'F') NOT NULL";
                    break;
                case 'classe_id':
                    $alter_query = "ADD COLUMN classe_id INT NOT NULL";
                    break;
                case 'contact_parent':
                    $alter_query = "ADD COLUMN contact_parent VARCHAR(20) NOT NULL";
                    break;
                case 'date_inscription':
                    $alter_query = "ADD COLUMN date_inscription DATETIME NOT NULL";
                    break;
            }
            
            if (!empty($alter_query)) {
                try {
                    $db->exec("ALTER TABLE eleves $alter_query");
                    echo "Colonne ajoutée : $column\n";
                } catch (PDOException $e) {
                    echo "Erreur lors de l'ajout de la colonne $column : " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "Toutes les colonnes nécessaires sont présentes.\n";
    }
    
} catch (PDOException $e) {
    // La table n'existe pas, on la crée
    echo "La table 'eleves' n'existe pas. Création en cours...\n";
    
    $create_table = "
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
        FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    CREATE UNIQUE INDEX idx_matricule ON eleves (matricule);
    ";
    
    try {
        $db->exec($create_table);
        echo "La table 'eleves' a été créée avec succès.\n";
    } catch (PDOException $e) {
        die("Erreur lors de la création de la table : " . $e->getMessage() . "\n");
    }
}

echo "Vérification terminée.\n";
