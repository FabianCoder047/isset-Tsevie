<?php
$pageTitle = 'Gestion des matières par classe';
include 'includes/header.php';

// Récupérer la liste des classes
$query = "SELECT * FROM classes ORDER BY niveau, nom";
$stmt = $db->query($query);
$classes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Récupérer les matières existantes
$query = "SELECT m.*, c.nom as classe_nom ,c.niveau as classe_niveau FROM matieres m 
          LEFT JOIN classes c ON m.classe_id = c.id 
          ORDER BY c.niveau, c.nom, m.nom";
$matieres = [];
try {
    $stmt = $db->query($query);
    $matieres = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des matières: " . $e->getMessage());
}

// Gestion de l'ajout et de la modification d'une matière
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter_matiere'])) {
        $nom = trim($_POST['nom']);
        $classe_id = (int)$_POST['classe_id'];
        $coefficient = (int)$_POST['coefficient'];
        
        if (!empty($nom) && $classe_id > 0 && $coefficient > 0) {
            try {
                // Vérifier si la matière existe déjà pour cette classe
                $checkQuery = "SELECT id FROM matieres WHERE nom = ? AND classe_id = ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$nom, $classe_id]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error = "Cette matière existe déjà pour cette classe.";
                } else {
                    $query = "INSERT INTO matieres (nom, classe_id, coefficient) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$nom, $classe_id, $coefficient]);
                    echo '<script>window.location.href = "matieres.php?success=1";</script>';
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout de la matière: " . $e->getMessage());
                $error = "Une erreur est survenue lors de l'ajout de la matière.";
            }
        } else {
            $error = "Veuillez remplir tous les champs correctement.";
        }
    } 
    // Gestion de la modification d'une matière
    elseif (isset($_POST['modifier_matiere'])) {
        $matiere_id = (int)$_POST['matiere_id'];
        $nom = trim($_POST['nom']);
        $coefficient = (int)$_POST['coefficient'];
        
        if (!empty($nom) && $matiere_id > 0 && $coefficient > 0) {
            try {
                $query = "UPDATE matieres SET nom = ?, coefficient = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$nom, $coefficient, $matiere_id]);
                echo '<script>window.location.href = "matieres.php?success=2";</script>';
                exit;
            } catch (PDOException $e) {
                error_log("Erreur lors de la modification de la matière: " . $e->getMessage());
                $error = "Une erreur est survenue lors de la modification de la matière.";
            }
        } else {
            $error = "Veuillez remplir tous les champs correctement.";
        }
    }
}

// Gestion de la suppression d'une matière
if (isset($_GET['supprimer'])) {
    $matiere_id = (int)$_GET['supprimer'];
    if ($matiere_id > 0) {
        try {
            // Vérifier s'il y a des notes associées à cette matière
            $checkNotes = $db->prepare("SELECT COUNT(*) FROM notes WHERE matiere_id = ?");
            $checkNotes->execute([$matiere_id]);
            $hasNotes = $checkNotes->fetchColumn() > 0;
            
            if ($hasNotes) {
                $error = "Impossible de supprimer cette matière car elle est associée à des notes.";
            } else {
                $query = "DELETE FROM matieres WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$matiere_id]);
                echo '<script>window.location.href = "matieres.php?success=3";</script>';
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la matière: " . $e->getMessage());
            $error = "Une erreur est survenue lors de la suppression de la matière.";
        }
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Ajouter une matière</h2>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>
                <?php 
                switch($_GET['success']) {
                    case '1':
                        echo 'Matière ajoutée avec succès !';
                        break;
                    case '2':
                        echo 'Matière modifiée avec succès !';
                        break;
                    case '3':
                        echo 'Matière supprimée avec succès !';
                        break;
                    default:
                        echo 'Opération effectuée avec succès !';
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="nom" class="block text-sm font-medium text-gray-700">Sélectionner une matière</label>
                <select id="nom" name="nom" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner une matière</option>
                    <?php 
                    // Récupérer la liste des matières uniques déjà existantes
                    $matieres_uniques = [];
                    foreach ($matieres as $m) {
                        $matieres_uniques[$m['nom']] = $m['nom'];
                    }
                    foreach (array_unique($matieres_uniques) as $matiere_nom): ?>
                        <option value="<?php echo htmlspecialchars($matiere_nom); ?>">
                            <?php echo htmlspecialchars($matiere_nom); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="autre">Autre (préciser)...</option>
                </select>
                <div id="autre_matiere_container" class="mt-2 hidden">
                    <label for="autre_matiere" class="block text-sm font-medium text-gray-700">Nouvelle matière</label>
                    <input type="text" id="autre_matiere" name="autre_matiere" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            
            <script>
            document.getElementById('nom').addEventListener('change', function() {
                const autreMatiereContainer = document.getElementById('autre_matiere_container');
                if (this.value === 'autre') {
                    autreMatiereContainer.classList.remove('hidden');
                    document.getElementById('autre_matiere').setAttribute('required', 'required');
                } else {
                    autreMatiereContainer.classList.add('hidden');
                    document.getElementById('autre_matiere').removeAttribute('required');
                }
            });
            
            // Gestion de la soumission du formulaire pour inclure la nouvelle matière si nécessaire
            document.querySelector('form').addEventListener('submit', function(e) {
                const selectMatiere = document.getElementById('nom');
                const autreMatiere = document.getElementById('autre_matiere');
                
                if (selectMatiere.value === 'autre' && autreMatiere.value.trim() !== '') {
                    // Créer une nouvelle option avec la valeur de l'input
                    const newOption = new Option(autreMatiere.value, autreMatiere.value, true, true);
                    // Ajouter la nouvelle option avant l'option 'autre'
                    selectMatiere.insertBefore(newOption, selectMatiere.lastElementChild);
                    // Sélectionner la nouvelle option
                    selectMatiere.value = autreMatiere.value;
                }
            });
            </script>
            
            <div>
                <label for="classe_id" class="block text-sm font-medium text-gray-700">Classe</label>
                <select id="classe_id" name="classe_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner une classe</option>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?php echo $classe['id']; ?>">
                            <?php echo htmlspecialchars($classe['nom'] . ' ' . $classe['niveau']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="coefficient" class="block text-sm font-medium text-gray-700">Coefficient</label>
                <input type="number" id="coefficient" name="coefficient" min="1" max="10" value="1" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" name="ajouter_matiere"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i> Ajouter la matière
            </button>
        </div>
    </form>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 bg-gray-50">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Liste des matières par classe</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Gérez les matières et leurs coefficients pour chaque classe.</p>
    </div>
    
    <div class="border-t border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coefficient</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($matieres)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                Aucune matière n'a été enregistrée pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($matieres as $matiere): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($matiere['classe_nom'].' '.$matiere['classe_niveau']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($matiere['nom']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo $matiere['coefficient']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="openEditModal(
                                        '<?php echo $matiere['id']; ?>',
                                        '<?php echo htmlspecialchars(addslashes($matiere['nom']), ENT_QUOTES); ?>',
                                        <?php echo (int)$matiere['coefficient']; ?>
                                    ); return false;" class="text-blue-600 hover:text-blue-900 mr-4">
                                        <i class="fas fa-edit"></i> Modifier
                                    </button>
                                    <a href="?supprimer=<?php echo $matiere['id']; ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette matière ?');"
                                       class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modale de modification (à implémenter avec JavaScript) -->
<div id="editModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Modifier la matière
                    </h3>
                    <div class="mt-4">
                        <form id="editMatiereForm" method="POST">
                            <input type="hidden" name="matiere_id" id="edit_matiere_id">
                            <input type="hidden" name="modifier_matiere" value="1">
                            <div class="space-y-4">
                                <div>
                                    <label for="edit_nom" class="block text-sm font-medium text-gray-700">Matière</label>
                                    <select id="edit_nom" name="nom" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Sélectionner une matière</option>
                                        <?php 
                                        $matieres_uniques = [];
                                        foreach ($matieres as $m) {
                                            $matieres_uniques[$m['nom']] = $m['nom'];
                                        }
                                        foreach (array_unique($matieres_uniques) as $matiere_nom): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($matiere_nom); ?>">
                                                <?php echo htmlspecialchars($matiere_nom); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_coefficient" class="block text-sm font-medium text-gray-700">Coefficient</label>
                                    <input type="number" id="edit_coefficient" name="coefficient" min="1" max="10" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </form>
                        <script>
                        // Aucune manipulation spéciale nécessaire pour la soumission
                        // La sélection de la matière et la modification du coefficient sont gérées directement par le formulaire
                        </script>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button type="button" id="saveChangesBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                    Enregistrer
                </button>
                <button type="button" id="cancelEditBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour ouvrir la modale d'édition avec les données de la matière
function openEditModal(id, nom, coefficient) {
    console.log('Ouverture de la modale avec ID:', id, 'Nom:', nom, 'Coefficient:', coefficient);
    
    // Mettre à jour les champs du formulaire
    const form = document.getElementById('editMatiereForm');
    form.reset(); // Réinitialiser le formulaire
    
    // Définir les valeurs des champs
    document.getElementById('edit_matiere_id').value = id;
    document.getElementById('edit_nom').value = nom;
    document.getElementById('edit_coefficient').value = coefficient;
    
    // Afficher la modale
    document.getElementById('editModal').classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation des écouteurs d\'événements');
    
    // Gestion de la modale d'édition
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editMatiereForm');
    const saveChangesBtn = document.getElementById('saveChangesBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    
    if (!editModal || !editForm || !saveChangesBtn || !cancelEditBtn) {
        console.error('Un ou plusieurs éléments de la modale sont introuvables');
        return;
    }
    
    // Fermer la modale lors du clic sur le bouton Annuler
    cancelEditBtn.addEventListener('click', function() {
        editModal.classList.add('hidden');
    });
    
    // Fermer la modale lors du clic en dehors du contenu
    editModal.addEventListener('click', function(e) {
        if (e.target === editModal) {
            editModal.classList.add('hidden');
        }
    });
    
    // Gérer la soumission du formulaire de modification
    saveChangesBtn.addEventListener('click', function() {
        editForm.submit();
    });
    
    // Fonction pour fermer la modale
    function closeEditModal() {
        editModal.classList.add('hidden');
    }
    
    // Afficher un message de débogage pour les boutons d'édition
    const editButtons = document.querySelectorAll('button[onclick^="openEditModal"]');
    console.log('Boutons d\'édition trouvés:', editButtons.length);
    document.querySelectorAll('[data-action="edit"]').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const matiere = {
                id: this.dataset.id,
                nom: row.cells[1].textContent.trim(),
                coefficient: parseInt(row.cells[2].textContent.trim())
            };
            openEditModal(matiere);
        });
    });
    
    // Annuler l'édition
    cancelEditBtn.addEventListener('click', closeEditModal);
    
    // Sauvegarder les modifications
    saveChangesBtn.addEventListener('click', function() {
        // Ici, vous devrez implémenter la logique pour sauvegarder les modifications via AJAX
        // Par exemple :
        /*
        const formData = new FormData(editForm);
        fetch('update_matiere.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'interface utilisateur
                closeEditModal();
                location.reload(); // Ou mettre à jour uniquement la ligne modifiée
            } else {
                alert('Erreur lors de la mise à jour: ' + data.message);
            }
        });
        */
        
        // Pour l'instant, on se contente de fermer la modale
        closeEditModal();
    });
    
    // Gestion de la suppression
    document.querySelectorAll('[data-action="delete"]').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette matière ? Cette action est irréversible.')) {
                const matiereId = this.dataset.id;
                // Ici, vous devrez implémenter la logique de suppression via AJAX
                // Par exemple :
                /*
                fetch('delete_matiere.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: matiereId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer la ligne du tableau
                        this.closest('tr').remove();
                    } else {
                        alert('Erreur lors de la suppression: ' + data.message);
                    }
                });
                */
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
