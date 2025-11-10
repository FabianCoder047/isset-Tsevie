<?php
// Script pour remplacer toutes les références à $pdo par $db dans les fichiers PHP

// Dossier à analyser
$directory = __DIR__;

// Fonction pour traiter un fichier
function processFile($file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Remplacer $pdo-> par $db->
    $content = str_replace('$pdo->', '$db->', $content);
    
    // Si des modifications ont été apportées, sauvegarder le fichier
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fichier mis à jour : " . basename($file) . "\n";
    }
}

// Parcourir tous les fichiers PHP du répertoire
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'fix_pdo_references.php') {
        processFile($file->getPathname());
    }
}

echo "Traitement terminé. Toutes les références à \$pdo ont été remplacées par \$db.\n";
