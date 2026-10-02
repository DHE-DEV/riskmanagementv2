<div class="flex flex-col gap-8">
    <div class="flex items-center gap-3 lg:hidden">
        <span class="flex shrink-0 items-center rounded-xl border border-zinc-200 bg-white px-3 py-2.5 shadow-xs dark:border-transparent">
            <img src="{{ asset('logo.png') }}" alt="" class="h-8 w-auto" />
        </span>
        <span class="text-base font-semibold leading-tight tracking-tight text-zinc-900 dark:text-white">Passolution Travel Information Platform</span>
    </div>

    <div>
        <flux:heading size="xl" level="1">Anmelden</flux:heading>
        <flux:subheading>Admin-Bereich der Passolution Travel Information Platform.</flux:subheading>
    </div>

    <form wire:submit="login" class="flex flex-col gap-5">
        <flux:input
            wire:model="email"
            label="E-Mail-Adresse"
            type="email"
            autocomplete="email"
            placeholder="name@passolution.de"
            required
            autofocus
        />

        <flux:input
            wire:model="password"
            label="Passwort"
            type="password"
            autocomplete="current-password"
            viewable
            required
        />

        <flux:checkbox wire:model="remember" label="Angemeldet bleiben" />

        <flux:button type="submit" variant="primary" class="w-full">Anmelden</flux:button>
    </form>

    <flux:text class="text-xs">
        Es gelten dieselben Zugangsdaten wie im bisherigen Admin-Bereich.
    </flux:text>
</div>
