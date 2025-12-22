<?php

namespace App\Filament\Resources\InfoSourceItems\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class InfoSourceItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Übersicht')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Placeholder::make('infoSource.name')
                                    ->label('Quelle')
                                    ->content(fn ($record) => $record?->infoSource?->name ?? '-'),

                                Placeholder::make('status')
                                    ->label('Status')
                                    ->content(fn ($record) => $record?->status_label ?? '-'),

                                Placeholder::make('published_at')
                                    ->label('Veröffentlicht')
                                    ->content(fn ($record) => $record?->published_at?->format('d.m.Y H:i') ?? '-'),

                                Placeholder::make('updated_at_source')
                                    ->label('Zuletzt aktualisiert')
                                    ->content(fn ($record) => $record?->updated_at_source?->format('d.m.Y H:i') ?? 'Keine Änderung'),

                                Placeholder::make('author')
                                    ->label('Autor')
                                    ->content(fn ($record) => $record?->author ?? '-'),

                                TextInput::make('title')
                                    ->label('Titel')
                                    ->disabled()
                                    ->columnSpanFull(),

                                Placeholder::make('link')
                                    ->label('Link')
                                    ->content(fn ($record) => $record?->link ?? '-')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make('Inhalt')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Placeholder::make('description_view')
                                    ->label('Beschreibung')
                                    ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                        '<div class="prose prose-sm max-w-none">' . ($record?->description ?? '-') . '</div>'
                                    ))
                                    ->columnSpanFull(),

                                Placeholder::make('content_view')
                                    ->label('Volltext')
                                    ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                        '<div class="prose prose-sm max-w-none max-h-96 overflow-y-auto">' . ($record?->content ?? '-') . '</div>'
                                    ))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Metadaten')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Placeholder::make('categories')
                                    ->label('Kategorien')
                                    ->content(fn ($record) => is_array($record?->categories) ? implode(', ', $record->categories) : '-')
                                    ->columnSpanFull(),

                                Placeholder::make('external_id')
                                    ->label('Externe ID')
                                    ->content(fn ($record) => $record?->external_id ?? '-'),

                                Placeholder::make('created_at')
                                    ->label('Abgerufen am')
                                    ->content(fn ($record) => $record?->created_at?->format('d.m.Y H:i:s') ?? '-'),

                                Placeholder::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->content(fn ($record) => $record?->updated_at?->format('d.m.Y H:i:s') ?? '-'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
