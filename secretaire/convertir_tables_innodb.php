<?php
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Désactiver temporairement la vérification des clés étrangères
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Convertir la table classes en InnoDB
    echo "Conversion de la table 'classes' en InnoDB...\n";
    $db->exec("ALTER TABLE classes ENGINE=InnoDB");
    
    // Convertir la table eleves en InnoDB si elle existe
    try {
        $db->exec("ALTER TABLE eleves ENGINE=InnoDB");
        echo "Conversion de la table 'eleves' en InnoDB...\n";
    } catch (PDOException $e) {
        echo "La table 'eleves' n'existe pas encore ou une erreur s'est produite : " . $e->getMessage() . "\n";
    }
    
    // Réactiver la vérification des clés étrangères
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Vérifier le moteur des tables
    $tables = ['classes', 'eleves'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLE STATUS LIKE '$table'");
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($status) {
                echo "\nStatut de la table '$table' :\n";
                echo "- Moteur : " . $status['Engine'] . "\n";
                echo "- Version : " . $status['Version'] . "\n";
                echo "- Lignes : " . $status['Rows'] . "\n";
                echo "- Collation : " . $status['Collation'] . "\n";
            }
        } catch (PDOException $e) {
            echo "Impossible de vérifier le statut de la table '$table' : " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nConversion terminée. Essayez à nouveau d'ajouter un élève.\n";
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage() . "\n");
}
?>
