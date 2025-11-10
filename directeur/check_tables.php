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

// Vérifier si la table matieres existe
$tables = $db->query("SHOW TABLES LIKE 'matieres'")->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    die("La table 'matieres' n'existe pas dans la base de données.");
}

// Afficher la structure de la table matieres
echo "<h2>Structure de la table 'matieres'</h2>";
$query = "DESCRIBE matieres";
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
?>
