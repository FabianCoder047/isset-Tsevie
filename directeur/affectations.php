<?php
$pageTitle = 'Affectation des professeurs';
include 'includes/header.php';

// Récupérer la liste des classes
$query = "SELECT * FROM classes ORDER BY niveau, nom";
$classes = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des professeurs
$query = "SELECT * FROM utilisateurs WHERE role = 'professeur' ORDER BY nom, prenom";
$professeurs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les affectations existantes
$query = "SELECT e.*, u.nom as prof_nom, u.prenom as prof_prenom, 
                 m.nom as matiere_nom, m.coefficient, 
                 c.nom as classe_nom, c.niveau as classe_niveau
          FROM enseignements e
          JOIN utilisateurs u ON e.professeur_id = u.id
          JOIN matieres m ON e.matiere_id = m.id
          JOIN classes c ON m.classe_id = c.id
          ORDER BY c.niveau, c.nom, m.nom";
$affectations = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Gestion de l'ajout d'une affectation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_affectation'])) {
    $professeur_id = (int)$_POST['professeur_id'];
    $matiere_id = (int)$_POST['matiere_id'];
    
    if ($professeur_id > 0 && $matiere_id > 0) {
        // Vérifier si l'affectation existe déjà
        $stmt = $db->prepare("SELECT id FROM enseignements WHERE professeur_id = ? AND matiere_id = ?");
        $stmt->execute([$professeur_id, $matiere_id]);
        
        if ($stmt->rowCount() === 0) {
            $query = "INSERT INTO enseignements (professeur_id, matiere_id) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$professeur_id, $matiere_id])) {
                header('Location: affectations.php?success=1');
                exit();
            } else {
                $error = "Une erreur est survenue lors de l'affectation.";
            }
        } else {
            $error = "Cette affectation existe déjà.";
        }
    } else {
        $error = "Veuillez sélectionner un professeur et une matière.";
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Affecter un professeur à une matière</h2>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>Affectation enregistrée avec succès !</p>
        </div>
    <?php endif; ?>
    
    <form id="affectationForm" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="classe_id" class="block text-sm font-medium text-gray-700">Classe</label>
                <select id="classe_id" name="classe_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner une classe</option>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?php echo $classe['id']; ?>">
                            <?php echo htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="matiere_id" class="block text-sm font-medium text-gray-700">Matière</label>
                <select id="matiere_id" name="matiere_id" required disabled
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionnez d'abord une classe</option>
                </select>
            </div>
            
            <div>
                <label for="professeur_id" class="block text-sm font-medium text-gray-700">Professeur</label>
                <select id="professeur_id" name="professeur_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner un professeur</option>
                    <?php foreach ($professeurs as $professeur): ?>
                        <option value="<?php echo $professeur['id']; ?>">
                            <?php echo htmlspecialchars($professeur['nom'] . ' ' . $professeur['prenom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-link mr-2"></i> Affecter
            </button>
        </div>
    </form>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 bg-gray-50">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Affectations enregistrées</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Liste des professeurs affectés aux différentes matières.</p>
    </div>
    
    <div class="border-t border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professeur</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coefficient</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($affectations)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                Aucune affectation n'a été enregistrée pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($affectations as $affectation): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($affectation['classe_niveau'] . ' ' . $affectation['classe_nom']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($affectation['matiere_nom']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($affectation['prof_prenom'] . ' ' . $affectation['prof_nom']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo $affectation['coefficient']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-red-600 hover:text-red-900" 
                                            onclick="deleteAffectation(<?php echo $affectation['id']; ?>, '<?php echo htmlspecialchars(addslashes($affectation['prof_prenom'] . ' ' . $affectation['prof_nom'] . ' - ' . $affectation['matiere_nom'])); ?>')">
                                        <i class="fas fa-unlink"></i> Retirer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Fonction pour charger les matières d'une classe
function loadMatieres(classeId) {
    const matiereSelect = document.getElementById('matiere_id');
    
    if (classeId) {
        // Activer le sélecteur de matières
        matiereSelect.disabled = false;
        
        // Afficher un indicateur de chargement
        matiereSelect.innerHTML = '<option value="">Chargement des matières...</option>';
        
        // Effectuer un appel AJAX pour récupérer les matières de la classe sélectionnée
        fetch(`get_matieres_by_classe.php?classe_id=${classeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des matières');
                }
                return response.json();
            })
            .then(matieres => {
                // Vider et réinitialiser le sélecteur de matières
                matiereSelect.innerHTML = '<option value="">Sélectionner une matière</option>';
                
                // Vérifier si des matières ont été trouvées
                if (matieres.length === 0) {
                    matiereSelect.innerHTML = '<option value="">Aucune matière trouvée pour cette classe</option>';
                    return;
                }
                
                // Ajouter chaque matière au sélecteur
                matieres.forEach(matiere => {
                    const option = document.createElement('option');
                    option.value = matiere.id;
                    option.textContent = matiere.nom;
                    matiereSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erreur:', error);
                matiereSelect.innerHTML = '<option value="">Erreur lors du chargement des matières</option>';
            });
    } else {
        // Désactiver le sélecteur si aucune classe n'est sélectionnée
        matiereSelect.disabled = true;
        matiereSelect.innerHTML = '<option value="">Sélectionnez d\'abord une classe</option>';
    }
}

// Ajouter l'écouteur d'événement pour le changement de classe
document.addEventListener('DOMContentLoaded', function() {
    const classeSelect = document.getElementById('classe_id');
    
    if (classeSelect) {
        // Charger les matières si une classe est déjà sélectionnée
        if (classeSelect.value) {
            loadMatieres(classeSelect.value);
        }
        
        // Écouter les changements de sélection
        classeSelect.addEventListener('change', function() {
            loadMatieres(this.value);
        });
    }
});

// Fonction pour mettre à jour la liste des affectations
function updateAffectationsList() {
    fetch('get_affectations.php')
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.querySelector('table');
            if (newTable) {
                const oldTable = document.querySelector('table');
                if (oldTable) {
                    oldTable.outerHTML = newTable.outerHTML;
                }
            }
        })
        .catch(error => console.error('Erreur lors du chargement des affectations:', error));
}

// Gestion de la soumission du formulaire
document.getElementById('affectationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalButtonText = button.innerHTML;
    
    // Désactiver le bouton pendant la soumission
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';
    
    fetch('ajouter_affectation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher un message de succès
            showAlert('success', 'Affectation enregistrée avec succès !');
            
            // Réinitialiser le formulaire
            this.reset();
            document.getElementById('matiere_id').disabled = true;
            
            // Mettre à jour la liste des affectations
            updateAffectationsList();
        } else {
            showAlert('error', data.error || 'Une erreur est survenue lors de l\'affectation.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('error', 'Une erreur est survenue lors de la communication avec le serveur.');
    })
    .finally(() => {
        // Réactiver le bouton
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-link mr-2"></i> Affecter';
    });
});

// Fonction pour afficher des messages d'alerte
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    const alertClass = type === 'success' 
        ? 'bg-green-100 border-green-500 text-green-700' 
        : 'bg-red-100 border-red-500 text-red-700';
    
    alertDiv.className = `border-l-4 p-4 mb-4 ${alertClass}`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        <p>${message}</p>
        <button type="button" class="float-right focus:outline-none" onclick="this.parentElement.remove()">
            <span class="text-xl">&times;</span>
        </button>
    `;
    
    // Insérer l'alerte avant le formulaire
    const form = document.getElementById('affectationForm');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Supprimer l'alerte après 5 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Fonction de confirmation de suppression d'affectation
function deleteAffectation(affectationId, affectationInfo) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'affectation "${affectationInfo}" ?`)) {
        fetch('supprimer_affectation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${affectationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Affectation supprimée avec succès !');
                updateAffectationsList();
            } else {
                showAlert('error', data.error || 'Erreur lors de la suppression de l\'affectation');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('error', 'Une erreur est survenue lors de la suppression');
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Écouter les changements de sélection de classe
    document.getElementById('classe_id').addEventListener('change', function() {
        loadMatieres(this.value);
    });
    
    // Désactiver la soumission du formulaire si les champs requis ne sont pas remplis
    document.getElementById('affectationForm').addEventListener('submit', function(e) {
        const matiereSelect = document.getElementById('matiere_id');
        const professeurSelect = document.getElementById('professeur_id');
        
        if (!matiereSelect.value || !professeurSelect.value) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs requis.');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
