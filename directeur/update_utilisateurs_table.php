<?php
// Désactiver temporairement l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Inclure le fichier de connexion à la base de données
require_once __DIR__ . '/../includes/db.php';

// Fonction pour exécuter une requête SQL et afficher le résultat
function executeQuery($db, $sql) {
    try {
        $db->exec($sql);
        return true;
    } catch (PDOException $e) {
        echo "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded-lg'>";
        echo "<p>Erreur lors de l'exécution de la requête : " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='font-mono text-sm mt-2'>" . htmlspecialchars($sql) . "</p>";
        echo "</div>";
        return false;
    }
}

// Vérifier si le formulaire a été soumis
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // 1. Vérifier si la colonne 'prenom' existe
        $stmt = $db->query("SHOW COLUMNS FROM utilisateurs LIKE 'prenom'");
        if ($stmt->rowCount() == 0) {
            // Ajouter la colonne 'prenom' si elle n'existe pas
            $sql = "ALTER TABLE utilisateurs ADD COLUMN prenom VARCHAR(100) AFTER nom";
            if (executeQuery($db, $sql)) {
                $message .= "<p>✅ Colonne 'prenom' ajoutée avec succès.</p>";
            }
        } else {
            $message .= "<p>ℹ️ La colonne 'prenom' existe déjà.</p>";
        }

        // 2. Vérifier si la colonne 'username' existe
        $stmt = $db->query("SHOW COLUMNS FROM utilisateurs LIKE 'username'");
        if ($stmt->rowCount() == 0) {
            // Ajouter la colonne 'username' si elle n'existe pas
            $sql = "ALTER TABLE utilisateurs ADD COLUMN username VARCHAR(50) UNIQUE AFTER email";
            if (executeQuery($db, $sql)) {
                $message .= "<p>✅ Colonne 'username' ajoutée avec succès.</p>";
            }
        } else {
            $message .= "<p>ℹ️ La colonne 'username' existe déjà.</p>";
        }
        
        // 2.1 Vérifier si la colonne 'statut' existe
        $stmt = $db->query("SHOW COLUMNS FROM utilisateurs LIKE 'statut'");
        if ($stmt->rowCount() == 0) {
            // Ajouter la colonne 'statut' si elle n'existe pas
            $sql = "ALTER TABLE utilisateurs ADD COLUMN statut ENUM('actif', 'inactif') DEFAULT 'actif' AFTER role";
            if (executeQuery($db, $sql)) {
                $message .= "<p>✅ Colonne 'statut' ajoutée avec succès.</p>";
                
                // Mettre à jour tous les utilisateurs existants comme actifs
                $sql = "UPDATE utilisateurs SET statut = 'actif' WHERE statut IS NULL";
                if (executeQuery($db, $sql)) {
                    $message .= "<p>✅ Tous les utilisateurs existants ont été marqués comme 'actifs'.</p>";
                }
            }
        } else {
            $message .= "<p>ℹ️ La colonne 'statut' existe déjà.</p>";
        }

        // 3. Mettre à jour les enregistrements existants si nécessaire
        $sql = "UPDATE utilisateurs SET prenom = '' WHERE prenom IS NULL";
        if (executeQuery($db, $sql)) {
            $message .= "<p>✅ Mise à jour des valeurs NULL pour 'prenom' effectuée.</p>";
        }

        // 4. Générer des noms d'utilisateur si nécessaire
        $sql = "SELECT id, nom, prenom, email FROM utilisateurs WHERE username IS NULL OR username = ''";
        $stmt = $db->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($users as $user) {
            $username = strtolower(substr($user['prenom'], 0, 1) . str_replace(' ', '', $user['nom']));
            $username = preg_replace('/[^a-z0-9]/', '', $username);
            $counter = 1;
            $original_username = $username;
            
            // Vérifier si le nom d'utilisateur existe déjà
            while (true) {
                $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE username = ? AND id != ?");
                $stmt->execute([$username, $user['id']]);
                if ($stmt->rowCount() == 0) {
                    break;
                }
                $username = $original_username . $counter++;
            }
            
            // Mettre à jour le nom d'utilisateur
            $stmt = $db->prepare("UPDATE utilisateurs SET username = ? WHERE id = ?");
            if ($stmt->execute([$username, $user['id']])) {
                $updated++;
            }
        }
        
        if ($updated > 0) {
            $message .= "<p>✅ $updated noms d'utilisateur générés avec succès.</p>";
        } else {
            $message .= "<p>ℹ️ Aucun nom d'utilisateur à mettre à jour.</p>";
        }

        $db->commit();
        $success = true;
        $message = "<div class='p-4 mb-6 bg-green-50 text-green-700 rounded-lg'>$message</div>";
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = "<div class='p-4 mb-6 text-red-700 bg-red-100 rounded-lg'>";
        $message .= "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
        $message .= "</div>";
    }
}

// Récupérer la structure actuelle de la table
$columns = [];
try {
    $stmt = $db->query("SHOW COLUMNS FROM utilisateurs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $message = "<div class='p-4 mb-6 text-red-700 bg-red-100 rounded-lg'>";
    $message .= "Erreur lors de la récupération de la structure de la table : " . htmlspecialchars($e->getMessage());
    $message .= "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de la table utilisateurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Mise à jour de la table utilisateurs</h1>
            <p class="text-gray-600">Ce script va mettre à jour la structure de la table utilisateurs pour ajouter les champs manquants.</p>
        </div>

        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:px-6 bg-gray-50">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Structure actuelle de la table</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <?php if (!empty($columns)): ?>
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Colonnes existantes :</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <ul class="list-disc pl-5">
                                    <?php foreach ($columns as $column): ?>
                                        <li><code class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($column); ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                            </dd>
                        </div>
                    <?php else: ?>
                        <div class="py-4 sm:py-5 sm:px-6">
                            <p class="text-sm text-gray-500">Impossible de récupérer la structure de la table.</p>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <?php if (!in_array('prenom', $columns) || !in_array('username', $columns)): ?>
            <form method="post" class="space-y-4">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle h-5 w-5 text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                La table des utilisateurs nécessite une mise à jour pour fonctionner correctement.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Mettre à jour la structure
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle h-5 w-5 text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            La structure de la table est à jour. Vous pouvez retourner à la <a href="comptes.php" class="font-medium text-green-700 underline hover:text-green-600">gestion des comptes</a>.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
