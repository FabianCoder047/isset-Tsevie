            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Gestion du menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // Gestion des menus déroulants au clic
            const dropdownButtons = document.querySelectorAll('.group > button');
            
            dropdownButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Fermer tous les autres menus ouverts
                    dropdownButtons.forEach(btn => {
                        if (btn !== this) {
                            const otherMenu = btn.nextElementSibling;
                            if (otherMenu && otherMenu.classList.contains('dropdown-content')) {
                                otherMenu.classList.add('hidden');
                            }
                        }
                    });
                    
                    // Basculer le menu actuel
                    const menu = this.nextElementSibling;
                    if (menu && menu.classList.contains('dropdown-content')) {
                        menu.classList.toggle('hidden');
                    }
                });
            });

            // Fermer les menus quand on clique ailleurs
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.group')) {
                    document.querySelectorAll('.dropdown-content').forEach(menu => {
                        menu.classList.add('hidden');
                    });
                }
            });
        });
    </script>
</body>
</html>
