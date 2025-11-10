<?php
require_once dirname(__DIR__) . '/includes/db.php';

// Fonction pour créer ou mettre à jour une table
function verifier_ou_creer_table($db, $table_name, $create_query) {
    try {
        $db->query("SELECT 1 FROM $table_name LIMIT 1");
        echo "La table '$table_name' existe déjà.\n";
        return true;
    } catch (PDOException $e) {
        // La table n'existe pas, on la crée
        echo "La table '$table_name' n'existe pas. Création en cours...\n";
        
        try {
            $db->exec($create_query);
            echo "La table '$table_name' a été créée avec succès.\n";
            return true;
        } catch (PDOException $e) {
            echo "Erreur lors de la création de la table '$table_name' : " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// 1. Vérifier/Créer la table 'classes'
$create_classes = "
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    niveau VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_classe (nom, niveau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$classes_ok = verifier_ou_creer_table($db, 'classes', $create_classes);

// Si la table classes n'existe pas et n'a pas pu être créée, on arrête
if (!$classes_ok) {
    die("Impossible de continuer sans la table 'classes'.\n");
}

// 2. Vérifier/Créer la table 'eleves'
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

$eleves_ok = verifier_ou_creer_table($db, 'eleves', $create_eleves);

// 3. Vérifier si des classes existent, sinon en créer quelques-unes par défaut
if ($classes_ok) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM classes");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            echo "Aucune classe trouvée. Création de classes par défaut...\n";
            
            $classes = [
                ['6ème', 'Collège'],
                ['5ème', 'Collège'],
                ['4ème', 'Collège'],
                ['3ème', 'Collège'],
                ['2nde', 'Lycée'],
                ['1ère', 'Lycée'],
                ['Tle', 'Lycée']
            ];
            
            $stmt = $db->prepare("INSERT INTO classes (nom, niveau) VALUES (?, ?)");
            
            foreach ($classes as $classe) {
                try {
                    $stmt->execute([$classe[0], $classe[1]]);
                    echo "Classe créée : {$classe[0]} {$classe[1]}\n";
                } catch (PDOException $e) {
                    echo "Erreur lors de la création de la classe {$classe[0]} : " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "$count classe(s) trouvée(s) dans la base de données.\n";
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la vérification des classes : " . $e->getMessage() . "\n";
    }
}

echo "Vérification terminée.\n";
