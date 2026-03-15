<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Services\CustomerFeatureService;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function __construct(
        protected CustomerFeatureService $featureService,
    ) {}

    public function index()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

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

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $logs = NotificationLog::where('customer_id', $customer->id)
            ->with('notificationRule')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('customer.notification-settings.history', compact('logs'));
    }

    public function toggleNotifications(Request $request)
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

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

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            return response()->json(['error' => 'Nicht berechtigt'], 403);
        }

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

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        return view('customer.notification-settings.rules.form', [
            'rule' => null,
        ]);
    }

    public function editRule(int $id)
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $rule = $customer->notificationRules()->with('recipients')->findOrFail($id);

        return view('customer.notification-settings.rules.form', [
            'rule' => $rule,
        ]);
    }

    public function templateIndex()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $templates = NotificationTemplate::forCustomer($customer->id)
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

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        return view('customer.notification-settings.templates.form', [
            'template' => null,
        ]);
    }

    public function editTemplate(int $id)
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

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
        $logs = NotificationLog::where('customer_id', $customer->id)
            ->with('notificationRule:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        if (request()->wantsJson()) {
            return response()->json($logs);
        }

        return response()->json($logs);
    }

    public function rulesJson()
    {
        $customer = auth('customer')->user();
        $rules = $customer->notificationRules()
            ->with(['recipients', 'template'])
            ->latest()
            ->get()
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

        $template = $rule->template ?? NotificationTemplate::system()->first();
        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Keine E-Mail-Vorlage gefunden.'], 404);
        }

        $placeholders = [
            '{event_title}' => 'Test-Ereignis',
            '{country_name}' => 'Deutschland',
            '{risk_level}' => 'Hoch',
            '{category}' => 'Allgemein',
            '{description}' => 'Dies ist eine Test-Benachrichtigung für die Regel "' . $rule->name . '".',
            '{event_date}' => now()->format('d.m.Y'),
            '{unsubscribe_url}' => '#',
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

        $placeholders = [
            '{event_title}' => 'Test-Ereignis',
            '{country_name}' => 'Deutschland',
            '{risk_level}' => 'Hoch',
            '{category}' => 'Allgemein',
            '{description}' => 'Dies ist eine Test-Benachrichtigung um den E-Mail-Versand zu prüfen.',
            '{event_date}' => now()->format('d.m.Y'),
            '{unsubscribe_url}' => '#',
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
