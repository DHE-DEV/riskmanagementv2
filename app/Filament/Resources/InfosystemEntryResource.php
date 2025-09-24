<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InfosystemEntryResource\Pages;
use App\Models\InfosystemEntry;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use App\Filament\Resources\CustomEvents\CustomEventResource;
use Filament\Tables\Columns\IconColumn;
use UnitEnum;

class InfosystemEntryResource extends Resource
{
    protected static ?string $model = InfosystemEntry::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-information-circle';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Infosystem Entry';

    protected static ?string $pluralModelLabel = 'Infosystem Entries';

    protected static ?string $navigationLabel = 'PDS Infosystem';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('header')
                    ->label('Header')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('content')
                    ->label('Content')
                    ->rows(5)
                    ->columnSpanFull(),

                DatePicker::make('tagdate')
                    ->label('Tag Date'),

                Toggle::make('active')
                    ->label('Active')
                    ->default(true),

                TextInput::make('api_id')
                    ->label('API ID')
                    ->required()
                    ->maxLength(255),

                TextInput::make('position')
                    ->numeric(),

                TextInput::make('appearance')
                    ->maxLength(255),

                TextInput::make('tagtype')
                    ->label('Tag Type')
                    ->maxLength(255),

                TextInput::make('tagtext')
                    ->label('Tag Text')
                    ->maxLength(255),

                TextInput::make('country_code')
                    ->label('Country Code')
                    ->maxLength(10),

                KeyValue::make('country_names')
                    ->label('Country Names')
                    ->keyLabel('Language')
                    ->valueLabel('Name'),

                Select::make('lang')
                    ->label('Language')
                    ->options([
                        'de' => 'German',
                        'en' => 'English',
                        'fr' => 'French',
                        'it' => 'Italian',
                    ])
                    ->default('de'),

                TextInput::make('language_content')
                    ->label('Language Content')
                    ->maxLength(255),

                TextInput::make('language_code')
                    ->label('Language Code')
                    ->maxLength(10),

                Toggle::make('archive')
                    ->label('Archived')
                    ->default(false),

                DateTimePicker::make('api_created_at')
                    ->label('API Created At'),

                TextInput::make('request_id')
                    ->label('Request ID')
                    ->maxLength(255),

                TextInput::make('response_time')
                    ->label('Response Time')
                    ->numeric()
                    ->suffix('ms'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('api_id')
                    ->label('API ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('header')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Country')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lang')
                    ->label('Language')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'de' => 'warning',
                        'en' => 'success',
                        'fr' => 'info',
                        'it' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tagtype')
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tagdate')
                    ->label('Tag Date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Veröffentlicht')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Veröffentlicht am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Nicht veröffentlicht'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('archive')
                    ->label('Archived')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Veröffentlichungsstatus')
                    ->placeholder('Alle')
                    ->trueLabel('Veröffentlicht')
                    ->falseLabel('Nicht veröffentlicht'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('archive')
                    ->label('Archive Status'),
                Tables\Filters\SelectFilter::make('lang')
                    ->label('Language')
                    ->options([
                        'de' => 'German',
                        'en' => 'English',
                        'fr' => 'French',
                        'it' => 'Italian',
                    ]),
                Tables\Filters\SelectFilter::make('country_code')
                    ->label('Country')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('tagtype')
                    ->label('Tag Type')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('createEvent')
                    ->label('Event anlegen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn ($record) => CustomEventResource::getUrl('create') . '?' . http_build_query(array_filter([
                        'title' => self::processTitle($record->header),
                        'description' => $record->content,
                        'start_date' => $record->tagdate ? $record->tagdate->format('Y-m-d') : null,
                        'country_code' => $record->country_code ?? null,
                        'country_name' => isset($record->country_names['de']) ? $record->country_names['de'] : null,
                        'tagtype' => $record->tagtype ?? null,
                        'source' => 'infosystem',
                        'source_id' => $record->api_id,
                    ])))
                    ->openUrlInNewTab()
                    ->hidden(fn ($record) => $record->is_published),
            ])
            ->bulkActions([])
            ->recordUrl(null)
            ->defaultSort('tagdate', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInfosystemEntries::route('/'),
            'create' => Pages\CreateInfosystemEntry::route('/create'),
            'view' => Pages\ViewInfosystemEntry::route('/{record}'),
            'edit' => Pages\EditInfosystemEntry::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->latest();
    }

    /**
     * Process the title to remove text before the first dash
     */
    protected static function processTitle(?string $title): ?string
    {
        if (!$title) {
            return $title;
        }

        // Check if the title contains a dash
        if (str_contains($title, '-')) {
            // Split by the first dash and take the part after it
            $parts = explode('-', $title, 2);
            if (count($parts) > 1) {
                // Return the second part, trimming any leading/trailing whitespace
                return trim($parts[1]);
            }
        }

        // If no dash found, return the original title
        return $title;
    }
}