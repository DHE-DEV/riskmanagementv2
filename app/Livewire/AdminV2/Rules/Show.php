<?php

namespace App\Livewire\AdminV2\Rules;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Benachrichtigungsregel eines Kunden – zum Nachsehen. Die Regel selbst
 * pflegt der Kunde in seinem Bereich; hier wird nichts geaendert.
 */
#[Layout('components.layouts.adminv2.app')]
class Show extends Component
{
    use AuthorizesAdminV2;
    use WithPagination;

    #[Locked]
    public int $ruleId;

    public function mount($rule): void
    {
        // Auch geloeschte Regeln bleiben ueber den Versandverlauf erreichbar.
        $this->ruleId = NotificationRule::withTrashed()->findOrFail((int) $rule)->id;
    }

    #[Computed]
    public function rule(): NotificationRule
    {
        return NotificationRule::withTrashed()->with(['customer', 'recipients'])->findOrFail($this->ruleId);
    }

    /**
     * Die Vorlage, mit der die Regel verschickt: die eigene, sonst die Standard-Vorlage der Quelle.
     */
    #[Computed]
    public function template(): ?NotificationTemplate
    {
        $rule = $this->rule;

        return ($rule->notification_template_id ? NotificationTemplate::withTrashed()->find($rule->notification_template_id) : null)
            ?? NotificationTemplate::system($rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT)->first();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function countryNames(): array
    {
        return Country::query()
            ->whereIn('id', $this->rule->country_ids ?? [])
            ->get()
            ->map(fn (Country $country) => $country->getName('de'))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function categoryNames(): array
    {
        $options = NotificationRule::categoryOptions();

        return collect(NotificationRule::normalizeCategories($this->rule->categories))
            ->map(fn ($code) => $options[$code] ?? $code)
            ->all();
    }

    /**
     * Die Mails dieser Regel, neueste zuerst – zehn je Seite.
     */
    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        $logs = NotificationLog::query()
            ->where('notification_rule_id', $this->ruleId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        $events = CustomEvent::withTrashed()
            ->whereIn('id', $logs->getCollection()->where('event_type', CustomEvent::class)->pluck('event_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return $logs->through(fn (NotificationLog $log) => [
            'id' => $log->id,
            'at' => $log->created_at?->format('d.m.Y H:i'),
            'event_id' => $log->event_type === CustomEvent::class && $events->has($log->event_id) ? $log->event_id : null,
            'event_title' => $log->event_type === CustomEvent::class
                ? ($events->get($log->event_id)?->getTitle('de') ?: 'Ereignis '.$log->event_id)
                : ($log->is_test ? 'Test-Mail' : 'Anderes Ereignis'),
            'recipient' => $log->recipient_email,
            'subject' => $log->subject,
            'status' => $log->status,
            'error' => $log->error_message,
            'trips_count' => $log->affected_trips_count,
        ]);
    }

    public function render()
    {
        return view('livewire.admin-v2.rules.show')->title('Regel „'.$this->rule->name.'“');
    }
}
