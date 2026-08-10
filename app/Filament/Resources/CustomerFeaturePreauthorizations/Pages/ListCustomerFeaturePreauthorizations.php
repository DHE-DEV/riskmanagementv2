<?php

namespace App\Filament\Resources\CustomerFeaturePreauthorizations\Pages;

use App\Filament\Resources\CustomerFeaturePreauthorizations\CustomerFeaturePreauthorizationResource;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Services\CustomerFeaturePreauthorizationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCustomerFeaturePreauthorizations extends ListRecords
{
    protected static string $resource = CustomerFeaturePreauthorizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('IDs importieren')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Account-IDs vormerken')
                ->modalDescription('Eine Liste von PDS Account-IDs auf einmal vormerken. Bereits vorgemerkte IDs werden aktualisiert, nicht doppelt angelegt.')
                ->modalSubmitActionLabel('Vormerken')
                ->form([
                    Textarea::make('ids')
                        ->label('Account-IDs')
                        ->helperText('Eine ID pro Zeile oder durch Komma getrennt. Alles, was keine Zahl ist, wird ignoriert.')
                        ->rows(10)
                        ->required()
                        ->placeholder("25893\n25427\n23854"),

                    Select::make('feature_key')
                        ->label('Feature')
                        ->options(CustomerFeatureOverride::getFeatureLabels())
                        ->default('navigation_risk_overview_enabled')
                        ->required()
                        ->native(false),

                    Toggle::make('enabled')
                        ->label('Freischalten')
                        ->helperText('Aus = Feature fuer diese Accounts sperren statt freischalten.')
                        ->default(true),

                    TextInput::make('note')
                        ->label('Notiz')
                        ->placeholder('z.B. Travel-Alert-Liste August 2026')
                        ->maxLength(255),

                    Toggle::make('apply_now')
                        ->label('Auf bestehende Konten sofort anwenden')
                        ->helperText('Aus = greift erst beim naechsten Login. Bestehende Werte bleiben in beiden Faellen unangetastet.')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $ids = self::parseIds($data['ids']);

                    if ($ids === []) {
                        Notification::make()
                            ->title('Keine gueltigen IDs erkannt')
                            ->warning()
                            ->send();

                        return;
                    }

                    $service = app(CustomerFeaturePreauthorizationService::class);
                    $recorded = $service->record(
                        $data['feature_key'],
                        $ids,
                        (bool) ($data['enabled'] ?? true),
                        $data['note'] ?? null,
                    );

                    $withAccount = Customer::query()->whereIn('pds_account_id', $ids)->count();
                    $body = "{$recorded} Account-IDs vorgemerkt, davon {$withAccount} mit bestehendem Konto.";

                    if ($data['apply_now'] ?? true) {
                        $result = $service->applyToExistingCustomers($data['feature_key'], $ids);
                        $body .= " {$result['customers']} Konten sofort angepasst.";
                    }

                    Notification::make()
                        ->title('Import abgeschlossen')
                        ->body($body)
                        ->success()
                        ->persistent()
                        ->send();
                }),

            Action::make('applyPending')
                ->label('Offene anwenden')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Offene Vormerkungen anwenden')
                ->modalDescription('Wendet alle Vormerkungen auf Konten an, die es bereits gibt. Bereits gesetzte Werte bleiben unangetastet.')
                ->action(function (): void {
                    $result = app(CustomerFeaturePreauthorizationService::class)->applyToExistingCustomers();

                    Notification::make()
                        ->title($result['customers'] > 0 ? 'Angewendet' : 'Nichts zu tun')
                        ->body("{$result['customers']} Konten angepasst ({$result['applied']} Freischaltungen).")
                        ->color($result['customers'] > 0 ? 'success' : 'warning')
                        ->send();
                }),

            CreateAction::make()
                ->label('Einzeln vormerken'),
        ];
    }

    /**
     * @return array<int, int>
     */
    private static function parseIds(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->filter(fn (string $value) => ctype_digit($value))
            ->map(fn (string $value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }
}
