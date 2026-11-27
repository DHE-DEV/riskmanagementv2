<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

/**
 * Der Punkt eines Landes liegt kuenftig auf seiner Hauptstadt statt in der
 * Landesmitte – wie bei landesweiten Ereignissen. Laender ohne Hauptstadt,
 * ohne ihre Koordinaten oder mit einer Hauptstadt ausserhalb der
 * Landesgrenze (falsch zugeordnete Altdaten) behalten ihren Punkt.
 *
 * Die bisherigen Mittelpunkte lassen sich nicht wiederherstellen.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Kein Pfeil-Callback: each() bricht ab, sobald er false liefert.
        Country::query()->withTrashed()->with('boundary')->each(function (Country $country): void {
            $country->syncCoordinatesFromCapital();
        });
    }

    public function down(): void
    {
        //
    }
};
