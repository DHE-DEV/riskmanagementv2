#!/bin/bash

echo "========================================="
echo "Import Diagnose Script"
echo "========================================="
echo ""

echo "1. Prüfe Jobs-Tabelle..."
php artisan tinker --execute="
try {
    \$count = DB::table('jobs')->count();
    echo '✓ Jobs-Tabelle existiert (' . \$count . ' Jobs in Queue)' . PHP_EOL;
} catch (\Exception \$e) {
    echo '✗ Jobs-Tabelle fehlt: ' . \$e->getMessage() . PHP_EOL;
    echo 'Lösung: php artisan queue:table && php artisan migrate' . PHP_EOL;
}
"

echo ""
echo "2. Prüfe Queue-Konfiguration..."
php artisan tinker --execute="
echo 'Queue Connection: ' . config('queue.default') . PHP_EOL;
echo 'Queue Driver: ' . config('queue.connections.' . config('queue.default') . '.driver') . PHP_EOL;
"

echo ""
echo "3. Prüfe ob Queue-Worker läuft..."
WORKER_COUNT=$(ps aux | grep "queue:work" | grep -v grep | wc -l)
if [ $WORKER_COUNT -gt 0 ]; then
    echo "✓ Queue-Worker läuft ($WORKER_COUNT Prozess(e))"
    ps aux | grep "queue:work" | grep -v grep
else
    echo "✗ Queue-Worker läuft NICHT"
    echo "Lösung: php artisan queue:work --tries=3 --timeout=300 &"
fi

echo ""
echo "4. Prüfe Branch Management ist aktiviert..."
php artisan tinker --execute="
\$customer = \App\Models\Customer::where('email', 'p1@dhe.de')->first();
if (\$customer) {
    echo 'Customer gefunden: ' . \$customer->name . PHP_EOL;
    echo 'Branch Management aktiv: ' . (\$customer->branch_management_active ? 'Ja' : 'Nein') . PHP_EOL;
    if (!\$customer->branch_management_active) {
        echo '⚠ Branch Management ist NICHT aktiviert!' . PHP_EOL;
        echo 'Lösung: Aktivieren Sie Branch Management im Dashboard' . PHP_EOL;
    }
} else {
    echo '✗ Customer nicht gefunden' . PHP_EOL;
}
"

echo ""
echo "5. Test Import-Route..."
php artisan route:list | grep "branches/import"

echo ""
echo "6. Prüfe Logs auf Fehler..."
if [ -f storage/logs/laravel.log ]; then
    echo "Letzte 10 Zeilen aus laravel.log:"
    tail -n 10 storage/logs/laravel.log
else
    echo "Keine Logs gefunden"
fi

echo ""
echo "========================================="
echo "Diagnose abgeschlossen"
echo "========================================="
