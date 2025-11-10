<?php
// Désactivation temporaire de la vérification d'authentification
session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'directeur') {
//     header('HTTP/1.0 403 Forbidden');
//     die('Accès refusé. Vous devez être connecté en tant que directeur.');
// }

require_once __DIR__ . '/../includes/db.php';

// Fonction pour créer la table des périodes si elle n'existe pas
function creerTablePeriodes($db) {
    $sql = "CREATE TABLE IF NOT EXISTS periodes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE NOT NULL,
        annee_scolaire VARCHAR(20) NOT NULL,
        est_actif BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    return $db->exec($sql);
}

// Fonction pour insérer les périodes de l'année en cours
function insererPeriodes($db, $annee_debut) {
    $annee_fin = $annee_debut + 1;
    $annee_scolaire = "$annee_debut-$annee_fin";
    
    // Vider la table avant d'insérer les nouvelles périodes
    $db->exec("TRUNCATE TABLE periodes");
    
    // Premier semestre : Septembre - Janvier
    $stmt = $db->prepare("INSERT INTO periodes (nom, date_debut, date_fin, annee_scolaire, est_actif) 
                         VALUES (?, ?, ?, ?, ?)");
    
    // Premier semestre
    $stmt->execute([
        '1er Semestre',
        "$annee_debut-09-01",
        "$annee_fin-01-31",
        $annee_scolaire,
        true  // Activer le semestre en cours
    ]);
    
    // Deuxième semestre : Février - Mai
    $stmt->execute([
        '2ème Semestre',
        "$annee_fin-02-01",
        "$annee_fin-05-31",
        $annee_scolaire,
        false
    ]);
    
    return $db->lastInsertId();
}

// Traitement du formulaire
$message = '';
$success = false;
$annee_courante = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Créer la table si elle n'existe pas
        creerTablePeriodes($db);
        
        // Insérer les périodes pour l'année spécifiée
        $annee = isset($_POST['annee']) ? (int)$_POST['annee'] : $annee_courante;
        insererPeriodes($db, $annee);
        
        $message = "Les périodes ont été configurées avec succès pour l'année scolaire $annee-" . ($annee + 1) . ".";
        $success = true;
    } catch (PDOException $e) {
        $message = "Erreur lors de la configuration des périodes : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration des Périodes - ISSET Tsévié</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Configuration des Périodes d'Évaluation</h1>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> <?php echo $success ? 'text-green-400' : 'text-red-400'; ?>"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">
                            <?php echo htmlspecialchars($message); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-gray-50">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Périodes de l'Année Scolaire</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Configurez les périodes d'évaluation pour l'année scolaire.
                </p>
            </div>
            <div class="border-t border-gray-200">
                <form method="post" class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="annee" class="block text-sm font-medium text-gray-700">Année Scolaire Début</label>
                            <select id="annee" name="annee" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <?php for ($i = $annee_courante - 1; $i <= $annee_courante + 1; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $annee_courante ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> - <?php echo $i + 1; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <p class="mt-2 text-sm text-gray-500">
                                Sélectionnez l'année de début de l'année scolaire (ex: 2024 pour 2024-2025).
                            </p>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-md">
                            <h4 class="text-sm font-medium text-blue-800">Périodes qui seront créées :</h4>
                            <ul class="mt-2 space-y-2">
                                <li class="flex items-center text-sm text-gray-700">
                                    <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                                    <span>1er Semestre : Septembre - Janvier</span>
                                </li>
                                <li class="flex items-center text-sm text-gray-700">
                                    <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                                    <span>2ème Semestre : Février - Mai</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i>
                                Enregistrer les Périodes
                            </button>
                            <a href="index.php" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php
        // Afficher les périodes existantes
        try {
            $query = "SELECT * FROM periodes ORDER BY date_debut";
            $stmt = $db->query($query);
            $periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($periodes)): ?>
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Périodes existantes</h3>
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Début</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Fin</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année Scolaire</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($periodes as $periode): ?>
                                    <tr class="<?php echo $periode['est_actif'] ? 'bg-blue-50' : ''; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($periode['nom']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($periode['date_debut'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($periode['date_fin'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($periode['annee_scolaire']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($periode['est_actif']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Actif
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Inactif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php } catch (PDOException $e) {
            // La table n'existe probablement pas encore
        } ?>
    </div>
</body>
</html>
