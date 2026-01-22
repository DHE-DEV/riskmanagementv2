# Projektspezifische Anweisungen

## Filament Infolist Layout Standard

Bei Filament Infolist-Seiten (ViewRecord) immer folgendes zweispaltiges Layout verwenden:

```php
public static function infolist(Schema $schema): Schema
{
    return $schema
        ->columns(['default' => 1, 'lg' => 2])
        ->components([
            // Linke Spalte - 1/2 Breite
            Group::make()
                ->columnSpan(['lg' => 1])
                ->schema([
                    // Sections für linke Seite
                ]),
            // Rechte Spalte - 1/2 Breite
            Group::make()
                ->columnSpan(['lg' => 1])
                ->schema([
                    // Sections für rechte Seite
                ]),
        ]);
}
```

**Wichtig:**
- `columns(['default' => 1, 'lg' => 2])` für 50/50 Aufteilung
- Beide `Group::make()` sind Geschwister auf gleicher Ebene in `->components([])`
- Jede Group hat `->columnSpan(['lg' => 1])`
- Auf kleinen Bildschirmen (`default`) werden die Spalten untereinander dargestellt
- Benötigter Import: `use Filament\Schemas\Components\Group;`
