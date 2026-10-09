@php
    use Illuminate\Support\Str;

    $tokens = $this->tokens;
    $sortHeader = 'livewire.admin-v2.customer-management.customers.partials.sort-header';
    $tokenSortState = ['method' => 'sortTokens', 'sort' => $tokenSort, 'direction' => $tokenDirection];
@endphp

<x-adminv2.card heading="API Tokens" flush>
    <x-slot:actions>
        <flux:button size="sm" icon="plus" wire:click="createToken">Token erstellen</flux:button>
    </x-slot:actions>

    @php($docsUrl = url('/docs/api'))
    {{-- Link zur API-Dokumentation, den man dem Kunden zusammen mit dem Token weitergeben kann --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-zinc-100 bg-zinc-50 px-5 py-3 text-sm dark:border-zinc-800 dark:bg-zinc-900" x-data="{ copied: false }">
        <flux:icon.book-open variant="mini" class="shrink-0 text-zinc-400" />
        <span class="text-zinc-600 dark:text-zinc-400">API-Dokumentation für den Kunden:</span>
        <a href="{{ $docsUrl }}" target="_blank" rel="noopener" class="font-mono text-xs text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white dark:decoration-zinc-600 dark:hover:decoration-white">{{ $docsUrl }}</a>
        <button
            type="button"
            class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
            x-on:click="navigator.clipboard.writeText(@js($docsUrl)); copied = true; setTimeout(() => copied = false, 1500)"
            aria-label="Link zur API-Dokumentation kopieren"
            title="Link kopieren"
        >
            <flux:icon.clipboard variant="micro" x-show="! copied" />
            <flux:icon.check variant="micro" class="text-green-600" x-show="copied" x-cloak />
            <span x-text="copied ? 'Kopiert' : 'Kopieren'">Kopieren</span>
        </button>
    </div>

    <div class="px-5 py-4">
        <flux:input wire:model.live.debounce.300ms="tokenSearch" size="sm" icon="magnifying-glass" placeholder="Name des Tokens suchen …" aria-label="API Tokens durchsuchen" clearable />
    </div>

    @if ($tokens->isEmpty())
        @include('livewire.admin-v2.customer-management.customers.partials.empty', [
            'icon' => 'key',
            'heading' => 'Keine API Tokens',
            'text' => $tokenSearch !== '' ? 'Zur Suche passt kein Token.' : 'Erstellen Sie einen API Token für diesen Kunden.',
        ])
    @else
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="tokenSearch, sortTokens, revokeToken, saveToken">
            <table class="w-full text-sm">
                <thead class="border-y border-zinc-100 bg-zinc-50 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">
                    <tr>
                        @include($sortHeader, $tokenSortState + ['column' => 'name', 'label' => 'Name'])
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Berechtigungen</th>
                        @include($sortHeader, $tokenSortState + ['column' => 'last_used_at', 'label' => 'Zuletzt verwendet'])
                        @include($sortHeader, $tokenSortState + ['column' => 'expires_at', 'label' => 'Läuft ab'])
                        @include($sortHeader, $tokenSortState + ['column' => 'created_at', 'label' => 'Erstellt am'])
                        <th scope="col" class="px-4 py-2.5"><span class="sr-only">Aktionen</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($tokens as $token)
                        <tr wire:key="token-{{ $token->id }}" class="text-zinc-700 dark:text-zinc-300">
                            <td class="px-4 py-2.5">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ Str::after($token->name, ':') ?: $token->name }}</span>
                                <span class="block text-xs text-zinc-500">{{ Str::startsWith($token->name, 'admin:') ? 'Vom Admin erstellt' : 'Vom Kunden erstellt' }}</span>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ((array) $token->abilities as $ability)
                                        <flux:badge size="sm" color="sky" inset="top bottom" class="font-mono">{{ $ability }}</flux:badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ $token->last_used_at?->format('d.m.Y H:i') ?? 'Nie verwendet' }}</td>
                            <td @class(['px-4 py-2.5 whitespace-nowrap tabular-nums', 'font-medium text-red-600 dark:text-red-400' => $token->expires_at?->isPast()])>
                                {{ $token->expires_at?->format('d.m.Y H:i') ?? 'Unbegrenzt' }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ $token->created_at?->format('d.m.Y H:i') ?? '–' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end">
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="revokeToken({{ $token->id }})" wire:confirm="Möchten Sie diesen Token wirklich widerrufen? Der API-Zugriff wird sofort gesperrt.">Widerrufen</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-adminv2.card>
