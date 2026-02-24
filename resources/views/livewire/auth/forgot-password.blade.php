<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('Ein Link zum Zurücksetzen wird gesendet, falls das Konto existiert.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Passwort vergessen')" :description="__('Geben Sie Ihre E-Mail-Adresse ein, um einen Link zum Zurücksetzen zu erhalten')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('E-Mail-Adresse')"
            type="email"
            required
            autofocus
            placeholder="email@example.com"
        />

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Link zum Zurücksetzen senden') }}</flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
        <span>{{ __('Oder zurück zum') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Login') }}</flux:link>
    </div>
</div>
