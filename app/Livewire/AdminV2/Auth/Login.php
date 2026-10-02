<?php

namespace App\Livewire\AdminV2\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.adminv2.auth')]
#[Title('Anmelden')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->is_admin && $user->is_active) {
            return $this->redirectRoute('adminv2.dashboard');
        }
    }

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        // is_admin/is_active gehoeren zu den Anmeldedaten: ein Konto ohne
        // Admin-Zugang bekommt dieselbe Meldung wie ein falsches Passwort.
        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'is_admin' => true,
            'is_active' => true,
        ];

        if (! Auth::guard('web')->attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Diese Zugangsdaten sind nicht gültig oder haben keinen Admin-Zugang.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(default: route('adminv2.dashboard', absolute: false));
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Zu viele Anmeldeversuche. Bitte in {$seconds} Sekunden erneut versuchen.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.admin-v2.auth.login');
    }
}
