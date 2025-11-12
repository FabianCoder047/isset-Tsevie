document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour enregistrer le fichier avec File System Access API
    async function saveFile(blob, suggestedName) {
        try {
            // Vérifier si l'API est disponible
            if ('showSaveFilePicker' in window) {
                const options = {
                    suggestedName: suggestedName,
                    types: [{
                        description: 'Fichier ZIP',
                        accept: { 'application/zip': ['.zip'] },
                    }],
                };
                
                const fileHandle = await window.showSaveFilePicker(options);
                const writable = await fileHandle.createWritable();
                await writable.write(blob);
                await writable.close();
                return true;
            } else {
                // Fallback pour les navigateurs qui ne supportent pas l'API
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = suggestedName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                return true;
            }
        } catch (err) {
            console.error('Erreur lors de l\'enregistrement du fichier:', err);
            return false;
        }
    }

    // Gestionnaire pour le bouton d'exportation de la classe entière
    const exportClasseBtn = document.getElementById('export-classe-pdf');
    if (exportClasseBtn) {
        exportClasseBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            const classeId = this.getAttribute('data-classe');
            const periodeId = this.getAttribute('data-periode');
            const classeNom = this.getAttribute('data-classe-nom') || 'classe';
            const filename = 'Bulletins_' + classeNom.replace(/\s+/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.zip';
            
            if (confirm('Voulez-vous exporter les bulletins de toute la classe ? Vous pourrez choisir où enregistrer le fichier ZIP.')) {
                // Afficher un indicateur de chargement
                const originalText = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Génération en cours...';
                
                try {
                    // Envoyer une requête pour générer le ZIP
                    const response = await fetch('generer_bulletin_pdf.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'export': 'classe',
                            'classe_id': classeId,
                            'periode_id': periodeId,
                            'generate_zip': '1'
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error('Erreur lors de la génération des bulletins');
                    }
                    
                    // Récupérer le fichier ZIP
                    const blob = await response.blob();
                    
                    // Proposer à l'utilisateur de choisir l'emplacement
                    const saved = await saveFile(blob, filename);
                    
                    if (saved) {
                        alert('Les bulletins ont été sauvegardés avec succès !');
                    } else {
                        throw new Error('Impossible de sauvegarder le fichier');
                    }
                    
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue : ' + error.message);
                } finally {
                    // Réactiver le bouton dans tous les cas
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            }
        });
    }
});
