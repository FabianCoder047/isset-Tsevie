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
if (!empty($error)) {
    echo '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">';
    echo '    <div class="flex">';
    echo '        <div class="flex-shrink-0">';
    echo '            <i class="fas fa-exclamation-circle text-red-400"></i>';
    echo '        </div>';
    echo '        <div class="ml-3">';
    echo '            <p class="text-sm text-red-700">';
    echo '                ' . htmlspecialchars($error);
    echo '            </p>';
    echo '        </div>';
    echo '    </div>';
    echo '</div>';
}

echo '<div class="bg-white p-6 rounded-lg shadow mb-8">';
echo '    <div class="flex justify-between items-center mb-6">';
echo '        <h2 class="text-xl font-semibold">Générer les bulletins de notes</h2>';
echo '    </div>';
echo '    ';
echo '    <form method="GET" class="space-y-4">';
echo '        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
echo '            <div>';
echo '                <label for="classe_id" class="block text-sm font-medium text-gray-700">Classe</label>';
echo '                <select id="classe_id" name="classe_id" required';
echo '                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
echo '                    <option value="">Sélectionner une classe</option>';

foreach ($classes as $classe) {
    $selected = ($classe_id == $classe['id']) ? 'selected' : '';
    echo '                    <option value="' . $classe['id'] . '" ' . $selected . '>';
    echo htmlspecialchars($classe['nom'] . ' ' . $classe['niveau']);
    echo '</option>';
}

echo '                </select>';
echo '            </div>';
echo '            ';
echo '            <div>';
echo '                <label for="periode_id" class="block text-sm font-medium text-gray-700">Période d\'évaluation</label>';
echo '                <select id="periode_id" name="periode_id" required';
echo '                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
echo '                    <option value="">Sélectionner une période</option>';

foreach ($periodes as $periode) {
    $selected = ($periode_id == $periode['id']) ? 'selected' : '';
    echo '                    <option value="' . $periode['id'] . '" ' . $selected . '>';
    echo htmlspecialchars($periode['nom']);
    echo '</option>';
}

echo '                </select>';
echo '            </div>';
echo '            ';
echo '            <div class="flex items-end">';
echo '                <button type="submit"';
echo '                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">';
echo '                    <i class="fas fa-search mr-2"></i> Afficher';
echo '                </button>';

if ($classe_id > 0 && $periode_id > 0 && !empty($eleves)) {
    echo '                <a href="generer_bulletin_pdf.php?classe_id=' . $classe_id . '&periode_id=' . $periode_id . '" ';
    echo '                   target="_blank"';
    echo '                   class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">';
    echo '                    <i class="fas fa-file-pdf mr-2"></i> Générer PDF';
    echo '                </a>';
}

echo '            </div>';
echo '        </div>';
echo '    </form>';
echo '</div>';

// Afficher les bulletins si une classe et une période sont sélectionnées
if ($classe_id > 0 && $periode_id > 0) { 
    // Afficher une alerte s'il y a une erreur
    if (!empty($error)) { 
        echo '<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">';
        echo '    <div class="flex">';
        echo '        <div class="flex-shrink-0">';
        echo '            <i class="fas fa-exclamation-triangle h-5 w-5 text-yellow-400"></i>';
        echo '        </div>';
        echo '        <div class="ml-3">';
        echo '            <p class="text-sm text-yellow-700">';
        echo '                ' . htmlspecialchars($error);
        echo '            </p>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';
    } else { // Pas d'erreur, afficher les bulletins
        $classe = array_filter($classes, function($c) use ($classe_id) {
            return $c['id'] == $classe_id;
        });
        $classe = reset($classe);
        
        $periode = array_filter($periodes, function($p) use ($periode_id) {
            return $p['id'] == $periode_id;
        });
        $periode = reset($periode);
        
        echo '<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">';
        echo '    <div class="px-4 py-5 sm:px-6 bg-gray-50">';
        echo '        <h3 class="text-lg font-medium leading-6 text-gray-900">';
        echo '            Bulletins de notes - ';
        echo '            ' . htmlspecialchars($classe['niveau'] . ' ' . $classe['nom'] . ' - ' . $periode['nom'] . ' ' . $periode['annee_scolaire']);
        echo '        </h3>';
        echo '        <p class="mt-1 max-w-2xl text-sm text-gray-500">';
        echo '            Liste des élèves et de leurs moyennes pour la période sélectionnée.';
        echo '        </p>';
        echo '    </div>';
        echo '    <div class="px-4 py-5 sm:p-0">';
        echo '        <table class="min-w-full divide-y divide-gray-200">';
        echo '            <thead class="bg-gray-50">';
        echo '                <tr>';
        echo '                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">';
        echo '                        Élève';
        echo '                    </th>';
        echo '                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">';
        echo '                        Moyenne';
        echo '                    </th>';
        echo '                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">';
        echo '                        Actions';
        echo '                    </th>';
        echo '                </tr>';
        echo '            </thead>';
        echo '            <tbody class="bg-white divide-y divide-gray-200">';
        
        // Vérifier si $eleves est défini et n'est pas vide
        if (!empty($eleves)) { 
            foreach ($eleves as $eleve) { 
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
                
                echo '                <tr>';
                echo '                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">';
                echo '                        ' . htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']);
                echo '                    </td>';
                echo '                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">';
                
                if ($has_notes && $total_coeff > 0) {
                    $moyenne_generale = $moyenne_eleve / $total_coeff;
                    $couleur = $moyenne_generale >= 10 ? 'text-green-600' : 'text-red-600';
                    echo '                        <span class="font-semibold ' . $couleur . '">' . number_format($moyenne_generale, 2, ',', ' ') . ' / 20</span>';
                } else {
                    echo '                        <span class="text-gray-400">-</span>';
                }
                
                echo '                    </td>';
                echo '                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">';
                
                if ($has_notes) {
                    echo '                        <a href="voir_bulletin.php?eleve_id=' . $eleve['id'] . '&classe_id=' . $classe_id . '&periode_id=' . $periode_id . '" ';
                    echo '                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"';
                    echo '                           title="Voir le bulletin">';
                    echo '                            <i class="fas fa-eye mr-1"></i> Voir';
                    echo '                        </a>';
                    echo '                        <a href="generer_bulletin_pdf.php?eleve_id=' . $eleve['id'] . '&classe_id=' . $classe_id . '&periode_id=' . $periode_id . '" ';
                    echo '                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"';
                    echo '                           title="Télécharger le PDF"';
                    echo '                           target="_blank">';
                    echo '                            <i class="fas fa-file-pdf mr-1"></i> PDF';
                    echo '                        </a>';
                } else {
                    echo '                        <span class="text-gray-400 text-sm">-</span>';
                }
                
                echo '                    </td>';
                echo '                </tr>';
            }
        } else { // Aucun élève trouvé
            echo '                <tr>';
            echo '                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">';
            echo '                        Aucun élève trouvé dans cette classe.';
            echo '                    </td>';
            echo '                </tr>';
        }
        
        echo '            </tbody>';
        echo '        </table>';
        echo '    </div>';
        echo '</div>';
    }
} else { // Aucune sélection de classe ou période 
    echo '<div class="bg-white p-6 rounded-lg shadow">';
    echo '    <div class="text-center">';
    echo '        <i class="fas fa-clipboard-list text-4xl text-gray-400 mb-4"></i>';
    echo '        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune sélection</h3>';
    echo '        <p class="text-gray-500">Veuillez sélectionner une classe et une période pour afficher les bulletins de notes.</p>';
    echo '    </div>';
    echo '</div>';
}

echo '<script>';
echo 'document.addEventListener("DOMContentLoaded", function() {';
echo '    // Mettre à jour l\'URL avec les paramètres de filtre';
echo '    const form = document.querySelector("form");';
echo '    const classeSelect = document.getElementById("classe_id");';
echo '    const periodeSelect = document.getElementById("periode_id");';
echo '    ';
echo '    [classeSelect, periodeSelect].forEach(select => {';
echo '        select.addEventListener("change", function() {';
echo '            // Si les deux champs sont remplis, soumettre le formulaire';
echo '            if (classeSelect.value && periodeSelect.value) {';
echo '                form.submit();';
echo '            }';
echo '        });';
echo '    });';
echo '    ';
echo '    // Gestion de l\'impression';
echo '    const printBtn = document.getElementById("printBtn");';
echo '    if (printBtn) {';
echo '        printBtn.addEventListener("click", function() {';
echo '            window.print();';
echo '        });';
echo '    }';
echo '});';
echo '</script>';

include 'includes/footer.php';
