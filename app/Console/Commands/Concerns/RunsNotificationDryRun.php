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
            $service->collectDecisions();
            $result = $service->processUnnotifiedEvents($source);

            $this->renderDecisions($service->getDecisions());
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
    private function renderDecisions(array $decisions): void
    {
        if ($decisions === []) {
            $this->line('Keine Regel wurde ausgewertet – es gab keine passenden Events oder keine aktiven Regeln.');

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
