<?php

namespace App\Filament\Resources;

use App\Models\NotificationTemplate;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SystemNotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Benachrichtigungsvorlagen';

    protected static ?string $modelLabel = 'Benachrichtigungsvorlage';

    protected static ?string $pluralModelLabel = 'Benachrichtigungsvorlagen';

    protected static ?int $navigationSort = 102;

    protected static ?string $slug = 'system/notification-templates';

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('is_system', true);
    }

    public static function form(Schema $schema): Schema
    {
        $placeholderHtml = collect(NotificationTemplate::PLACEHOLDERS)
            ->map(fn ($desc, $key) => "<code>{$key}</code> – {$desc}")
            ->implode('<br>');

        return $schema->components([
            Section::make('Vorlage bearbeiten')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Select::make('source')
                        ->label('Quelle')
                        ->options([
                            'travel-alert' => 'Travel Alert',
                            'global-travel-monitor' => 'Global Travel Monitor',
                        ])
                        ->required()
                        ->disabled(),

                    TextInput::make('subject')
                        ->label('Betreff')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Platzhalter wie {event_title} oder {country_name} können verwendet werden.'),

                    Textarea::make('body_html')
                        ->label('E-Mail-Inhalt (HTML)')
                        ->required()
                        ->columnSpanFull()
                        ->rows(25)
                        ->helperText('HTML-Code mit Platzhaltern. Änderungen wirken sich auf alle Kunden aus, die die Standardvorlage verwenden.'),
                ]),

            Section::make('Verfügbare Platzhalter')
                ->description(new \Illuminate\Support\HtmlString($placeholderHtml))
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('source')
                    ->label('Quelle')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'travel-alert' => 'Travel Alert',
                        'global-travel-monitor' => 'Global Travel Monitor',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'travel-alert' => 'warning',
                        'global-travel-monitor' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('subject')
                    ->label('Betreff')
                    ->limit(50),

                TextColumn::make('updated_at')
                    ->label('Zuletzt geändert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SystemNotificationTemplateResource\Pages\ListSystemNotificationTemplates::route('/'),
            'edit' => \App\Filament\Resources\SystemNotificationTemplateResource\Pages\EditSystemNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
