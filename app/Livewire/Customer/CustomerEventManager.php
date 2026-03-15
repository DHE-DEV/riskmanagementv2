<?php

namespace App\Livewire\Customer;

use App\Models\CustomEvent;
use App\Models\EventType;
use Livewire\Component;

class CustomerEventManager extends Component
{
    public ?int $editingEventId = null;
    public bool $showForm = false;
    public ?int $deletingEventId = null;

    // Form fields
    public string $title = '';
    public string $description = '';
    public array $selectedEventTypes = [];
    public string $priority = 'medium';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public bool $isActive = true;
    public ?string $latitude = null;
    public ?string $longitude = null;
    public bool $visibleCommunity = false;
    public ?string $communityStartDate = null;
    public ?string $communityEndDate = null;
    public bool $visibleOrganization = true;
    public ?string $organizationStartDate = null;
    public ?string $organizationEndDate = null;
    public array $selectedOrgNodes = [];
    public array $orgNodeDates = [];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'selectedEventTypes' => 'required|array|min:1',
            'selectedEventTypes.*' => 'exists:event_types,id',
            'priority' => 'required|in:info,low,medium,high',
            'startDate' => 'required|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'isActive' => 'boolean',
            'visibleCommunity' => 'boolean',
            'visibleOrganization' => 'boolean',
            'selectedOrgNodes' => 'array',
            'selectedOrgNodes.*' => 'exists:org_nodes,id',
            'orgNodeDates' => 'array',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Bitte geben Sie einen Titel ein.',
            'title.max' => 'Der Titel darf maximal 255 Zeichen lang sein.',
            'description.max' => 'Die Beschreibung darf maximal 5000 Zeichen lang sein.',
            'selectedEventTypes.required' => 'Bitte wählen Sie mindestens eine Kategorie aus.',
            'selectedEventTypes.min' => 'Bitte wählen Sie mindestens eine Kategorie aus.',
            'priority.required' => 'Bitte wählen Sie eine Risikostufe aus.',
            'startDate.required' => 'Bitte geben Sie ein Startdatum ein.',
            'startDate.date' => 'Bitte geben Sie ein gültiges Datum ein.',
            'endDate.date' => 'Bitte geben Sie ein gültiges Datum ein.',
            'endDate.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.',
        ];
    }

    public function viewEvent(int $eventId): void
    {
        $customer = auth('customer')->user();
        $event = $customer->customEvents()->with(['eventTypes', 'orgNodes'])->findOrFail($eventId);

        $this->dispatch('event-view-opened', data: [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description ?? '',
            'selectedEventTypes' => $event->eventTypes->pluck('name')->toArray(),
            'priority' => $event->priority,
            'startDate' => $event->start_date?->format('d.m.Y'),
            'endDate' => $event->end_date?->format('d.m.Y'),
            'isActive' => $event->is_active,
            'latitude' => $event->latitude ? (string) $event->latitude : null,
            'longitude' => $event->longitude ? (string) $event->longitude : null,
            'visibleCommunity' => $event->visible_community ?? false,
            'communityStartDate' => $event->community_start_date?->format('d.m.Y'),
            'communityEndDate' => $event->community_end_date?->format('d.m.Y'),
            'visibleOrganization' => $event->visible_organization ?? true,
            'organizationStartDate' => $event->organization_start_date?->format('d.m.Y'),
            'organizationEndDate' => $event->organization_end_date?->format('d.m.Y'),
            'orgNodes' => $event->orgNodes->map(fn($n) => [
                'name' => $n->name,
                'color' => $n->color,
                'start_date' => $n->pivot->start_date ? \Carbon\Carbon::parse($n->pivot->start_date)->format('d.m.Y') : null,
                'end_date' => $n->pivot->end_date ? \Carbon\Carbon::parse($n->pivot->end_date)->format('d.m.Y') : null,
            ])->toArray(),
            'createdAt' => $event->created_at?->format('d.m.Y H:i'),
            'updatedAt' => $event->updated_at?->format('d.m.Y H:i'),
        ]);
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('event-form-opened', mode: 'create', data: [
            'id' => null, 'title' => '', 'description' => '',
            'selectedEventTypes' => [], 'priority' => 'medium',
            'startDate' => null, 'endDate' => null, 'isActive' => true,
            'latitude' => null, 'longitude' => null,
            'visibleCommunity' => false, 'communityStartDate' => null, 'communityEndDate' => null,
            'visibleOrganization' => true, 'organizationStartDate' => null, 'organizationEndDate' => null,
            'selectedOrgNodes' => [], 'orgNodeDates' => [],
        ]);
    }

    public function openEditForm(int $eventId): void
    {
        $customer = auth('customer')->user();
        $event = $customer->customEvents()->with(['eventTypes', 'orgNodes'])->findOrFail($eventId);

        $this->editingEventId = $event->id;
        $this->title = $event->title;
        $this->description = $event->description ?? '';
        $this->selectedEventTypes = $event->eventTypes->pluck('id')->toArray();
        $this->priority = $event->priority ?? 'medium';
        $this->startDate = $event->start_date?->format('Y-m-d');
        $this->endDate = $event->end_date?->format('Y-m-d');
        $this->isActive = $event->is_active;
        $this->latitude = $event->latitude ? (string) $event->latitude : null;
        $this->longitude = $event->longitude ? (string) $event->longitude : null;
        $this->visibleCommunity = $event->visible_community ?? false;
        $this->communityStartDate = $event->community_start_date?->format('Y-m-d');
        $this->communityEndDate = $event->community_end_date?->format('Y-m-d');
        $this->visibleOrganization = $event->visible_organization ?? true;
        $this->organizationStartDate = $event->organization_start_date?->format('Y-m-d');
        $this->organizationEndDate = $event->organization_end_date?->format('Y-m-d');
        $this->selectedOrgNodes = $event->orgNodes->pluck('id')->toArray();
        $this->orgNodeDates = $event->orgNodes->map(fn($n) => [
            'id' => $n->id,
            'start_date' => $n->pivot->start_date,
            'end_date' => $n->pivot->end_date,
        ])->toArray();
        $this->showForm = true;
        $this->dispatch('event-form-opened', mode: 'edit', data: [
            'id' => $event->id, 'title' => $this->title, 'description' => $this->description,
            'selectedEventTypes' => $this->selectedEventTypes, 'priority' => $this->priority,
            'startDate' => $this->startDate, 'endDate' => $this->endDate, 'isActive' => $this->isActive,
            'latitude' => $this->latitude, 'longitude' => $this->longitude,
            'visibleCommunity' => $this->visibleCommunity,
            'communityStartDate' => $this->communityStartDate, 'communityEndDate' => $this->communityEndDate,
            'visibleOrganization' => $this->visibleOrganization,
            'organizationStartDate' => $this->organizationStartDate, 'organizationEndDate' => $this->organizationEndDate,
            'selectedOrgNodes' => $this->selectedOrgNodes, 'orgNodeDates' => $this->orgNodeDates,
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $customer = auth('customer')->user();

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'priority' => $this->priority,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate ?: null,
            'is_active' => $this->isActive,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            'visible_community' => $this->visibleCommunity,
            'community_start_date' => $this->communityStartDate ?: null,
            'community_end_date' => $this->communityEndDate ?: null,
            'visible_organization' => $this->visibleOrganization,
            'organization_start_date' => $this->organizationStartDate ?: null,
            'organization_end_date' => $this->organizationEndDate ?: null,
            'customer_id' => $customer->id,
            'event_type' => 'other',
            'review_status' => 'approved',
        ];

        if ($this->editingEventId) {
            $event = $customer->customEvents()->findOrFail($this->editingEventId);
            $event->update($data);
        } else {
            $event = CustomEvent::create($data);
        }

        // Sync event types
        $event->eventTypes()->sync($this->selectedEventTypes);

        // Sync org nodes with dates
        $syncData = [];
        foreach ($this->selectedOrgNodes as $nodeId) {
            $dates = collect($this->orgNodeDates)->firstWhere('id', $nodeId);
            $syncData[$nodeId] = [
                'start_date' => $dates['start_date'] ?? null,
                'end_date' => $dates['end_date'] ?? null,
            ];
        }
        $event->orgNodes()->sync($syncData);

        $this->resetForm();
        $this->showForm = false;

        $this->dispatch('event-form-closed');

        session()->flash('success', $this->editingEventId
            ? 'Ereignis erfolgreich aktualisiert.'
            : 'Ereignis erfolgreich erstellt.'
        );
    }

    public function confirmDelete(int $eventId): void
    {
        $this->deletingEventId = $eventId;
    }

    public function cancelDelete(): void
    {
        $this->deletingEventId = null;
    }

    public function deleteEvent(): void
    {
        if (! $this->deletingEventId) {
            return;
        }

        $customer = auth('customer')->user();
        $event = $customer->customEvents()->findOrFail($this->deletingEventId);
        $event->eventTypes()->detach();
        $event->delete();

        $this->deletingEventId = null;

        session()->flash('success', 'Ereignis erfolgreich gelöscht.');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('event-form-closed');
    }

    public function toggleActive(int $eventId): void
    {
        $customer = auth('customer')->user();
        $event = $customer->customEvents()->findOrFail($eventId);
        $event->update(['is_active' => ! $event->is_active]);
    }

    private function resetForm(): void
    {
        $this->editingEventId = null;
        $this->title = '';
        $this->description = '';
        $this->selectedEventTypes = [];
        $this->priority = 'medium';
        $this->startDate = null;
        $this->endDate = null;
        $this->isActive = true;
        $this->latitude = null;
        $this->longitude = null;
        $this->visibleCommunity = false;
        $this->communityStartDate = null;
        $this->communityEndDate = null;
        $this->visibleOrganization = true;
        $this->organizationStartDate = null;
        $this->organizationEndDate = null;
        $this->selectedOrgNodes = [];
        $this->orgNodeDates = [];
        $this->resetValidation();
    }

    public function render()
    {
        $customer = auth('customer')->user();
        $events = $customer->customEvents()
            ->with('eventTypes')
            ->latest()
            ->get();

        $eventTypes = EventType::active()->ordered()->get();

        return view('livewire.customer.customer-event-manager', [
            'events' => $events,
            'eventTypes' => $eventTypes,
        ]);
    }
}
