<?php

use App\Models\CustomerFeatureOverride;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statt eines Schalters fuer alle Features: je Feature, ob die Zentrale es
     * an ihre Unteraccounts vererbt (Liste der Feature-Keys). Zentralen mit
     * bisher eingeschaltetem Schalter vererben danach alle Features.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->json('inherited_features')->nullable()->after('show_sub_accounts_as_branches');
        });

        if (Schema::hasColumn('customers', 'inherit_features_to_sub_accounts')) {
            DB::table('customers')->where('inherit_features_to_sub_accounts', true)
                ->update(['inherited_features' => json_encode(CustomerFeatureOverride::getFeatureKeys())]);

            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('inherit_features_to_sub_accounts');
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('inherit_features_to_sub_accounts')->default(false)->after('show_sub_accounts_as_branches');
        });

        DB::table('customers')->whereNotNull('inherited_features')->update(['inherit_features_to_sub_accounts' => true]);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('inherited_features');
        });
    }
};
