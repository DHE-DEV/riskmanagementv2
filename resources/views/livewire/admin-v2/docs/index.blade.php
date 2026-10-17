<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Doku</flux:heading>
        <flux:subheading>Interne Anleitungen: wie Abläufe der Plattform funktionieren, wie man sie einrichtet und betreibt.</flux:subheading>
    </div>

    @foreach ($groups as $category => $docs)
        <section class="flex flex-col gap-3">
            <flux:heading size="lg" level="2">{{ $category }}</flux:heading>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($docs as $doc)
                    <a
                        href="{{ route('adminv2.docs.show', $doc['key']) }}"
                        wire:navigate
                        class="group flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs transition hover:-translate-y-0.5 hover:border-[#002742]/40 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-[#CEE741]/50"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-[#002742] text-[#CEE741]">
                                <flux:icon :name="$doc['icon']" class="size-5" />
                            </span>
                            <flux:heading size="lg" level="3">{{ $doc['title'] }}</flux:heading>
                        </div>
                        <flux:text>{{ $doc['description'] }}</flux:text>
                        <span class="mt-auto text-sm font-medium text-[#002742] group-hover:underline dark:text-[#CEE741]">Anleitung lesen →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
