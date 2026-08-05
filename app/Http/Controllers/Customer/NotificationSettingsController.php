<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

/**
 * Bewusst ohne Feature-Gate: Diese Endpunkte bedienen sowohl Travel Alert als
 * auch den Global Travel Monitor. Frueher pruefte ein Teil der Methoden
 * navigation_risk_overview_enabled – das Flag von Travel Alert –, wodurch das
 * Deaktivieren von Travel Alert auch die GTM-Benachrichtigungen lahmlegte:
 * Regeln und Protokoll luden (dort gab es nie ein Gate), die Vorlagenliste
 * antwortete dagegen mit 403 und blieb leer.
 *
 * Der Zugriff ist weiterhin durch den customer-Guard geschuetzt, und jede
 * Methode arbeitet ausschliesslich auf den Daten des angemeldeten Kunden.
 */
class NotificationSettingsController extends Controller
{

    public function index()
    {
        $customer = auth('customer')->user();

        $rules = $customer->notificationRules()
            ->with(['recipients', 'template'])
            ->latest()
            ->get();

        $templateCount = NotificationTemplate::forCustomer($customer->id)->count();
        $customTemplateCount = $customer->notificationTemplates()->count();
        $systemTemplateCount = NotificationTemplate::system()->count();

        return view('customer.notification-settings.index', compact(
            'customer',
            'rules',
            'templateCount',
            'customTemplateCount',
            'systemTemplateCount',
        ));
    }

    public function history()
    {
        $customer = auth('customer')->user();

        $logs = NotificationLog::where('customer_id', $customer->id)
            ->with('notificationRule')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('customer.notification-settings.history', compact('logs'));
    }

    public function toggleNotifications(Request $request)
    {
        $customer = auth('customer')->user();

        $customer->update([
            'notifications_enabled' => ! $customer->notifications_enabled,
        ]);

        return back()->with('success', $customer->notifications_enabled
            ? 'Benachrichtigungen aktiviert.'
            : 'Benachrichtigungen deaktiviert.'
        );
    }

    public function stats()
    {
        $customer = auth('customer')->user();

        return response()->json([
            'notifications_enabled' => $customer->notifications_enabled,
            'rules_count' => $customer->notificationRules()->count(),
            'active_rules_count' => $customer->notificationRules()->where('is_active', true)->count(),
            'templates_count' => NotificationTemplate::forCustomer($customer->id)->count(),
        ]);
    }

    public function createRule()
    {
        $customer = auth('customer')->user();

        return view('customer.notification-settings.rules.form', [
            'rule' => null,
        ]);
    }

    public function editRule(int $id)
    {
        $customer = auth('customer')->user();

        $rule = $customer->notificationRules()->with('recipients')->findOrFail($id);

        return view('customer.notification-settings.rules.form', [
            'rule' => $rule,
        ]);
    }

    public function templateIndex()
    {
        $customer = auth('customer')->user();

        $source = request()->query('source');
        $templates = NotificationTemplate::forCustomer($customer->id, $source)
            ->latest()
            ->get();

        if (request()->wantsJson()) {
            return response()->json(['templates' => $templates]);
        }

        return view('customer.notification-settings.templates.index', compact('templates'));
    }

    public function createTemplate()
    {
        $customer = auth('customer')->user();

        return view('customer.notification-settings.templates.form', [
            'template' => null,
        ]);
    }

    public function editTemplate(int $id)
    {
        $customer = auth('customer')->user();

        $template = NotificationTemplate::forCustomer($customer->id)->findOrFail($id);

        if ($template->is_system) {
            abort(403, 'System-Vorlagen können nicht bearbeitet werden.');
        }

        return view('customer.notification-settings.templates.form', [
            'template' => $template,
        ]);
    }

    public function logs()
    {
        $customer = auth('customer')->user();
        $query = NotificationLog::where('customer_id', $customer->id)
            ->with('notificationRule:id,name,source')
            ->orderBy('created_at', 'desc');

        $source = request()->query('source');
        if ($source) {
            // Test-Mails ohne Rule werden ueber den Vorlagennamen zugeordnet.
            // Die Namen werden bewusst vorab geladen statt per Unterabfrage mit
            // whereColumn verglichen: notification_logs.template_name und
            // notification_templates.name haben unterschiedliche Kollationen
            // (utf8mb4_general_ci vs. utf8mb4_unicode_ci), woran MySQL einen
            // Spalten-zu-Spalten-Vergleich mit Fehler 1267 abbricht.
            $templateNames = NotificationTemplate::where('source', $source)
                ->pluck('name')
                ->all();

            $query->where(function ($q) use ($source, $templateNames) {
                $q->whereHas('notificationRule', fn ($r) => $r->where('source', $source));

                if ($templateNames !== []) {
                    $q->orWhere(fn ($q2) => $q2
                        ->whereNull('notification_rule_id')
                        ->whereIn('template_name', $templateNames));
                }
            });
        }

        $logs = $query->paginate(25);

        return response()->json($logs);
    }

    public function rulesJson(Request $request)
    {
        $customer = auth('customer')->user();
        $query = $customer->notificationRules()
            ->with(['recipients', 'template'])
            ->latest();

        if ($request->has('source') && \Schema::hasColumn('notification_rules', 'source')) {
            $query->where('source', $request->input('source'));
        }

        $rules = $query->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'is_active' => $rule->is_active,
                    'risk_level_labels' => $rule->risk_levels ? $rule->risk_level_labels : [],
                    'category_labels' => $rule->categories ? $rule->category_labels : [],
                    'country_count' => $rule->country_ids ? count($rule->country_ids) : null,
                    'recipients_count' => $rule->recipients->count(),
                ];
            });

        return response()->json(['rules' => $rules]);
    }

    public function deleteRule(int $id)
    {
        $customer = auth('customer')->user();
        $rule = $customer->notificationRules()->findOrFail($id);
        $rule->recipients()->delete();
        $rule->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('customer.settings', ['section' => 'notifications'])
            ->with('success', 'Regel erfolgreich gelöscht.');
    }

    public function sendRuleTestMail(int $id)
    {
        $customer = auth('customer')->user();
        $rule = $customer->notificationRules()->with(['template', 'recipients'])->findOrFail($id);

        $source = $rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT;
        $template = $rule->template ?? NotificationTemplate::system($source)->first();
        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Keine E-Mail-Vorlage gefunden.'], 404);
        }

        // Betroffene Reisen für Test-Mail ermitteln (Travel Alert)
        $affectedTripsHtml = '';
        $affectedTripsCount = '0';
        if ($source === NotificationRule::SOURCE_TRAVEL_ALERT) {
            $activeTrips = \App\Models\TravelDetail\TdTrip::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->where('computed_start_at', '<=', now())
                ->where('computed_end_at', '>=', now())
                ->with('travellers')
                ->limit(10)
                ->get();
            if ($activeTrips->isNotEmpty()) {
                $affectedTripsCount = (string) $activeTrips->count();
                $affectedTripsHtml = app(\App\Services\NotificationRuleService::class)
                    ->buildAffectedTripsHtmlPublic($activeTrips);
            }
        }

        $placeholders = [
            '{event_title}' => 'Test-Ereignis',
            '{country_name}' => 'Deutschland',
            '{risk_level}' => 'Hoch',
            '{category}' => 'Allgemein',
            '{description}' => 'Dies ist eine Test-Benachrichtigung für die Regel "' . $rule->name . '".',
            '{event_date}' => now()->format('d.m.Y'),
            '{unsubscribe_url}' => '#',
            '{affected_trips}' => $affectedTripsHtml,
            '{affected_trips_count}' => $affectedTripsCount,
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($customer->email)
                ->send(new \App\Mail\RiskEventMail($template, $placeholders, $rule));

            NotificationLog::create([
                'customer_id' => $customer->id,
                'notification_rule_id' => $rule->id,
                'recipient_email' => $customer->email,
                'subject' => str_replace(array_keys($placeholders), array_values($placeholders), $template->subject),
                'template_name' => $template->name,
                'rule_name' => $rule->name,
                'is_test' => true,
                'status' => 'sent',
            ]);

            return response()->json(['success' => true, 'message' => 'Test-Mail für Regel "' . $rule->name . '" an ' . $customer->email . ' gesendet.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Fehler: ' . $e->getMessage()], 500);
        }
    }

    public function sendTestMail(int $id)
    {
        $customer = auth('customer')->user();
        $template = NotificationTemplate::forCustomer($customer->id)->findOrFail($id);

        // Betroffene Reisen für Test-Mail ermitteln (Travel Alert)
        $affectedTripsHtml = '';
        $affectedTripsCount = '0';
        $templateSource = $template->source ?? 'travel-alert';
        if ($templateSource === NotificationRule::SOURCE_TRAVEL_ALERT) {
            $activeTrips = \App\Models\TravelDetail\TdTrip::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->where('computed_start_at', '<=', now())
                ->where('computed_end_at', '>=', now())
                ->with('travellers')
                ->limit(10)
                ->get();
            if ($activeTrips->isNotEmpty()) {
                $affectedTripsCount = (string) $activeTrips->count();
                $affectedTripsHtml = app(\App\Services\NotificationRuleService::class)
                    ->buildAffectedTripsHtmlPublic($activeTrips);
            }
        }

        $placeholders = [
            '{event_title}' => 'Test-Ereignis',
            '{country_name}' => 'Deutschland',
            '{risk_level}' => 'Hoch',
            '{category}' => 'Allgemein',
            '{description}' => 'Dies ist eine Test-Benachrichtigung um den E-Mail-Versand zu prüfen.',
            '{event_date}' => now()->format('d.m.Y'),
            '{unsubscribe_url}' => '#',
            '{affected_trips}' => $affectedTripsHtml,
            '{affected_trips_count}' => $affectedTripsCount,
        ];

        $tempRule = new NotificationRule();
        $tempRule->setRelation('recipients', collect());

        try {
            \Illuminate\Support\Facades\Mail::to($customer->email)
                ->send(new \App\Mail\RiskEventMail($template, $placeholders, $tempRule));

            NotificationLog::create([
                'customer_id' => $customer->id,
                'recipient_email' => $customer->email,
                'subject' => str_replace(array_keys($placeholders), array_values($placeholders), $template->subject),
                'template_name' => $template->name,
                'is_test' => true,
                'status' => 'sent',
            ]);

            return response()->json(['success' => true, 'message' => 'Test-Mail "' . $template->name . '" an ' . $customer->email . ' gesendet.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Fehler: ' . $e->getMessage()], 500);
        }
    }

    public function deleteTemplate(int $id)
    {
        $customer = auth('customer')->user();
        $template = $customer->notificationTemplates()->findOrFail($id);
        $template->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('customer.notification-settings.templates.index')
            ->with('success', 'Vorlage erfolgreich gelöscht.');
    }
}
