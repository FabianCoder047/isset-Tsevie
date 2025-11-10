<?php
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Vérifier les élèves existants avec les informations de leur classe
    $query = "SELECT e.*, c.nom as classe_nom, c.niveau as classe_niveau 
              FROM eleves e 
              LEFT JOIN classes c ON e.classe_id = c.id 
              ORDER BY e.date_inscription DESC";
              
    $stmt = $db->query($query);
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($eleves)) {
        echo "Aucun élève trouvé dans la base de données.\n";
    } else {
        echo "Liste des élèves :\n";
        echo str_repeat("-", 120) . "\n";
        printf("%-10s %-20s %-20s %-15s %-10s %-20s %-15s %-10s\n", 
               'ID', 'Matricule', 'Nom', 'Prénom', 'Sexe', 'Classe', 'Date Inscription', 'Contact Parent');
        echo str_repeat("-", 120) . "\n";
        
        foreach ($eleves as $eleve) {
            printf("%-10s %-20s %-20s %-15s %-10s %-20s %-15s %-10s\n",
                   $eleve['id'],
                   $eleve['matricule'],
                   $eleve['nom'],
                   $eleve['prenom'],
                   $eleve['sexe'],
                   ($eleve['classe_nom'] ?? 'N/A') . ' ' . ($eleve['classe_niveau'] ?? ''),
                   $eleve['date_inscription'],
                   $eleve['contact_parent']
            );
        }
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la récupération des élèves : " . $e->getMessage());
}
?>
