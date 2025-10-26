#!/bin/bash

echo "=== Export-Diagnose ==="
echo ""

echo "1. Migration Status:"
php artisan migrate:status | grep -E "(2025_10_26|branch_export)"
echo ""

echo "2. Failed Jobs:"
php artisan queue:failed | tail -20
echo ""

echo "3. Queue Worker Status:"
ps aux | grep "queue:work" | grep -v grep
echo ""

echo "4. BranchExport Tabelle:"
php artisan tinker --execute="echo 'Anzahl Exporte: ' . App\Models\BranchExport::count(); echo PHP_EOL; echo 'Letzte 3 Exporte:'; App\Models\BranchExport::latest()->take(3)->get(['id', 'customer_id', 'status', 'created_at'])->each(function(\$e) { echo \$e->id . ' - Status: ' . \$e->status . ' - ' . \$e->created_at . PHP_EOL; });"
echo ""

echo "5. Letzte 20 Log-Einträge mit 'export':"
grep -i "export" storage/logs/laravel.log | tail -20
echo ""

echo "=== Diagnose abgeschlossen ==="
