<?php
require_once 'includes/header.php';

// Récupérer les classes et matières du professeur
$query = "SELECT DISTINCT m.id as matiere_id, 
          m.nom as matiere_nom, 
          c.nom as classe_nom,
          c.id as classe_id,
          m.coefficient
          FROM enseignements e
          JOIN matieres m ON e.matiere_id = m.id
          JOIN classes c ON m.classe_id = c.id
          WHERE e.professeur_id = ?
          ORDER BY m.nom, c.nom";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            <i class="fas fa-chalkboard-teacher mr-2"></i> Mes matières
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Liste des matières qui vous sont attribuées avec les classes correspondantes
        </p>
    </div>
    
    <div class="border-t border-gray-200">
        <?php if (empty($matieres)): ?>
            <div class="px-4 py-5 sm:px-6">
                <p class="text-gray-500 text-center py-4">Aucune matière attribuée pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php 
                    // Grouper les matières par ID pour afficher une seule ligne par matière avec toutes les classes
                    $matieres_grouped = [];
                    foreach ($matieres as $matiere) {
                        $matiere_id = $matiere['matiere_id'];
                        if (!isset($matieres_grouped[$matiere_id])) {
                            $matieres_grouped[$matiere_id] = [
                                'nom' => $matiere['matiere_nom'],
                                'coefficient' => $matiere['coefficient'],
                                'classes' => []
                            ];
                        }
                        $matieres_grouped[$matiere_id]['classes'][] = [
                            'id' => $matiere['classe_id'],
                            'nom' => $matiere['classe_nom']
                        ];
                    }
                    
                    foreach ($matieres_grouped as $matiere_id => $matiere): ?>
                        <li>
                            <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-medium text-blue-600 truncate">
                                        <?php echo htmlspecialchars($matiere['nom']); ?>
                                        <span class="text-xs text-gray-500 ml-2">(Coef: <?php echo $matiere['coefficient']; ?>)</span>
                                    </div>
                                    <div class="ml-2 flex-shrink-0 flex flex-wrap gap-1">
                                        <?php foreach ($matiere['classes'] as $classe): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($classe['nom']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="mt-2 sm:flex sm:justify-between">
                                    <div class="sm:flex">
                                        <div class="mr-6 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-graduation-cap mr-1"></i>
                                            <?php 
                                            $classes_list = array_map(function($c) { 
                                                return $c['nom']; 
                                            }, $matiere['classes']);
                                            echo htmlspecialchars(implode(', ', $classes_list));
                                            ?>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                        <?php if (count($matiere['classes']) === 1): ?>
                                            <a href="saisie_notes.php?matiere=<?php echo $matiere_id; ?>&classe=<?php echo $matiere['classes'][0]['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-edit mr-1"></i> Saisir les notes
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">Sélectionnez une classe pour saisir les notes</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>