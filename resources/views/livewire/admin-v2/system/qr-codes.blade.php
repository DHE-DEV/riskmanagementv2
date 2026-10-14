@php
    use App\Livewire\AdminV2\System\QrCodes;
@endphp

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">QR-Codes</flux:heading>
        <flux:subheading>System · QR-Code erzeugen, anzeigen und als PDF herunterladen – z. B. für die Reise-ID eines Travel Detail Links.</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="flex flex-col gap-5 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <flux:radio.group wire:model.live="type" label="Art" variant="segmented">
                <flux:radio value="{{ QrCodes::TYPE_TRIPDATA }}" label="TripData-Link" />
                <flux:radio value="{{ QrCodes::TYPE_TRIPDATA_APP }}" label="App-Direktlink" />
                <flux:radio value="{{ QrCodes::TYPE_TRAVEL_DETAILS }}" label="Travel Details" />
                <flux:radio value="{{ QrCodes::TYPE_FREE }}" label="Freier Inhalt" />
            </flux:radio.group>

            @if ($type !== QrCodes::TYPE_FREE)
                <flux:input
                    wire:model.live.debounce.300ms="value"
                    label="Reise-ID"
                    description="12 Zeichen, mit oder ohne Bindestriche. Ein ganzer Link mit „?tid=…“ geht auch."
                    placeholder="ZIPT-HRG1-Z6Y5"
                    class="font-mono"
                    autofocus
                />
                @if ($type === QrCodes::TYPE_TRIPDATA)
                    <flux:callout icon="device-phone-mobile" variant="secondary">
                        <flux:callout.text>Mit installierter TripData-App öffnet die Handy-Kamera die Reise direkt in der App – ist sie dort noch nicht gespeichert, fragt die App, ob sie angelegt werden soll. Ohne App führt der Link zur Reise auf travel-details.eu.</flux:callout.text>
                    </flux:callout>
                @elseif ($type === QrCodes::TYPE_TRIPDATA_APP)
                    <flux:callout icon="device-phone-mobile" variant="secondary">
                        <flux:callout.text>Öffnet die TripData-App direkt über ihr eigenes Link-Format (tripdata://) – funktioniert ohne Server, aber nur mit installierter App: Ohne App passiert beim Scannen nichts.</flux:callout.text>
                    </flux:callout>
                @else
                    <flux:select wire:model.live="language" label="Sprache der Reisenden-Seite">
                        <flux:select.option value="de">Deutsch</flux:select.option>
                        <flux:select.option value="en">Englisch</flux:select.option>
                        <flux:select.option value="nl">Niederländisch</flux:select.option>
                    </flux:select>
                @endif
            @else
                <flux:textarea
                    wire:model.live.debounce.300ms="value"
                    label="Text oder Link"
                    description="Wird unverändert in den QR-Code übernommen."
                    placeholder="https://…"
                    rows="4"
                />
            @endif

            @if ($this->inputError)
                <flux:callout variant="warning" icon="exclamation-triangle" :heading="$this->inputError" />
            @endif
            @error('value')
                <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
            @enderror

            <flux:input
                wire:model.live.debounce.300ms="title"
                label="Überschrift im PDF (optional)"
                :placeholder="$this->travelCode ? 'Reise '.$this->travelCode : 'QR-Code'"
            />

            @if ($this->content)
                <div class="rounded-xl bg-zinc-50 p-3 text-sm dark:bg-zinc-900">
                    <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Inhalt des QR-Codes</div>
                    <div class="mt-1 break-all font-mono text-zinc-900 dark:text-white">{{ $this->content }}</div>
                </div>
            @endif
        </section>

        <section class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            @if ($this->svg)
                <div class="w-full max-w-sm rounded-xl bg-white p-2 ring-1 ring-zinc-200" wire:loading.class="opacity-60">
                    {!! $this->svg !!}
                </div>
                @if ($this->travelCode)
                    <div class="font-mono text-2xl font-semibold tracking-wider text-zinc-900 dark:text-white">{{ $this->travelCode }}</div>
                @endif
                <div class="flex flex-wrap justify-center gap-2">
                    <flux:button variant="primary" icon="arrow-down-tray" wire:click="downloadPdf">Als PDF herunterladen</flux:button>
                    @if ($this->travelCode && $type !== QrCodes::TYPE_TRIPDATA_APP)
                        <flux:button icon="arrow-top-right-on-square" :href="$this->content" target="_blank">Reise öffnen</flux:button>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center gap-2 py-16 text-center text-zinc-500">
                    <flux:icon.qr-code class="size-12 text-zinc-300 dark:text-zinc-700" />
                    <p class="text-sm">Hier erscheint der QR-Code, sobald ein gültiger Inhalt eingegeben ist.</p>
                </div>
            @endif
        </section>
    </div>
</div>
