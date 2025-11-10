<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Chemin vers l'autoloader de Composer
    $autoloadPath = 'C:/wamp64/www/isset/vendor/autoload.php';
    
    if (!file_exists($autoloadPath)) {
        throw new Exception("L'autoloader de Composer est introuvable à l'emplacement : " . $autoloadPath);
    }
    
    require $autoloadPath;
    
    // Inclure les fichiers nécessaires
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    
    // Vérifier les paramètres requis
    $required = ['eleve_id', 'classe_id', 'periode_id'];
    foreach ($required as $param) {
        if (!isset($_GET[$param]) || empty($_GET[$param])) {
            throw new Exception("Paramètre manquant : $param");
        }
    }
    
    $eleve_id = (int)$_GET['eleve_id'];
    $classe_id = (int)$_GET['classe_id'];
    $periode_id = (int)$_GET['periode_id'];
    
    // Capturer la sortie du bulletin
    ob_start();
    include __DIR__ . '/voir_bulletin.php';
    $html = ob_get_clean();
    
    if (empty($html)) {
        throw new Exception("Impossible de générer le contenu du bulletin");
    }
    
    // Créer une instance de TCPDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Configuration du document
    $pdf->SetCreator('Groupe Scolaire ISSET');
    $pdf->SetAuthor('Groupe Scolaire ISSET');
    $pdf->SetTitle('Bulletin de notes');
    $pdf->SetSubject('Bulletin de notes');
    
    // Désactiver l'en-tête et le pied de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Ajouter une page
    $pdf->AddPage();
    
    // Écrire le contenu HTML
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Définir le nom du fichier
    $filename = 'bulletin_eleve_' . $eleve_id . '_periode_' . $periode_id . '.pdf';
    $filepath = __DIR__ . '/../../bulletins/' . $filename;
    
    // Créer le dossier bulletins s'il n'existe pas
    $bulletinDir = dirname($filepath);
    if (!file_exists($bulletinDir)) {
        if (!mkdir($bulletinDir, 0777, true)) {
            throw new Exception("Impossible de créer le répertoire : " . $bulletinDir);
        }
    }
    
    // Sauvegarder le fichier PDF
    $pdf->Output($filepath, 'F');
    
    // Vérifier si le fichier a été créé
    if (!file_exists($filepath)) {
        throw new Exception("Échec de la création du fichier PDF");
    }
    
    // Envoyer le fichier au navigateur
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filepath));
    
    readfile($filepath);
    
    // Supprimer le fichier après envoi (optionnel)
    // unlink($filepath);
    
    exit;
    
} catch (Exception $e) {
    // En cas d'erreur, afficher un message clair
    die("Erreur lors de la génération du PDF : " . $e->getMessage());
}
