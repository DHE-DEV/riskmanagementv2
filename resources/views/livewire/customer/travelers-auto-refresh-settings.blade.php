<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-sync-alt mr-2"></i>
        Automatische Aktualisierung - Meine Reisenden
    </h3>

    <form wire:submit="save" class="space-y-4">
        <!-- Auto-Refresh Toggle -->
        <div class="flex items-center justify-between">
            <div>
                <label for="autoRefresh" class="block text-sm font-medium text-gray-700">
                    Automatische Aktualisierung aktivieren
                </label>
                <p class="text-sm text-gray-500 mt-1">
                    Die Liste wird automatisch aktualisiert, wenn neue oder geänderte Reisen importiert werden.
                </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input
                    type="checkbox"
                    wire:model="autoRefresh"
                    id="autoRefresh"
                    class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- Refresh Interval -->
        <div x-show="$wire.autoRefresh">
            <label for="refreshInterval" class="block text-sm font-medium text-gray-700">
                Aktualisierungs-Intervall (in Sekunden)
            </label>
            <div class="mt-2 flex items-center gap-4">
                <input
                    type="range"
                    wire:model.live="refreshInterval"
                    id="refreshInterval"
                    min="10"
                    max="300"
                    step="10"
                    class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                >
                <span class="text-sm font-medium text-gray-700 w-20 text-right">
                    {{ $refreshInterval }}s
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Empfohlen: 30-60 Sekunden. Niedrigere Werte erhöhen die Serverlast.
            </p>
            @error('refreshInterval')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Save Button -->
        <div class="flex items-center justify-between pt-4 border-t">
            <p class="text-xs text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Änderungen werden sofort auf der "Meine Reisenden" Seite wirksam.
            </p>
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="save">
                    <i class="fas fa-save mr-2"></i>
                    Speichern
                </span>
                <span wire:loading wire:target="save">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Speichern...
                </span>
            </button>
        </div>
    </form>

    <!-- Success Message -->
    <div
        x-data="{ show: false }"
        x-on:notify.window="show = true; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition
        class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg"
        style="display: none;"
    >
        <i class="fas fa-check-circle mr-2"></i>
        Einstellungen erfolgreich gespeichert!
    </div>
</div>
