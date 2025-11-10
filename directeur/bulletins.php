<?php
$pageTitle = 'Gestion des bulletins de notes';
include 'includes/header.php';

// Initialiser les variables
$classes = [];
$periodes = [];
$error = null;

try {
    // Récupérer la liste des classes
    $query = "SELECT * FROM classes ORDER BY niveau, nom";
    $stmt = $db->query($query);
    if ($stmt) {
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des classes: " . $e->getMessage());
    $error = "Erreur lors du chargement des classes. Veuillez réessayer.";
}

try {
    // Récupérer les périodes d'évaluation
    $query = "SELECT * FROM periodes ORDER BY date_debut DESC";
    $stmt = $db->query($query);
    if ($stmt) {
        $periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des périodes: " . $e->getMessage());
    $error = $error ? $error . "<br>" : "";
    $error .= "Erreur lors du chargement des périodes. Veuillez réessayer.";
}

// Récupérer les paramètres de l'URL
$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;

$eleves = [];
$matieres = [];
$notes = [];

// Si une classe et une période sont sélectionnées
if ($classe_id > 0 && $periode_id > 0) {
    try {
        // Récupérer les élèves de la classe
        $query = "SELECT * FROM eleves WHERE classe_id = ? ORDER BY nom, prenom";
        $stmt = $db->prepare($query);
        $stmt->execute([$classe_id]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les matières de la classe
        $query = "SELECT * FROM matieres WHERE classe_id = ? ORDER BY nom";
        $stmt = $db->prepare($query);
        $stmt->execute([$classe_id]);
        $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si on a des élèves et des matières, on peut essayer de récupérer les notes
        if (!empty($eleves) && !empty($matieres)) {
            $eleve_ids = array_column($eleves, 'id');
            $matiere_ids = array_column($matieres, 'id');
            
            try {
                // Préparer les placeholders pour la requête SQL
                $placeholders = rtrim(str_repeat('?,', count($eleve_ids)), ',');
                $matiere_placeholders = rtrim(str_repeat('?,', count($matiere_ids)), ',');
                
                // Récupérer les notes avec les informations des élèves et des matières
                $query = "SELECT n.*, m.nom as matiere_nom, e.nom as eleve_nom, e.prenom as eleve_prenom, 
                                 m.coefficient as coefficient
                          FROM notes n 
                          LEFT JOIN matieres m ON n.matiere_id = m.id 
                          LEFT JOIN eleves e ON n.eleve_id = e.id 
                          WHERE n.eleve_id IN ($placeholders) 
                          AND n.matiere_id IN ($matiere_placeholders)
                          AND n.periode_id = ?
                          ORDER BY e.nom, e.prenom, m.nom";
                
                $params = array_merge($eleve_ids, $matiere_ids, [$periode_id]);
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $notes_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Initialiser le tableau des bulletins avec des valeurs par défaut
                foreach ($eleve_ids as $eleve_id) {
                    $bulletins[$eleve_id] = [];
                    foreach ($matiere_ids as $matiere_id) {
                        $bulletins[$eleve_id][$matiere_id] = [
                            'note' => null,
                            'appreciation' => '',
                            'coefficient' => 1
                        ];
                    }
                }
                
                // Mettre à jour avec les données de la base
                foreach ($notes_result as $note) {
                    $eleve_id = $note['eleve_id'];
                    $matiere_id = $note['matiere_id'];
                    
                    if (isset($bulletins[$eleve_id][$matiere_id])) {
                        $bulletins[$eleve_id][$matiere_id] = [
                            'note' => $note['note'],
                            'appreciation' => $note['appreciation'] ?? '',
                            'coefficient' => $note['coefficient'] ?? 1,
                            'matiere_nom' => $note['matiere_nom'] ?? 'Matière inconnue',
                            'eleve_nom' => trim(($note['eleve_nom'] ?? '') . ' ' . ($note['eleve_prenom'] ?? ''))
                        ];
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Erreur lors de la récupération des notes: " . $e->getMessage());
                $error = $error ? $error . "<br>" : "";
                $error .= "Une erreur est survenue lors du chargement des notes. Veuillez réessayer.";
            }
        }
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des données: " . $e->getMessage());
        $error = $error ? $error . "<br>" : "";
        $error .= "Erreur lors du chargement des données. Veuillez réessayer.";
    }
}

// Afficher les messages d'erreur s'il y en a
if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">
                    <?php echo $error; ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-lg shadow mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Générer les bulletins de notes</h2>
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
                <label for="periode_id" class="block text-sm font-medium text-gray-700">Période d'évaluation</label>
                <select id="periode_id" name="periode_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner une période</option>
                    <?php foreach ($periodes as $periode): ?>
                        <option value="<?php echo $periode['id']; ?>" <?php echo $periode_id == $periode['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($periode['nom'] . ' (' . date('d/m/Y', strtotime($periode['date_debut'])) . ' - ' . date('d/m/Y', strtotime($periode['date_fin'])) . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-search mr-2"></i> Afficher
                </button>
                
                <?php if ($classe_id > 0 && $periode_id > 0 && !empty($eleves)): ?>
                    <a href="generer_bulletin_pdf.php?classe_id=<?php echo $classe_id; ?>&periode_id=<?php echo $periode_id; ?>" 
                       target="_blank"
                       class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-file-pdf mr-2"></i> Générer PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php if ($classe_id > 0 && $periode_id > 0): ?>
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
    <?php else: ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:px-6 bg-gray-50">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
                    Bulletins de notes - 
                    <?php 
                        $classe_selected = array_filter($classes, function($c) use ($classe_id) {
                            return $c['id'] == $classe_id;
                        });
                        $classe_selected = reset($classe_selected);
                        echo htmlspecialchars($classe_selected['niveau'] . ' ' . $classe_selected['nom']);
                        
                        $periode_selected = array_filter($periodes, function($p) use ($periode_id) {
                            return $p['id'] == $periode_id;
                        });
                        $periode_selected = reset($periode_selected);
                        echo ' - ' . htmlspecialchars($periode_selected['nom']);
                    ?>
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Élève</th>
                            <?php foreach ($matieres as $matiere): ?>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" title="<?php echo htmlspecialchars($matiere['nom']); ?>">
                                    <?php echo strlen($matiere['nom']) > 10 ? substr($matiere['nom'], 0, 10) . '...' : $matiere['nom']; ?>
                                    <div class="text-xs text-gray-400">Coef. <?php echo $matiere['coefficient']; ?></div>
                                </th>
                            <?php endforeach; ?>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        if (empty($bulletins)) {
                            echo '<tr><td colspan="' . (count($matieres) + 2) . '" class="px-6 py-4 text-center text-gray-500">Aucune note enregistrée pour cette période.</td></tr>';
                        } else {
                            foreach ($eleves as $eleve): 
                                $total_points = 0;
                                $total_coefficients = 0;
                                $has_notes = false;
                                $eleve_notes = $bulletins[$eleve['id']] ?? [];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <span class="text-blue-600 font-medium">
                                                <?php echo strtoupper(substr($eleve['prenom'] ?? '', 0, 1) . substr($eleve['nom'] ?? '', 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars(($eleve['nom'] ?? '') . ' ' . ($eleve['prenom'] ?? '')); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo $eleve['matricule'] ?? ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <?php foreach ($matieres as $matiere): 
                                    $matiere_id = $matiere['id'];
                                    $note_data = $eleve_notes[$matiere_id] ?? [
                                        'note' => null,
                                        'appreciation' => '',
                                        'coefficient' => $matiere['coefficient'] ?? 1
                                    ];
                                    
                                    $note = $note_data['note'];
                                    $coefficient = $note_data['coefficient'] ?? 1;
                                    $points = $note !== null ? $note * $coefficient : 0;
                                    $total_points += $points;
                                    $total_coefficients += $coefficient;
                                    $has_notes = $has_notes || $note !== null;
                                    
                                    // Déterminer la couleur en fonction de la note
                                    $bg_color = '';
                                    $text_color = 'text-gray-500';
                                    if ($note !== null) {
                                        if ($note < 10) {
                                            $bg_color = 'bg-red-50';
                                            $text_color = 'text-red-700';
                                        } elseif ($note < 12) {
                                            $bg_color = 'bg-yellow-50';
                                            $text_color = 'text-yellow-700';
                                        } else {
                                            $bg_color = 'bg-green-50';
                                            $text_color = 'text-green-700';
                                        }
                                    }
                                ?>
                                    <td class="px-2 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $bg_color; ?> <?php echo $text_color; ?>">
                                            <?php echo $note; ?>/20
                                        </span>
                                    </td>
                                <?php endforeach; ?>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <?php if ($has_notes && $total_coefficients > 0): 
                                        $moyenne = $total_points / $total_coefficients;
                                    ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $moyenne >= 10 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
                                            <?php echo number_format($moyenne, 2); ?>/20
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-50 text-gray-500">
                                            -
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($has_notes): ?>
                                        <a href="voir_bulletin.php?eleve_id=<?php echo $eleve['id']; ?>&periode_id=<?php echo $periode_id; ?>" 
                                           class="text-blue-600 hover:text-blue-900 mr-4"
                                           target="_blank"
                                           title="Voir le bulletin">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                        <a href="editer_bulletin.php?eleve_id=<?php echo $eleve['id']; ?>&periode_id=<?php echo $periode_id; ?>" 
                                           class="text-indigo-600 hover:text-indigo-900 mr-4"
                                           target="_blank"
                                           title="Éditer les notes">
                                            <i class="fas fa-edit"></i> Éditer
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 mr-4" title="Aucune note enregistrée">
                                            <i class="fas fa-eye-slash"></i> Voir
                                        </span>
                                        <a href="editer_bulletin.php?eleve_id=<?php echo $eleve['id']; ?>&periode_id=<?php echo $periode_id; ?>" 
                                           class="text-indigo-300 hover:text-indigo-400 mr-4"
                                           target="_blank"
                                           title="Ajouter des notes">
                                            <i class="fas fa-plus-circle"></i> Ajouter
                                        </a>
                                    <?php endif; ?>
                                    <a href="generer_bulletin_pdf.php?eleve_id=<?php echo $eleve['id']; ?>&periode_id=<?php echo $periode_id; ?>" 
                                       class="text-green-600 hover:text-green-800"
                                       target="_blank"
                                       title="Télécharger le PDF">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            endforeach; // Fin de la boucle foreach des élèves
                        } // Fin du else (si $bulletins n'est pas vide)
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="text-center">
            <i class="fas fa-clipboard-list text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune sélection</h3>
            <p class="text-gray-500">Veuillez sélectionner une classe et une période pour afficher les bulletins de notes.</p>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour l'URL avec les paramètres de filtre
    const form = document.querySelector('form');
    const classeSelect = document.getElementById('classe_id');
    const periodeSelect = document.getElementById('periode_id');
    
    [classeSelect, periodeSelect].forEach(select => {
        select.addEventListener('change', function() {
            // Si les deux champs sont remplis, soumettre le formulaire
            if (classeSelect.value && periodeSelect.value) {
                form.submit();
            }
        });
    });
    
    // Gestion de l'impression
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
