{{-- Seitenspalte: Angaben zum Eintrag und Loeschen, darunter seine Aufgaben. --}}
@props(['record'])

<x-adminv2.card heading="Eintrag">
    <dl class="flex flex-col gap-2 text-sm">
        <div class="flex justify-between gap-3">
            <dt class="text-zinc-500">ID</dt>
            <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $record->getKey() }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-zinc-500">Angelegt</dt>
            <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $record->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-zinc-500">Zuletzt geändert</dt>
            <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $record->updated_at?->format('d.m.Y H:i') ?? '–' }}</dd>
        </div>
    </dl>

    {{ $slot }}

    <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
        @if ($record->trashed())
            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $record->getKey() }}, true)" class="!text-red-600">Endgültig löschen</flux:button>
        @else
            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $record->getKey() }})" class="!text-red-600">Löschen</flux:button>
        @endif
    </div>
</x-adminv2.card>

<x-adminv2.record-tasks :record="$record" />
