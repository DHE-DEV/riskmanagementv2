<?php

namespace App\Filament\Resources\TravelAlertOrders\Tables;

use App\Models\TravelAlertOrder;
use App\Services\TravelAlertOrderService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TravelAlertOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (TravelAlertOrder $record) => $record->status_label)
                    ->color(fn (TravelAlertOrder $record) => match ($record->status) {
                        TravelAlertOrder::STATUS_ACTIVE => 'success',
                        TravelAlertOrder::STATUS_PENDING_APPROVAL => 'warning',
                        TravelAlertOrder::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Eingegangen')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('company')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('Ansprechpartner')
                    ->getStateUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: '-')
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->label('Stadt')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('country')
                    ->label('Land')
                    ->sortable()
                    ->badge(),
                TextColumn::make('existing_billing')
                    ->label('Abrechnung')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'ja' ? 'Bestehend' : 'Neu')
                    ->color(fn (string $state) => $state === 'ja' ? 'success' : 'info'),
                TextColumn::make('trial_expires_at')
                    ->label('Test läuft ab')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn ($record) => $record->trial_expires_at?->isPast() ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        TravelAlertOrder::STATUS_PENDING_CONFIRMATION => 'Wartet auf Bestätigung',
                        TravelAlertOrder::STATUS_PENDING_APPROVAL => 'Wartet auf Freischaltung',
                        TravelAlertOrder::STATUS_ACTIVE => 'Freigeschaltet',
                        TravelAlertOrder::STATUS_REJECTED => 'Abgelehnt',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        TravelAlertOrder::STATUS_PENDING_CONFIRMATION => $query->whereNull('confirmed_at')->whereNull('rejected_at'),
                        TravelAlertOrder::STATUS_PENDING_APPROVAL => $query->whereNotNull('confirmed_at')->whereNull('approved_at')->whereNull('rejected_at'),
                        TravelAlertOrder::STATUS_ACTIVE => $query->whereNotNull('approved_at')->whereNull('rejected_at'),
                        TravelAlertOrder::STATUS_REJECTED => $query->whereNotNull('rejected_at'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Freischalten')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Travel Alert freischalten')
                    ->modalDescription('Der Kunde erhält eine E-Mail, dass sein Zugang bereitsteht.')
                    ->visible(fn (TravelAlertOrder $record) => ! $record->isApproved() && ! $record->isRejected())
                    ->disabled(fn (TravelAlertOrder $record) => ! $record->isConfirmed())
                    ->tooltip(fn (TravelAlertOrder $record) => $record->isConfirmed()
                        ? null
                        : 'Der Kunde hat die Bestellung noch nicht bestätigt.')
                    ->action(function (TravelAlertOrder $record) {
                        app(TravelAlertOrderService::class)->approve($record, Auth::id());
                    }),
                Action::make('reject')
                    ->label('Ablehnen')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Bestellung ablehnen')
                    ->modalDescription('Der Zugang wird nicht freigeschaltet. Der Kunde wird nicht automatisch benachrichtigt.')
                    ->visible(fn (TravelAlertOrder $record) => ! $record->isRejected())
                    ->action(function (TravelAlertOrder $record) {
                        app(TravelAlertOrderService::class)->reject($record, Auth::id());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
