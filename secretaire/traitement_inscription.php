<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Initialiser l'authentification
$auth = new Auth($db);

// Vérifier si l'utilisateur est connecté et est une secrétaire
if (!$auth->isLoggedIn() || !$auth->hasRole('secretaire')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

header('Content-Type: application/json');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

try {
    // Récupérer et valider les données du formulaire
    $required = ['nom', 'prenom', 'date_naissance', 'lieu_naissance', 'sexe', 'classe_id', 'contact_parent'];
    $data = [];
    $errors = [];

    // Vérifier les champs obligatoires
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ " . ucfirst(str_replace('_', ' ', $field)) . " est obligatoire";
        } else {
            $data[$field] = trim($_POST[$field]);
        }
    }

    // Vérifier s'il y a des erreurs de validation
    if (!empty($errors)) {
        throw new Exception(implode(", ", $errors));
    }

    // Validation supplémentaire pour la date de naissance
    $date_naissance = new DateTime($data['date_naissance']);
    $today = new DateTime();
    $age = $today->diff($date_naissance)->y;

    if ($age < 1 || $age > 25) {
        throw new Exception("L'âge de l'élève doit être compris entre 1 et 25 ans");
    }

    // Vérifier si la classe existe
    $stmt = $db->prepare("SELECT id, nom, niveau FROM classes WHERE id = ?");
    $stmt->execute([$data['classe_id']]);
    $classe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$classe) {
        error_log("Classe non trouvée - ID: " . $data['classe_id']);
        $stmt = $db->query("SELECT id, nom, niveau FROM classes");
        $allClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Classes disponibles: " . print_r($allClasses, true));
        throw new Exception("La classe sélectionnée (ID: " . $data['classe_id'] . ") n'existe pas. Vérifiez la liste des classes disponibles.");
    }

    // Générer un matricule unique (ex: ANNEE-xxxxx)
    $annee_courante = date('Y');
    $dernier_numero = 1;
    
    // Récupérer le dernier numéro d'inscription de l'année
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(matricule, '-', -1) AS UNSIGNED)) as dernier_num 
             FROM eleves 
             WHERE matricule LIKE ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$annee_courante . '-%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['dernier_num']) {
        $dernier_numero = $result['dernier_num'] + 1;
    }
    
    $matricule = $annee_courante . '-' . str_pad($dernier_numero, 5, '0', STR_PAD_LEFT);
    $date_inscription = date('Y-m-d H:i:s');

    // Préparer les données pour l'insertion
    $insertData = [
        'matricule' => $matricule,
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'date_naissance' => $data['date_naissance'],
        'lieu_naissance' => $data['lieu_naissance'],
        'sexe' => $data['sexe'],
        'classe_id' => $data['classe_id'],
        'contact_parent' => $data['contact_parent'],
        'date_inscription' => $date_inscription
    ];

    // Insérer les données dans la base de données
    $columns = implode(', ', array_keys($insertData));
    $placeholders = ':' . implode(', :', array_keys($insertData));
    
    $query = "INSERT INTO eleves ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($query);
    
    // Log des données à insérer
    error_log("Tentative d'insertion: " . print_r($insertData, true));
    
    // Exécution de la requête
    if ($stmt->execute($insertData)) {
        echo json_encode([
            'success' => true,
            'message' => "L'élève a été inscrit avec succès",
            'matricule' => $matricule
        ]);
    } else {
        throw new Exception("Une erreur est survenue lors de l'enregistrement de l'élève");
    }
} catch (Exception $e) {
    // Log de l'erreur complète
    $errorInfo = $stmt->errorInfo();
    error_log("Erreur PDO: " . print_r($errorInfo, true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'pdo_error' => $errorInfo[2] ?? null
    ]);
}
