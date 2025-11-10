    </div> <!-- Fin du conteneur principal -->

    <!-- Pied de page -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        
    </footer>

    <!-- Scripts -->
    <script>
        // Gestion du menu utilisateur et du menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Éléments du menu utilisateur
            const userMenuButton = document.getElementById('user-menu');
            const userMenu = document.querySelector('[role="menu"]');
            
            // Gestion du menu utilisateur
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const expanded = this.getAttribute('aria-expanded') === 'true' || false;
                    this.setAttribute('aria-expanded', !expanded);
                    userMenu.classList.toggle('hidden');
                });
            }

            // Gestion du menu mobile
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    const expanded = this.getAttribute('aria-expanded') === 'true' || false;
                    this.setAttribute('aria-expanded', !expanded);
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // Fermer les menus si on clique en dehors
            document.addEventListener('click', function(event) {
                // Fermer le menu utilisateur
                if (userMenuButton && !userMenuButton.contains(event.target) && 
                    userMenu && !userMenu.contains(event.target)) {
                    userMenuButton.setAttribute('aria-expanded', 'false');
                    userMenu.classList.add('hidden');
                }
                
                // Fermer le menu mobile si on clique sur un lien
                if (event.target.closest('a')) {
                    if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                        if (mobileMenuButton) {
                            mobileMenuButton.setAttribute('aria-expanded', 'false');
                        }
                    }
                }
            });
            
            // Fermer le menu mobile lors du redimensionnement de la fenêtre
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768 && mobileMenu && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                    if (mobileMenuButton) {
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });
    </script>
</body>
</html>
