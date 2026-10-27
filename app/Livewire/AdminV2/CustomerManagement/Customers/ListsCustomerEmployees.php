<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;

/**
 * Tab „Mitarbeiter“: Mitarbeiter-Zugaenge, die der Kunde selbst angelegt hat
 * (Einstellungen › Benutzerverwaltung) – nur zur Ansicht.
 */
trait ListsCustomerEmployees
{
    /** Mitarbeiter je Seite */
    public const EMPLOYEES_PER_PAGE = 30;

    public string $employeeSearch = '';

    /** all | active | inactive */
    public string $employeeStatus = 'all';

    public function updatedEmployeeSearch(): void
    {
        $this->resetPage('employeesPage');
    }

    public function updatedEmployeeStatus(): void
    {
        $this->resetPage('employeesPage');
    }

    #[Computed]
    public function employees(): LengthAwarePaginator
    {
        $query = Employee::query()->where('customer_id', $this->customer->id)->with('branch:id,name');

        if (($term = trim($this->employeeSearch)) !== '') {
            $this->whereEveryWord($query, $term, ['first_name', 'last_name', 'email', 'position', 'department', 'personnel_number']);
        }

        $today = today()->toDateString();
        $activeNow = fn ($q) => $q->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('active_from')->orWhereDate('active_from', '<=', $today))
            ->where(fn ($q) => $q->whereNull('active_until')->orWhereDate('active_until', '>=', $today));

        match ($this->employeeStatus) {
            'active' => $query->where($activeNow),
            'inactive' => $query->whereNot($activeNow),
            default => null,
        };

        return $this->paginateWithinRange($query->orderBy('last_name')->orderBy('first_name')->orderBy('id'), self::EMPLOYEES_PER_PAGE, 'employeesPage');
    }

    #[Computed]
    public function employeeCount(): int
    {
        return Employee::where('customer_id', $this->customer->id)->count();
    }
}
