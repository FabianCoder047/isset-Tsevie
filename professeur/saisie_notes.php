<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

/**
 * Vérifie si un enregistrement existe dans une table
 * @param PDO $db Instance PDO
 * @param string $table Nom de la table
 * @param int $id ID à vérifier
 * @return bool
 */
function verifierExistence($db, $table, $id) {
    $query = "SELECT id FROM $table WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    return (bool)$stmt->fetch();
}

// Vérifier si l'utilisateur est connecté et est un professeur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professeur') {
    header('Location: ../login.php');
    exit();
}

// Vérifier que matiere et classe sont fournis
if (!isset($_GET['matiere']) || !is_numeric($_GET['matiere']) || !isset($_GET['classe']) || !is_numeric($_GET['classe'])) {
    $_SESSION['error'] = "Paramètres manquants pour la saisie des notes.";
    header('Location: index.php');
    exit();
}

$matiere_id = (int)$_GET['matiere'];
$classe_id = (int)$_GET['classe'];

// Vérifier que la matière et la classe existent
if (!verifierExistence($db, 'matieres', $matiere_id)) {
    $_SESSION['error'] = "La matière spécifiée n'existe pas.";
    header('Location: index.php');
    exit();
}

if (!verifierExistence($db, 'classes', $classe_id)) {
    $_SESSION['error'] = "La classe spécifiée n'existe pas.";
    header('Location: index.php');
    exit();
}

// Récupérer les informations de la matière et de la classe
$query = "SELECT m.id as matiere_id, m.nom as matiere_nom, 
                 c.id as classe_id, c.nom as classe_nom, c.niveau, 
                 m.coefficient
          FROM matieres m
          JOIN classes c ON m.classe_id = c.id
          WHERE m.id = ? AND m.classe_id = ?";
          
$stmt = $db->prepare($query);
$stmt->execute([$matiere_id, $classe_id]);
$matiereClasse = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$matiereClasse) {
    $_SESSION['error'] = "Cette matière n'existe pas pour cette classe.";
    header('Location: index.php');
    exit();
}

// Vérifier que le professeur est bien affecté à cette matière
$query = "SELECT 1 
          FROM enseignements e
          WHERE e.professeur_id = ? 
          AND e.matiere_id = ?";
          
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $matiere_id]);
$isAuthorized = $stmt->fetch();

if (!$isAuthorized) {
    $_SESSION['error'] = "Vous n'êtes pas autorisé à saisir des notes pour cette matière.";
    header('Location: index.php');
    exit();
}

// Récupérer la période active
$query = "SELECT * FROM periodes WHERE est_actif = 1 LIMIT 1";
$periode = $db->query($query)->fetch(PDO::FETCH_ASSOC);
$semestre_actif = $periode ? (strpos($periode['nom'], '1') !== false ? 1 : 2) : 1;

// Récupérer la liste des élèves de la classe avec leurs notes
$query = "SELECT 
            e.id, 
            e.nom, 
            e.prenom, 
            n.interro1, 
            n.interro2, 
            n.devoir, 
            n.compo,
            n.semestre,
            n.derniere_maj,
            u.nom as prof_nom,
            u.prenom as prof_prenom
          FROM eleves e
          LEFT JOIN notes n ON n.eleve_id = e.id 
              AND n.matiere_id = ? 
              AND n.classe_id = ?
              AND n.semestre = ?
          LEFT JOIN utilisateurs u ON n.saisie_par = u.id
          WHERE e.classe_id = ?
          ORDER BY e.nom, e.prenom";
          
$stmt = $db->prepare($query);
$stmt->execute([$matiere_id, $classe_id, $semestre_actif, $classe_id]);
$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de saisie des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log the POST data for debugging
        error_log("POST data: " . print_r($_POST, true));
        error_log("Matiere ID: $matiere_id, Classe ID: $classe_id, User ID: " . $_SESSION['user_id']);
        
        $db->beginTransaction();
        
        $semestre = isset($_POST['semestre']) ? (int)$_POST['semestre'] : $semestre_actif;
        $professeur_id = $_SESSION['user_id'];
        $now = date('Y-m-d H:i:s');
        
        // Vérifier que la matière existe et est disponible pour cette classe
        $query = "SELECT m.* FROM matieres m 
                 WHERE m.id = ? AND (m.classe_id IS NULL OR m.classe_id = ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$matiere_id, $classe_id]);
        $matiere = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$matiere) {
            // Vérifier si la matière existe mais n'est pas disponible pour cette classe
            $query = "SELECT 1 FROM matieres WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$matiere_id]);
            if ($stmt->fetch()) {
                throw new Exception("La matière spécifiée n'est pas disponible pour cette classe (Matière ID: $matiere_id, Classe ID: $classe_id).");
            } else {
                throw new Exception("La matière spécifiée n'existe pas (ID: $matiere_id).");
            }
        }
        
        error_log("Matière trouvée: " . $matiere['nom'] . " (ID: " . $matiere['id'] . ", Classe ID: " . ($matiere['classe_id'] ?? 'toutes') . ")");
        
        // Vérifier que le professeur a le droit d'enseigner cette matière
        $query = "SELECT e.*, m.nom as matiere_nom 
                 FROM enseignements e
                 JOIN matieres m ON e.matiere_id = m.id
                 WHERE e.professeur_id = ? 
                 AND e.matiere_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$professeur_id, $matiere_id]);
        $enseignement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier que la classe existe
        $query = "SELECT id, nom FROM classes WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$classe_id]);
        $classe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$classe) {
            throw new Exception("La classe spécifiée n'existe pas (ID: $classe_id).");
        }
        
        if (!$enseignement) {
            error_log("ERREUR: Le professeur (ID: $professeur_id) n'est pas autorisé à enseigner la matière (ID: $matiere_id)");
            throw new Exception("Vous n'êtes pas autorisé à saisir des notes pour cette matière (ID: $matiere_id).");
        }
        
        error_log("Enseignement trouvé: " . print_r($enseignement, true));
        
        foreach ($_POST['notes'] as $eleveId => $noteData) {
            // Vérifier que l'élève existe et appartient bien à la classe
            $query = "SELECT 1 FROM eleves WHERE id = ? AND classe_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$eleveId, $classe_id]);
            
            if (!$stmt->fetch()) {
                error_log("Tentative d'ajout de note pour un élève inexistant ou ne faisant pas partie de la classe: $eleveId");
                continue; // Passe à l'élève suivant
            }
            
            // Préparer les données des notes avec validation
            $interro1 = !empty($noteData['interro1']) ? (float)str_replace(',', '.', $noteData['interro1']) : null;
            $interro2 = !empty($noteData['interro2']) ? (float)str_replace(',', '.', $noteData['interro2']) : null;
            $devoir = !empty($noteData['devoir']) ? (float)str_replace(',', '.', $noteData['devoir']) : null;
            $compo = !empty($noteData['compo']) ? (float)str_replace(',', '.', $noteData['compo']) : null;
            
            // Valider les notes (entre 0 et 20)
            $notes = ['interro1' => $interro1, 'interro2' => $interro2, 'devoir' => $devoir, 'compo' => $compo];
            foreach ($notes as $type => $note) {
                if ($note !== null && ($note < 0 || $note > 20)) {
                    throw new Exception("La note de $type doit être comprise entre 0 et 20.");
                }
            }
            
            // Vérifier si une note existe déjà pour cet élève, cette matière, cette classe et ce semestre
            $query = "SELECT id, interro1, interro2, devoir, compo FROM notes 
                     WHERE eleve_id = ? 
                     AND matiere_id = ? 
                     AND classe_id = ? 
                     AND semestre = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$eleveId, $matiere_id, $classe_id, $semestre]);
            $noteExistante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log des données avant l'insertion/mise à jour
            error_log("Données de note pour élève $eleveId - Interro1: $interro1, Interro2: $interro2, Devoir: $devoir, Compo: $compo");
            
            if ($noteExistante) {
                error_log("Note existante trouvée (ID: " . $noteExistante['id'] . ") - Anciennes valeurs: " . 
                         "Interro1: " . $noteExistante['interro1'] . ", " .
                         "Interro2: " . $noteExistante['interro2'] . ", " .
                         "Devoir: " . $noteExistante['devoir'] . ", " .
                         "Compo: " . $noteExistante['compo']);
                
                // Mise à jour de la note existante
                $query = "UPDATE notes 
                         SET interro1 = ?, 
                             interro2 = ?, 
                             devoir = ?, 
                             compo = ?,
                             saisie_par = ?,
                             derniere_maj = ?
                         WHERE id = ?";
                $stmt = $db->prepare($query);
                $params = [
                    $interro1, 
                    $interro2, 
                    $devoir, 
                    $compo, 
                    $professeur_id,
                    $now,
                    $noteExistante['id']
                ];
                
                error_log("Exécution de la requête UPDATE avec les paramètres: " . print_r($params, true));
                
                $stmt->execute($params);
                error_log("Note mise à jour pour l'élève $eleveId (Note ID: " . $noteExistante['id'] . ")");
            } else {
                // Vérifier que l'élève existe dans la classe avant d'insérer
                $queryCheckEleve = "SELECT id FROM eleves WHERE id = ? AND classe_id = ?";
                $stmtCheck = $db->prepare($queryCheckEleve);
                $stmtCheck->execute([$eleveId, $classe_id]);
                
                if (!$stmtCheck->fetch()) {
                    throw new Exception("L'élève (ID: $eleveId) n'existe pas ou n'appartient pas à la classe (ID: $classe_id)");
                }
                
                // Vérifier si une note existe déjà pour cet élève, cette matière, cette classe et ce semestre
                $query = "SELECT id FROM notes 
                         WHERE eleve_id = ? 
                         AND matiere_id = ? 
                         AND classe_id = ? 
                         AND semestre = ?";
                $stmtCheck = $db->prepare($query);
                $stmtCheck->execute([$eleveId, $matiere_id, $classe_id, $semestre]);
                $existingNote = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                if ($existingNote) {
                    // Mise à jour de la note existante
                    $query = "UPDATE notes 
                             SET interro1 = ?, 
                                 interro2 = ?, 
                                 devoir = ?, 
                                 compo = ?,
                                 saisie_par = ?,
                                 derniere_maj = ?
                             WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $params = [
                        $interro1, 
                        $interro2, 
                        $devoir, 
                        $compo, 
                        $professeur_id,
                        $now,
                        $existingNote['id']
                    ];
                    
                    throw new Exception("La matière avec l'ID $matiere_id n'est pas disponible pour la classe $classe_id (elle est liée à la classe $matiereClasseId).");
                }
                
                // Insertion d'une nouvelle note
                $query = "INSERT INTO notes 
                         (eleve_id, matiere_id, classe_id, interro1, interro2, devoir, compo, semestre, saisie_par, date_saisie) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $params = [
                    $eleveId, 
                    $matiere_id, 
                    $classe_id, 
                    $interro1, 
                    $interro2, 
                    $devoir, 
                    $compo, 
                    $semestre, 
                    $professeur_id,
                    $now
                ];
                
                error_log("Exécution de la requête INSERT avec les paramètres: " . print_r($params, true));
                
                try {
                    $stmt->execute($params);
                    $newNoteId = $db->lastInsertId();
                    error_log("Nouvelle note insérée pour l'élève $eleveId (ID de la note: $newNoteId)");
                } catch (PDOException $e) {
                    $errorInfo = $e->errorInfo;
                    error_log("Erreur PDO lors de l'insertion : " . $e->getMessage());
                    error_log("Code d'erreur : " . $e->getCode());
                    error_log("Info d'erreur : " . print_r($errorInfo, true));
                    
                    if ($e->getCode() == '23000') {
                        // Duplicate entry error, try to update instead
                        error_log("Erreur d'insertion, tentative de mise à jour: " . $e->getMessage());
                        
                        $query = "UPDATE notes 
                                 SET interro1 = ?, 
                                     interro2 = ?, 
                                     devoir = ?, 
                                     compo = ?,
                                     saisie_par = ?,
                                     derniere_maj = ?
                                 WHERE eleve_id = ? 
                                 AND matiere_id = ? 
                                 AND classe_id = ? 
                                 AND semestre = ?";
                        $stmt = $db->prepare($query);
                        $params = [
                            $interro1, 
                            $interro2, 
                            $devoir, 
                            $compo, 
                            $professeur_id,
                            $now,
                            $eleveId,
                            $matiere_id,
                            $classe_id,
                            $semestre
                        ];
                        
                        try {
                            $stmt->execute($params);
                            error_log("Note mise à jour après erreur d'insertion pour l'élève $eleveId");
                        } catch (PDOException $updateEx) {
                            error_log("Erreur lors de la mise à jour : " . $updateEx->getMessage());
                            throw new Exception("Erreur lors de la mise à jour de la note : " . $updateEx->getMessage());
                        }
                    } else {
                        throw new Exception("Erreur lors de l'insertion de la note : " . $e->getMessage());
                    }
                }
            }
        }
        
        $db->commit();
        $_SESSION['success'] = "Les notes ont été enregistrées avec succès.";
        
        // Recharger les données pour afficher les mises à jour
        $query = "SELECT 
                    e.id, 
                    e.nom, 
                    e.prenom, 
                    n.interro1, 
                    n.interro2, 
                    n.devoir, 
                    n.compo, 
                    n.id as note_id,
                    n.semestre,
                    n.derniere_maj,
                    u.nom as modifie_par_nom,
                    u.prenom as modifie_par_prenom
                  FROM eleves e
                  LEFT JOIN notes n ON e.id = n.eleve_id 
                      AND n.matiere_id = ? 
                      AND n.classe_id = ?
                      AND n.semestre = ?
                  LEFT JOIN utilisateurs u ON n.saisie_par = u.id
                  WHERE e.classe_id = ?
                  ORDER BY e.nom, e.prenom";
        $stmt = $db->prepare($query);
        $stmt->execute([$matiere_id, $classe_id, $semestre, $classe_id]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Nombre d'élèves chargés : " . count($eleves));
        
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        // Log detailed error information
        $errorInfo = $e->errorInfo;
        $errorMessage = "Erreur PDO [" . $e->getCode() . "]: " . $e->getMessage() . "\n";
        $errorMessage .= "SQL State: " . $errorInfo[0] . "\n";
        $errorMessage .= "Driver Error Code: " . $errorInfo[1] . "\n";
        $errorMessage .= "Driver Error Message: " . $errorInfo[2] . "\n";
        
        // Log the complete error
        error_log("Erreur PDO lors de l'enregistrement des notes :\n$errorMessage");
        error_log("Trace : " . $e->getTraceAsString());
        
        // User-friendly error message
        if ($e->getCode() == '23000') {
            // Foreign key constraint violation
            if (strpos($e->getMessage(), 'notes_ibfk_2') !== false) {
                $_SESSION['error'] = "Erreur de référence : La matière spécifiée n'existe pas ou n'est pas accessible.";
            } else if (strpos($e->getMessage(), 'notes_ibfk_1') !== false) {
                $_SESSION['error'] = "Erreur de référence : L'élève spécifié n'existe pas.";
            } else if (strpos($e->getMessage(), 'notes_ibfk_3') !== false) {
                $_SESSION['error'] = "Erreur de référence : La classe spécifiée n'existe pas.";
            } else {
                $_SESSION['error'] = "Erreur de contrainte d'intégrité : " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement des notes : " . $e->getMessage();
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Erreur lors de l'enregistrement des notes : " . $e->getMessage());
        error_log("Trace : " . $e->getTraceAsString());
        $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
    }
}
?>

<?php 
$page_title = 'Saisie des notes - ' . htmlspecialchars($matiereClasse['matiere_nom']);
require_once __DIR__ . '/includes/header.php'; 
?>

<div class="container-fluid py-4">
    <div class="card shadow-lg border-0 rounded-lg">
        <div class="card-header bg-primary p-3 d-flex flex-row align-items-center justify-content-between">
            <div>
                <h2 class="h4 font-weight-bold mb-0">
                    <i class="fas fa-edit me-2"></i>Saisie des notes
                </h2>
                <p class="mb-0 mt-1 text-white-50">
                    <?php 
                    echo htmlspecialchars(
                        $matiereClasse['matiere_nom'] . ' - ' . 
                        $matiereClasse['classe_nom'] . ' ' . 
                        $matiereClasse['niveau'] . 
                        ' (Coefficient: ' . $matiereClasse['coefficient'] . ')'
                    ); 
                    ?>
                </p>
            </div>
            <div class="d-none d-md-block">
                <span class="badge bg-white text-primary fs-6 fw-normal">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <?php echo date('d/m/Y'); ?>
                </span>
            </div>
        </div>
        
        <div class="card-body p-4">

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="mb-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="semestre" class="form-label fw-bold text-primary mb-1">
                                    <i class="fas fa-calendar-week me-2"></i>Période de notation :
                                </label>
                                <div class="d-flex align-items-center">
                                    <select name="semestre" id="semestre" class="form-select form-select-sm" style="max-width: 200px;">
                                        <option value="1" <?php echo ($semestre_actif == 1) ? 'selected' : ''; ?>>1er Semestre</option>
                                        <option value="2" <?php echo ($semestre_actif == 2) ? 'selected' : ''; ?>>2ème Semestre</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-sync-alt me-1"></i> Actualiser
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Période active : 
                                    <span class="fw-bold"><?php echo $periode ? htmlspecialchars($periode['nom']) : 'Non définie'; ?></span>
                                    (<?php echo $periode ? date('d/m/Y', strtotime($periode['date_debut'])) . ' - ' . date('d/m/Y', strtotime($periode['date_fin'])) : ''; ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                    
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase small">
                                    <th class="ps-4" style="min-width: 200px;">Élève</th>
                                    <th class="text-center" style="width: 120px;">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>Interro 1</span>
                                            <small class="text-muted fw-normal">/20</small>
                                        </div>
                                    </th>
                                    <th class="text-center" style="width: 120px;">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>Interro 2</span>
                                            <small class="text-muted fw-normal">/20</small>
                                        </div>
                                    </th>
                                    <th class="text-center" style="width: 120px;">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>Devoir</span>
                                            <small class="text-muted fw-normal">/20</small>
                                        </div>
                                    </th>
                                    <th class="text-center" style="width: 120px;">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>Compo</span>
                                            <small class="text-muted fw-normal">/20</small>
                                        </div>
                                    </th>
                                    <th class="text-center" style="width: 120px;">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>Moyenne</span>
                                            <small class="text-muted fw-normal">/20</small>
                                        </div>
                                    </th>
                                    <th class="text-center" style="min-width: 180px;">
                                        <i class="far fa-clock me-1"></i> Dernière mise à jour
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $currentSemestre = $semestre_actif;
                                foreach ($eleves as $eleve): 
                                    // Calculer la moyenne (pondérée si nécessaire)
                                    $notes = [
                                        'interro1' => $eleve['interro1'] !== null ? (float)$eleve['interro1'] : null,
                                        'interro2' => $eleve['interro2'] !== null ? (float)$eleve['interro2'] : null,
                                        'devoir' => $eleve['devoir'] !== null ? (float)$eleve['devoir'] : null,
                                        'compo' => $eleve['compo'] !== null ? (float)$eleve['compo'] : null
                                    ];
                                    
                                    // Calcul de la moyenne (vous pouvez ajuster la pondération si nécessaire)
                                    $moyenne = null;
                                    $notes_valides = array_filter($notes, function($n) { return $n !== null; });
                                    
                                    if (!empty($notes_valides)) {
                                        // Moyenne simple pour l'instant, à adapter selon vos besoins
                                        $moyenne = array_sum($notes_valides) / count($notes_valides);
                                    }
                                    
                                    // Formatage de la date de dernière mise à jour
                                    $derniere_maj = '';
                                    if (!empty($eleve['derniere_maj'])) {
                                        $date = new DateTime($eleve['derniere_maj']);
                                        $derniere_maj = $date->format('d/m/Y H:i');
                                        if (!empty($eleve['prof_nom'])) {
                                            $derniere_maj .= ' par ' . htmlspecialchars($eleve['prof_prenom'] . ' ' . $eleve['prof_nom']);
                                        }
                                    }
                                ?>
                                    <tr class="position-relative">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <?php 
                                        $types = [
                                            'interro1' => ['icon' => 'pencil-alt', 'color' => 'info'],
                                            'interro2' => ['icon' => 'pencil-alt', 'color' => 'info'],
                                            'devoir' => ['icon' => 'tasks', 'color' => 'warning'],
                                            'compo' => ['icon' => 'file-alt', 'color' => 'danger']
                                        ];
                                        
                                        foreach ($types as $type => $info): 
                                            $value = $eleve[$type] !== null ? number_format($eleve[$type], 2, ',', ' ') : '';
                                            $inputId = 'note_' . $eleve['id'] . '_' . $type;
                                        ?>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-<?php echo $info['color']; ?>-subtle text-<?php echo $info['color']; ?> border-0" style="min-width: 40px;">
                                                        <i class="fas fa-<?php echo $info['icon']; ?> fa-fw"></i>
                                                    </span>
                                                    <input type="text" 
                                                           id="<?php echo $inputId; ?>"
                                                           class="form-control note-input text-center border-0 border-start" 
                                                           name="notes[<?php echo $eleve['id']; ?>][<?php echo $type; ?>]"
                                                           value="<?php echo htmlspecialchars($value); ?>"
                                                           placeholder="0-20"
                                                           pattern="^\d{1,2}([.,]\d{0,2})?$"
                                                           title="Entrez une note entre 0 et 20"
                                                           style="background-color: #f8f9fa;">
                                                </div>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="moyenne-cell">
                                            <?php 
                                            if ($moyenne !== null) {
                                                $colorClass = '';
                                                if ($moyenne < 10) $colorClass = 'text-danger';
                                                elseif ($moyenne < 12) $colorClass = 'text-warning';
                                                else $colorClass = 'text-success';
                                                
                                                echo '<div class="d-flex align-items-center justify-content-center">';
                                                echo '<span class="fw-bold ' . $colorClass . '" style="color: inherit !important;">' . number_format($moyenne, 2, ',', ' ') . '</span>';
                                                echo '</div>';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?php if ($derniere_maj): ?>
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <i class="far fa-clock me-1"></i>
                                                    <span><?php echo $derniere_maj; ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted">Jamais modifié</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <div class="card-footer bg-transparent border-top py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div class="mb-2 mb-md-0">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-1"></i> Réinitialiser
                            </button>
                            <button type="submit" class="btn btn-primary px-4 rounded">
                                <i class="fas fa-save me-1"></i> Enregistrer les notes
                            </button>
                        </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Amélioration du contraste des champs de formulaire */
    .form-control, .form-select {
        background-color: #fff;
        border: 1px solid #dee2e6;
        color: #212529;
    }
    
    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        border-color: #86b7fe;
        background-color: #fff;
    }
    
    .note-input {
        font-weight: 500;
        transition: all 0.2s;
        background-color: #fff !important;
        color: #212529 !important;
    }
    
    .note-input:focus {
        transform: translateY(-1px);
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        background-color: #fff;
    }
    
    /* Amélioration du contraste du tableau */
    .table {
        --bs-table-bg: #fff;
        --bs-table-color: #212529;
    }
    
    .table-hover > tbody > tr:hover {
        --bs-table-hover-bg: rgba(13, 110, 253, 0.05);
    }
    
    .table > :not(:first-child) {
        border-top: 1px solid #dee2e6;
    }
    
    .table > thead > tr > th {
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
    }
    
    /* Amélioration des avatars */
    .avatar-sm {
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #e9ecef !important;
        color: #495057 !important;
    }
    
    /* Amélioration des couleurs de fond des badges */
    .bg-primary-subtle {
        background-color: #e7f1ff !important;
        color: #084298 !important;
    }
    
    .bg-info-subtle {
        background-color: #e2f3f8 !important;
        color: #055160 !important;
    }
    
    .bg-warning-subtle {
        background-color: #fff3cd !important;
        color: #664d03 !important;
    }
    
    .bg-danger-subtle {
        background-color: #f8d7da !important;
        color: #842029 !important;
    }
    
    /* Amélioration des couleurs de texte */
    .text-info {
        color: #0dcaf0 !important;
    }
    
    .text-warning {
        color: #ffc107 !important;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .text-success {
        color: #198754 !important;
    }
    
    /* Amélioration des boutons */
    .btn-outline-primary {
        color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-outline-primary:hover {
        color: #fff;
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }
    
    /* Amélioration des alertes */
    .alert {
        border: 1px solid transparent;
    }
    
    .alert-success {
        background-color: #d1e7dd;
        color: #0f5132;
        border-color: #badbcc;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #842029;
        border-color: #f5c2c7;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const noteInputs = document.querySelectorAll('.note-input');
        
        // Fonction pour calculer la moyenne d'une ligne
        function calculerMoyenne(row) {
            const inputs = row.querySelectorAll('.note-input');
            let somme = 0;
            let nbNotes = 0;
            
            inputs.forEach(input => {
                const valeur = parseFloat(input.value.replace(',', '.'));
                if (!isNaN(valeur) && input.value.trim() !== '') {
                    somme += valeur;
                    nbNotes++;
                }
            });
            
            return nbNotes > 0 ? (somme / nbNotes).toFixed(2) : '-';
        }
        
        // Mettre à jour toutes les moyennes
        function mettreAJourMoyennes() {
            document.querySelectorAll('tbody tr').forEach(row => {
                const moyenneCell = row.querySelector('.moyenne-cell');
                if (moyenneCell) {
                    const moyenne = calculerMoyenne(row);
                    moyenneCell.textContent = moyenne !== '-' ? moyenne.replace('.', ',') : '-';
                }
            });
        }
        
        // Ajouter un gestionnaire d'événements à chaque champ de note
        noteInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                // Remplacer les virgules par des points pour la validation
                this.value = this.value.replace(',', '.');
                
                // Validation de la note entre 0 et 20
                if (this.value && (parseFloat(this.value) < 0 || parseFloat(this.value) > 20)) {
                    this.setCustomValidity('La note doit être comprise entre 0 et 20');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    
                    // Mettre à jour la moyenne en temps réel
                    mettreAJourMoyennes();
                }
            });
            
            // Validation au changement de focus
            input.addEventListener('change', function() {
                if (this.value) {
                    // Arrondir à 2 décimales
                    const valeur = parseFloat(this.value.replace(',', '.'));
                    if (!isNaN(valeur)) {
                        this.value = valeur.toFixed(2).replace('.', ',');
                    }
                }
            });
        });
        
        // Initialiser les moyennes au chargement
        mettreAJourMoyennes();
    });
</script>

<style>
    .note-input {
        max-width: 100px;
        margin: 0 auto;
    }
    .moyenne-cell {
        font-weight: 700;
        background-color: #f8f9fa;
        text-align: center;
        vertical-align: middle;
        font-size: 1.1em;
    }
    .table th {
        vertical-align: middle;
        text-align: center;
    }
    .table td {
        vertical-align: middle;
    }
</style>
</body>
</html>
