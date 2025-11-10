<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Secrétaire - ISSET Tsévié</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialiser l'authentification
$auth = new Auth($db);

// Vérifier si l'utilisateur est connecté et est une secrétaire
if (!$auth->isLoggedIn() || !$auth->hasRole('secretaire')) {
    header('Location: /isset/login.php');
    exit();
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'] ?? 'Secrétaire';

// Définir les URLs de base
$base_url = '/isset';
$secretaire_url = $base_url . '/secretaire';

// Inclure le header de la section secrétaire
include __DIR__ . '/includes/header.php';

// Récupérer les statistiques d'inscription
$stats = [
    'total_eleves' => 0,
    'total_filles' => 0,
    'total_garcons' => 0,
    'par_classe' => []
];

try {
    // Compter le nombre total d'élèves
    $query = "SELECT COUNT(*) as total FROM eleves";
    $stmt = $db->query($query);
    $stats['total_eleves'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Compter le nombre de filles et garçons
    $query = "SELECT 
                SUM(CASE WHEN sexe = 'F' THEN 1 ELSE 0 END) as filles,
                SUM(CASE WHEN sexe = 'M' THEN 1 ELSE 0 END) as garcons
              FROM eleves";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_filles'] = $result['filles'] ?? 0;
    $stats['total_garcons'] = $result['garcons'] ?? 0;

    // Statistiques par classe
    $query = "SELECT 
                c.nom as classe, 
                c.niveau,
                COUNT(e.id) as effectif,
                SUM(CASE WHEN e.sexe = 'F' THEN 1 ELSE 0 END) as filles,
                SUM(CASE WHEN e.sexe = 'M' THEN 1 ELSE 0 END) as garcons
              FROM classes c
              LEFT JOIN eleves e ON c.id = e.classe_id
              GROUP BY c.id, c.nom, c.niveau
              ORDER BY c.niveau, c.nom";
    $stats['par_classe'] = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
}

// Récupérer la liste des élèves
$eleves = [];
try {
    $query = "SELECT e.*, c.nom as classe_nom, c.niveau as classe_niveau 
              FROM eleves e 
              LEFT JOIN classes c ON e.classe_id = c.id 
              ORDER BY e.nom, e.prenom";
    $eleves = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération de la liste des élèves: " . $e->getMessage();
}
?>

<!-- En-tête de la page -->
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900">Tableau de bord</h1>
        <p class="mt-1 text-sm text-gray-500">Bienvenue dans l'espace secrétaire de l'ISSET Tsévié</p>
    </div>
</div>

<!-- Contenu principal -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Cartes de statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total des élèves</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_eleves']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <i class="fas fa-female text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Filles</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_filles']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <i class="fas fa-male text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Garçons</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_garcons']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières inscriptions -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Dernières inscriptions</h3>
                <a href="<?php echo $secretaire_url; ?>/inscription.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i> Nouvelle inscription
                </a>
            </div>
        </div>
        <div class="bg-white overflow-hidden">
            <?php if (!empty($eleves)): ?>
                <ul class="divide-y divide-gray-200">
                    <?php 
                    // Afficher uniquement les 5 derniers élèves inscrits
                    $derniers_eleves = array_slice($eleves, 0, 5);
                    foreach ($derniers_eleves as $eleve): 
                        $date_inscription = !empty($eleve['date_inscription']) ? new DateTime($eleve['date_inscription']) : null;
                    ?>
                        <li class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-user text-blue-600"></i>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-blue-600 truncate">
                                            <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
                                        </p>
                                        <div class="text-sm text-gray-500">
                                            <?php echo $date_inscription ? $date_inscription->format('d/m/Y') : 'N/A'; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <span class="mr-2"><?php echo htmlspecialchars($eleve['matricule'] ?? 'N/A'); ?></span>
                                        <span class="mx-1">•</span>
                                        <span><?php echo htmlspecialchars($eleve['classe_nom']. ' ' . $eleve['classe_niveau'] ?? 'Non affecté'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="bg-gray-50 px-6 py-3 text-right text-sm">
                    <a href="<?php echo $secretaire_url; ?>/eleves.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Voir tous les élèves <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-users-slash text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-500">Aucun élève enregistré pour le moment.</p>
                    <div class="mt-4">
                        <a href="<?php echo $secretaire_url; ?>/inscription.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Ajouter un élève
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiques par classe -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Effectifs par classe</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Répartition des élèves par classe et par sexe</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Classe
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Niveau
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Filles
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Garçons
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($stats['par_classe'])): ?>
                        <?php foreach ($stats['par_classe'] as $classe): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($classe['classe']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($classe['niveau']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $classe['effectif']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-pink-600">
                                    <?php echo $classe['filles']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-blue-600">
                                    <?php echo $classe['garcons']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                Aucune donnée disponible
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
// Fonction de recherche dans le tableau des élèves
document.getElementById('searchInput')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
document.getElementById('exportBtn')?.addEventListener('click', function() {
    // Ici, vous pouvez implémenter la logique d'exportation (Excel, PDF, etc.)
    alert('Fonctionnalité d\'exportation à implémenter');
});
</script>

<?php // Footer removed for now, will be added later if needed ?>
</body>
</html>