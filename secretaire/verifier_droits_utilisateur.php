<?php
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Vérifier les privilèges de l'utilisateur actuel
    $stmt = $db->query("SELECT CURRENT_USER(), USER(), VERSION()");
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Informations sur l'utilisateur de la base de données :\n";
    echo "----------------------------------------\n";
    echo "Utilisateur actuel : " . ($userInfo['CURRENT_USER()'] ?? 'N/A') . "\n";
    echo "Connexion utilisée : " . ($userInfo['USER()'] ?? 'N/A') . "\n";
    echo "Version de MySQL : " . ($userInfo['VERSION()'] ?? 'N/A') . "\n\n";
    
    // Vérifier les privilèges sur la table eleves
    $stmt = $db->query("
        SELECT * FROM information_schema.TABLE_PRIVILEGES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'eleves'
    ");
    
    $privileges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($privileges)) {
        echo "Aucun privilège trouvé pour la table 'eleves'.\n";
    } else {
        echo "Privilèges pour la table 'eleves' :\n";
        foreach ($privileges as $priv) {
            echo "- " . $priv['GRANTEE'] . " : " . $priv['PRIVILEGE_TYPE'] . "\n";
        }
    }
    
    // Tester une insertion factice (sans la valider)
    echo "\nTest d'insertion factice...\n";
    
    try {
        $db->beginTransaction();
        
        // Tester l'insertion avec une classe qui existe
        $testData = [
            'matricule' => 'TEST-' . time(),
            'nom' => 'Test',
            'prenom' => 'Utilisateur',
            'date_naissance' => '2000-01-01',
            'lieu_naissance' => 'Test',
            'sexe' => 'M',
            'classe_id' => 1,  // Utiliser une classe qui existe
            'contact_parent' => '+228 00 00 00 00',
            'date_inscription' => date('Y-m-d H:i:s')
        ];
        
        $columns = implode(', ', array_keys($testData));
        $placeholders = ':' . implode(', :', array_keys($testData));
        
        $query = "INSERT INTO eleves ($columns) VALUES ($placeholders)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($testData)) {
            echo "Test d'insertion RÉUSSI avec la classe ID 1.\n";
        } else {
            $error = $stmt->errorInfo();
            throw new Exception("Échec de l'insertion de test : " . ($error[2] ?? 'Erreur inconnue'));
        }
        
        // Annuler la transaction de test
        $db->rollBack();
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    
} catch (PDOException $e) {
    die("Erreur PDO : " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage() . "\n");
}
?>
