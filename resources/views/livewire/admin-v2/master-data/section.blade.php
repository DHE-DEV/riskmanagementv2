<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ $definition['label'] }}</flux:heading>
        <flux:subheading>Stammdaten · {{ $definition['description'] }}</flux:subheading>
    </div>

    <x-adminv2.card>
        <div class="flex flex-col items-center gap-4 px-4 py-12 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-[var(--color-accent)]/10 text-[var(--color-accent)]">
                <flux:icon.wrench-screwdriver class="size-7" />
            </span>

            <div class="max-w-xl">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">An dieser Seite wird aktuell gearbeitet</h2>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Der Bereich „{{ $definition['label'] }}“ zieht gerade in den neuen Admin-Bereich um und steht hier in Kürze zur Verfügung.
                    @if ($definition['legacy'])
                        Bis dahin lassen sich die Daten wie gewohnt im bisherigen Admin pflegen.
                    @endif
                </p>
            </div>

            @if ($definition['legacy'])
                <flux:button :href="url($definition['legacy'])" icon-trailing="arrow-up-right">
                    {{ $definition['label'] }} im bisherigen Admin öffnen
                </flux:button>
            @endif
        </div>
    </x-adminv2.card>
</div>
