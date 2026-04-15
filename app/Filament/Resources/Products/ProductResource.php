<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Produkte';

    protected static ?string $modelLabel = 'Produkt';

    protected static ?string $pluralModelLabel = 'Produkte';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'system/products';

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Produkt')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Produktname')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('subtitle')
                            ->label('Zusatzbezeichnung')
                            ->maxLength(255),
                    ]),

                    Textarea::make('description')
                        ->label('Beschreibung')
                        ->rows(3),

                    Textarea::make('internal_description')
                        ->label('Interne Beschreibung')
                        ->rows(3),

                    Grid::make(4)->schema([
                        TextInput::make('price_monthly')
                            ->label('Preis monatlich')
                            ->numeric()
                            ->prefix('€'),

                        TextInput::make('price_yearly')
                            ->label('Preis jährlich')
                            ->numeric()
                            ->prefix('€'),

                        TextInput::make('price_one_time')
                            ->label('Einmalpreis')
                            ->numeric()
                            ->prefix('€'),

                        Select::make('currency')
                            ->label('Währung')
                            ->options([
                                'EUR' => 'EUR',
                                'USD' => 'USD',
                                'GBP' => 'GBP',
                                'CHF' => 'CHF',
                            ])
                            ->default('EUR'),
                    ]),

                    Grid::make(2)->schema([
                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Sortierung')
                            ->numeric()
                            ->default(0),
                    ]),
                ]),

            Section::make('Produktversionen')
                ->description('Versionen dieses Produkts (z.B. Basic, Pro, Enterprise)')
                ->schema([
                    Repeater::make('versions')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Versionsname')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('subtitle')
                                    ->label('Zusatzbezeichnung')
                                    ->maxLength(255),
                            ]),

                            Textarea::make('description')
                                ->label('Beschreibung')
                                ->rows(2),

                            Textarea::make('internal_description')
                                ->label('Interne Beschreibung')
                                ->rows(2),

                            Grid::make(4)->schema([
                                TextInput::make('price_monthly')
                                    ->label('Preis monatlich')
                                    ->numeric()
                                    ->prefix('€'),

                                TextInput::make('price_yearly')
                                    ->label('Preis jährlich')
                                    ->numeric()
                                    ->prefix('€'),

                                TextInput::make('price_one_time')
                                    ->label('Einmalpreis')
                                    ->numeric()
                                    ->prefix('€'),

                                Select::make('currency')
                                    ->label('Währung')
                                    ->options([
                                        'EUR' => 'EUR',
                                        'USD' => 'USD',
                                        'GBP' => 'GBP',
                                        'CHF' => 'CHF',
                                    ])
                                    ->default('EUR'),
                            ]),

                            Grid::make(2)->schema([
                                Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),

                                TextInput::make('sort_order')
                                    ->label('Sortierung')
                                    ->numeric()
                                    ->default(0),
                            ]),

                            Section::make('Module')
                                ->description('Module dieser Version')
                                ->collapsed()
                                ->schema([
                                    Repeater::make('modules')
                                        ->relationship()
                                        ->label('')
                                        ->schema([
                                            Grid::make(2)->schema([
                                                TextInput::make('name')
                                                    ->label('Modulname')
                                                    ->required()
                                                    ->maxLength(255),

                                                TextInput::make('subtitle')
                                                    ->label('Zusatzbezeichnung')
                                                    ->maxLength(255),
                                            ]),

                                            Textarea::make('description')
                                                ->label('Beschreibung')
                                                ->rows(2),

                                            Textarea::make('internal_description')
                                                ->label('Interne Beschreibung')
                                                ->rows(2),

                                            Grid::make(4)->schema([
                                                TextInput::make('price_monthly')
                                                    ->label('Preis monatlich')
                                                    ->numeric()
                                                    ->prefix('€'),

                                                TextInput::make('price_yearly')
                                                    ->label('Preis jährlich')
                                                    ->numeric()
                                                    ->prefix('€'),

                                                TextInput::make('price_one_time')
                                                    ->label('Einmalpreis')
                                                    ->numeric()
                                                    ->prefix('€'),

                                                Select::make('currency')
                                                    ->label('Währung')
                                                    ->options([
                                                        'EUR' => 'EUR',
                                                        'USD' => 'USD',
                                                        'GBP' => 'GBP',
                                                        'CHF' => 'CHF',
                                                    ])
                                                    ->default('EUR'),
                                            ]),

                                            Grid::make(2)->schema([
                                                Toggle::make('is_active')
                                                    ->label('Aktiv')
                                                    ->default(true),

                                                TextInput::make('sort_order')
                                                    ->label('Sortierung')
                                                    ->numeric()
                                                    ->default(0),
                                            ]),
                                        ])
                                        ->orderColumn('sort_order')
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neues Modul')
                                        ->addActionLabel('Modul hinzufügen'),
                                ]),
                        ])
                        ->orderColumn('sort_order')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neue Version')
                        ->addActionLabel('Version hinzufügen'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('name')
                    ->label('Produktname')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subtitle')
                    ->label('Zusatzbezeichnung')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('versions_count')
                    ->label('Versionen')
                    ->counts('versions')
                    ->sortable(),

                TextColumn::make('price_monthly')
                    ->label('Monatlich')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('price_yearly')
                    ->label('Jährlich')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
