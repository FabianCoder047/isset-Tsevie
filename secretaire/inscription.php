<?php
// Démarrer la session si elle n'est pas déjà démarrée
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

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'] ?? 'Secrétaire';

// Définir les URLs de base
$base_url = '/isset';
$secretaire_url = $base_url . '/secretaire';

// Initialiser les variables
$error = '';
$success = '';

// Récupérer la liste des classes pour le select
$classes = [];
try {
    $query = "SELECT id, nom,niveau FROM classes ORDER BY nom";
    $stmt = $db->query($query);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des classes: " . $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $classe_id = $_POST['classe_id'] ?? '';
    $contact_parent = trim($_POST['contact_parent'] ?? '');

    // Validation des données
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($lieu_naissance) || 
        empty($sexe) || empty($classe_id) || empty($contact_parent)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        try {
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

            // Préparer et exécuter la requête d'insertion
            $query = "INSERT INTO eleves (matricule, nom, prenom, date_naissance, lieu_naissance, 
                     sexe, classe_id, contact_parent, date_inscription) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                $matricule,
                $nom,
                $prenom,
                $date_naissance,
                $lieu_naissance,
                $sexe,
                $classe_id,
                $contact_parent,
                $date_inscription
            ]);

            // Rediriger vers la liste des élèves avec un message de succès
            $_SESSION['success'] = "L'élève a été inscrit avec succès. Matricule: $matricule";
            header('Location: ' . $secretaire_url . '/eleves.php');
            exit();
            
        } catch (PDOException $e) {
            $error = "Erreur lors de l'inscription de l'élève: " . $e->getMessage();
        }
    }
}

// Inclure le header
include __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Inscription d'un nouvel élève</h1>
        <p class="mt-1 text-sm text-gray-500">
            Remplissez le formulaire ci-dessous pour inscrire un nouvel élève.
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <!-- Nom -->
                <div class="sm:col-span-3">
                    <label for="nom" class="block text-sm font-medium text-gray-700">Nom *</label>
                    <input type="text" name="nom" id="nom" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                </div>

                <!-- Prénom -->
                <div class="sm:col-span-3">
                    <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom(s) *</label>
                    <input type="text" name="prenom" id="prenom" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                </div>

                <!-- Date de naissance -->
                <div class="sm:col-span-2">
                    <label for="date_naissance" class="block text-sm font-medium text-gray-700">Date de naissance *</label>
                    <input type="date" name="date_naissance" id="date_naissance" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           value="<?php echo htmlspecialchars($_POST['date_naissance'] ?? ''); ?>">
                </div>

                <!-- Lieu de naissance -->
                <div class="sm:col-span-2">
                    <label for="lieu_naissance" class="block text-sm font-medium text-gray-700">Lieu de naissance *</label>
                    <input type="text" name="lieu_naissance" id="lieu_naissance" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           value="<?php echo htmlspecialchars($_POST['lieu_naissance'] ?? ''); ?>">
                </div>

                <!-- Sexe -->
                <div class="sm:col-span-2">
                    <label for="sexe" class="block text-sm font-medium text-gray-700">Sexe *</label>
                    <select id="sexe" name="sexe" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="" disabled selected>Sélectionner...</option>
                        <option value="M" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'M') ? 'selected' : ''; ?>>Masculin</option>
                        <option value="F" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'F') ? 'selected' : ''; ?>>Féminin</option>
                    </select>
                </div>

                <!-- Classe -->
                <div class="sm:col-span-3">
                    <label for="classe_id" class="block text-sm font-medium text-gray-700">Classe *</label>
                    <select id="classe_id" name="classe_id" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="" disabled selected>Sélectionner une classe...</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?php echo $classe['id']; ?>" <?php echo (isset($_POST['classe_id']) && $_POST['classe_id'] == $classe['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe['nom'].' '.$classe['niveau']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Contact du parent -->
                <div class="sm:col-span-3">
                    <label for="contact_parent" class="block text-sm font-medium text-gray-700">Contact du parent *</label>
                    <input type="tel" name="contact_parent" id="contact_parent" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Ex: +228 XX XX XX XX"
                           value="<?php echo htmlspecialchars($_POST['contact_parent'] ?? ''); ?>">
                </div>
            </div>

            <div class="pt-5">
                <div class="flex justify-end">
                    <a href="<?php echo $secretaire_url; ?>/eleves.php"
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Annuler
                    </a>
                    <button type="submit"
                            class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Enregistrer l'élève
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
// Inclure le footer
include __DIR__ . '/includes/footer.php';
?>
