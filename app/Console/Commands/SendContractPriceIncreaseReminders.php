<?php

namespace App\Console\Commands;

use App\Mail\ContractPriceIncreaseReminderMail;
use App\Models\CustomerContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Erinnert an vereinbarte jaehrliche Preiserhoehungen in Kundenvertraegen:
 * 30 Tage vor jedem Jahrestag des Vertragsbeginns eine Mail an Passolution
 * (config app.contract_price_increase_mail), einmal je Jahrestag. Preise
 * aendert der Befehl nicht.
 */
class SendContractPriceIncreaseReminders extends Command
{
    protected $signature = 'contracts:price-increase-reminders {--days=30 : So viele Tage vor dem Jahrestag erinnern}';

    protected $description = 'Erinnerungsmail für vereinbarte jährliche Preiserhöhungen in Kundenverträgen';

    public function handle(): int
    {
        $recipients = config('app.contract_price_increase_mail');
        if (empty($recipients['to'])) {
            $this->warn('Keine Empfänger konfiguriert (CONTRACT_PRICE_INCREASE_MAIL_TO).');

            return self::SUCCESS;
        }

        $days = (int) $this->option('days');
        $sent = 0;

        CustomerContract::query()
            ->where('annual_increase_percent', '>', 0)
            ->whereDate('starts_on', '<=', today()->addDays($days))
            ->with(['customer', 'prices.version.product'])
            ->each(function (CustomerContract $contract) use ($recipients, $days, &$sent) {
                $anniversary = $contract->nextAnniversary();

                $due = $anniversary->copy()->subDays($days)->lte(today());
                $stillRunning = ! $contract->ends_on || $contract->ends_on->gte($anniversary);
                $alreadySent = $contract->increase_reminded_for && $contract->increase_reminded_for->gte($anniversary);

                if (! $due || ! $stillRunning || $alreadySent || ! $contract->customer) {
                    return;
                }

                Mail::to($recipients['to'])->cc($recipients['cc'] ?? [])
                    ->send(new ContractPriceIncreaseReminderMail($contract, $anniversary));

                $contract->forceFill(['increase_reminded_for' => $anniversary->toDateString()])->save();
                $sent++;
                $this->line('Erinnerung: '.$contract->name.' ('.$contract->customer->company_name.'), Stichtag '.$anniversary->format('d.m.Y'));
            });

        $this->info($sent.' Erinnerung(en) verschickt.');

        return self::SUCCESS;
    }
}
