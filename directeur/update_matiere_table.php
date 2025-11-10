<?php
// Script de mise à jour de la table matieres
require_once __DIR__ . '/../includes/db.php';

try {
    // Vérifier si les colonnes existent déjà
    $stmt = $db->query("SHOW COLUMNS FROM matieres LIKE 'classe_id'");
    if ($stmt->rowCount() === 0) {
        // Ajouter les colonnes manquantes
        $db->exec("ALTER TABLE matieres 
                  ADD COLUMN classe_id INT NOT NULL AFTER nom,
                  ADD COLUMN coefficient INT NOT NULL DEFAULT 1 AFTER classe_id");
        
        // Ajouter la clé étrangère si la table classes existe
        $db->exec("ALTER TABLE matieres 
                  ADD CONSTRAINT fk_matiere_classe 
                  FOREIGN KEY (classe_id) REFERENCES classes(id)");
        
        echo "La table matieres a été mise à jour avec succès.<br>";
    } else {
        echo "La table matieres est déjà à jour.<br>";
    }
    
    // Afficher la structure actuelle de la table
    echo "<h3>Structure actuelle de la table matieres :</h3>";
    $result = $db->query("SHOW CREATE TABLE matieres");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
    
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la table : " . $e->getMessage());
}

// Lien pour retourner à la page des matières
echo '<p><a href="matieres.php">Retour à la gestion des matières</a></p>';
?>
