<?php
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $stmt = $db->query('SELECT * FROM classes');
    
    if ($stmt->rowCount() === 0) {
        echo "Aucune classe trouvée dans la base de données.\n";
        echo "Veuillez d'abord créer des classes avant d'ajouter des élèves.\n";
    } else {
        echo "Classes disponibles :\n";
        echo str_repeat("-", 50) . "\n";
        echo "ID  | Nom             | Niveau\n";
        echo str_repeat("-", 50) . "\n";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            printf("%-3d | %-15s | %s\n", 
                $row['id'], 
                $row['nom'], 
                $row['niveau'] ?? 'N/A'
            );
        }
    }
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
