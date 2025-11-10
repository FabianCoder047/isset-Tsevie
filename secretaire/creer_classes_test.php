<?php
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Vérifier si la table classes existe
    $tableExists = $db->query("SHOW TABLES LIKE 'classes'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table classes si elle n'existe pas
        $db->exec("
            CREATE TABLE IF NOT EXISTS classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(50) NOT NULL,
                niveau VARCHAR(2) NOT NULL DEFAULT 'A',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        echo "Table 'classes' créée avec succès.\n";
    }
    
    // Vérifier si des classes existent déjà
    $stmt = $db->query("SELECT COUNT(*) as count FROM classes");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo "Il y a déjà $count classes dans la base de données.\n";
        
        // Afficher les classes existantes
        $stmt = $db->query("SELECT * FROM classes");
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nClasses existantes :\n";
        foreach ($classes as $classe) {
            echo "- ID: " . $classe['id'] . ", Classe: " . $classe['nom'] . " " . ($classe['niveau'] ?? '') . "\n";
        }
        
        echo "\nPour ajouter quand même des classes de test, supprimez d'abord les classes existantes.\n";
        exit;
    }
    
    // Créer des classes de test
    $testClasses = [
        ['nom' => '6ème', 'niveau' => 'A'],
        ['nom' => '5ème', 'niveau' => 'A'],
        ['nom' => '4ème', 'niveau' => 'A'],
        ['nom' => '3ème', 'niveau' => 'A'],
        ['nom' => '2nde', 'niveau' => 'A'],
        ['nom' => '1ère', 'niveau' => 'A'],
        ['nom' => 'Tle', 'niveau' => 'A']
    ];
    
    $stmt = $db->prepare("INSERT INTO classes (nom, niveau) VALUES (:nom, :niveau)");
    
    foreach ($testClasses as $classe) {
        $stmt->execute([
            ':nom' => $classe['nom'],
            ':niveau' => $classe['niveau']
        ]);
        $id = $db->lastInsertId();
        echo "Classe créée : ID $id - " . $classe['nom'] . " " . $classe['niveau'] . "\n";
    }
    
    echo "\nClasses de test créées avec succès.\n";
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
