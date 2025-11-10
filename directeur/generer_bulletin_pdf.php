<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Vérifier les paramètres
$eleve_id = isset($_GET['eleve_id']) ? (int)$_GET['eleve_id'] : 0;
$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;

if ($eleve_id <= 0 || $classe_id <= 0 || $periode_id <= 0) {
    $_SESSION['error'] = "Paramètres manquants ou invalides.";
    header('Location: bulletins.php');
    exit;
}

try {
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
            (n.interro1 + n.interro2 + n.devoir + (n.compo * 2)) / 5 as moyenne,
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
        $moyenne = ($note['interro1'] + $note['interro2'] + $note['devoir'] + ($note['compo'] * 2)) / 5;
        $moyenne = round($moyenne, 2);
        
        $bulletins[$note['matiere_id']] = [
            'matiere_nom' => $note['matiere_nom'],
            'coefficient' => $note['coefficient'],
            'interro1' => $note['interro1'],
            'interro2' => $note['interro2'],
            'devoir' => $note['devoir'],
            'compo' => $note['compo'],
            'moyenne' => $moyenne,
            'appreciation' => $note['appreciation'] ?? ''
        ];

        if ($moyenne > 0) {
            $moyenne_generale += $moyenne * $note['coefficient'];
            $total_coeff += $note['coefficient'];
            $has_notes = true;
        }
    }

    // Calculer la moyenne générale
    $moyenne_generale = $has_notes ? $moyenne_generale / $total_coeff : 0;

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
                SELECT e.id, AVG((n.interro1 + n.interro2 + n.devoir + (n.compo * 2)) / 5) as moyenne
                FROM eleves e
                JOIN notes n ON n.eleve_id = e.id
                WHERE e.classe_id = ? AND n.semestre = ?
                GROUP BY e.id
                HAVING AVG((n.interro1 + n.interro2 + n.devoir + (n.compo * 2)) / 5) > ?
            ) as t
        ");
        $stmt->execute([$classe_id, $semestre, $moyenne_generale]);
        $rang_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $rang = $rang_data ? $rang_data['rang'] : 1;
    }

    // Inclure la bibliothèque TCPDF
    require_once(__DIR__ . '/../tcpdf/tcpdf.php');

    // Créer un nouveau document PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Définir les informations du document
    $pdf->SetCreator('Système de Gestion Scolaire');
    $pdf->SetAuthor('Établissement Scolaire');
    $pdf->SetTitle('Bulletin de notes - ' . $eleve['nom'] . ' ' . $eleve['prenom']);
    $pdf->SetSubject('Bulletin de notes');

    // Supprimer le header et le footer par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Ajouter une page
    $pdf->AddPage();

    // Définir la police
    $pdf->SetFont('helvetica', '', 10);

    // En-tête du bulletin
    $header = '\n\n';
    $header .= '<h1 style="text-align:center;font-size:16px;font-weight:bold;">BULLETIN DE NOTES</h1>';
    $header .= '<p style="text-align:center;font-size:12px;">' . htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']) . ' - ' . htmlspecialchars($periode['nom']) . '</p>';
    $header .= '<p style="text-align:center;font-size:12px;">Année Scolaire ' . htmlspecialchars($periode['annee_scolaire']) . '</p>';
    $pdf->writeHTML($header, true, false, true, false, '');

    // Informations de l'élève
    $info_eleve = '\n';
    $info_eleve .= '<table cellspacing="0" cellpadding="5" border="0">';
    $info_eleve .= '<tr><td width="50%" style="font-weight:bold;">Nom et prénom :</td><td>' . htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) . '</td></tr>';
    $info_eleve .= '<tr><td style="font-weight:bold;">Date de naissance :</td><td>' . (!empty($eleve['date_naissance']) ? date('d/m/Y', strtotime($eleve['date_naissance'])) : '-') . '</td></tr>';
    $info_eleve .= '<tr><td style="font-weight:bold;">Classe :</td><td>' . htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']) . '</td></tr>';
    $info_eleve .= '<tr><td style="font-weight:bold;">Effectif :</td><td>' . $effectif . ' élèves</td></tr>';
    $info_eleve .= '</table>';
    $pdf->writeHTML($info_eleve, true, false, true, false, '');

    // Tableau des notes
    $tbl = '\n\n';
    $tbl .= '<table border="1" cellpadding="4">';
    $tbl .= '<thead><tr style="background-color:#f2f2f2;font-weight:bold;text-align:center;">
                <th width="30%">Matières</th>
                <th width="7%">Coef</th>
                <th width="7%">I1</th>
                <th width="7%">I2</th>
                <th width="10%">Devoir</th>
                <th width="10%">Compo</th>
                <th width="10%">Moyenne</th>
                <th>Appréciation</th>
            </tr></thead><tbody>';

    foreach ($bulletins as $matiere) {
        $tbl .= '<tr>';
        $tbl .= '<td>' . htmlspecialchars($matiere['matiere_nom']) . '</td>';
        $tbl .= '<td style="text-align:center;">' . $matiere['coefficient'] . '</td>';
        $tbl .= '<td style="text-align:center;">' . ($matiere['interro1'] ?? '-') . '</td>';
        $tbl .= '<td style="text-align:center;">' . ($matiere['interro2'] ?? '-') . '</td>';
        $tbl .= '<td style="text-align:center;">' . ($matiere['devoir'] ?? '-') . '</td>';
        $tbl .= '<td style="text-align:center;">' . ($matiere['compo'] ?? '-') . '</td>';
        $tbl .= '<td style="text-align:center;font-weight:bold;' . (($matiere['moyenne'] ?? 0) >= 10 ? 'color:green;' : 'color:red;') . '">' . ($matiere['moyenne'] ?? '-') . '</td>';
        $tbl .= '<td style="font-size:9px;">' . nl2br(htmlspecialchars($matiere['appreciation'] ?? '')) . '</td>';
        $tbl .= '</tr>';
    }

    $tbl .= '</tbody></table>';
    $pdf->writeHTML($tbl, true, false, false, false, '');

    // Résultats et appréciation
    $results = '\n\n';
    $results .= '<table cellspacing="0" cellpadding="5" border="0">';
    $results .= '<tr>';
    $results .= '<td width="50%" valign="top">';
    $results .= '<h4 style="font-weight:bold;">Résultats</h4>';
    $results .= '<p>Moyenne générale : <span style="font-weight:bold;' . ($moyenne_generale >= 10 ? 'color:green;' : 'color:red;') . '">' . number_format($moyenne_generale, 2, ',', ' ') . ' / 20</span></p>';
    $results .= '<p>Rang : ' . $rang . ' / ' . $effectif . '</p>';
    $results .= '</td>';
    $results .= '<td width="50%" valign="top">';
    $results .= '<h4 style="font-weight:bold;">Appréciation générale</h4>';
    $results .= '<p>' . nl2br(htmlspecialchars($appreciation ?: 'Aucune appréciation')) . '</p>';
    $results .= '</td>';
    $results .= '</tr>';
    $results .= '</table>';
    $pdf->writeHTML($results, true, false, true, false, '');

    // Signature
    $signature = '\n\n\n\n\n\n';
    $signature .= '<table cellspacing="0" cellpadding="5" border="0">';
    $signature .= '<tr>';
    $signature .= '<td width="50%" style="text-align:center;">Le Directeur</td>';
    $signature .= '<td width="50%" style="text-align:center;">Le Professeur Principal</td>';
    $signature .= '</tr>';
    $signature .= '<tr>';
    $signature .= '<td style="text-align:center;">
                      <div style="margin-top:40px;">
                          <div style="border-top:1px solid #000;width:150px;margin:0 auto;"></div>
                      </div>
                   </td>';
    $signature .= '<td style="text-align:center;">
                      <div style="margin-top:40px;">
                          <div style="border-top:1px solid #000;width:150px;margin:0 auto;"></div>
                      </div>
                   </td>';
    $signature .= '</tr>';
    $signature .= '<tr>';
    $signature .= '<td colspan="2" style="text-align:center;font-size:10px;padding-top:20px;">
                      Fait à [Ville], le ' . date('d/m/Y') . '
                   </td>';
    $signature .= '</tr>';
    $signature .= '</table>';
    $pdf->writeHTML($signature, true, false, true, false, '');

    // Nom du fichier PDF
    $filename = 'Bulletin_' . $eleve['nom'] . '_' . $eleve['prenom'] . '_' . str_replace(' ', '_', $periode['nom']) . '.pdf';

    // Télécharger le PDF
    $pdf->Output($filename, 'D');

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la génération du PDF : " . $e->getMessage();
    header('Location: bulletins.php');
    exit;
}
?>
