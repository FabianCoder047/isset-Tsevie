<?php
// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/includes/db.php';

try {
    // Vérifier si la table enseignements existe
    $tables = $db->query("SHOW TABLES LIKE 'enseignements'")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        // Créer la table si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS enseignements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            professeur_id INT NOT NULL,
            matiere_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (professeur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
            FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
            UNIQUE KEY unique_affectation (professeur_id, matiere_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "Table 'enseignements' créée avec succès.<br>";
    } else {
        // Vérifier si les colonnes existent
        $columns = [];
        $result = $db->query("DESCRIBE enseignements");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }

        // Ajouter les colonnes manquantes
        if (!in_array('matiere_id', $columns)) {
            $db->exec("ALTER TABLE enseignements ADD COLUMN matiere_id INT NOT NULL AFTER professeur_id");
            echo "Colonne 'matiere_id' ajoutée à la table 'enseignements'.<br>";
        }

        if (!in_array('created_at', $columns)) {
            $db->exec("ALTER TABLE enseignements ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            echo "Colonne 'created_at' ajoutée à la table 'enseignements'.<br>";
        }

        if (!in_array('updated_at', $columns)) {
            $db->exec("ALTER TABLE enseignements ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "Colonne 'updated_at' ajoutée à la table 'enseignements'.<br>";
        }

        // Ajouter les clés étrangères si elles n'existent pas
        $foreignKeys = $db->query("
            SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'enseignements' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll(PDO::FETCH_ASSOC);

        $hasMatiereFk = false;
        $hasProfFk = false;

        foreach ($foreignKeys as $fk) {
            if ($fk['COLUMN_NAME'] === 'matiere_id') {
                $hasMatiereFk = true;
            }
            if ($fk['COLUMN_NAME'] === 'professeur_id') {
                $hasProfFk = true;
            }
        }

        if (!$hasMatiereFk) {
            $db->exec("ALTER TABLE enseignements ADD CONSTRAINT fk_enseignements_matiere FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE");
            echo "Contrainte de clé étrangère pour 'matiere_id' ajoutée.<br>";
        }

        if (!$hasProfFk) {
            $db->exec("ALTER TABLE enseignements ADD CONSTRAINT fk_enseignements_professeur FOREIGN KEY (professeur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE");
            echo "Contrainte de clé étrangère pour 'professeur_id' ajoutée.<br>";
        }

        // Ajouter la contrainte d'unicité si elle n'existe pas
        $indexes = $db->query("SHOW INDEX FROM enseignements WHERE Key_name = 'unique_affectation'")->fetchAll();
        if (empty($indexes)) {
            $db->exec("ALTER TABLE enseignements ADD UNIQUE KEY unique_affectation (professeur_id, matiere_id)");
            echo "Contrainte d'unicité sur (professeur_id, matiere_id) ajoutée.<br>";
        }
    }

    echo "La table 'enseignements' est à jour.<br>";
    echo "<a href='affectations.php'>Retour à la page des affectations</a>";

} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la table 'enseignements': " . $e->getMessage());
}
?>
