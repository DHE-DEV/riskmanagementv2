<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ShowNotificationRules extends Command
{
    protected $signature = 'notifications:rules
        {customer? : Kunde als ID, E-Mail oder App-Code – ohne Angabe werden alle Kunden mit Regeln gelistet}
        {--source= : Nur eine Quelle: global-travel-monitor oder travel-alert}
        {--inactive : Auch inaktive Regeln anzeigen}';

    protected $description = 'Zeigt die Benachrichtigungsregeln eines Accounts samt Empfaengern und Kriterien';

    public function handle(): int
    {
        $source = $this->option('source');

        if ($source && ! in_array($source, [NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, NotificationRule::SOURCE_TRAVEL_ALERT], true)) {
            $this->error("Unbekannte Quelle '{$source}'. Erlaubt: global-travel-monitor, travel-alert");

            return self::FAILURE;
        }

        $customer = null;

        if ($needle = $this->argument('customer')) {
            $customer = $this->resolveCustomer($needle);

            if (! $customer) {
                $this->error("Kein Kunde gefunden fuer '{$needle}'.");

                return self::FAILURE;
            }
        }

        $rules = NotificationRule::query()
            ->with(['recipients', 'template', 'customer'])
            ->when($customer, fn ($q) => $q->where('customer_id', $customer->id))
            ->when($source, fn ($q) => $q->where('source', $source))
            ->when(! $this->option('inactive'), fn ($q) => $q->where('is_active', true))
            ->orderBy('customer_id')
            ->orderBy('source')
            ->get();

        if ($customer) {
            $this->line("Kunde   : {$customer->id} – ".$this->customerLabel($customer)." <{$customer->email}>");
            $this->line('Versand : '.($customer->notifications_enabled
                ? 'aktiviert'
                : 'DEAKTIVIERT – es geht nichts raus, egal welche Regeln existieren'));
            $this->newLine();
        }

        if ($rules->isEmpty()) {
            $this->warn($this->option('inactive')
                ? 'Keine Regeln vorhanden.'
                : 'Keine aktiven Regeln vorhanden (mit --inactive auch inaktive anzeigen).');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Kunde', 'Quelle', 'Aktiv', 'Name', 'Risiko', 'Kategorien', 'Laender', 'Vorlage', 'Empfaenger', 'Zuletzt versendet'],
            $rules->map(fn (NotificationRule $rule) => [
                $rule->id,
                $rule->customer_id.' – '.($rule->customer ? $this->customerLabel($rule->customer) : '?'),
                $rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT,
                $rule->is_active ? 'ja' : 'nein',
                $rule->name,
                $this->criteria($rule->risk_level_labels),
                $this->criteria($rule->category_labels),
                $this->countries($rule->country_ids),
                $rule->template?->name ?? 'System-Standard',
                $this->recipients($rule->recipients),
                $this->lastSent($rule),
            ])->all(),
        );

        $this->newLine();
        $this->line('"alle" bedeutet: kein Filter gesetzt, das Kriterium schraenkt nicht ein.');

        if ($rules->contains(fn (NotificationRule $r) => $r->recipients->where('recipient_type', 'to')->isEmpty())) {
            $this->warn('Achtung: Regeln ohne TO-Empfaenger versenden nichts.');
        }

        return self::SUCCESS;
    }

    /**
     * Nicht jeder Kunde hat einen company_name – dann ist die E-Mail
     * aussagekraeftiger als ein Platzhalter.
     */
    private function customerLabel(Customer $customer): string
    {
        return $customer->company_name ?: ($customer->email ?: '?');
    }

    private function resolveCustomer(string $needle): ?Customer
    {
        return Customer::query()
            ->when(is_numeric($needle), fn ($q) => $q->orWhere('id', (int) $needle))
            ->orWhere('email', $needle)
            ->orWhere('app_code', $needle)
            ->first();
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function criteria(array $labels): string
    {
        return $labels === [] ? 'alle' : implode(', ', $labels);
    }

    /**
     * @param  array<int, int>|null  $countryIds
     */
    private function countries(?array $countryIds): string
    {
        if (empty($countryIds)) {
            return 'alle';
        }

        // countries hat keine name-Spalte, der Name steckt in name_translations
        // und wird ueber getName() aufgeloest.
        $names = Country::whereIn('id', $countryIds)->get()
            ->map(fn (Country $c) => $c->getName('de'))
            ->sort()
            ->values()
            ->all();

        return $names === [] ? 'alle' : implode(', ', $names);
    }

    private function recipients(Collection $recipients): string
    {
        if ($recipients->isEmpty()) {
            return 'KEINE';
        }

        return $recipients
            ->sortBy(fn ($r) => $r->recipient_type === 'to' ? 0 : 1)
            ->map(fn ($r) => strtoupper($r->recipient_type).': '.$r->email)
            ->implode("\n");
    }

    private function lastSent(NotificationRule $rule): string
    {
        $log = NotificationLog::where('notification_rule_id', $rule->id)
            ->where('status', 'sent')
            ->latest('created_at')
            ->first();

        return $log?->created_at?->format('d.m.Y H:i') ?? '–';
    }
}
