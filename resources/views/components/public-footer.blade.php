<footer class="footer">
    <div class="flex items-center justify-between px-4 h-full">
        <div class="flex items-center space-x-6 text-sm">
            <span>© 2025 Passolution GmbH</span>
            <a href="https://www.passolution.de/impressum/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">Impressum</a>
            <a href="https://www.passolution.de/datenschutz/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">Datenschutz</a>
            <a href="https://www.passolution.de/agb/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">AGB</a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('disclaimerModal').classList.remove('hidden');" class="hover:text-blue-300 transition-colors">Haftungsausschluss</a>
        </div>
        <div class="flex items-center space-x-4 text-sm">
            <span>Version 1.0.19</span>
            <span>Build: 2025-11-04</span>
        </div>
    </div>
</footer>

<!-- Haftungsausschluss Modal -->
<div id="disclaimerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Haftungsausschluss</h2>
                <button onclick="document.getElementById('disclaimerModal').classList.add('hidden');" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-4 text-gray-700">
                <p>
                    Die auf global-travel-monitor.eu bereitgestellten Informationen werden mit größter Sorgfalt recherchiert und regelmäßig aktualisiert. Dennoch kann keine Gewähr für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte übernommen werden. Alle Angaben erfolgen ohne Gewähr. Eine Haftung, insbesondere für eventuelle Schäden oder Konsequenzen, die durch die Nutzung der angebotenen Informationen entstehen, ist ausgeschlossen.
                </p>
                <p>
                    Das Portal kann Verlinkungen zu externen Webseiten Dritter enthalten, auf deren Inhalte kein Einfluss besteht. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber verantwortlich.
                </p>
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="document.getElementById('disclaimerModal').classList.add('hidden');" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Schließen
                </button>
            </div>
        </div>
    </div>
</div>
