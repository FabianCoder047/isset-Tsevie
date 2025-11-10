<?php
$pageTitle = 'Gestion des bulletins de notes';
include 'includes/header.php';

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialiser les variables
$classes = [];
$periodes = [];
$error = null;

try {
    // Récupérer la liste des périodes d'évaluation depuis la base de données
    $query = "SELECT id, nom, date_debut, date_fin, annee_scolaire, est_actif 
              FROM periodes 
              ORDER BY date_debut ASC";
    $stmt = $db->query($query);
    if ($stmt) {
        $periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des périodes: " . $e->getMessage());
    $error = "Erreur lors du chargement des périodes d'évaluation.";
}

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

// Récupérer les paramètres de l'URL
$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;

$eleves = [];
$matieres = [];
$bulletins = [];

// Si une classe et une période sont sélectionnées
if ($classe_id > 0 && $periode_id > 0) {
    try {
        // Trouver la période sélectionnée
        $periode_courante = null;
        foreach ($periodes as $p) {
            if ($p['id'] == $periode_id) {
                $periode_courante = $p;
                break;
            }
        }

        // Déterminer le semestre en fonction du nom de la période
        $semestre = 1; // Par défaut, premier semestre
        if ($periode_courante && preg_match('/2|deuxi[eè]me|second/i', $periode_courante['nom'])) {
            $semestre = 2;
        }
        
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
        
        if (empty($eleves)) {
            $error = "Aucun élève trouvé dans cette classe.";
        } elseif (empty($matieres)) {
            $error = "Aucune matière trouvée pour cette classe.";
        } else {
            $eleve_ids = array_column($eleves, 'id');
            $matiere_ids = array_column($matieres, 'id');
            
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
                      AND n.semestre = ?
                      AND n.classe_id = ?
                      ORDER BY e.nom, e.prenom, m.nom";
            
            $params = array_merge($eleve_ids, $matiere_ids, [$semestre, $classe_id]);
            
            try {
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $notes_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Initialiser le tableau des bulletins avec des valeurs par défaut
                foreach ($eleve_ids as $eleve_id) {
                    $eleve = array_filter($eleves, function($e) use ($eleve_id) {
                        return $e['id'] == $eleve_id;
                    });
                    $eleve = reset($eleve);
                    
                    $bulletins[$eleve_id] = [
                        'eleve_id' => $eleve_id,
                        'eleve_nom' => $eleve['nom'] . ' ' . $eleve['prenom'],
                        'matieres' => []
                    ];
                    
                    foreach ($matieres as $matiere) {
                        $note_data = [
                            'matiere_id' => $matiere['id'],
                            'matiere_nom' => $matiere['nom'],
                            'coefficient' => $matiere['coefficient'],
                            'interro1' => null,
                            'interro2' => null,
                            'devoir' => null,
                            'compo' => null,
                            'moyenne' => null
                        ];
                        
                        // Chercher si une note existe pour cet élève et cette matière
                        foreach ($notes_result as $note) {
                            if ($note['eleve_id'] == $eleve_id && $note['matiere_id'] == $matiere['id']) {
                                $note_data['interro1'] = $note['interro1'];
                                $note_data['interro2'] = $note['interro2'];
                                $note_data['devoir'] = $note['devoir'];
                                $note_data['compo'] = $note['compo'];
                                
                                // Calculer la moyenne
                                $somme = 0;
                                $nb_notes = 0;
                                
                                if (!is_null($note['interro1'])) {
                                    $somme += $note['interro1'];
                                    $nb_notes++;
                                }
                                if (!is_null($note['interro2'])) {
                                    $somme += $note['interro2'];
                                    $nb_notes++;
                                }
                                if (!is_null($note['devoir'])) {
                                    $somme += $note['devoir'];
                                    $nb_notes++;
                                }
                                if (!is_null($note['compo'])) {
                                    $somme += $note['compo'] * 2; // La composition compte double
                                    $nb_notes += 2;
                                }
                                
                                if ($nb_notes > 0) {
                                    $moyenne = $somme / $nb_notes;
                                    $note_data['moyenne'] = number_format($moyenne, 2, ',', ' ');
                                }
                                
                                break;
                            }
                        }
                        
                        $bulletins[$eleve_id]['matieres'][$matiere['id']] = $note_data;
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Erreur lors de la récupération des notes: " . $e->getMessage());
                $error = "Une erreur est survenue lors du chargement des notes: " . $e->getMessage();
            }
        }
        
    } catch (PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
        $error = "Erreur lors du chargement des données: " . $e->getMessage();
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
                            <?php echo htmlspecialchars($classe['nom'] . ' ' . $classe['niveau']); ?>
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
                            <?php echo htmlspecialchars($periode['nom']); ?>
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
    <?php if (!empty($error)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle h-5 w-5 text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <?php echo $error; ?>
                    </p>
                </div>
            </div>
        </div>
    <?php elseif (empty($eleves)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle h-5 w-5 text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Aucun élève trouvé dans cette classe.
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
                    $classe = array_filter($classes, function($c) use ($classe_id) {
                        return $c['id'] == $classe_id;
                    });
                    $classe = reset($classe);
                    $periode = array_filter($periodes, function($p) use ($periode_id) {
                        return $p['id'] == $periode_id;
                    });
                    $periode = reset($periode);
                    echo htmlspecialchars($classe['nom'] . ' ' . $classe['niveau'] . ' - ' . $periode['nom']);
                    ?>
                </h3>
            </div>
            
            <div class="px-4 py-5 sm:p-0">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Élève
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Moyenne
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($eleves as $eleve): ?>
                        <?php 
                        $has_notes = false;
                        $moyenne_eleve = 0;
                        $total_coeff = 0;
                        
                        if (isset($bulletins[$eleve['id']])) {
                            foreach ($bulletins[$eleve['id']]['matieres'] as $matiere_id => $matiere_data) {
                                if ($matiere_data['moyenne'] !== null) {
                                    $has_notes = true;
                                    $moyenne_eleve += (float)str_replace(',', '.', $matiere_data['moyenne']) * $matiere_data['coefficient'];
                                    $total_coeff += $matiere_data['coefficient'];
                                }
                            }
                        }
                        
                        if ($has_notes && $total_coeff > 0) {
                            $moyenne_generale = $moyenne_eleve / $total_coeff;
                            $couleur = $moyenne_generale >= 10 ? 'text-green-600' : 'text-red-600';
                        } else {
                            $couleur = 'text-gray-400';
                        }
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?>
                            </td>
                            
                            <?php endforeach; ?>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                <?php 
                                if ($has_notes && $total_coeff > 0) {
                                    $moyenne_generale = $moyenne_eleve / $total_coeff;
                                    $couleur = $moyenne_generale >= 10 ? 'text-green-600' : 'text-red-600';
                                    echo '<span class="font-semibold ' . $couleur . '">' . number_format($moyenne_generale, 2, ',', ' ') . ' / 20</span>';
                                } else {
                                    echo '<span class="text-gray-400">-</span>';
                                }
                                ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <?php if ($has_notes): ?>
                                    <a href="voir_bulletin.php?eleve_id=<?php echo $eleve['id']; ?>&classe_id=<?php echo $classe_id; ?>&periode_id=<?php echo $periode_id; ?>" 
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                       title="Voir le bulletin">
                                        <i class="fas fa-eye mr-1"></i> Voir
                                    </a>
                                    <a href="generer_bulletin_pdf.php?eleve_id=<?php echo $eleve['id']; ?>&classe_id=<?php echo $classe_id; ?>&periode_id=<?php echo $periode_id; ?>" 
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                       title="Télécharger le PDF"
                                       target="_blank">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
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
