<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->text('body_html');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default system template
        \DB::table('notification_templates')->insert([
            'customer_id' => null,
            'name' => 'Standard-Vorlage',
            'subject' => 'Reisewarnung: {event_title} - {country_name}',
            'body_html' => '<h2>Reisewarnung: {event_title}</h2>
<p><strong>Land:</strong> {country_name}</p>
<p><strong>Risikostufe:</strong> {risk_level}</p>
<p><strong>Kategorie:</strong> {category}</p>
<p><strong>Datum:</strong> {event_date}</p>
<hr>
<p>{description}</p>
<hr>
<p style="color: #666; font-size: 12px;">Diese Benachrichtigung wurde automatisch vom Global Travel Monitor gesendet.</p>',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
