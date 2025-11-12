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

// Initialiser les variables
$error = '';
$success = '';
$eleve = null;
$classes = [];

// Récupérer les données de l'élève si un ID est fourni
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $eleve_id = (int)$_GET['id'];
    
    try {
        // Récupérer les informations de l'élève
        $query = "SELECT * FROM eleves WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $eleve_id, PDO::PARAM_INT);
        $stmt->execute();
        $eleve = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$eleve) {
            $error = "Élève non trouvé.";
        }
        
        // Récupérer la liste des classes
        $query = "SELECT * FROM classes ORDER BY niveau, nom";
        $stmt = $db->query($query);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des données: " . $e->getMessage();
    }
} else {
    $error = "ID d'élève non spécifié.";
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_eleve'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $sexe = trim($_POST['sexe'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $contact_parent = trim($_POST['contact_parent'] ?? '');
    $classe_id = !empty($_POST['classe_id']) ? (int)$_POST['classe_id'] : null;
    
    // Validation des données
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($lieu_naissance) || empty($sexe)) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        try {
            // Mise à jour des informations de l'élève
            $query = "UPDATE eleves SET 
                      nom = :nom, 
                      prenom = :prenom, 
                      date_naissance = :date_naissance, 
                      lieu_naissance = :lieu_naissance, 
                      sexe = :sexe, 
                      contact_parent = :contact_parent,
                      classe_id = :classe_id,
                      updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':date_naissance', $date_naissance);
            $stmt->bindParam(':lieu_naissance', $lieu_naissance);
            $stmt->bindParam(':sexe', $sexe);
            $stmt->bindParam(':contact_parent', $contact_parent);
            $stmt->bindParam(':classe_id', $classe_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $eleve_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $success = "Les informations de l'élève ont été mises à jour avec succès.";
                // Mettre à jour les données affichées
                $eleve = array_merge($eleve, [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'date_naissance' => $date_naissance,
                    'lieu_naissance' => $lieu_naissance,
                    'sexe' => $sexe,
                    'contact_parent' => $contact_parent,
                    'classe_id' => $classe_id
                ]);
            } else {
                $error = "Une erreur est survenue lors de la mise à jour.";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
}

// Inclure le header
include __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Modifier un élève</h1>
        <p class="mt-1 text-sm text-gray-500">
            <a href="eleves.php" class="text-blue-600 hover:text-blue-800">
                &larr; Retour à la liste des élèves
            </a>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($eleve): ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="POST" action="">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="nom" class="block text-sm font-medium text-gray-700">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" id="nom" required
                                   value="<?php echo htmlspecialchars($eleve['nom'] ?? ''); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="prenom" id="prenom" required
                                   value="<?php echo htmlspecialchars($eleve['prenom'] ?? ''); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="date_naissance" class="block text-sm font-medium text-gray-700">Date de naissance <span class="text-red-500">*</span></label>
                            <input type="date" name="date_naissance" id="date_naissance" required
                                   value="<?php echo !empty($eleve['date_naissance']) ? htmlspecialchars($eleve['date_naissance']) : ''; ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="lieu_naissance" class="block text-sm font-medium text-gray-700">Lieu de naissance <span class="text-red-500">*</span></label>
                            <input type="text" name="lieu_naissance" id="lieu_naissance" required
                                   value="<?php echo htmlspecialchars($eleve['lieu_naissance'] ?? ''); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="sexe" class="block text-sm font-medium text-gray-700">Sexe <span class="text-red-500">*</span></label>
                            <select id="sexe" name="sexe" required
                                    class="mt-1 block w-full border border-gray-300 bg-white rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="" disabled>Sélectionnez un genre</option>
                                <option value="M" <?php echo (isset($eleve['sexe']) && $eleve['sexe'] === 'M') ? 'selected' : ''; ?>>Masculin</option>
                                <option value="F" <?php echo (isset($eleve['sexe']) && $eleve['sexe'] === 'F') ? 'selected' : ''; ?>>Féminin</option>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="classe_id" class="block text-sm font-medium text-gray-700">Classe</label>
                            <select id="classe_id" name="classe_id"
                                    class="mt-1 block w-full border border-gray-300 bg-white rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Sélectionnez une classe</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo $classe['id']; ?>"
                                        <?php echo (isset($eleve['classe_id']) && $eleve['classe_id'] == $classe['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="contact_parent" class="block text-sm font-medium text-gray-700">Contact du parent</label>
                            <input type="tel" name="contact_parent" id="contact_parent"
                                   value="<?php echo htmlspecialchars($eleve['contact_parent'] ?? ''); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <a href="eleves.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Annuler
                        </a>
                        <button type="submit" name="update_eleve" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Inclure le footer
include __DIR__ . '/includes/footer.php';
?>
