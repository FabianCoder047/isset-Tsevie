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
    $query = "SELECT e.*, c.nom as classe_nom, c.niveau 
              FROM eleves e 
              LEFT JOIN classes c ON e.classe_id = c.id 
              ORDER BY e.nom, e.prenom";
    $eleves = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération de la liste des élèves: " . $e->getMessage();
}
?>

<!-- Onglets de navigation -->
<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <a href="#inscription" 
           class="tab-link border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" 
           data-tab="inscription">
            Inscription
        </a>
        <a href="#statistiques" 
           class="tab-link border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" 
           data-tab="statistiques">
            Statistiques
        </a>
    </nav>
</div>

<!-- Contenu des onglets -->
<div class="space-y-6">
    <!-- Onglet Inscription -->
    <div id="inscription-tab" class="tab-content">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Nouvelle inscription</h2>
            
            <!-- Formulaire d'inscription -->
            <form id="inscriptionForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" id="nom" name="nom" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom(s)</label>
                        <input type="text" id="prenom" name="prenom" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="date_naissance" class="block text-sm font-medium text-gray-700">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="lieu_naissance" class="block text-sm font-medium text-gray-700">Lieu de naissance</label>
                        <input type="text" id="lieu_naissance" name="lieu_naissance" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="sexe" class="block text-sm font-medium text-gray-700">Sexe</label>
                        <select id="sexe" name="sexe" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sélectionner...</option>
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                </div>
                
            
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="classe_id" class="block text-sm font-medium text-gray-700">Classe</label>
                        <select id="classe_id" name="classe_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sélectionner une classe...</option>
                            <?php
                            try {
                                $stmt = $db->query("SELECT id, nom, niveau FROM classes ORDER BY niveau, nom");
                                while ($classe = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . $classe['id'] . "'>" . htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']) . "</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Erreur de chargement des classes</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="annee_scolaire" class="block text-sm font-medium text-gray-700">Année scolaire</label>
                        <select id="annee_scolaire" name="annee_scolaire" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <?php
                            $current_year = date('Y');
                            for ($i = -2; $i <= 2; $i++) {
                                $year = $current_year + $i;
                                $next_year = $year + 1;
                                $annee_scolaire = "$year-$next_year";
                                $selected = ($i === 0) ? 'selected' : '';
                                echo "<option value='$annee_scolaire' $selected>$annee_scolaire</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Enregistrer l'inscription
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Liste des élèves -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">Liste des élèves</h2>
                <div class="flex space-x-2">
                    <input type="text" id="searchInput" placeholder="Rechercher un élève..." 
                           class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <button id="exportBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-file-export mr-2"></i>Exporter
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matricule</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom et prénoms</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($eleves as $eleve): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($eleve['matricule'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille'; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                $classe = '';
                                if (!empty($eleve['niveau']) && !empty($eleve['classe_nom'])) {
                                    $classe = $eleve['niveau'] . ' ' . $eleve['classe_nom'];
                                } else {
                                    $classe = 'Non affecté';
                                }
                                echo htmlspecialchars($classe);
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo !empty($eleve['contact']) ? htmlspecialchars($eleve['contact']) : 'Non renseigné'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="#" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($eleves)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                Aucun élève enregistré pour le moment.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Onglet Statistiques -->
    <div id="statistiques-tab" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Statistiques des inscriptions</h2>
            
            <!-- Cartes de statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-blue-800">Total des élèves</div>
                    <div class="mt-1 text-3xl font-semibold text-blue-600"><?php echo $stats['total_eleves']; ?></div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-green-800">Filles</div>
                    <div class="mt-1 text-3xl font-semibold text-green-600"><?php echo $stats['total_filles']; ?></div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-purple-800">Garçons</div>
                    <div class="mt-1 text-3xl font-semibold text-purple-600"><?php echo $stats['total_garcons']; ?></div>
                </div>
            </div>
            
            <!-- Tableau des effectifs par classe -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effectif total</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filles</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Garçons</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($stats['par_classe'] as $classe): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($classe['classe'] . ' ' . $classe['niveau']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $classe['effectif']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $classe['filles']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $classe['garcons']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stats['par_classe'])): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                Aucune donnée statistique disponible.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    // Activer le premier onglet par défaut
    const defaultTab = document.querySelector('.tab-link');
    if (defaultTab) {
        defaultTab.classList.add('border-blue-500', 'text-blue-600');
        defaultTab.classList.remove('border-transparent', 'text-gray-500');
        const tabId = defaultTab.getAttribute('data-tab');
        document.getElementById(`${tabId}-tab`).classList.remove('hidden');
    }

    // Gestion du clic sur les onglets
    document.querySelectorAll('.tab-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab-link').forEach(t => {
                t.classList.remove('border-blue-500', 'text-blue-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Masquer tous les contenus d'onglets
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Activer l'onglet cliqué
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-blue-500', 'text-blue-600');
            
            // Afficher le contenu correspondant
            const tabId = this.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.remove('hidden');
        });
    });
});

// Gestion de la soumission du formulaire d'inscription
document.getElementById('inscriptionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Récupérer les données du formulaire
    const formData = new FormData(this);
    
    // Afficher un indicateur de chargement
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enregistrement...';
    
    // Envoyer les données au serveur
    fetch('traitement_inscription.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher un message de succès
            alert('Inscription enregistrée avec succès !');
            // Réinitialiser le formulaire
            this.reset();
            // Recharger la page pour mettre à jour les statistiques
            location.reload();
        } else {
            // Afficher un message d'erreur
            alert('Erreur lors de l\'inscription : ' + (data.message || 'Une erreur est survenue'));
        }
    })
    .catch(error => {
        console.error('Erreur :', error);
        alert('Une erreur est survenue lors de l\'envoi du formulaire.');
    })
    .finally(() => {
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

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

// Gestion de l'exportation des données
document.getElementById('exportBtn')?.addEventListener('click', function() {
    // Ici, vous pouvez implémenter la logique d'exportation (Excel, PDF, etc.)
    alert('Fonctionnalité d\'exportation à implémenter');
});
</script>

<?php // Footer removed for now, will be added later if needed ?>
</body>
</html>