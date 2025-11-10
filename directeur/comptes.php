<?php
// Démarrer la temporisation de sortie
ob_start();

// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/includes/db.php';

// Vérifier si c'est une requête AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Gestion de l'ajout d'utilisateur via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    // Nettoyer le buffer de sortie pour éviter tout contenu indésirable
    while (ob_get_level()) ob_end_clean();
    
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    
    try {
        // Validation des champs obligatoires
        if (empty($nom) || empty($prenom) || empty($email) || empty($role)) {
            throw new Exception("Tous les champs sont obligatoires");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("L'adresse email n'est pas valide");
        }
        
        $username = strtolower($prenom[0] . str_replace(' ', '', $nom));
        $password = password_hash('isset123', PASSWORD_DEFAULT);
        
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Un utilisateur avec cet email existe déjà.");
        }
        
        // Générer un nom d'utilisateur unique si nécessaire
        $base_username = $username;
        $counter = 1;
        
        do {
            $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $username = $base_username . $counter;
                $counter++;
            } else {
                break;
            }
        } while (true);
        
        // Insérer le nouvel utilisateur
        $query = "INSERT INTO utilisateurs (nom, prenom, email, username, password, role, statut, date_creation) 
                  VALUES (?, ?, ?, ?, ?, ?, 'actif', NOW())";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nom, $prenom, $email, $username, $password, $role])) {
            $response['success'] = true;
            $response['message'] = 'Utilisateur créé avec succès';
            $response['user'] = [
                'id' => $db->lastInsertId(),
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'username' => $username,
                'role' => $role,
                'statut' => 'actif',
                'date_creation' => date('Y-m-d H:i:s')
            ];
            
            // Générer le HTML du tableau des utilisateurs
            require_once 'includes/users_table_rows.php';
            $response['html'] = generateUsersTableRows($db);
        } else {
            throw new Exception("Erreur lors de la création du compte");
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Ne pas inclure le header pour les requêtes AJAX
if (!$isAjax) {
    $pageTitle = 'Gestion des comptes';
    include 'includes/header.php';
}

// Fonction pour récupérer la liste des utilisateurs
function getUtilisateurs($db) {
    $query = "SELECT * FROM utilisateurs WHERE role IN ('professeur', 'secretaire') ORDER BY nom, prenom";
    return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer la liste des utilisateurs (professeurs et secrétaires)
$utilisateurs = getUtilisateurs($db);

// Gestion de l'ajout d'utilisateur via AJAX
// Gestion de l'ajout d'utilisateur via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    // Nettoyer le buffer de sortie pour éviter tout contenu indésirable
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $username = strtolower($prenom[0] . str_replace(' ', '', $nom));
    $password = password_hash('isset123', PASSWORD_DEFAULT);
    
    try {
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Un utilisateur avec cet email existe déjà.");
        }
        
        // Générer un nom d'utilisateur unique si nécessaire
        $base_username = $username;
        $counter = 1;
        
        do {
            $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $username = $base_username . $counter;
                $counter++;
            } else {
                break;
            }
        } while (true);
        
        // Insérer le nouvel utilisateur
        $query = "INSERT INTO utilisateurs (nom, prenom, email, username, password, role, statut, date_creation) 
                  VALUES (?, ?, ?, ?, ?, ?, 'actif', NOW())";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nom, $prenom, $email, $username, $password, $role])) {
            $response['success'] = true;
            $response['message'] = 'Utilisateur créé avec succès';
            $response['user'] = [
                'id' => $db->lastInsertId(),
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'username' => $username,
                'role' => $role,
                'statut' => 'actif',
                'date_creation' => date('Y-m-d H:i:s')
            ];
        } else {
            throw new Exception("Erreur lors de la création du compte");
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    // Envoyer la réponse JSON et terminer le script
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<div class="bg-white p-6 rounded-lg shadow mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Ajouter un utilisateur</h2>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <form id="addUserForm" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="nom" class="block text-sm font-medium text-gray-700">Nom</label>
                <input type="text" id="nom" name="nom" required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
                <input type="text" id="prenom" name="prenom" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Rôle</label>
                <select id="role" name="role" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner un rôle</option>
                    <option value="professeur">Professeur</option>
                    <option value="secretaire">Secrétaire</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <div class="w-full">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                        Mot de passe par défaut : <strong>isset123</strong>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        L'utilisateur devra changer son mot de passe à sa première connexion.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" id="addUserBtn">
                <i class="fas fa-plus mr-2"></i>Ajouter l'utilisateur
                <span id="addUserSpinner" class="hidden ml-2">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
        </div>
    </form>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 bg-gray-50">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Liste des utilisateurs</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Gérez les comptes des professeurs et secrétaires.</p>
    </div>
    
    <div class="border-t border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom & Prénom</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom d'utilisateur</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de création</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                    <?php 
                    require_once 'includes/users_table_rows.php';
                    echo generateUsersTableRows($db);
                    ?>
                </tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modale d'édition d'utilisateur -->
<div id="editUserModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Modifier l'utilisateur
                    </h3>
                    <div class="mt-4">
                        <form id="editUserForm" method="POST">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="edit_nom" class="block text-sm font-medium text-gray-700">Nom</label>
                                        <input type="text" id="edit_nom" name="nom" required 
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="edit_prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
                                        <input type="text" id="edit_prenom" name="prenom" required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>
                                <div>
                                    <label for="edit_email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" id="edit_email" name="email" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="edit_role" class="block text-sm font-medium text-gray-700">Rôle</label>
                                    <select id="edit_role" name="role" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="professeur">Professeur</option>
                                        <option value="secretaire">Secrétaire</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_statut" class="block text-sm font-medium text-gray-700">Statut</label>
                                    <select id="edit_statut" name="statut" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="actif">Actif</option>
                                        <option value="inactif">Inactif</option>
                                    </select>
                                </div>
                                <div class="mt-4 p-4 bg-yellow-50 rounded-md">
                                    <h4 class="text-sm font-medium text-yellow-800">Réinitialiser le mot de passe</h4>
                                    <p class="text-xs text-yellow-700 mt-1">
                                        Cochez la case ci-dessous pour réinitialiser le mot de passe de l'utilisateur à "isset123".
                                    </p>
                                    <div class="mt-2 flex items-center">
                                        <input id="reset_password" name="reset_password" type="checkbox"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="reset_password" class="ml-2 block text-sm text-gray-700">
                                            Réinitialiser le mot de passe
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button type="button" id="saveUserChangesBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                    Enregistrer
                </button>
                <button type="button" id="cancelEditUserBtn"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour rafraîchir la liste des utilisateurs
function refreshUsersList() {
    fetch('includes/users_table_rows.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau lors du rafraîchissement de la liste');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('usersTableBody').innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur lors du rafraîchissement de la liste:', error);
            showAlert('error', 'Erreur lors du rafraîchissement de la liste des utilisateurs');
        });
}

// Fonction pour afficher une notification
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white z-50`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    // Supprimer l'alerte après 5 secondes
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Gestion du formulaire d'ajout d'utilisateur
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const addUserBtn = document.getElementById('addUserBtn');
    const spinner = document.getElementById('addUserSpinner');
    
    // Afficher le spinner
    addUserBtn.disabled = true;
    spinner.classList.remove('hidden');
    
    // Ajouter l'en-tête X-Requested-With pour la détection AJAX côté serveur
    formData.append('X-Requested-With', 'XMLHttpRequest');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Réponse non-JSON reçue:', text);
            throw new Error('Le serveur a renvoyé une réponse non valide');
        }
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erreur lors de la requête');
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Réinitialiser le formulaire
            this.reset();
            // Afficher un message de succès
            showAlert('success', data.message);
            
            // Mettre à jour le tableau avec le nouveau HTML
            if (data.html) {
                document.getElementById('usersTableBody').innerHTML = data.html;
            } else {
                // Si pas de HTML, recharger la page pour être sûr
                window.location.reload();
            }
        } else {
            throw new Error(data.message || 'Erreur lors de la création de l\'utilisateur');
        }
    })
    .catch(error => {
        console.error('Erreur détaillée:', error);
        showAlert('error', error.message || 'Une erreur est survenue lors de la création de l\'utilisateur');
    })
    .finally(() => {
        // Cacher le spinner et réactiver le bouton
        addUserBtn.disabled = false;
        spinner.classList.add('hidden');
    });
});


// Fonction pour ouvrir la modale d'édition d'utilisateur
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_nom').value = user.nom;
    document.getElementById('edit_prenom').value = user.prenom;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_statut').value = user.statut || 'actif';
    document.getElementById('reset_password').checked = false;
    
    // Afficher la modale
    document.getElementById('editUserModal').classList.remove('hidden');
}

// Fonction de confirmation de suppression
function confirmDelete(userId, userName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${userName}" ? Cette action est irréversible.`)) {
        // Ici, vous devrez implémenter la logique de suppression via AJAX
        // Par exemple :
        /*
        fetch('delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger la page ou supprimer la ligne du tableau
                location.reload();
            } else {
                alert('Erreur lors de la suppression : ' + data.message);
            }
        });
        */
        
        // Pour l'instant, on affiche simplement un message
        alert('Fonctionnalité de suppression à implémenter');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editUserModal');
    const editForm = document.getElementById('editUserForm');
    const saveChangesBtn = document.getElementById('saveUserChangesBtn');
    const cancelEditBtn = document.getElementById('cancelEditUserBtn');
    
    // Annuler l'édition
    cancelEditBtn.addEventListener('click', function() {
        editModal.classList.add('hidden');
    });
    
    // Sauvegarder les modifications
    saveChangesBtn.addEventListener('click', function() {
        // Ici, vous devrez implémenter la logique pour sauvegarder les modifications via AJAX
        // Par exemple :
        /*
        const formData = new FormData(editForm);
        fetch('update_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger la page ou mettre à jour uniquement les données modifiées
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour : ' + data.message);
            }
        });
        */
        
        // Pour l'instant, on affiche simplement un message
        alert('Fonctionnalité de mise à jour à implémenter');
        
        // Et on ferme la modale
        editModal.classList.add('hidden');
    });
    
    // Fermer la modale en cliquant en dehors
    editModal.addEventListener('click', function(e) {
        if (e.target === editModal) {
            editModal.classList.add('hidden');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
