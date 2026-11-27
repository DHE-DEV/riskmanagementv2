<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

/**
 * Wiederholt set_country_coordinates_to_capital: Dort brach each() nach dem
 * ersten Land ab, dessen Punkt schon stimmte (der Callback lieferte false),
 * live wurde daher kein Land umgesetzt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Country::query()->withTrashed()->with('boundary')->each(function (Country $country): void {
            $country->syncCoordinatesFromCapital();
        });
    }

    public function down(): void
    {
        //
    }
};
