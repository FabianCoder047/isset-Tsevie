<?php
/**
 * Template pour l'affichage et l'impression des bulletins
 * 
 * Variables attendues :
 * - $eleve : Tableau avec les informations de l'élève
 * - $classe : Tableau avec les informations de la classe
 * - $periode : Tableau avec les informations de la période
 * - $bulletins : Tableau des notes par matière
 * - $moyenne_generale : Moyenne générale de l'élève
 * - $appreciation : Appréciation générale
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de notes - <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @media print {
            body { font-size: 12px; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            .header, .footer { position: fixed; }
            .header { top: 0; }
            .footer { bottom: 0; }
            .content { margin-top: 100px; margin-bottom: 50px; }
        }
        .border-black { border: 1px solid #000; }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- En-tête -->
        <div class="bg-blue-800 text-white p-6 text-center">
            <div class="flex justify-between items-center mb-4">
                <div class="w-1/4 text-left">
                    <img src="/images/logo.jpeg" alt="Logo" class="h-20 mx-auto">
                </div>
                <div class="w-2/4">
                    <h1 class="text-2xl font-bold">BULLETIN DE NOTES</h1>
                    <p class="text-lg"><?php echo htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']); ?></p>
                    <p class="text-lg"><?php echo htmlspecialchars($periode['nom']); ?> - Année Scolaire <?php echo htmlspecialchars($periode['annee_scolaire']); ?></p>
                </div>
                <div class="w-1/4"></div>
            </div>
        </div>

        <!-- Informations élève -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p><span class="font-semibold">Nom et prénom :</span> <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></p>
                    <p><span class="font-semibold">Date de naissance :</span> <?php echo !empty($eleve['date_naissance']) ? date('d/m/Y', strtotime($eleve['date_naissance'])) : '-'; ?></p>
                </div>
                <div>
                    <p><span class="font-semibold">Classe :</span> <?php echo htmlspecialchars($classe['niveau'] . ' ' . $classe['nom']); ?></p>
                    <p><span class="font-semibold">Effectif de la classe :</span> <?php echo isset($effectif) ? $effectif : '-'; ?></p>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matières</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Coef</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">I1</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">I2</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Devoir</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Compo</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appréciation</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bulletins as $matiere_id => $note): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($note['matiere_nom']); ?>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                <?php echo $note['coefficient']; ?>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center">
                                <?php echo $note['interro1'] ?? '-'; ?>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center">
                                <?php echo $note['interro2'] ?? '-'; ?>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center">
                                <?php echo $note['devoir'] ?? '-'; ?>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center">
                                <?php echo $note['compo'] ?? '-'; ?>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center font-semibold <?php echo ($note['moyenne'] ?? 0) >= 10 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $note['moyenne'] ?? '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo $note['appreciation'] ?? '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Résultats -->
        <div class="p-6 bg-gray-50 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="text-lg font-semibold mb-2">Résultats</h3>
                    <p><span class="font-semibold">Moyenne générale :</span> 
                        <span class="font-bold <?php echo $moyenne_generale >= 10 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($moyenne_generale, 2, ',', ' '); ?> / 20
                        </span>
                    </p>
                    <p><span class="font-semibold">Rang :</span> 
                        <?php echo isset($rang) ? $rang . ' / ' . $effectif : '-'; ?>
                    </p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Appréciation générale</h3>
                    <p class="italic"><?php echo nl2br(htmlspecialchars($appreciation ?? 'Aucune appréciation')); ?></p>
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="bg-gray-100 p-4 text-center text-sm text-gray-600 border-t border-gray-200">
            <div class="flex justify-between">
                <div class="text-left">
                    <p>Le Directeur</p>
                    <div class="mt-8">
                        <p class="border-t border-black w-32 mt-2"></p>
                    </div>
                </div>
                <div class="text-right">
                    <p>Fait à [Ville], le <?php echo date('d/m/Y'); ?></p>
                    <p>Le Professeur Principal</p>
                    <div class="mt-8">
                        <p class="border-t border-black w-32 ml-auto mt-2"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="mt-6 flex justify-center space-x-4 no-print">
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-print mr-2"></i> Imprimer
        </button>
        <a href="bulletins.php?classe_id=<?php echo $classe_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-arrow-left mr-2"></i> Retour
        </a>
    </div>
</body>
</html>
