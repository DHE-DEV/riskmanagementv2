<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('app_code', 4)->nullable()->after('customer_id');
        });

        // Generiere App-Codes für existierende Einträge
        $branches = \DB::table('branches')->whereNull('app_code')->orWhere('app_code', '')->get();
        foreach ($branches as $branch) {
            $appCode = $this->generateUniqueAppCode();
            \DB::table('branches')->where('id', $branch->id)->update(['app_code' => $appCode]);
        }

        // Jetzt mache das Feld unique und not nullable
        Schema::table('branches', function (Blueprint $table) {
            $table->string('app_code', 4)->nullable(false)->unique()->change();
        });
    }

    /**
     * Generiert einen einzigartigen 4-stelligen alphanumerischen App-Code
     */
    private function generateUniqueAppCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        } while (\DB::table('branches')->where('app_code', $code)->exists());

        return $code;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('app_code');
        });
    }
};
