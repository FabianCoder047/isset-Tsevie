<?php
// Vérifier si l'utilisateur est connecté et est administrateur
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'directeur') {
    header('HTTP/1.0 403 Forbidden');
    die('Accès refusé. Vous devez être connecté en tant que directeur.');
}

require_once __DIR__ . '/includes/db.php';

$tables = [
    'activites' => "
        CREATE TABLE IF NOT EXISTS activites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            description TEXT,
            date_activite DATETIME NOT NULL,
            utilisateur_id INT,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'periodes' => "
        CREATE TABLE IF NOT EXISTS periodes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            annee_scolaire VARCHAR(20) NOT NULL,
            est_actif BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'matieres' => "
        CREATE TABLE IF NOT EXISTS matieres (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            classe_id INT NOT NULL,
            coefficient INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'classes' => "
        CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50) NOT NULL,
            niveau VARCHAR(50) NOT NULL,
            annee_scolaire VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    "
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration de la base de données</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Configuration de la base de données</h1>
        <div class="space-y-4">
<?php

try {
    // Vérifier si la table classes existe
    $result = $db->query("SHOW TABLES LIKE 'classes'");
    if ($result->rowCount() == 0) {
        // Créer la table classes d'abord car les autres tables en dépendent
        $db->exec($tables['classes']);
        echo "<p style='color: green;'>Table 'classes' créée avec succès.</p>";
        
        // Insérer des données de test

    }
    
    // Créer les autres tables
    foreach ($tables as $table => $sql) {
        if ($table !== 'classes') { // On l'a déjà géré
            $result = $db->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() == 0) {
                $db->exec($sql);
                echo "<div class='p-3 bg-green-50 text-green-700 rounded-md flex items-start'>
    <i class='fas fa-check-circle mt-1 mr-2 text-green-500'></i>
    <span>Table <strong>$table</strong> créée avec succès.</span>
</div>";
                
                // Insérer des données de test pour les périodes
                if ($table === 'periodes') {
                    $db->exec("INSERT INTO periodes (nom, date_debut, date_fin, annee_scolaire, est_actif) VALUES 
                        ('1er Trimestre', '2024-10-01', '2024-12-20', '2024-2025', TRUE),
                        ('2ème Trimestre', '2025-01-07', '2025-03-28', '2024-2025', FALSE),
                        ('3ème Trimestre', '2025-04-07', '2025-06-30', '2024-2025', FALSE)");
                    echo "<p style='color: green;'>Données de test insérées dans la table 'periodes'.</p>";
                }
            } else {
                echo "<div class='p-3 bg-blue-50 text-blue-700 rounded-md flex items-start'>
    <i class='fas fa-info-circle mt-1 mr-2 text-blue-500'></i>
    <span>La table <strong>$table</strong> existe déjà.</span>
</div>";
            }
        }
    }
    
    ?>
        </div>
        
        <div class="mt-8 p-4 bg-green-50 border border-green-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-green-800">Configuration terminée avec succès !</h3>
                    <div class="mt-2">
                        <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-home mr-2"></i>
                            Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    
} catch (PDOException $e) {
    ?>
        </div>
        
        <div class="mt-8 p-4 bg-red-50 border border-red-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-red-800">Erreur lors de la configuration</h3>
                    <div class="mt-2 text-red-700">
                        <p><strong>Message :</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
                        <p><strong>Code d'erreur :</strong> <?php echo $e->getCode(); ?></p>
                        <p class="mt-2">Veuillez vérifier les logs pour plus de détails.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}
?>
