<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/header.php';

// Récupérer les statistiques
$query = "SELECT COUNT(*) as total FROM utilisateurs WHERE role IN ('professeur', 'secretaire')";
$result = $db->query($query);
$stats['total_personnel'] = $result->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM classes";
$result = $db->query($query);
$stats['total_classes'] = $result->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM eleves";
$result = $db->query($query);
$stats['total_eleves'] = $result->fetch(PDO::FETCH_ASSOC)['total'];


// Récupérer les effectifs par niveau (si les tables existent)
$effectifs = [];
try {
    $query = "SELECT c.nom as niveau, COUNT(e.id) as effectif 
              FROM eleves e 
              JOIN classes c ON e.classe_id = c.id 
              GROUP BY c.nom";
    $effectifs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table non trouvée, on continue avec un tableau vide
    error_log("Erreur lors de la récupération des effectifs: " . $e->getMessage());
    
    // Données factices pour le développement
    $effectifs = [
        ['niveau' => '6ème', 'effectif' => 45],
        ['niveau' => '5ème', 'effectif' => 52],
        ['niveau' => '4ème', 'effectif' => 48],
        ['niveau' => '3ème', 'effectif' => 55],
        ['niveau' => '2nde', 'effectif' => 60],
        ['niveau' => '1ère', 'effectif' => 58],
        ['niveau' => 'Tle', 'effectif' => 62]
    ];
}
?>

<!-- Cartes de statistiques -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Carte Effectifs élèves -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                <i class="fas fa-user-graduate text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-gray-500 text-sm font-medium">Effectif total</h3>
                <p class="text-2xl font-semibold"><?php echo $stats['total_eleves']; ?> élèves</p>
            </div>
        </div>
    </div>

    <!-- Carte Classes -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-chalkboard-teacher text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-gray-500 text-sm font-medium">Classes</h3>
                <p class="text-2xl font-semibold"><?php echo $stats['total_classes']; ?> classes</p>
            </div>
        </div>
    </div>

    <!-- Carte Personnel -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-gray-500 text-sm font-medium">Personnel</h3>
                <p class="text-2xl font-semibold"><?php echo $stats['total_personnel']; ?> membres</p>
            </div>
        </div>
    </div>

    <!-- Carte Prochaines échéances -->
    
    </div>
</div>




<!-- Prochaines échéances -->
<div class="grid grid-cols-1 md:grid-cols-1 gap-6">
    

    <!-- Actions rapides -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Actions rapides</h2>
        <div class="grid grid-cols-2 gap-4">
            <a href="matieres.php" class="p-4 border rounded-lg text-center hover:bg-gray-50 transition-colors">
                <div class="text-blue-600 mb-2">
                    <i class="fas fa-book text-2xl"></i>
                </div>
                <span class="text-sm">Gérer les matières</span>
            </a>
            <a href="comptes.php" class="p-4 border rounded-lg text-center hover:bg-gray-50 transition-colors">
                <div class="text-green-600 mb-2">
                    <i class="fas fa-user-plus text-2xl"></i>
                </div>
                <span class="text-sm">Créer un compte</span>
            </a>
            <a href="affectations.php" class="p-4 border rounded-lg text-center hover:bg-gray-50 transition-colors">
                <div class="text-purple-600 mb-2">
                    <i class="fas fa-chalkboard-teacher text-2xl"></i>
                </div>
                <span class="text-sm">Affecter un professeur</span>
            </a>
            <a href="bulletins.php" class="p-4 border rounded-lg text-center hover:bg-gray-50 transition-colors">
                <div class="text-yellow-600 mb-2">
                    <i class="fas fa-file-alt text-2xl"></i>
                </div>
                <span class="text-sm">Valider les bulletins</span>
            </a>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des effectifs par niveau
    const ctx = document.getElementById('studentsByLevelChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($effectifs, 'niveau')); ?>,
            datasets: [{
                label: 'Nombre d\'élèves',
                data: <?php echo json_encode(array_column($effectifs, 'effectif')); ?>,
                backgroundColor: [
                    'rgba(79, 70, 229, 0.7)',
                    'rgba(67, 56, 202, 0.7)',
                    'rgba(99, 102, 241, 0.7)',
                    'rgba(129, 140, 248, 0.7)',
                    'rgba(165, 180, 252, 0.7)'
                ],
                borderColor: [
                    'rgba(79, 70, 229, 1)',
                    'rgba(67, 56, 202, 1)',
                    'rgba(99, 102, 241, 1)',
                    'rgba(129, 140, 248, 1)',
                    'rgba(165, 180, 252, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 10
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
