<?php
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Vérifier les classes disponibles
    $stmt = $db->query("SELECT * FROM classes");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($classes)) {
        echo "Aucune classe trouvée dans la base de données.\n";
        echo "C'est probablement la cause de l'erreur de contrainte de clé étrangère.\n";
        
        // Proposer de créer des classes de test
        echo "\nVoulez-vous créer des classes de test ? (O/N) ";
        $handle = fopen('php://stdin', 'r');
        $response = trim(fgets($handle));
        
        if (strtoupper($response) === 'O') {
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
                echo "Classe créée : " . $classe['nom'] . " " . $classe['niveau'] . "\n";
            }
            
            echo "\nClasses de test créées avec succès.\n";
        } else {
            echo "\nVeuillez d'abord créer des classes avant d'ajouter des élèves.\n";
        }
    } else {
        echo "Classes disponibles :\n";
        foreach ($classes as $classe) {
            echo "- ID: " . $classe['id'] . ", Classe: " . $classe['nom'] . " " . ($classe['niveau'] ?? '') . "\n";
        }
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la vérification des classes : " . $e->getMessage());
}
?>
