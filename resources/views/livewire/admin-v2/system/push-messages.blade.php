@php
    use App\Livewire\AdminV2\System\PushMessages;
    use App\Models\Push\AppPushMessage;

    $parsed = $this->parsedTids;
@endphp

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Push senden</flux:heading>
        <flux:subheading>System · Eigene Benachrichtigung an die TripData-App – an alle Geräte mit einer bestimmten Reise oder an alle Geräte.</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Empfaenger --}}
        <section class="flex flex-col gap-5 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <flux:heading size="lg" level="2">Empfänger</flux:heading>

            <flux:radio.group wire:model.live="target" variant="segmented">
                <flux:radio value="{{ PushMessages::TARGET_TIDS }}" label="Geräte mit diesen Reisen" />
                <flux:radio value="{{ PushMessages::TARGET_ALL }}" label="Alle Geräte" />
            </flux:radio.group>

            @if ($target === PushMessages::TARGET_TIDS)
                <flux:textarea
                    wire:model.live.debounce.400ms="tidsInput"
                    label="Reise-IDs"
                    description="Reise-IDs von Travel Detail Links, z. B. ZIPT-HRG1-Z6Y5 – mehrere durch Zeilen, Kommas oder Leerzeichen getrennt. Ganze Links (…?tid=… oder …/reise/…) gehen auch."
                    placeholder="ZIPT-HRG1-Z6Y5"
                    rows="4"
                    class="font-mono"
                />
                @if ($parsed['valid'] !== [])
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (array_slice($parsed['valid'], 0, 30) as $tid)
                            <flux:badge size="sm" color="zinc" class="font-mono">{{ implode('-', str_split($tid, 4)) }}</flux:badge>
                        @endforeach
                        @if (count($parsed['valid']) > 30)
                            <flux:badge size="sm" color="zinc">+ {{ count($parsed['valid']) - 30 }} weitere</flux:badge>
                        @endif
                    </div>
                @endif
                @if ($parsed['invalid'] !== [])
                    <flux:callout variant="warning" icon="exclamation-triangle" heading="Keine gültige Reise-ID (wird ignoriert): {{ implode(', ', array_slice($parsed['invalid'], 0, 10)) }}" />
                @endif
                @error('tidsInput')
                    <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
                @enderror
            @else
                <flux:callout variant="warning" icon="megaphone">
                    <flux:callout.heading>Geht an alle erreichbaren Geräte der TripData-App</flux:callout.heading>
                    <flux:callout.text>Nur für Hinweise, die wirklich alle betreffen. Die Mitteilung zählt in der App bei keiner Reise.</flux:callout.text>
                </flux:callout>
                <flux:checkbox wire:model.live="confirmAll" label="Ja, an alle Geräte senden" />
                @error('confirmAll')
                    <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
                @enderror
            @endif

            <div class="flex items-center gap-3 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900" wire:loading.class="opacity-60" wire:target="tidsInput, target">
                <span class="flex size-10 items-center justify-center rounded-xl bg-[#002742] text-[#CEE741]">
                    <flux:icon.device-phone-mobile class="size-5" />
                </span>
                <div>
                    <div class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->reach, 0, ',', '.') }}</div>
                    <div class="text-sm text-zinc-500">{{ $this->reach === 1 ? 'Gerät wird erreicht' : 'Geräte werden erreicht' }}</div>
                </div>
            </div>
            <flux:text class="text-xs">Gezählt werden Geräte mit erlaubten Mitteilungen und gültigem Push-Token, die sich in den letzten 180 Tagen gemeldet haben.</flux:text>
            @error('target')
                <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
            @enderror
        </section>

        {{-- Inhalt --}}
        <section class="flex flex-col gap-5 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <flux:heading size="lg" level="2">Inhalt</flux:heading>

            <div x-data="{ lang: 'de' }" class="flex flex-col gap-4">
                <div class="inline-flex w-fit rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800" role="tablist">
                    @foreach (PushMessages::LANGUAGES as $lang => $label)
                        <button
                            type="button"
                            role="tab"
                            x-on:click="lang = '{{ $lang }}'"
                            :aria-selected="lang === '{{ $lang }}'"
                            :class="lang === '{{ $lang }}' ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'"
                            class="rounded-md px-3 py-1.5 text-sm font-medium"
                        >
                            {{ $label }}{{ $lang === 'de' ? ' *' : '' }}
                            @if ($lang !== 'de' && trim($title[$lang] ?? '') !== '' && trim($body[$lang] ?? '') !== '')
                                <span class="ms-1 text-green-600">✓</span>
                            @endif
                        </button>
                    @endforeach
                </div>
                @foreach (PushMessages::LANGUAGES as $lang => $label)
                    <div x-show="lang === '{{ $lang }}'" @if ($lang !== 'de') x-cloak @endif class="flex flex-col gap-4">
                        <flux:input wire:model.live.debounce.300ms="title.{{ $lang }}" label="Titel ({{ $label }})" maxlength="120" placeholder="{{ $lang === 'de' ? 'Hinweis zu deiner Reise' : '' }}" />
                        <flux:textarea wire:model.live.debounce.300ms="body.{{ $lang }}" label="Text ({{ $label }})" maxlength="500" rows="4" placeholder="{{ $lang === 'de' ? 'Der Transfer startet heute 30 Minuten später.' : '' }}" />
                    </div>
                @endforeach
            </div>
            <flux:text class="text-xs">Deutsch ist Pflicht. Englisch und Niederländisch sind optional – Geräte in einer Sprache ohne Text bekommen die deutsche Fassung. Titel höchstens 120, Text höchstens 500 Zeichen.</flux:text>

            {{-- Vorschau wie auf dem iPhone --}}
            <div class="rounded-2xl bg-zinc-100 p-4 dark:bg-zinc-900">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Vorschau</div>
                <div class="mt-2 flex gap-3 rounded-2xl bg-white/90 p-3 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                    <img src="{{ asset('logo.png') }}" alt="" class="size-9 shrink-0 rounded-lg bg-white object-contain p-1 ring-1 ring-zinc-200" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ trim($title['de']) !== '' ? $title['de'] : 'Titel' }}</span>
                            <span class="shrink-0 text-xs text-zinc-400">jetzt</span>
                        </div>
                        <p class="line-clamp-4 text-sm text-zinc-700 dark:text-zinc-300">{{ trim($body['de']) !== '' ? $body['de'] : 'Text der Benachrichtigung' }}</p>
                    </div>
                </div>
            </div>

            @error('title.de') <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" /> @enderror
            @error('body.de') <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" /> @enderror

            <flux:modal.trigger name="push-send-confirm">
                <flux:button variant="primary" icon="paper-airplane" :disabled="$this->reach === 0">
                    {{ $this->reach === 1 ? 'An 1 Gerät senden …' : 'An '.number_format($this->reach, 0, ',', '.').' Geräte senden …' }}
                </flux:button>
            </flux:modal.trigger>
        </section>
    </div>

    <flux:modal name="push-send-confirm" class="md:w-[28rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Push jetzt senden?</flux:heading>
                <flux:text class="mt-2">
                    „{{ $title['de'] }}“ geht an {{ $this->reach === 1 ? '1 Gerät' : number_format($this->reach, 0, ',', '.').' Geräte' }}{{ $target === PushMessages::TARGET_ALL ? ' – alle erreichbaren Geräte der App' : '' }}. Gesendete Benachrichtigungen lassen sich nicht zurückholen.
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" icon="paper-airplane" wire:click="send" x-on:click="$flux.modal('push-send-confirm').close()">Senden</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Verlauf --}}
    <section class="flex flex-col gap-3" wire:poll.5s>
        <flux:heading size="lg" level="2">Letzte Versände</flux:heading>
        <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3">Zeit</th>
                        <th class="px-4 py-3">Quelle</th>
                        <th class="px-4 py-3">Inhalt</th>
                        <th class="px-4 py-3">Ziel</th>
                        <th class="px-4 py-3 text-right">Geräte</th>
                        <th class="px-4 py-3 text-right">Gesendet</th>
                        <th class="px-4 py-3 text-right">Fehler</th>
                        <th class="px-4 py-3 text-right">Ungültig</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->messages as $message)
                        @php
                            $targetInfo = (array) $message->target;
                            $tids = (array) ($targetInfo['tids'] ?? []);
                        @endphp
                        <tr wire:key="push-{{ $message->id }}" class="align-top">
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $message->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @switch($message->source)
                                    @case(AppPushMessage::SOURCE_SCANNER)
                                        <flux:badge size="sm" color="sky">Reise-Änderung</flux:badge>
                                        @break
                                    @case(AppPushMessage::SOURCE_SHARING)
                                        <flux:badge size="sm" color="emerald">Teilen</flux:badge>
                                        @break
                                    @case(AppPushMessage::SOURCE_TRANSFER)
                                        <flux:badge size="sm" color="amber">Geräteübertragung</flux:badge>
                                        @break
                                    @case(AppPushMessage::SOURCE_ADMIN)
                                        <flux:badge size="sm" color="violet">{{ $message->user?->name ?? 'Admin' }}</flux:badge>
                                        @break
                                    @default
                                        <flux:badge size="sm" color="zinc">API</flux:badge>
                                @endswitch
                            </td>
                            <td class="max-w-md px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $message->text('title', 'de') }}</div>
                                <div class="line-clamp-2 text-zinc-500">{{ $message->text('body', 'de') }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                @if (! empty($targetInfo['all']))
                                    Alle Geräte
                                @elseif (! empty($targetInfo['device_ids']))
                                    {{ count((array) $targetInfo['device_ids']) }} {{ count((array) $targetInfo['device_ids']) === 1 ? 'Gerät' : 'Geräte' }}
                                @elseif (is_array($targetInfo['tids'] ?? null))
                                    <span class="font-mono text-xs">{{ implode(', ', array_map(fn ($t) => implode('-', str_split((string) $t, 4)), array_slice($tids, 0, 3))) }}</span>
                                    @if (count($tids) > 3) <span class="text-xs">+ {{ count($tids) - 3 }}</span> @endif
                                @elseif (isset($targetInfo['tids']))
                                    {{ $targetInfo['tids'] }} {{ $targetInfo['tids'] === 1 ? 'Reise' : 'Reisen' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($message->targeted, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-green-700 dark:text-green-400">{{ number_format($message->sent, 0, ',', '.') }}</td>
                            <td @class(['px-4 py-3 text-right tabular-nums', 'text-red-600' => $message->failed > 0])>{{ number_format($message->failed, 0, ',', '.') }}</td>
                            <td @class(['px-4 py-3 text-right tabular-nums', 'text-amber-600' => $message->invalid > 0])>{{ number_format($message->invalid, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($message->status === AppPushMessage::STATUS_DONE)
                                    <flux:badge size="sm" color="green" icon="check">fertig</flux:badge>
                                @else
                                    <flux:badge size="sm" color="amber" icon="arrow-path">wird gesendet</flux:badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-zinc-500">Noch keine Push-Benachrichtigungen verschickt.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <flux:text class="text-xs">„Ungültig“: Apple bzw. Google kennen den Push-Token nicht mehr (App gelöscht) – solche Geräte bekommen keine weiteren Pushs. Die Liste aktualisiert sich alle 5 Sekunden.</flux:text>
    </section>
</div>
