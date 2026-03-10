<div>
    <form wire:submit="save" class="space-y-6">
        {{-- Anleitung --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                <div class="flex items-center gap-2">
                    <i class="fas fa-question-circle text-blue-600"></i>
                    <span class="font-semibold text-blue-900">Anleitung: Benachrichtigungsregel einrichten</span>
                </div>
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-blue-400"></i>
            </button>
            <div x-show="open" x-collapse class="mt-4 text-sm text-blue-800 space-y-2">
                <p><i class="fas fa-info-circle mr-1"></i> Mit einer Regel legen Sie fest, wann und an wen Benachrichtigungen versendet werden.</p>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li><strong>Regelname:</strong> Geben Sie einen aussagekräftigen Namen ein (z.B. "Sicherheitswarnungen Europa").</li>
                    <li><strong>Bedingungen:</strong> Wählen Sie aus, bei welchen Ereignissen Sie informiert werden möchten. Lassen Sie ein Feld leer, um bei <em>allen</em> Ereignissen dieses Typs benachrichtigt zu werden.</li>
                    <li><strong>Empfänger:</strong> Tragen Sie mindestens eine E-Mail-Adresse ein. TO = Hauptempfänger, CC = Kopie (sichtbar), BCC = Blindkopie (unsichtbar für andere).</li>
                    <li><strong>Vorlage:</strong> Wählen Sie optional eine eigene E-Mail-Vorlage, oder nutzen Sie die Standard-Vorlage.</li>
                </ul>
                <div class="mt-2 p-3 bg-blue-100 rounded-lg">
                    <p><i class="fas fa-lightbulb mr-1"></i> <strong>Tipp:</strong> Nutzen Sie den Button "Test-Mail senden" (nach dem Speichern verfügbar), um die Regel mit einer Beispiel-E-Mail zu testen.</p>
                </div>
            </div>
        </div>

        {{-- Name & Active Toggle --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Regelname</label>
                    <input type="text" wire:model="name" id="name"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="z.B. Sicherheitswarnungen DE">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label for="isActive" class="block text-sm font-medium text-gray-700">Regel aktiv</label>
                        <p class="text-sm text-gray-500">Deaktivierte Regeln senden keine Benachrichtigungen.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="isActive" id="isActive" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Conditions --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-filter mr-2"></i>
                Bedingungen
            </h3>
            <p class="text-sm text-gray-500 mb-4">Bestimmen Sie, bei welchen Ereignissen diese Regel greifen soll. Wenn Sie keine Auswahl treffen, gilt die Regel für alle Ereignisse.</p>

            {{-- Risk Levels --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Risikostufen</label>
                <div class="flex flex-wrap gap-3">
                    @foreach($availableRiskLevels as $value => $label)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="riskLevels" value="{{ $value }}"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-1">Leer = alle Risikostufen</p>
            </div>

            {{-- Categories --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategorien</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($availableCategories as $value => $label)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="categories" value="{{ $value }}"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-1">Leer = alle Kategorien</p>
            </div>

            {{-- Country Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Länder</label>
                <p class="text-xs text-gray-500 mb-2">Leer = alle Länder</p>

                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="countrySearch"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Land suchen...">

                    @if(count($countryResults) > 0)
                        <div class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            @foreach($countryResults as $country)
                                <button type="button"
                                        wire:click="addCountry({{ $country['id'] }}, '{{ addslashes($country['name']) }}')"
                                        class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm">
                                    {{ $country['name'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if(count($selectedCountries) > 0)
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($selectedCountries as $country)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                {{ $country['name'] }}
                                <button type="button" wire:click="removeCountry({{ $country['id'] }})"
                                        class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recipients --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-envelope mr-2"></i>
                    E-Mail-Empfänger
                </h3>
                <button type="button" wire:click="addRecipient"
                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-1"></i>
                    Empfänger hinzufügen
                </button>
            </div>

            <p class="text-sm text-gray-500 mb-2">TO = Hauptempfänger, CC = Kopie (für andere sichtbar), BCC = Blindkopie (für andere nicht sichtbar).</p>

            <div class="space-y-3">
                @foreach($recipients as $index => $recipient)
                    <div class="flex gap-2 items-start" wire:key="recipient-{{ $index }}">
                        <select wire:model="recipients.{{ $index }}.type"
                                class="w-24 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="to">TO</option>
                            <option value="cc">CC</option>
                            <option value="bcc">BCC</option>
                        </select>
                        <div class="flex-1">
                            <input type="email" wire:model="recipients.{{ $index }}.email"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="empfaenger@beispiel.de">
                            @error("recipients.{$index}.email") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @if(count($recipients) > 1)
                            <button type="button" wire:click="removeRecipient({{ $index }})"
                                    class="px-2 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Template Selection --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-file-alt mr-2"></i>
                E-Mail-Vorlage
            </h3>

            <select wire:model="notificationTemplateId"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Standard-Vorlage verwenden</option>
                @foreach($templates as $template)
                    <option value="{{ $template->id }}">
                        {{ $template->name }}
                        @if($template->is_system) (System) @endif
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Test Mail Status --}}
        @if($testMailStatus)
            @php
                $statusParts = explode(':', $testMailStatus, 2);
                $statusType = $statusParts[0];
                $statusMessage = $statusParts[1] ?? '';
            @endphp
            <div class="p-4 rounded-lg {{ $statusType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
                <i class="fas {{ $statusType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-2"></i>
                {{ $statusMessage }}
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <div class="flex gap-3">
                @if($ruleId)
                    <button type="button" wire:click="deleteRule"
                            wire:confirm="Möchten Sie diese Regel wirklich löschen?"
                            class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Regel löschen
                    </button>
                @endif
            </div>
            <div class="flex gap-3">
                @if($ruleId)
                    <button type="button" wire:click="sendTestMail"
                            class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="sendTestMail">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Test-Mail senden
                        </span>
                        <span wire:loading wire:target="sendTestMail">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Sende...
                        </span>
                    </button>
                @endif
                <a href="{{ route('customer.notification-settings.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Abbrechen
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="fas fa-save mr-2"></i>
                        Regel speichern
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Speichern...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
