<div>
    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($deletingEventId)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center" wire:click.self="cancelDelete">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Ereignis löschen</h3>
                </div>
                <p class="text-sm text-gray-600 mb-6">Möchten Sie dieses Ereignis wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Abbrechen
                    </button>
                    <button wire:click="deleteEvent" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        <i class="fas fa-trash mr-1"></i> Löschen
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    @if($showForm)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas {{ $editingEventId ? 'fa-pen' : 'fa-plus' }} mr-2"></i>
                {{ $editingEventId ? 'Ereignis bearbeiten' : 'Neues Ereignis erstellen' }}
            </h2>

            <form wire:submit="save">
                <div class="space-y-4">
                    {{-- Titel --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titel <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Titel des Ereignisses">
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Beschreibung --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                        <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Beschreibung des Ereignisses"></textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Event-Kategorie --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategorie <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($eventTypes as $type)
                                <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($type->id, $selectedEventTypes) ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                    <input type="checkbox" wire:model="selectedEventTypes" value="{{ $type->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm">{{ $type->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedEventTypes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Risikostufe --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Risikostufe <span class="text-red-500">*</span></label>
                        <select wire:model="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="info">Information</option>
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                        </select>
                        @error('priority') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Datum --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Startdatum <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="startDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('startDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Enddatum</label>
                            <input type="date" wire:model="endDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('endDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Aktiv/Inaktiv --}}
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="isActive" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                        <span class="text-sm font-medium text-gray-700">Ereignis ist aktiv</span>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <button type="button" wire:click="cancelForm" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Abbrechen
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                        <i class="fas fa-save mr-1"></i>
                        {{ $editingEventId ? 'Aktualisieren' : 'Erstellen' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Header with Create Button --}}
    @if(!$showForm)
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-sm text-gray-500 mt-1">{{ $events->count() }} {{ $events->count() === 1 ? 'Ereignis' : 'Ereignisse' }} angelegt</p>
            </div>
            <button wire:click="openCreateForm" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Neues Ereignis
            </button>
        </div>
    @endif

    {{-- Events List --}}
    @if($events->isEmpty() && !$showForm)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <i class="fas fa-calendar-plus text-4xl text-gray-300 mb-3"></i>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">Noch keine Ereignisse</h3>
            <p class="text-sm text-gray-500 mb-4">Erstellen Sie Ihr erstes Ereignis, um es hier zu sehen.</p>
            <button wire:click="openCreateForm" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors inline-flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Erstes Ereignis erstellen
            </button>
        </div>
    @else
        <div class="space-y-3">
            @foreach($events as $event)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $event->title }}</h3>
                                @if($event->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktiv</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inaktiv</span>
                                @endif
                                @php
                                    $priorityColors = [
                                        'info' => 'bg-blue-100 text-blue-800',
                                        'low' => 'bg-yellow-100 text-yellow-800',
                                        'medium' => 'bg-orange-100 text-orange-800',
                                        'high' => 'bg-red-100 text-red-800',
                                    ];
                                    $priorityLabels = [
                                        'info' => 'Information',
                                        'low' => 'Niedrig',
                                        'medium' => 'Mittel',
                                        'high' => 'Hoch',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityColors[$event->priority] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $priorityLabels[$event->priority] ?? $event->priority }}
                                </span>
                            </div>

                            @if($event->description)
                                <p class="text-xs text-gray-500 mb-2 line-clamp-2">{{ Str::limit(strip_tags($event->description), 150) }}</p>
                            @endif

                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                {{-- Event Types --}}
                                @if($event->eventTypes->isNotEmpty())
                                    <div class="flex items-center gap-1">
                                        @foreach($event->eventTypes as $type)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-700">
                                                {{ $type->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Dates --}}
                                <span>
                                    <i class="far fa-calendar mr-1"></i>
                                    {{ $event->start_date?->format('d.m.Y') }}
                                    @if($event->end_date)
                                        – {{ $event->end_date->format('d.m.Y') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button wire:click="toggleActive({{ $event->id }})" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors" title="{{ $event->is_active ? 'Deaktivieren' : 'Aktivieren' }}">
                                <i class="fas {{ $event->is_active ? 'fa-toggle-on text-green-600' : 'fa-toggle-off' }}"></i>
                            </button>
                            <button wire:click="openEditForm({{ $event->id }})" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition-colors" title="Bearbeiten">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $event->id }})" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 transition-colors" title="Löschen">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
