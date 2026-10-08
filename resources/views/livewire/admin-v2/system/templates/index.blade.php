@php
    use App\Livewire\AdminV2\System\Templates\Index;
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Vorlagen</flux:heading>
            <flux:subheading>System · Standard-Vorlagen der Benachrichtigungs-E-Mails. Änderungen wirken sich auf alle Kunden aus, die die Standardvorlage verwenden.</flux:subheading>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($this->templates as $template)
            @php
                [$sourceLabel, $sourceColor] = Index::sourceLabel($template->source);
                $editUrl = route('adminv2.system.templates.edit', $template);
                $excerpt = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['</p>', '</div>', '<br>', '<br/>', '<br />'], ' ', (string) $template->body_html)))), 220);
            @endphp
            <article wire:key="template-{{ $template->id }}" class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                            <a href="{{ $editUrl }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $template->name }}</a>
                        </h2>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" :color="$sourceColor" inset="top bottom">{{ $sourceLabel }}</flux:badge>
                        </div>
                    </div>

                    <div class="relative z-10 shrink-0">
                        <flux:button size="sm" icon="pencil-square" :href="$editUrl">Bearbeiten</flux:button>
                    </div>
                </div>

                <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                    <x-adminv2.master-data.card-row icon="envelope" label="Betreff">
                        <span class="text-zinc-900 dark:text-white">{{ $template->subject }}</span>
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="document-text" label="Inhalt">
                        <span class="line-clamp-3">{{ $excerpt ?: '–' }}</span>
                    </x-adminv2.master-data.card-row>
                </dl>

                <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                    <span class="tabular-nums">zuletzt geändert {{ $template->updated_at?->format('d.m.Y H:i') ?? '–' }}</span>
                    <span class="tabular-nums">{{ $template->notification_rules_count === 1 ? 'von 1 Regel genutzt' : 'von '.$template->notification_rules_count.' Regeln genutzt' }}</span>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.envelope class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Standard-Vorlagen</p>
                    <p class="text-sm text-zinc-500">Die Standard-Vorlagen werden mit den Migrationen angelegt.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
