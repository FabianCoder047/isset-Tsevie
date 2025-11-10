<?php
require_once dirname(__DIR__) . '/includes/db.php';

// Récupérer les affectations existantes
$query = "SELECT e.*, u.nom as prof_nom, u.prenom as prof_prenom, 
                 m.nom as matiere_nom, m.coefficient, 
                 c.nom as classe_nom, c.niveau as classe_niveau
          FROM enseignements e
          JOIN utilisateurs u ON e.professeur_id = u.id
          JOIN matieres m ON e.matiere_id = m.id
          JOIN classes c ON m.classe_id = c.id
          ORDER BY c.niveau, c.nom, m.nom";
$affectations = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Tableau des affectations -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professeur</th>
                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Coef.</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($affectations) > 0): ?>
                <?php foreach ($affectations as $affectation): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($affectation['classe_niveau'] . ' ' . $affectation['classe_nom']); ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($affectation['matiere_nom']); ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($affectation['prof_prenom'] . ' ' . $affectation['prof_nom']); ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?php echo htmlspecialchars($affectation['coefficient']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="deleteAffectation(<?php echo $affectation['id']; ?>, '<?php echo addslashes($affectation['matiere_nom'] . ' - ' . $affectation['prof_nom']); ?>')"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-unlink mr-1"></i> Retirer
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">
                        Aucune affectation enregistrée pour le moment.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
