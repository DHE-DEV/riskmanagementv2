<?php

namespace App\Filament\Resources\Labels\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LabelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(1),

                TextInput::make('description')
                    ->label('Beschreibung')
                    ->maxLength(255)
                    ->columnSpan(1),

                ColorPicker::make('color')
                    ->label('Farbe')
                    ->default('#3B82F6')
                    ->columnSpan(1),

                Select::make('icon')
                    ->label('Icon')
                    ->options(self::getIconOptions())
                    ->default('fa-tag')
                    ->searchable()
                    ->allowHtml()
                    ->columnSpan(1),

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true)
                    ->columnSpan(1),

                TextInput::make('sort_order')
                    ->label('Sortierung')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(1),
            ]);
    }

    private static function getIconOptions(): array
    {
        return [
            'fa-tag' => '<i class="fa fa-tag"></i> Tag',
            'fa-tags' => '<i class="fa fa-tags"></i> Tags',
            'fa-bookmark' => '<i class="fa fa-bookmark"></i> Lesezeichen',
            'fa-star' => '<i class="fa fa-star"></i> Stern',
            'fa-heart' => '<i class="fa fa-heart"></i> Herz',
            'fa-flag' => '<i class="fa fa-flag"></i> Flagge',
            'fa-circle' => '<i class="fa fa-circle"></i> Kreis',
            'fa-square' => '<i class="fa fa-square"></i> Quadrat',
            'fa-diamond' => '<i class="fa fa-diamond"></i> Diamant',
            'fa-bell' => '<i class="fa fa-bell"></i> Glocke',
            'fa-bolt' => '<i class="fa fa-bolt"></i> Blitz',
            'fa-fire' => '<i class="fa fa-fire"></i> Feuer',
            'fa-shield-alt' => '<i class="fa fa-shield-alt"></i> Schild',
            'fa-exclamation-triangle' => '<i class="fa fa-exclamation-triangle"></i> Warnung',
            'fa-info-circle' => '<i class="fa fa-info-circle"></i> Information',
            'fa-check-circle' => '<i class="fa fa-check-circle"></i> Bestätigung',
            'fa-times-circle' => '<i class="fa fa-times-circle"></i> Fehler',
            'fa-globe' => '<i class="fa fa-globe"></i> Global',
            'fa-plane' => '<i class="fa fa-plane"></i> Flugzeug',
            'fa-ship' => '<i class="fa fa-ship"></i> Schiff',
            'fa-hotel' => '<i class="fa fa-hotel"></i> Hotel',
            'fa-car' => '<i class="fa fa-car"></i> Auto',
            'fa-umbrella' => '<i class="fa fa-umbrella"></i> Regenschirm',
            'fa-briefcase' => '<i class="fa fa-briefcase"></i> Koffer',
            'fa-users' => '<i class="fa fa-users"></i> Gruppe',
            'fa-user' => '<i class="fa fa-user"></i> Person',
            'fa-calendar' => '<i class="fa fa-calendar"></i> Kalender',
            'fa-clock' => '<i class="fa fa-clock"></i> Uhr',
            'fa-map-marker' => '<i class="fa fa-map-marker"></i> Marker',
            'fa-thumbtack' => '<i class="fa fa-thumbtack"></i> Pin',
        ];
    }
}
