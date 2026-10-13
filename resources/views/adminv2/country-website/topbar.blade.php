{{-- Leiste oben: zurück, Sprache. Erwartet $backUrl, $backLabel, $languages, $lang. --}}
<div class="sticky top-0 z-30 border-b border-zinc-200/70 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-2 text-sm">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
            <i class="fa-solid fa-arrow-left"></i> {{ $backLabel }}
        </a>
        <span class="hidden text-xs uppercase tracking-widest text-zinc-400 sm:block">Website-Ansicht · wie in TripData</span>
        <div class="flex items-center gap-1 rounded-full bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach ($languages as $code => $label)
                <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" title="{{ $label }}"
                   class="rounded-full px-3 py-1 text-xs font-semibold {{ $lang === $code ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">{{ strtoupper($code) }}</a>
            @endforeach
        </div>
    </div>
</div>
