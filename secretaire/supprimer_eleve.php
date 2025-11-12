<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialiser l'authentification
$auth = new Auth($db);

// Vérifier si l'utilisateur est connecté et est une secrétaire
if (!$auth->isLoggedIn() || !$auth->hasRole('secretaire')) {
    header('Location: /isset/login.php');
    exit();
}

// Vérifier si un ID d'élève est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID d'élève invalide.";
    header('Location: eleves.php');
    exit();
}

$eleve_id = (int)$_GET['id'];

// Vérifier si l'élève existe avant de le supprimer
try {
    // Vérifier d'abord si l'élève existe
    $query = "SELECT id, nom, prenom FROM eleves WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $eleve_id, PDO::PARAM_INT);
    $stmt->execute();
    $eleve = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$eleve) {
        $_SESSION['error'] = "L'élève demandé n'existe pas ou a déjà été supprimé.";
        header('Location: eleves.php');
        exit();
    }
    
    // Commencer une transaction
    $db->beginTransaction();
    
    try {
        // Supprimer d'abord les enregistrements liés dans les tables enfants
        // Note: Assurez-vous d'ajuster ces requêtes en fonction de votre schéma de base de données
        
        // Exemple: Supprimer les notes de l'élève
        // $query = "DELETE FROM notes WHERE eleve_id = :eleve_id";
        // $stmt = $db->prepare($query);
        // $stmt->bindParam(':eleve_id', $eleve_id, PDO::PARAM_INT);
        // $stmt->execute();
        
        // Supprimer l'élève
        $query = "DELETE FROM eleves WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $eleve_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Valider la transaction
        $db->commit();
        
        $_SESSION['success'] = "L'élève " . htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) . " a été supprimé avec succès.";
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la suppression de l'élève: " . $e->getMessage();
}

// Rediriger vers la liste des élèves
header('Location: eleves.php');
exit();
?>
