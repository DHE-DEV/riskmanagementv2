<div>
    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded-lg mb-4 text-xs">
            <i class="fas fa-check-circle mr-1"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($deletingEventId)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center" wire:click.self="cancelDelete">
            <div class="bg-white rounded-lg shadow-xl p-5 max-w-md w-full mx-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xs"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">Ereignis löschen</h3>
                </div>
                <p class="text-xs text-gray-600 mb-4">Möchten Sie dieses Ereignis wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.</p>
                <div class="flex justify-end gap-2">
                    <button wire:click="cancelDelete" class="px-3 py-1.5 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Abbrechen
                    </button>
                    <button wire:click="deleteEvent" class="px-3 py-1.5 text-xs text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        <i class="fas fa-trash mr-1"></i> Löschen
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Header with Create Button --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <p class="text-xs text-gray-500">{{ $events->count() }} {{ $events->count() === 1 ? 'Ereignis' : 'Ereignisse' }} angelegt</p>
        </div>
        <button wire:click="openCreateForm" class="px-3 py-1.5 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1.5">
            <i class="fas fa-plus"></i>
            Neu
        </button>
    </div>

    {{-- Events List --}}
    @if($events->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
            <i class="fas fa-calendar-plus text-3xl text-gray-300 mb-2"></i>
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Noch keine Ereignisse</h3>
            <p class="text-xs text-gray-500 mb-3">Erstellen Sie Ihr erstes Ereignis.</p>
            <button wire:click="openCreateForm" class="px-3 py-1.5 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors inline-flex items-center gap-1.5">
                <i class="fas fa-plus"></i>
                Erstes Ereignis erstellen
            </button>
        </div>
    @else
        <div class="space-y-2">
            @foreach($events as $event)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow cursor-pointer"
                     wire:click="viewEvent({{ $event->id }})">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                <h3 class="text-xs font-semibold text-gray-900 truncate">{{ $event->title }}</h3>
                                @if($event->is_active)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">Aktiv</span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">Inaktiv</span>
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
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $priorityColors[$event->priority] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $priorityLabels[$event->priority] ?? $event->priority }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-[10px] text-gray-500">
                                @if($event->eventTypes->isNotEmpty())
                                    <div class="flex items-center gap-1 flex-wrap">
                                        @foreach($event->eventTypes as $type)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-700">
                                                {{ $type->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <span>
                                    <i class="far fa-calendar mr-0.5"></i>
                                    {{ $event->start_date?->format('d.m.Y') }}
                                    @if($event->end_date)
                                        – {{ $event->end_date->format('d.m.Y') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-0.5 flex-shrink-0" @click.stop>
                            <button wire:click="toggleActive({{ $event->id }})" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors" title="{{ $event->is_active ? 'Deaktivieren' : 'Aktivieren' }}">
                                <i class="fas {{ $event->is_active ? 'fa-toggle-on text-green-600' : 'fa-toggle-off' }} text-xs"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $event->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 transition-colors" title="Löschen">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
