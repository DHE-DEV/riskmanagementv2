{{-- Sortierbarer Spaltenkopf einer Liste auf der Kundenseite: method = Livewire-Methode, column = Spalte. --}}
<th scope="col" class="px-4 py-2.5 text-start font-medium" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <button type="button" wire:click="{{ $method }}('{{ $column }}')" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
        {{ $label }}
        @if ($sort === $column)
            <flux:icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" />
        @endif
    </button>
</th>
