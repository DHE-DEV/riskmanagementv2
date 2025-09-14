@php
    // This view shows only the structured content without redundant text
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-2 gap-6">
        <div class="space-y-1">
            <dt class="text-sm font-medium text-gray-500">Land</dt>
            <dd class="text-sm text-gray-900">{{ $record->country_code }} - {{ $record->getCountryName() }}</dd>
        </div>
        <div class="space-y-1">
            <dt class="text-sm font-medium text-gray-500">Datum</dt>
            <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($record->tagdate)->format('d.m.Y') }}</dd>
        </div>
        <div class="space-y-1">
            <dt class="text-sm font-medium text-gray-500">Tag</dt>
            <dd class="text-sm text-gray-900">{{ $record->tagtext ?? 'Nicht angegeben' }}</dd>
        </div>
        <div class="space-y-1">
            <dt class="text-sm font-medium text-gray-500">Sprache</dt>
            <dd class="text-sm text-gray-900">{{ strtoupper($record->lang) }}</dd>
        </div>
    </div>
    
    <div class="space-y-1">
        <dt class="text-sm font-medium text-gray-500">Titel</dt>
        <dd class="text-lg font-semibold text-gray-900">{{ $record->header }}</dd>
    </div>
    
    <div class="space-y-1">
        <dt class="text-sm font-medium text-gray-500">Inhalt</dt>
        <dd class="mt-1">
            <div class="prose prose-sm max-w-none bg-gray-50 p-3 rounded-md max-h-36 overflow-y-auto text-sm text-gray-800">
                {!! nl2br(e($record->content)) !!}
            </div>
        </dd>
    </div>
    
    <div class="grid grid-cols-2 gap-6 pt-4 border-t border-gray-200">
        <div class="space-y-1">
            <dt class="text-sm font-medium text-gray-500">Status</dt>
            <dd class="mt-1">
                @if($record->active)
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-md">
                        Aktiv
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-md">
                        Inaktiv
                    </span>
                @endif
                
                @if($record->archive)
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-md ml-2">
                        Archiviert
                    </span>
                @endif
            </dd>
        </div>
        <div class="space-y-1">
            <dt class="text-sm font-medium text-gray-500">API ID</dt>
            <dd class="text-sm text-gray-900">{{ $record->api_id }}</dd>
        </div>
    </div>
</div>