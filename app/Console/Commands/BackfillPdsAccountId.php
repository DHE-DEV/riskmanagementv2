<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\PassolutionApiService;
use Illuminate\Console\Command;

/**
 * Traegt die Kundennummer (pds_account_id) fuer Bestandskunden nach.
 *
 * Hintergrund: pds_account_id wird beim SSO-Login aus dem userinfo-Response
 * gesetzt (account_id bzw. account.id). Kunden, die sich seit Einfuehrung der
 * Spalte nicht mehr angemeldet haben, stehen deshalb auf NULL – im Dashboard
 * erscheint dann "Kundennummer: Unbekannt".
 *
 * Der Befehl holt die Account-ID per __internal/account/info ueber die
 * E-Mail-Adresse und schreibt NUR pds_account_id. Alle uebrigen Stammdaten
 * bleiben unangetastet – die aktualisiert der naechste Login bzw.
 * syncPdsAccountData() ohnehin.
 *
 *   php artisan pds:backfill-account-id --dry-run   (Zahlen ansehen)
 *   php artisan pds:backfill-account-id             (schreiben)
 *
 * Bereits gefuellte Werte werden nie ueberschrieben, ausser mit --force.
 */
class BackfillPdsAccountId extends Command
{
    protected $signature = 'pds:backfill-account-id
                            {--dry-run : Nur auswerten und anzeigen, nichts schreiben}
                            {--force : Auch bereits gesetzte Kundennummern neu abfragen}
                            {--limit= : Nur die ersten N Kunden bearbeiten}';

    protected $description = 'Traegt die Kundennummer (pds_account_id) fuer Bestandskunden nach';

    public function handle(PassolutionApiService $api): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        if ($dryRun) {
            $this->comment('Testlauf – es wird nichts geschrieben.');
        }

        $query = Customer::query()
            ->when(! $force, fn ($q) => $q->whereNull('pds_account_id'))
            // Ohne eine der E-Mail-Adressen ist kein Lookup moeglich.
            ->where(function ($q) {
                $q->whereNotNull('email')
                    ->orWhereNotNull('pds_username')
                    ->orWhereNotNull('pds_email');
            })
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $customers = $query->get();

        if ($customers->isEmpty()) {
            $this->info('Keine Kunden ohne Kundennummer gefunden – nichts zu tun.');

            return self::SUCCESS;
        }

        $this->info($customers->count().' Kunden werden abgefragt...');
        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        $updated = 0;
        $unchanged = 0;
        $notFound = 0;
        $noEmail = 0;
        $misses = [];

        foreach ($customers as $customer) {
            $bar->advance();

            $email = $customer->email ?: ($customer->pds_username ?: $customer->pds_email);

            if (! $email) {
                $noEmail++;

                continue;
            }

            $account = $api->fetchAccountByEmail($email);
            $accountId = data_get($account, 'id');

            if (! $accountId) {
                $notFound++;
                $misses[] = [$customer->id, $email, 'kein Account gefunden'];

                continue;
            }

            if ((string) $customer->pds_account_id === (string) $accountId) {
                $unchanged++;

                continue;
            }

            if (! $dryRun) {
                // saveQuietly + forceFill: reiner Datenabgleich, keine Model-Events
                // und kein Anfassen von updated_at-abhaengiger Logik.
                $customer->forceFill(['pds_account_id' => $accountId])->saveQuietly();
            }

            $updated++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['', 'Anzahl'], [
            ['Geprueft', $customers->count()],
            [$dryRun ? 'Wuerden gesetzt' : 'Gesetzt', $updated],
            ['Unveraendert (ID bereits korrekt)', $unchanged],
            ['Kein Account zur E-Mail', $notFound],
            ['Ohne E-Mail-Adresse', $noEmail],
        ]);

        if ($misses !== [] && $this->output->isVerbose()) {
            $this->newLine();
            $this->table(['Kunde', 'E-Mail', 'Grund'], $misses);
        } elseif ($misses !== []) {
            $this->comment('Mit -v werden die '.count($misses).' nicht gefundenen Kunden aufgelistet.');
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('Zum Anwenden ohne --dry-run erneut ausfuehren.');
        }

        return self::SUCCESS;
    }
}
