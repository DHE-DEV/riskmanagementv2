<?php

namespace App\Filament\Resources\EventTypes\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventCategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'eventCategories';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. Notfall, Übung, Wartung'),

                Textarea::make('description')
                    ->label('Beschreibung')
                    ->rows(3)
                    ->placeholder('Optionale Beschreibung der Kategorie'),

                ColorPicker::make('color')
                    ->label('Farbe')
                    ->placeholder('#3B82F6')
                    ->helperText('Optional - Farbe für die Kategorie-Anzeige'),

                TextInput::make('sort_order')
                    ->label('Sortierreihenfolge')
                    ->numeric()
                    ->default(0)
                    ->helperText('Niedrigere Zahlen werden zuerst angezeigt'),

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true)
                    ->helperText('Nur aktive Kategorien werden in der Auswahl angezeigt'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->placeholder('Keine Beschreibung'),

                ColorColumn::make('color')
                    ->label('Farbe')
                    ->placeholder('Keine Farbe'),

                TextColumn::make('sort_order')
                    ->label('Sortierung')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Neue Kategorie'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Bearbeiten'),
                DeleteAction::make()
                    ->label('Löschen'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Ausgewählte löschen'),
                ]),
            ])
            ->defaultSort('sort_order')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
