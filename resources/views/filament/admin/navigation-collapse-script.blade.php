<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Funktion zum Einklappen von Navigation Groups
        function collapseNavigationGroups() {
            const groupsToCollapse = ['Geografische Daten', 'Verwaltung'];

            groupsToCollapse.forEach(groupName => {
                // Finde alle Navigation Group Buttons mit dem entsprechenden Text
                const buttons = document.querySelectorAll('button[type="button"]');

                buttons.forEach(button => {
                    // Check if button contains the group name
                    const buttonText = button.textContent.trim();
                    if (buttonText.includes(groupName)) {
                        // Check if it's a navigation group button (has chevron icon)
                        const chevronIcon = button.querySelector('svg[class*="rotate"]');
                        if (chevronIcon) {
                            // Check if group is expanded (chevron is rotated)
                            const isExpanded = chevronIcon.classList.contains('rotate-180');

                            if (isExpanded) {
                                // Klick simulieren, um die Gruppe einzuklappen
                                button.click();
                            }
                        }
                    }
                });
            });
        }

        // Führe die Funktion aus, nachdem die Seite geladen wurde
        setTimeout(collapseNavigationGroups, 100);

        // Auch nach Livewire Updates ausführen
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                setTimeout(collapseNavigationGroups, 100);
            });
        }
    });
</script>