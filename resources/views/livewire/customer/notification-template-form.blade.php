<div>
    <form wire:submit="save" class="space-y-6">
        {{-- Template Details --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-file-alt mr-2"></i>
                Vorlagen-Details
            </h3>

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Vorlagenname</label>
                    <input type="text" wire:model="name" id="name"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="z.B. Meine Sicherheitsvorlage">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">E-Mail-Betreff</label>
                    <input type="text" wire:model="subject" id="subject"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="z.B. Reisewarnung: {event_title} - {country_name}">
                    @error('subject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="bodyHtml" class="block text-sm font-medium text-gray-700">E-Mail-Inhalt (HTML)</label>
                    <textarea wire:model="bodyHtml" id="bodyHtml" rows="12"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                              placeholder="HTML-Inhalt der E-Mail..."></textarea>
                    @error('bodyHtml') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Placeholders Reference --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-code mr-2"></i>
                Verfügbare Platzhalter
            </h3>
            <p class="text-sm text-gray-600 mb-3">Diese Platzhalter werden beim Versand automatisch durch die echten Werte ersetzt:</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach($placeholders as $placeholder => $description)
                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <code class="text-sm font-mono text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $placeholder }}</code>
                        <span class="text-sm text-gray-600">{{ $description }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Preview --}}
        @if($bodyHtml)
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-eye mr-2"></i>
                    Vorschau
                </h3>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="mb-3 pb-3 border-b border-gray-200">
                        <span class="text-sm text-gray-500">Betreff:</span>
                        <span class="text-sm font-medium">{{ $subject }}</span>
                    </div>
                    <div class="prose prose-sm max-w-none">
                        {!! $bodyHtml !!}
                    </div>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <div>
                @if($templateId)
                    <button type="button" wire:click="deleteTemplate"
                            wire:confirm="Möchten Sie diese Vorlage wirklich löschen?"
                            class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Vorlage löschen
                    </button>
                @endif
            </div>
            <div class="flex gap-3">
                <a href="{{ route('customer.notification-settings.templates.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Abbrechen
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="fas fa-save mr-2"></i>
                        Vorlage speichern
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
