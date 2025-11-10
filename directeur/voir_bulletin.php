<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le titre de la page
$pageTitle = 'Détails du bulletin';

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialiser la variable d'erreur
$_SESSION['error'] = $_SESSION['error'] ?? '';

// Vérifier les paramètres
$eleve_id = isset($_GET['eleve_id']) ? (int)$_GET['eleve_id'] : 0;
$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;

if ($eleve_id <= 0 || $classe_id <= 0 || $periode_id <= 0) {
    $_SESSION['error'] = "Paramètres manquants ou invalides.";
    header('Location: bulletins.php');
    exit();
}

try {
    // Initialiser la connexion à la base de données
    $db = new PDO("mysql:host=localhost;dbname=isset_tsevie", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les informations de l'élève
    $stmt = $db->prepare("SELECT * FROM eleves WHERE id = ? AND classe_id = ?");
    $stmt->execute([$eleve_id, $classe_id]);
    $eleve = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$eleve) {
        throw new Exception("Élève non trouvé dans cette classe.");
    }

    // Récupérer les informations de la classe
    $stmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$classe_id]);
    $classe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$classe) {
        throw new Exception("Classe non trouvée.");
    }

    // Récupérer les informations de la période
    $stmt = $db->prepare("SELECT * FROM periodes WHERE id = ?");
    $stmt->execute([$periode_id]);
    $periode = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$periode) {
        throw new Exception("Période non trouvée.");
    }

    // Déterminer le semestre
    $semestre = 1;
    if (preg_match('/2|deuxi[eè]me|second/i', $periode['nom'])) {
        $semestre = 2;
    }

    // Récupérer les notes de l'élève pour la période
    $query = "
        SELECT 
            n.*, 
            m.nom as matiere_nom,
            m.coefficient,
            (COALESCE(n.interro1, 0) + COALESCE(n.interro2, 0) + COALESCE(n.devoir, 0) + (COALESCE(n.compo, 0) * 2)) / 
            NULLIF((CASE WHEN n.interro1 IS NOT NULL THEN 1 ELSE 0 END + 
                   CASE WHEN n.interro2 IS NOT NULL THEN 1 ELSE 0 END + 
                   CASE WHEN n.devoir IS NOT NULL THEN 1 ELSE 0 END + 
                   CASE WHEN n.compo IS NOT NULL THEN 2 ELSE 0 END), 0) as moyenne,
            a.contenu as appreciation
        FROM notes n
        JOIN matieres m ON n.matiere_id = m.id
        LEFT JOIN appreciations a ON a.eleve_id = n.eleve_id 
            AND a.matiere_id = n.matiere_id 
            AND a.classe_id = n.classe_id 
            AND a.semestre = n.semestre
        WHERE n.eleve_id = ? 
        AND n.classe_id = ?
        AND n.semestre = ?
        ORDER BY m.nom
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$eleve_id, $classe_id, $semestre]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Préparer les données pour le template
    $bulletins = [];
    $moyenne_generale = 0;
    $total_coeff = 0;
    $has_notes = false;

    foreach ($notes as $note) {
        $moyenne = $note['moyenne'] ?? 0;
        
        $bulletins[$note['matiere_id']] = [
            'matiere_nom' => $note['matiere_nom'],
            'coefficient' => $note['coefficient'],
            'interro1' => $note['interro1'] ?? '-',
            'interro2' => $note['interro2'] ?? '-',
            'devoir' => $note['devoir'] ?? '-',
            'compo' => $note['compo'] ?? '-',
            'moyenne' => $moyenne > 0 ? number_format($moyenne, 2, ',', ' ') : '-',
            'appreciation' => $note['appreciation'] ?? ''
        ];

        if ($moyenne > 0) {
            $moyenne_generale += $moyenne * $note['coefficient'];
            $total_coeff += $note['coefficient'];
            $has_notes = true;
        }
    }

    // Calculer la moyenne générale
    $moyenne_generale = $has_notes && $total_coeff > 0 ? $moyenne_generale / $total_coeff : 0;

    // Récupérer l'appréciation générale
    $appreciation = '';
    $stmt = $db->prepare("
        SELECT contenu 
        FROM appreciations 
        WHERE eleve_id = ? 
        AND classe_id = ? 
        AND matiere_id IS NULL 
        AND semestre = ?
    ");
    $stmt->execute([$eleve_id, $classe_id, $semestre]);
    $appreciation_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $appreciation = $appreciation_data ? $appreciation_data['contenu'] : '';

    // Récupérer l'effectif de la classe pour le rang
    $stmt = $db->prepare("SELECT COUNT(*) as effectif FROM eleves WHERE classe_id = ?");
    $stmt->execute([$classe_id]);
    $effectif = $stmt->fetch(PDO::FETCH_ASSOC)['effectif'];

    // Calculer le rang (simplifié)
    $rang = 'N/A';
    if ($has_notes) {
        $stmt = $db->prepare("
            SELECT COUNT(*) + 1 as rang
            FROM (
                SELECT e.id, 
                       AVG((COALESCE(n.interro1, 0) + COALESCE(n.interro2, 0) + COALESCE(n.devoir, 0) + (COALESCE(n.compo, 0) * 2)) / 
                           NULLIF((CASE WHEN n.interro1 IS NOT NULL THEN 1 ELSE 0 END + 
                                  CASE WHEN n.interro2 IS NOT NULL THEN 1 ELSE 0 END + 
                                  CASE WHEN n.devoir IS NOT NULL THEN 1 ELSE 0 END + 
                                  CASE WHEN n.compo IS NOT NULL THEN 2 ELSE 0 END), 0)) as moyenne
                FROM eleves e
                JOIN notes n ON n.eleve_id = e.id
                WHERE e.classe_id = ? AND n.semestre = ?
                GROUP BY e.id
                HAVING AVG((COALESCE(n.interro1, 0) + COALESCE(n.interro2, 0) + COALESCE(n.devoir, 0) + (COALESCE(n.compo, 0) * 2)) / 
                          NULLIF((CASE WHEN n.interro1 IS NOT NULL THEN 1 ELSE 0 END + 
                                 CASE WHEN n.interro2 IS NOT NULL THEN 1 ELSE 0 END + 
                                 CASE WHEN n.devoir IS NOT NULL THEN 1 ELSE 0 END + 
                                 CASE WHEN n.compo IS NOT NULL THEN 2 ELSE 0 END), 0)) > ?
            ) as t
        
        ");
        $stmt->execute([$classe_id, $semestre, $moyenne_generale]);
        $rang_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $rang = $rang_data ? $rang_data['rang'] : 1;
    }

    // Inclure le header après avoir traité toutes les données
    include __DIR__ . '/includes/header.php';

    // Inclure le template
    include __DIR__ . '/templates/bulletin_template.php';

} catch (Exception $e) {
    // En cas d'erreur, rediriger avec un message d'erreur
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: bulletins.php');
    exit();
}

// Inclure le footer
include __DIR__ . '/includes/footer.php';
?>
