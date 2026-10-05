<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * API Tokens (Sanctum) eines Kunden: Liste, Erstellen und Widerrufen. Vom Admin
 * erstellte Tokens tragen das Praefix "admin:" im Namen. Der Klartext eines
 * neuen Tokens wird genau einmal angezeigt.
 */
trait ManagesCustomerTokens
{
    public const TOKEN_ABILITIES = [
        'folder:import' => 'Folder Import',
        'folder:read' => 'Folder Lesen',
        'folder:write' => 'Folder Schreiben',
        'gtm:read' => 'GTM Lesen',
    ];

    public string $tokenSearch = '';

    public string $tokenSort = 'created_at';

    public string $tokenDirection = 'desc';

    /** @var array{name: string, abilities: array<int, string>, expires_at: string} */
    public array $tokenForm = ['name' => '', 'abilities' => [], 'expires_at' => ''];

    /** Klartext des soeben erstellten Tokens – nur bis zum Schliessen des Dialogs. */
    public ?string $plainTextToken = null;

    /**
     * @return array<string, string>
     */
    public function tokenSortOptions(): array
    {
        return [
            'name' => 'Name',
            'last_used_at' => 'Zuletzt verwendet',
            'expires_at' => 'Läuft ab',
            'created_at' => 'Erstellt am',
        ];
    }

    public function sortTokens(string $column): void
    {
        if (! array_key_exists($column, $this->tokenSortOptions())) {
            return;
        }

        $this->tokenDirection = $this->tokenSort === $column && $this->tokenDirection === 'asc' ? 'desc' : 'asc';
        $this->tokenSort = $column;
    }

    #[Computed]
    public function tokens(): Collection
    {
        $query = $this->customer->tokens();

        if (($term = trim($this->tokenSearch)) !== '') {
            $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
        }

        $sort = array_key_exists($this->tokenSort, $this->tokenSortOptions()) ? $this->tokenSort : 'created_at';

        return $query->orderBy($sort, $this->tokenDirection === 'asc' ? 'asc' : 'desc')->orderByDesc('id')->get();
    }

    public function createToken(): void
    {
        $this->reset('tokenForm', 'plainTextToken');
        $this->resetErrorBag();

        $this->modal('customer-token')->show();
    }

    public function saveToken(): void
    {
        $this->validate([
            'tokenForm.name' => ['required', 'string', 'max:255'],
            'tokenForm.abilities' => ['required', 'array', 'min:1'],
            'tokenForm.abilities.*' => [Rule::in(array_keys(self::TOKEN_ABILITIES))],
            'tokenForm.expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'tokenForm.name.required' => 'Bitte einen Namen für den Token eingeben.',
            'tokenForm.abilities.required' => 'Bitte mindestens eine Berechtigung wählen.',
            'tokenForm.abilities.min' => 'Bitte mindestens eine Berechtigung wählen.',
            'tokenForm.expires_at.date' => 'Bitte ein gültiges Ablaufdatum angeben.',
            'tokenForm.expires_at.after' => 'Das Ablaufdatum muss in der Zukunft liegen.',
        ]);

        $token = $this->customer->createToken(
            'admin:'.trim($this->tokenForm['name']),
            array_values($this->tokenForm['abilities']),
            $this->tokenForm['expires_at'] !== '' ? Carbon::parse($this->tokenForm['expires_at']) : null,
        );

        // Der Dialog bleibt offen und zeigt den Klartext – er ist danach nicht mehr abrufbar.
        $this->plainTextToken = $token->plainTextToken;
        $this->reset('tokenForm');
        unset($this->tokens);

        $this->dispatch('adminv2-toast', message: 'API Token erstellt. Der Token wird nur einmal angezeigt!');
    }

    public function dismissToken(): void
    {
        $this->plainTextToken = null;
        $this->modal('customer-token')->close();
    }

    public function revokeToken(int $tokenId): void
    {
        $this->customer->tokens()->findOrFail($tokenId)->delete();
        unset($this->tokens);

        $this->dispatch('adminv2-toast', message: 'Token widerrufen – der API-Zugriff ist gesperrt.');
    }
}
