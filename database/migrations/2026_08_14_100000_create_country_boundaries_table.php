<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ländergrenzen als Polygone (Quelle: Natural Earth, ne_10m_admin_0_countries, Public Domain).
     *
     * Eigene Tabelle statt einer Spalte in `countries`, weil MySQL fuer einen SPATIAL INDEX
     * eine NOT-NULL-Spalte verlangt - Laender ohne Polygon haetten sonst einen Dummy gebraucht.
     */
    public function up(): void
    {
        Schema::create('country_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->unique()->constrained('countries')->cascadeOnDelete();
            $table->string('iso_a2', 2)->nullable()->index();
            $table->string('iso_a3', 3)->nullable()->index();
            $table->string('name')->nullable();
            $table->string('source', 50)->default('natural_earth_10m');
            // Aus wie vielen Quell-Features (Untereinheiten) wurde die Geometrie zusammengefasst?
            $table->unsignedSmallInteger('source_features')->default(1);

            // SRID 4326 (WGS 84). Achtung: MySQL nutzt bei 4326 die Achsenreihenfolge
            // Breitengrad/Laengengrad - GeoJSON dagegen Laengengrad/Breitengrad.
            $table->geometry('boundary', subtype: 'multipolygon', srid: 4326);

            // Bounding-Box fuer schnelle Vorfilter und zur Plausibilitaetspruefung des Imports.
            $table->decimal('min_lat', 10, 6)->nullable();
            $table->decimal('max_lat', 10, 6)->nullable();
            $table->decimal('min_lng', 10, 6)->nullable();
            $table->decimal('max_lng', 10, 6)->nullable();

            $table->timestamps();

            $table->spatialIndex('boundary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_boundaries');
    }
};
