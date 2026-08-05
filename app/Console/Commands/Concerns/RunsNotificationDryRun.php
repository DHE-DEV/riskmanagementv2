<?php

namespace App\Console\Commands\Concerns;

use App\Mail\RiskEventMail;
use App\Services\NotificationRuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Dry-Run fuer die Benachrichtigungs-Queues.
 *
 * Laesst den kompletten Verarbeitungspfad laufen, versendet aber nichts und
 * hinterlaesst keine Spuren in der Datenbank:
 *
 *   - Mail::fake()   verhindert den SMTP-Versand
 *   - Transaktion    wird am Ende immer zurueckgerollt, damit die im Lauf
 *                    geschriebenen notification_logs den spaeteren Echtlauf
 *                    nicht per Dublettenpruefung blockieren
 *   - render()       baut jede Mail trotzdem einmal, damit Fehler in den
 *                    Templates auffallen (Mail::fake() allein rendert nicht)
 */
trait RunsNotificationDryRun
{
    protected function runDryRun(NotificationRuleService $service, string $source, string $queueName): int
    {
        $this->warn("[{$queueName}] DRY-RUN – es wird nichts versendet und nichts gespeichert.");
        $this->newLine();

        Mail::fake();
        DB::beginTransaction();

        try {
            $summary = $service->scopeSummary($source);
            $service->collectDecisions();
            $result = $service->processUnnotifiedEvents($source);

            $this->renderDecisions($service->getDecisions(), $summary);
            $renderErrors = $this->renderMailables();

            $this->newLine();
            $this->line("Events verarbeitet  : {$result['events_processed']}");
            $this->line("Mails wuerden gehen : {$result['notifications_sent']}");
            $this->line("Fehler im Lauf      : {$result['errors']}");
            $this->line('Template-Fehler     : '.count($renderErrors));

            foreach ($renderErrors as $error) {
                $this->error("  Template konnte nicht gerendert werden: {$error}");
            }

            $this->newLine();
            $this->info("[{$queueName}] Dry-Run beendet – keine Mail versendet, Datenbank unveraendert.");

            return ($result['errors'] > 0 || $renderErrors !== []) ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("[{$queueName}] Dry-Run fehlgeschlagen: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Gibt pro Regel aus, was passiert waere – und warum.
     *
     * @param  array<int, array<string, mixed>>  $decisions
     */
    private function renderDecisions(array $decisions, array $summary): void
    {
        if ($decisions === []) {
            $this->explainEmptyRun($summary);

            return;
        }

        $this->table(
            ['Event', 'Regel', 'Kunde', 'Ergebnis', 'Empfaenger', 'Begruendung'],
            array_map(fn (array $d) => [
                $d['event_id'],
                "{$d['rule_id']} – {$d['rule_name']}",
                $d['customer_id'],
                match ($d['outcome']) {
                    'sent' => 'WUERDE SENDEN',
                    'failed' => 'FEHLER',
                    default => 'uebersprungen',
                },
                $d['recipient'] ?? '–',
                $d['reason'],
            ], $decisions),
        );
    }

    /**
     * Benennt bei einem Lauf ohne einzige Regel-Auswertung konkret die
     * fehlende Voraussetzung, statt beide Moeglichkeiten offenzulassen.
     *
     * @param  array<string, int>  $summary
     */
    private function explainEmptyRun(array $summary): void
    {
        $events = $summary['custom_events'] + $summary['disaster_events'];

        $this->line(sprintf(
            'Events im Fenster (%dh): %d (%d CustomEvents, %d DisasterEvents)',
            $summary['lookback_hours'],
            $events,
            $summary['custom_events'],
            $summary['disaster_events'],
        ));
        $this->line(sprintf(
            'Regeln dieser Quelle    : %d gesamt, %d aktiv, %d davon bei Kunden mit aktiviertem Versand',
            $summary['rules_total'],
            $summary['rules_active'],
            $summary['rules_effective'],
        ));
        $this->newLine();

        if ($events === 0) {
            $this->warn('Keine Regel ausgewertet: Es liegen keine Events im Lookback-Fenster.');
            $this->line('  -> NOTIFICATION_LOOKBACK_HOURS pruefen, oder es wurden schlicht keine Events angelegt.');

            return;
        }

        if ($summary['rules_total'] === 0) {
            $this->warn('Keine Regel ausgewertet: Fuer diese Quelle existiert keine einzige Regel.');
            $this->line('  -> Im Kundenbereich unter Einstellungen eine Benachrichtigungsregel anlegen.');

            return;
        }

        if ($summary['rules_active'] === 0) {
            $this->warn('Keine Regel ausgewertet: Alle Regeln dieser Quelle sind inaktiv (is_active = 0).');

            return;
        }

        if ($summary['rules_effective'] === 0) {
            $this->warn('Keine Regel ausgewertet: Die aktiven Regeln gehoeren zu Kunden mit deaktiviertem Versand.');
            $this->line('  -> Toggle "Automatische Benachrichtigungen" beim Kunden einschalten (customers.notifications_enabled).');

            return;
        }

        $this->warn('Keine Regel ausgewertet, obwohl Events und Regeln vorhanden sind – bitte Log pruefen.');
    }

    /**
     * Baut jede aufgelaufene Mail einmal, um Template-Fehler sichtbar zu machen.
     *
     * @return array<int, string>
     */
    private function renderMailables(): array
    {
        $errors = [];

        foreach (Mail::sent(RiskEventMail::class) as $mailable) {
            try {
                $mailable->render();
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }
}
