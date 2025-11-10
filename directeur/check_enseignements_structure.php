<?php
// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/includes/db.php';

// Vérifier si la table enseignements existe
$tables = $db->query("SHOW TABLES LIKE 'enseignements'")->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    die("La table 'enseignements' n'existe pas dans la base de données.");
}

// Afficher la structure de la table enseignements
echo "<h2>Structure de la table 'enseignements'</h2>";
$query = "DESCRIBE enseignements";
$columns = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Afficher les premières lignes de la table enseignements
echo "<h2>Contenu de la table 'enseignements' (10 premières lignes)</h2>";
$query = "SELECT * FROM enseignements LIMIT 10";
$enseignements = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
if (!empty($enseignements)) {
    // En-têtes du tableau
    echo "<tr>";
    foreach (array_keys($enseignements[0]) as $column) {
        echo "<th>" . htmlspecialchars($column) . "</th>";
    }
    echo "</tr>";
    
    // Données
    foreach ($enseignements as $enseignement) {
        echo "<tr>";
        foreach ($enseignement as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='10'>Aucune affectation trouvée dans la base de données.</td></tr>";
}
echo "</table>";
?>
