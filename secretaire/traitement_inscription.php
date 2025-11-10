<?php
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer et valider les données du formulaire
    $required = ['nom', 'prenom', 'date_naissance', 'sexe', 'classe_id', 'matricule'];
    $data = [];
    $errors = [];

    // Vérifier les champs obligatoires
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ " . ucfirst($field) . " est obligatoire";
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

    if ($age < 5 || $age > 25) {
        throw new Exception("L'âge de l'élève doit être compris entre 5 et 25 ans");
    }

    // Vérifier si la classe existe
    $stmt = $db->prepare("SELECT id FROM classes WHERE id = ?");
    $stmt->execute([$data['classe_id']]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("La classe sélectionnée n'existe pas");
    }

    // Vérifier si le matricule existe déjà
    $stmt = $db->prepare("SELECT id FROM eleves WHERE matricule = ?");
    $stmt->execute([$data['matricule']]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception("Ce matricule est déjà attribué à un autre élève");
    }

    // Préparer les données pour l'insertion
    $insertData = [
        'matricule' => $data['matricule'],
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'date_naissance' => $data['date_naissance'],
        'sexe' => $data['sexe'],
        'classe_id' => $data['classe_id'],
        'annee_scolaire' => $data['annee_scolaire'],
        'telephone' => !empty($_POST['telephone']) ? $_POST['telephone'] : null,
        'adresse' => !empty($_POST['adresse']) ? $_POST['adresse'] : null,
        'date_inscription' => date('Y-m-d H:i:s')
    ];

    // Insérer les données dans la base de données
    $columns = implode(', ', array_keys($insertData));
    $placeholders = ':' . implode(', :', array_keys($insertData));
    
    $query = "INSERT INTO eleves ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($insertData)) {
        echo json_encode([
            'success' => true,
            'matricule' => $matricule
        ]);
    } else {
        throw new Exception("Une erreur est survenue lors de l'enregistrement");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
