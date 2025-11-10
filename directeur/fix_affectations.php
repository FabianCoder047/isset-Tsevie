<?php
// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/includes/db.php';

try {
    // Vérifier si la table matieres a une colonne classe_id
    $columns = $db->query("SHOW COLUMNS FROM matieres LIKE 'classe_id'")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        // Ajouter la colonne classe_id si elle n'existe pas
        $db->exec("ALTER TABLE matieres ADD COLUMN classe_id INT NULL AFTER id");
        echo "Colonne 'classe_id' ajoutée à la table 'matieres'.<br>";
        
        // Mettre à jour les enregistrements existants si nécessaire
        // Par exemple, si vous avez une logique pour déterminer la classe par défaut
        // $db->exec("UPDATE matieres SET classe_id = [valeur_par_défaut] WHERE classe_id IS NULL");
    }
    
    // Vérifier et mettre à jour la requête dans affectations.php
    $affectations_php = file_get_contents(__DIR__ . '/affectations.php');
    
    // Remplacer la requête problématique
    $new_query = "SELECT e.*, u.nom as prof_nom, u.prenom as prof_prenom, 
                         m.nom as matiere_nom, m.coefficient, 
                         c.nom as classe_nom, c.niveau as classe_niveau
                  FROM enseignements e
                  JOIN utilisateurs u ON e.professeur_id = u.id
                  JOIN matieres m ON e.matiere_id = m.id
                  LEFT JOIN classes c ON m.classe_id = c.id
                  ORDER BY COALESCE(c.niveau, ''), COALESCE(c.nom, ''), m.nom";
    
    $updated = preg_replace(
        "/SELECT e\.\*, u\.nom as prof_nom, u\.prenom as prof_prenom,.*?ORDER BY c\.niveau, c\.nom, m\.nom/s",
        $new_query,
        $affectations_php
    );
    
    if ($updated !== $affectations_php) {
        file_put_contents(__DIR__ . '/affectations.php', $updated);
        echo "Le fichier affectations.php a été mis à jour avec succès.<br>";
    } else {
        echo "Aucune modification nécessaire dans affectations.php.<br>";
    }
    
    echo "<a href='affectations.php'>Retour à la page des affectations</a>";
    
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la structure : " . $e->getMessage());
}
?>
