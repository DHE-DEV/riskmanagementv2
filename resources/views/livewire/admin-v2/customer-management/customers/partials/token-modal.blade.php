@php use App\Livewire\AdminV2\CustomerManagement\Customers\Editor; @endphp

{{-- Dialog: API Token erstellen – danach zeigt er einmalig den Klartext --}}
<flux:modal name="customer-token" class="md:w-[36rem]" :dismissible="false">
    @if ($plainTextToken)
        <div class="flex flex-col gap-5" x-data="{ copied: false }">
            <div>
                <flux:heading size="lg">API Token erstellt</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Dieser Token wird nur einmal angezeigt! Bitte jetzt kopieren und sicher weitergeben.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <code class="min-w-0 flex-1 break-all rounded-lg bg-zinc-100 px-3 py-2 font-mono text-sm text-zinc-900 dark:bg-zinc-800 dark:text-white">{{ $plainTextToken }}</code>
                <flux:button
                    icon="clipboard"
                    x-on:click="navigator.clipboard.writeText(@js($plainTextToken)); copied = true; setTimeout(() => copied = false, 2000)"
                >
                    <span x-show="! copied">Kopieren</span>
                    <span x-show="copied" x-cloak>Kopiert</span>
                </flux:button>
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="dismissToken">Fertig</flux:button>
            </div>
        </div>
    @else
        <form wire:submit="saveToken" class="flex flex-col gap-5">
            <flux:heading size="lg">Neuen API Token erstellen</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="tokenForm.name" maxlength="255" />
                <flux:description>Beschreibender Name, z.B. "Jack API", "CRM System"</flux:description>
                <flux:error name="tokenForm.name" />
            </flux:field>

            <flux:field>
                <flux:label>Berechtigungen</flux:label>
                <div class="grid gap-x-5 gap-y-2.5 sm:grid-cols-2">
                    @foreach (Editor::TOKEN_ABILITIES as $value => $label)
                        <flux:checkbox wire:model="tokenForm.abilities" value="{{ $value }}" :label="$label" />
                    @endforeach
                </div>
                <flux:error name="tokenForm.abilities" />
            </flux:field>

            <flux:field>
                <flux:label>Ablaufdatum</flux:label>
                <flux:input wire:model="tokenForm.expires_at" type="datetime-local" min="{{ now()->format('Y-m-d\TH:i') }}" />
                <flux:description>Leer lassen für unbegrenzte Gültigkeit</flux:description>
                <flux:error name="tokenForm.expires_at" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="saveToken">Erstellen</flux:button>
            </div>
        </form>
    @endif
</flux:modal>
