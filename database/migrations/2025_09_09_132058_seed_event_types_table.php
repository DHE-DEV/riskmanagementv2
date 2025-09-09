<?php

use App\Models\EventType;
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
        // Seed event types based on existing CustomEvent options
        $eventTypes = [
            [
                'code' => 'earthquake',
                'name' => 'Erdbeben',
                'description' => 'Seismische Aktivitäten und Erdbeben',
                'color' => '#DC2626', // red-600
                'icon' => 'fa-mountain',
                'sort_order' => 1
            ],
            [
                'code' => 'hurricane',
                'name' => 'Hurrikan',
                'description' => 'Tropische Wirbelstürme und Hurrikans',
                'color' => '#059669', // emerald-600
                'icon' => 'fa-wind',
                'sort_order' => 2
            ],
            [
                'code' => 'flood',
                'name' => 'Überschwemmung',
                'description' => 'Hochwasser und Überschwemmungen',
                'color' => '#3B82F6', // blue-600
                'icon' => 'fa-tint',
                'sort_order' => 3
            ],
            [
                'code' => 'wildfire',
                'name' => 'Waldbrand',
                'description' => 'Waldbrände und Vegetationsbrände',
                'color' => '#EA580C', // orange-600
                'icon' => 'fa-fire',
                'sort_order' => 4
            ],
            [
                'code' => 'volcano',
                'name' => 'Vulkan',
                'description' => 'Vulkanische Aktivitäten und Ausbrüche',
                'color' => '#DC2626', // red-600
                'icon' => 'fa-mountain',
                'sort_order' => 5
            ],
            [
                'code' => 'drought',
                'name' => 'Dürre',
                'description' => 'Wassermangel und Dürreperioden',
                'color' => '#CA8A04', // yellow-600
                'icon' => 'fa-sun',
                'sort_order' => 6
            ],
            [
                'code' => 'exercise',
                'name' => 'Übung',
                'description' => 'Trainings und Übungsszenarien',
                'color' => '#059669', // emerald-600
                'icon' => 'fa-graduation-cap',
                'sort_order' => 7
            ],
            [
                'code' => 'other',
                'name' => 'Sonstiges',
                'description' => 'Andere Events und spezielle Situationen',
                'color' => '#6B7280', // gray-500
                'icon' => 'fa-exclamation-circle',
                'sort_order' => 99
            ]
        ];

        foreach ($eventTypes as $eventType) {
            EventType::create($eventType);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        EventType::truncate();
    }
};
