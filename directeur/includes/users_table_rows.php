<?php
// Fonction pour générer le HTML du tableau des utilisateurs
function generateUsersTableRows($db) {
    $html = '';
    
    try {
        // Récupérer la liste des utilisateurs
        $query = "SELECT * FROM utilisateurs WHERE role IN ('professeur', 'secretaire') ORDER BY nom, prenom";
        $utilisateurs = $db->query($query);
        
        if ($utilisateurs === false) {
            throw new Exception("Erreur lors de la récupération des utilisateurs");
        }
        
        $utilisateurs = $utilisateurs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // En cas d'erreur, retourner un message d'erreur
        $html .= '<tr><td colspan="7" class="px-6 py-4 text-center text-red-600">';
        $html .= 'Erreur : ' . htmlspecialchars($e->getMessage());
        $html .= '</td></tr>';
        return $html;
    }
    
    if (empty($utilisateurs)) {
        $html .= '<tr>';
        $html .= '<td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">';
        $html .= 'Aucun utilisateur n\'a été enregistré pour le moment.';
        $html .= '</td>';
        $html .= '</tr>';
    } else {
        foreach ($utilisateurs as $utilisateur) {
            $html .= '<tr>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="text-sm font-medium text-gray-900">';
            $html .= htmlspecialchars(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''));
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="text-sm text-gray-900">' . htmlspecialchars($utilisateur['email'] ?? '') . '</div>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="text-sm text-gray-900">' . htmlspecialchars($utilisateur['username'] ?? 'N/A') . '</div>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $roleClass = ($utilisateur['role'] ?? '') === 'professeur' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800';
            $html .= '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $roleClass . '">';
            $html .= ucfirst($utilisateur['role'] ?? '');
            $html .= '</span>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $statut = $utilisateur['statut'] ?? 'actif';
            $statutClass = $statut === 'actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            $html .= '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statutClass . '">';
            $html .= ucfirst($statut);
            $html .= '</span>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
            $html .= !empty($utilisateur['date_creation']) ? date('d/m/Y', strtotime($utilisateur['date_creation'])) : '';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">';
            $html .= '<button class="text-blue-600 hover:text-blue-900 mr-4" ';
            $html .= 'onclick="editUser(' . htmlspecialchars(json_encode($utilisateur)) . ')">';
            $html .= '<i class="fas fa-edit"></i> Modifier';
            $html .= '</button>';
            $html .= '<button class="text-red-600 hover:text-red-900" ';
            $html .= 'onclick="confirmDelete(' . ($utilisateur['id'] ?? '0') . ', ';
            $html .= '\'' . addslashes(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? '')) . '\')">';
            $html .= '<i class="fas fa-trash"></i> Supprimer';
            $html .= '</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
    }
    
    return $html;
}

// Si le fichier est inclus directement, générer le HTML
if (!function_exists('generateUsersTableRows')) {
    require_once __DIR__ . '/../../includes/db.php';
    echo generateUsersTableRows($db);
}
