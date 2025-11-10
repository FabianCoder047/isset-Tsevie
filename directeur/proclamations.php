<?php
$pageTitle = 'Fiches de proclamation';
include 'includes/header.php';

// Récupérer la liste des classes
$query = "SELECT * FROM classes ORDER BY niveau, nom";
$classes = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les années scolaires
$query = "SELECT DISTINCT annee_scolaire FROM periodes ORDER BY annee_scolaire DESC";
$annees_scolaires = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

// Si une classe et une année sont sélectionnées
$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$annee_scolaire = isset($_GET['annee_scolaire']) ? $_GET['annee_scolaire'] : '';

$eleves = [];
$periodes = [];
$resultats = [];

if ($classe_id > 0 && $annee_scolaire) {
    // Récupérer les élèves de la classe
    $query = "SELECT * FROM eleves WHERE classe_id = ? ORDER BY nom, prenom";
    $stmt = $db->prepare($query);
    $stmt->execute([$classe_id]);
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les périodes de l'année scolaire sélectionnée
    $query = "SELECT * FROM periodes 
              WHERE annee_scolaire = ? 
              ORDER BY date_debut";
    $stmt = $db->prepare($query);
    $stmt->execute([$annee_scolaire]);
    $periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les résultats si des élèves et des périodes existent
    if (!empty($eleves) && !empty($periodes)) {
        $eleve_ids = array_column($eleves, 'id');
        $periode_ids = array_column($periodes, 'id');
        
        $placeholders = rtrim(str_repeat('?,', count($eleve_ids)), ',');
        $periode_placeholders = rtrim(str_repeat('?,', count($periode_ids)), ',');
        
        // Récupérer les moyennes par période pour chaque élève
        $query = "SELECT n.eleve_id, n.periode_id, AVG(n.note) as moyenne
                  FROM notes n
                  JOIN matieres m ON n.matiere_id = m.id
                  WHERE n.eleve_id IN ($placeholders)
                  AND n.periode_id IN ($periode_placeholders)
                  GROUP BY n.eleve_id, n.periode_id";
        
        $params = array_merge($eleve_ids, $periode_ids);
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $moyennes = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
        
        // Organiser les résultats par élève
        foreach ($eleves as $eleve) {
            $resultats[$eleve['id']] = [
                'nom' => $eleve['nom'] . ' ' . $eleve['prenom'],
                'moyennes' => [],
                'moyenne_annuelle' => 0
            ];
            
            $total_moyennes = 0;
            $nb_periodes = 0;
            
            // Initialiser les moyennes pour chaque période
            foreach ($periodes as $periode) {
                $moyenne = null;
                
                // Rechercher la moyenne pour cette période
                if (isset($moyennes[$eleve['id']])) {
                    foreach ($moyennes[$eleve['id']] as $m) {
                        if ($m['periode_id'] == $periode['id']) {
                            $moyenne = (float)$m['moyenne'];
                            $total_moyennes += $moyenne;
                            $nb_periodes++;
                            break;
                        }
                    }
                }
                
                $resultats[$eleve['id']]['moyennes'][$periode['id']] = $moyenne;
            }
            
            // Calculer la moyenne annuelle
            if ($nb_periodes > 0) {
                $resultats[$eleve['id']]['moyenne_annuelle'] = $total_moyennes / $nb_periodes;
            }
        }
        
        // Trier les résultats par moyenne annuelle décroissante
        uasort($resultats, function($a, $b) {
            return $b['moyenne_annuelle'] <=> $a['moyenne_annuelle'];
        });
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Générer les fiches de proclamation</h2>
    </div>
    
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="classe_id" class="block text-sm font-medium text-gray-700">Classe</label>
                <select id="classe_id" name="classe_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner une classe</option>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?php echo $classe['id']; ?>" <?php echo $classe_id == $classe['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="annee_scolaire" class="block text-sm font-medium text-gray-700">Année scolaire</label>
                <select id="annee_scolaire" name="annee_scolaire" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner une année</option>
                    <?php foreach ($annees_scolaires as $annee): ?>
                        <option value="<?php echo htmlspecialchars($annee); ?>" <?php echo $annee_scolaire === $annee ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($annee); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-search mr-2"></i> Afficher
                </button>
                
                <?php if ($classe_id > 0 && $annee_scolaire && !empty($eleves)): ?>
                    <a href="generer_fiche_proclamation_pdf.php?classe_id=<?php echo $classe_id; ?>&annee_scolaire=<?php echo urlencode($annee_scolaire); ?>" 
                       target="_blank"
                       class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-file-pdf mr-2"></i> Générer PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php if ($classe_id > 0 && $annee_scolaire): ?>
    <?php if (empty($eleves)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle h-5 w-5 text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Aucun élève n'est inscrit dans cette classe pour le moment.
                    </p>
                </div>
            </div>
        </div>
    <?php elseif (empty($periodes)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle h-5 w-5 text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Aucune période d'évaluation n'a été définie pour l'année scolaire sélectionnée.
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:px-6 bg-gray-50 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        Fiche de proclamation - 
                        <?php 
                            $classe_selected = array_filter($classes, function($c) use ($classe_id) {
                                return $c['id'] == $classe_id;
                            });
                            $classe_selected = reset($classe_selected);
                            echo htmlspecialchars($classe_selected['niveau'] . ' ' . $classe_selected['nom']);
                        ?>
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Année scolaire : <?php echo htmlspecialchars($annee_scolaire); ?>
                    </p>
                </div>
                <div>
                    <button type="button"
                            onclick="window.print()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-print mr-2"></i> Imprimer
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rang</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom et Prénom</th>
                            <?php foreach ($periodes as $periode): ?>
                                <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo htmlspecialchars($periode['nom']); ?>
                                </th>
                            <?php endforeach; ?>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne<br>annuelle</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Mention</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Décision</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $rang = 0; ?>
                        <?php foreach ($resultats as $eleve_id => $resultat): ?>
                            <?php $rang++; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $rang; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($resultat['nom']); ?>
                                    </div>
                                </td>
                                
                                <?php foreach ($periodes as $periode): ?>
                                    <?php 
                                        $moyenne = $resultat['moyennes'][$periode['id']] ?? null;
                                        $moyenne_affichage = $moyenne !== null ? number_format($moyenne, 2) : '-';
                                    ?>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                        <?php if ($moyenne !== null): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $moyenne >= 10 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $moyenne_affichee; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($resultat['moyenne_annuelle'] > 0): 
                                        $moyenne_annuelle = number_format($resultat['moyenne_annuelle'], 2);
                                        $mention = '';
                                        $decision = 'Ajourné(e)';
                                        
                                        if ($moyenne_annuelle >= 16) {
                                            $mention = 'Très Bien';
                                            $decision = 'Admis(e)';
                                        } elseif ($moyenne_annuelle >= 14) {
                                            $mention = 'Bien';
                                            $decision = 'Admis(e)';
                                        } elseif ($moyenne_annuelle >= 12) {
                                            $mention = 'Assez Bien';
                                            $decision = 'Admis(e)';
                                        } elseif ($moyenne_annuelle >= 10) {
                                            $mention = 'Passable';
                                            $decision = 'Admis(e)';
                                        }
                                    ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $resultat['moyenne_annuelle'] >= 10 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $moyenne_annuelle; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <?php if (isset($mention) && $mention): ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo $mention; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <?php if (isset($decision)): ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $decision === 'Admis(e)' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $decision; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                <div class="text-sm text-gray-700">
                    Effectif : <span class="font-medium"><?php echo count($eleves); ?> élèves</span> | 
                    Admis : <span class="font-medium text-green-600">
                        <?php 
                            $nb_admis = array_reduce($resultats, function($carry, $item) {
                                $moyenne = $item['moyenne_annuelle'] ?? 0;
                                return $carry + ($moyenne >= 10 ? 1 : 0);
                            }, 0);
                            echo $nb_admis;
                        ?>
                    </span> | 
                    Ajournés : <span class="font-medium text-red-600">
                        <?php echo count($eleves) - $nb_admis; ?>
                    </span> | 
                    Taux de réussite : <span class="font-medium">
                        <?php echo count($eleves) > 0 ? round(($nb_admis / count($eleves)) * 100, 2) : 0; ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Légende des mentions</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-3 bg-green-50 rounded">
                    <div class="flex items-center">
                        <div class="h-4 w-4 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium">Très Bien</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">Moyenne ≥ 16/20</p>
                </div>
                <div class="p-3 bg-blue-50 rounded">
                    <div class="flex items-center">
                        <div class="h-4 w-4 bg-blue-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium">Bien</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">14/20 ≤ Moyenne < 16/20</p>
                </div>
                <div class="p-3 bg-indigo-50 rounded">
                    <div class="flex items-center">
                        <div class="h-4 w-4 bg-indigo-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium">Assez Bien</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">12/20 ≤ Moyenne < 14/20</p>
                </div>
                <div class="p-3 bg-yellow-50 rounded">
                    <div class="flex items-center">
                        <div class="h-4 w-4 bg-yellow-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium">Passable</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">10/20 ≤ Moyenne < 12/20</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="text-center">
            <i class="fas fa-award text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune sélection</h3>
            <p class="text-gray-500">Veuillez sélectionner une classe et une année scolaire pour afficher les fiches de proclamation.</p>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour l'URL avec les paramètres de filtre
    const form = document.querySelector('form');
    const classeSelect = document.getElementById('classe_id');
    const anneeSelect = document.getElementById('annee_scolaire');
    
    [classeSelect, anneeSelect].forEach(select => {
        select.addEventListener('change', function() {
            // Si les deux champs sont remplis, soumettre le formulaire
            if (classeSelect.value && anneeSelect.value) {
                form.submit();
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
