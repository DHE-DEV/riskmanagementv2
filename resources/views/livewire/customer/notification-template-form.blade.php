<div>
    <form wire:submit.prevent="save" class="space-y-6" x-on:submit.prevent>
        {{-- Anleitung --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                <div class="flex items-center gap-2">
                    <i class="fas fa-question-circle text-blue-600"></i>
                    <span class="font-semibold text-blue-900">Anleitung: E-Mail-Vorlage gestalten</span>
                </div>
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-blue-400"></i>
            </button>
            <div x-show="open" x-transition x-cloak class="mt-4 text-sm text-blue-800 space-y-2">
                <p><i class="fas fa-info-circle mr-1"></i> Mit eigenen Vorlagen können Sie das Aussehen und den Inhalt Ihrer Benachrichtigungs-E-Mails anpassen.</p>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li><strong>Vorlagenname:</strong> Vergeben Sie einen eindeutigen Namen (z.B. "Sicherheitswarnung intern").</li>
                    <li><strong>Betreff:</strong> Verwenden Sie Platzhalter wie <code class="bg-blue-100 px-1 rounded">{event_title}</code> oder <code class="bg-blue-100 px-1 rounded">{country_name}</code> - diese werden beim Versand automatisch durch die echten Daten ersetzt.</li>
                    <li><strong>Inhalt:</strong> Gestalten Sie den E-Mail-Text mit HTML. Auch hier können Sie alle Platzhalter verwenden.</li>
                </ul>
                <div class="mt-2 p-3 bg-blue-100 rounded-lg">
                    <p><i class="fas fa-lightbulb mr-1"></i> <strong>Tipp:</strong> Die Vorschau unten zeigt Ihnen in Echtzeit, wie Ihre E-Mail aussehen wird. Nach dem Speichern können Sie mit "Test-Mail senden" eine Beispiel-E-Mail an Ihre eigene Adresse schicken.</p>
                </div>
            </div>
        </div>

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

                <div x-data="{
                    mode: 'editor',
                    html: @entangle('bodyHtml'),
                    init() {
                        this.$nextTick(() => {
                            if (this.$refs.editorContent) {
                                this.$refs.editorContent.innerHTML = this.html || '';
                            }
                        });
                        this.$watch('html', (val, oldVal) => {
                            this.$nextTick(() => {
                                if (this.$refs.editorContent && this.$refs.editorContent.innerHTML !== val) {
                                    this.$refs.editorContent.innerHTML = val || '';
                                }
                            });
                        });
                    },
                    syncFromEditor() {
                        this.html = this.$refs.editorContent.innerHTML;
                    },
                    syncFromHtml() {
                        if (this.$refs.editorContent) {
                            this.$refs.editorContent.innerHTML = this.html || '';
                        }
                    },
                    exec(cmd, val = null) {
                        document.execCommand(cmd, false, val);
                        this.$refs.editorContent.focus();
                        this.syncFromEditor();
                    },
                    insertLink() {
                        const url = prompt('URL eingeben:', 'https://');
                        if (url) this.exec('createLink', url);
                    },
                    insertPlaceholder(placeholder) {
                        this.$refs.editorContent.focus();
                        document.execCommand('insertText', false, placeholder);
                        this.syncFromEditor();
                    },
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail-Inhalt (HTML)</label>

                    {{-- Mode Toggle --}}
                    <div class="flex items-center gap-2 mb-2">
                        <button type="button" @click="mode = 'editor'; syncFromHtml()"
                                :class="mode === 'editor' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3 py-1 text-xs rounded-lg transition-colors">
                            <i class="fas fa-pen-fancy mr-1"></i> Editor
                        </button>
                        <button type="button" @click="mode = 'html'"
                                :class="mode === 'html' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3 py-1 text-xs rounded-lg transition-colors">
                            <i class="fas fa-code mr-1"></i> HTML
                        </button>
                    </div>

                    {{-- Visual Editor --}}
                    <div x-show="mode === 'editor'" x-cloak
                         x-effect="if (mode === 'editor' && $refs.editorContent && $refs.editorContent.innerHTML !== html) { $refs.editorContent.innerHTML = html || ''; }">
                        {{-- Toolbar --}}
                        <div class="flex flex-wrap items-center gap-1 p-2 bg-gray-50 border border-gray-300 rounded-t-lg border-b-0">
                            <button type="button" @click="exec('bold')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Fett"><i class="fas fa-bold fa-sm"></i></button>
                            <button type="button" @click="exec('italic')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Kursiv"><i class="fas fa-italic fa-sm"></i></button>
                            <button type="button" @click="exec('underline')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Unterstrichen"><i class="fas fa-underline fa-sm"></i></button>
                            <div class="w-px h-5 bg-gray-300 mx-1"></div>
                            <button type="button" @click="exec('formatBlock', 'h2')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 text-xs font-bold" title="Überschrift">H2</button>
                            <button type="button" @click="exec('formatBlock', 'h3')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 text-xs font-bold" title="Überschrift">H3</button>
                            <button type="button" @click="exec('formatBlock', 'p')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 text-xs" title="Absatz">P</button>
                            <div class="w-px h-5 bg-gray-300 mx-1"></div>
                            <button type="button" @click="exec('insertUnorderedList')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Aufzählung"><i class="fas fa-list-ul fa-sm"></i></button>
                            <button type="button" @click="exec('insertOrderedList')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Nummerierung"><i class="fas fa-list-ol fa-sm"></i></button>
                            <div class="w-px h-5 bg-gray-300 mx-1"></div>
                            <button type="button" @click="insertLink()" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Link einfügen"><i class="fas fa-link fa-sm"></i></button>
                            <button type="button" @click="exec('unlink')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Link entfernen"><i class="fas fa-unlink fa-sm"></i></button>
                            <button type="button" @click="exec('insertHorizontalRule')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Trennlinie"><i class="fas fa-minus fa-sm"></i></button>
                            <div class="w-px h-5 bg-gray-300 mx-1"></div>
                            <button type="button" @click="exec('removeFormat')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700" title="Formatierung entfernen"><i class="fas fa-eraser fa-sm"></i></button>

                            {{-- Placeholder Dropdown --}}
                            <div class="relative ml-auto" x-data="{ open: false }">
                                <button type="button" @click="open = !open" class="px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded hover:bg-blue-100 flex items-center gap-1">
                                    <i class="fas fa-code fa-sm"></i> Platzhalter
                                    <i class="fas fa-caret-down fa-sm"></i>
                                </button>
                                <div x-show="open" @click.outside="open = false" x-cloak
                                     class="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-1 w-56">
                                    @foreach($placeholders as $placeholder => $description)
                                        <button type="button"
                                                @click="insertPlaceholder('{{ $placeholder }}'); open = false"
                                                class="w-full text-left px-3 py-1.5 text-xs hover:bg-blue-50 flex items-center gap-2">
                                            <code class="text-blue-700 bg-blue-50 px-1 rounded">{{ $placeholder }}</code>
                                            <span class="text-gray-500">{{ $description }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Editable Area --}}
                        <div x-ref="editorContent"
                             contenteditable="true"
                             @input="syncFromEditor()"
                             @blur="syncFromEditor()"
                             class="block w-full min-h-[250px] px-4 py-3 border border-gray-300 rounded-b-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 prose prose-sm max-w-none bg-white overflow-y-auto"
                             style="max-height: 500px;"
                        ></div>
                    </div>

                    {{-- HTML Source Editor --}}
                    <div x-show="mode === 'html'" x-cloak>
                        <textarea x-model="html"
                                  rows="12"
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                  placeholder="HTML-Inhalt der E-Mail..."></textarea>
                    </div>

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
                    <div class="prose prose-sm max-w-none" style="white-space: pre-line;">
                        {!! $bodyHtml !!}
                    </div>
                </div>
            </div>
        @endif

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
                @if($templateId)
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
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('template-saved'))"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Abbrechen
                </button>
                <button type="button" wire:click="save"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Vorlage speichern
                </button>
            </div>
        </div>
    </form>
</div>
