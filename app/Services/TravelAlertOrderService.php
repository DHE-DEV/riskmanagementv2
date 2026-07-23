<?php

namespace App\Services;

use App\Mail\TravelAlertAccessActivatedMail;
use App\Mail\TravelAlertOrderMail;
use App\Mail\TravelAlertOrderPendingApprovalMail;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\TravelAlertOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Bestellablauf fuer Travel Alert.
 *
 * Der Kunde bestaetigt jede Bestellung per Mail (Double Opt-in). Erst danach
 * wird ueber die Freischaltung entschieden: bei TRAVEL_ALERT_AUTO_ACTIVATION
 * sofort, sonst durch einen Mitarbeiter im Backend.
 */
class TravelAlertOrderService
{
    public function __construct(
        protected CustomerFeatureService $featureService,
    ) {}

    public function autoActivationEnabled(): bool
    {
        return (bool) config('app.travel_alert_auto_activation', true);
    }

    public function generateConfirmationToken(): string
    {
        do {
            $token = Str::random(64);
        } while (TravelAlertOrder::where('confirmation_token', $token)->exists());

        return $token;
    }

    /**
     * Der Kunde hat den Link aus der Bestaetigungsmail geklickt.
     *
     * Damit ist auch die E-Mail-Adresse nachgewiesen – ein zweiter
     * Verifizierungslink waere nur eine zusaetzliche Huerde.
     */
    public function confirm(TravelAlertOrder $order): void
    {
        $order->forceFill(['confirmed_at' => now()])->save();

        $customer = $order->customer;

        if ($customer && ! $customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
        }

        if ($this->autoActivationEnabled()) {
            $this->activate($order);
        } else {
            Mail::to($order->email)->send(new TravelAlertOrderPendingApprovalMail($order));
        }

        $this->notifyStaff($order, 'confirmed');
    }

    /**
     * Freischaltung durch einen Mitarbeiter im Backend.
     */
    public function approve(TravelAlertOrder $order, ?int $userId = null): void
    {
        $this->activate($order, $userId);
    }

    public function reject(TravelAlertOrder $order, ?int $userId = null): void
    {
        $order->forceFill([
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'approved_at' => null,
            'approved_by' => null,
        ])->save();
    }

    /**
     * Zugang tatsaechlich freischalten und den Kunden informieren.
     */
    protected function activate(TravelAlertOrder $order, ?int $userId = null): void
    {
        $order->forceFill([
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
        ])->save();

        $customer = $order->customer;

        if (! $customer) {
            Log::warning('TravelAlert-Bestellung ohne Kundenkonto freigeschaltet', ['order_id' => $order->id]);

            return;
        }

        $this->enableFeature($customer);

        Mail::to($order->email)->send(new TravelAlertAccessActivatedMail($order));
    }

    /**
     * Feature fuer den Kunden aktivieren.
     *
     * Ein bereits gesetztes Override wird nur angehoben, nie entzogen – eine
     * Bestellung darf keinem Kunden den Zugang wegnehmen.
     */
    public function enableFeature(Customer $customer): void
    {
        $override = CustomerFeatureOverride::firstOrNew(['customer_id' => $customer->id]);

        if ($override->exists && $override->navigation_risk_overview_enabled === true) {
            return;
        }

        $override->customer_id = $customer->id;
        $override->navigation_risk_overview_enabled = true;
        $override->save();

        $this->featureService->clearCache($customer->id);
    }

    /**
     * Interne Bestellmail an Passolution.
     *
     * $stage 'received' geht beim Absenden raus, 'confirmed' nach der
     * Bestaetigung durch den Kunden.
     */
    public function notifyStaff(TravelAlertOrder $order, string $stage): void
    {
        $recipient = config('mail.order_recipient', 'info@passolution.de');

        Mail::to($recipient)
            ->bcc(['info@passolution.de', 'info@dhe.de'])
            ->send(new TravelAlertOrderMail(
                $order->only($order->getFillable()),
                $this->accountWasCreatedForOrder($order),
                $order->customer_id,
                $stage,
                $order->awaitsApproval(),
            ));
    }

    /**
     * Ob das Kundenkonto erst durch diese Bestellung entstanden ist. Der
     * Datensatz wird unmittelbar nach der Bestellung angelegt, ein aelteres
     * Konto gehoert also zu einem Bestandskunden.
     */
    protected function accountWasCreatedForOrder(TravelAlertOrder $order): bool
    {
        $customer = $order->customer;

        return $customer !== null
            && $customer->created_at !== null
            && $customer->created_at->greaterThanOrEqualTo($order->created_at);
    }
}
