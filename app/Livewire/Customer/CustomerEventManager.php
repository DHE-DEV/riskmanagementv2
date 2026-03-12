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

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $eventId): void
    {
        $customer = auth('customer')->user();
        $event = $customer->customEvents()->with('eventTypes')->findOrFail($eventId);

        $this->editingEventId = $event->id;
        $this->title = $event->title;
        $this->description = $event->description ?? '';
        $this->selectedEventTypes = $event->eventTypes->pluck('id')->toArray();
        $this->priority = $event->priority ?? 'medium';
        $this->startDate = $event->start_date?->format('Y-m-d');
        $this->endDate = $event->end_date?->format('Y-m-d');
        $this->isActive = $event->is_active;
        $this->showForm = true;
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

        $this->resetForm();
        $this->showForm = false;

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
