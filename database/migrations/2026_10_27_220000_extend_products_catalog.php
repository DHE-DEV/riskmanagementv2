<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Produktkatalog in Admin v2 › System › Produkte, aufbauend auf der
     * bisherigen Produktverwaltung:
     *  - products: Kuerzel, Voraussetzung, Preisbasis
     *  - product_versions = Auspraegungen (Allgemein oder Standard/Premium …)
     *  - product_version_prices: Preise je Auspraegung mit „gueltig ab“, auch kostenlos
     *  - product_features + product_feature_values: Leistungsumfang je Auspraegung
     * Vorhandene Preise werden als erster Preisstand uebernommen; die Module des
     * Travel Information Service werden angelegt bzw. per Name zugeordnet.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('key', 50)->nullable()->unique()->after('id');
            $table->foreignId('requires_product_id')->nullable()->after('internal_description')->constrained('products')->nullOnDelete();
            // per_location = je Buero, Agentur, Reiseberater | per_customer = pauschal je Kunde
            $table->string('price_basis', 20)->default('per_location')->after('requires_product_id');
        });

        Schema::create('product_version_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_version_id')->constrained('product_versions')->cascadeOnDelete();
            $table->date('valid_from');
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->decimal('price_one_time', 10, 2)->nullable();
            $table->decimal('price_setup', 10, 2)->nullable();
            $table->boolean('is_free')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_version_id', 'valid_from']);
        });

        Schema::create('product_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_feature_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_feature_id')->constrained('product_features')->cascadeOnDelete();
            $table->foreignId('product_version_id')->constrained('product_versions')->cascadeOnDelete();
            $table->boolean('included')->default(false);
            // z. B. "bis 5 Nutzer" statt nur Haken
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['product_feature_id', 'product_version_id'], 'product_feature_values_unique');
        });

        $now = now();
        $priceColumns = ['price_monthly', 'price_yearly', 'price_one_time', 'price_setup'];

        // Bisherige Produkte ohne Auspraegung: eine „Allgemein“ mit den Produktpreisen
        foreach (DB::table('products')->get() as $product) {
            if (! DB::table('product_versions')->where('product_id', $product->id)->exists()) {
                DB::table('product_versions')->insert([
                    'product_id' => $product->id, 'name' => 'Allgemein', 'currency' => $product->currency ?? 'EUR', 'is_active' => true, 'sort_order' => 0,
                    ...array_intersect_key((array) $product, array_flip($priceColumns)),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // Bisherige Preise als erster Preisstand
        foreach (DB::table('product_versions')->get() as $version) {
            $prices = array_intersect_key((array) $version, array_flip($priceColumns));
            if (array_filter($prices, fn ($value) => $value !== null)) {
                DB::table('product_version_prices')->insert([
                    'product_version_id' => $version->id, 'valid_from' => substr((string) $version->created_at, 0, 10) ?: now()->toDateString(),
                    ...$prices, 'is_free' => false, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $modules = [
            'travel_requirements' => ['Travel Requirements Service', [['Standard', '9.90'], ['Premium', '14.90']]],
            'global_travel_monitor' => ['Global Travel Monitor', [['Allgemein', null]]],
            'travel_alert' => ['Travel Alert', [['Allgemein', '7.00']]],
            'airport_data' => ['Airport Data', [['Allgemein', null]]],
            'airline_data' => ['Airline Data', [['Allgemein', null]]],
            'country_data' => ['Country Data', [['Allgemein', null]]],
            'destination_data' => ['Destination Data', [['Allgemein', null]]],
            'attraction_data' => ['Attraction Data', [['Allgemein', null]]],
            'business_visa' => ['Business Visum', [['Allgemein', null]]],
            'visa_service_link' => ['Visum Service Link', [['Allgemein', null]]],
            'wallet' => ['Wallet', [['Allgemein', null]]],
            'travel_details_website' => ['Travel-Details Website', [['Allgemein', null]]],
            'tripdata_app' => ['TripData App', [['Allgemein', null]]],
        ];

        $sort = (int) DB::table('products')->max('sort_order');
        foreach ($modules as $key => [$name, $versions]) {
            // Gibt es das Produkt schon (gleicher Name), bekommt es nur das Kuerzel.
            if ($existing = DB::table('products')->whereNull('key')->where('name', $name)->first()) {
                DB::table('products')->where('id', $existing->id)->update(['key' => $key]);

                continue;
            }

            $productId = DB::table('products')->insertGetId([
                'key' => $key, 'name' => $name, 'currency' => 'EUR', 'is_active' => true, 'sort_order' => $sort += 10,
                'price_basis' => 'per_location', 'created_at' => $now, 'updated_at' => $now,
            ]);

            foreach ($versions as $index => [$versionName, $amount]) {
                $versionId = DB::table('product_versions')->insertGetId([
                    'product_id' => $productId, 'name' => $versionName, 'currency' => 'EUR', 'is_active' => true,
                    'sort_order' => $index, 'created_at' => $now, 'updated_at' => $now,
                ]);

                if ($amount !== null) {
                    DB::table('product_version_prices')->insert([
                        'product_version_id' => $versionId, 'valid_from' => '2026-01-01', 'price_monthly' => $amount,
                        'is_free' => false, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }

        // Travel Alert nur zusammen mit Travel Requirements Service
        DB::table('products')->where('key', 'travel_alert')->whereNull('requires_product_id')->update([
            'requires_product_id' => DB::table('products')->where('key', 'travel_requirements')->value('id'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_feature_values');
        Schema::dropIfExists('product_features');
        Schema::dropIfExists('product_version_prices');

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requires_product_id');
            $table->dropUnique(['key']);
            $table->dropColumn(['key', 'price_basis']);
        });
    }
};
