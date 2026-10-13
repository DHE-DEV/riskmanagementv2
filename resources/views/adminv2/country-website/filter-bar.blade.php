{{-- Filterleiste einer Liste (Regionen, Städte, Sehenswert); das Filtern macht das Skript der Seite. --}}
<div data-filter-bar="{{ $target }}" class="mb-5 flex flex-wrap items-center gap-2">
    <div class="flex rounded-full bg-white p-1 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
        @foreach ($filters as $mode => $label)
            <button type="button" data-mode="{{ $mode }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                    class="rounded-full px-3 py-1.5 text-sm font-medium text-zinc-600 aria-pressed:bg-[#0b3250] aria-pressed:text-white dark:text-zinc-400 dark:aria-pressed:bg-lime-300 dark:aria-pressed:text-[#0b3250]">{{ $label }}</button>
        @endforeach
    </div>
    @if (! empty($regionOptions) && count($regionOptions) > 1)
        <select data-region-select class="rounded-full border-0 bg-white py-2 ps-4 pe-9 text-sm shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
            <option value="">Alle Regionen</option>
            @foreach ($regionOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
        </select>
    @endif
    @if (! empty($categoryOptions) && count($categoryOptions) > 1)
        <select data-category-select class="rounded-full border-0 bg-white py-2 ps-4 pe-9 text-sm shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
            <option value="">Alle Kategorien</option>
            @foreach ($categoryOptions as $key => $name)<option value="{{ $key }}">{{ $name }}</option>@endforeach
        </select>
    @endif
    <label class="relative ms-auto">
        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-xs text-zinc-400"></i>
        <input type="search" data-search-input placeholder="Suchen…" class="w-48 rounded-full border-0 bg-white py-2 ps-8 pe-3 text-sm shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
    </label>
    <span class="text-xs text-zinc-500"><span data-count></span> Einträge</span>
</div>
