<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // For each customer that has no groups yet, create the two default groups
        $customerIds = Customer::pluck('id');
        $customersWithGroups = EmployeeGroup::distinct()->pluck('customer_id');
        $customersMissingGroups = $customerIds->diff($customersWithGroups);

        foreach ($customersMissingGroups as $customerId) {
            EmployeeGroup::create([
                'customer_id' => $customerId,
                'name' => 'Administratoren',
                'description' => 'Systemadministratoren in der Passolution Travel Information Platform',
            ]);

            EmployeeGroup::create([
                'customer_id' => $customerId,
                'name' => 'Mitarbeiter',
                'description' => 'Mitarbeiter der Organisation',
            ]);
        }

        // Assign all employees without any group to the "Administratoren" group of their customer
        $employeesWithoutGroups = Employee::whereDoesntHave('groups')->get();

        foreach ($employeesWithoutGroups as $employee) {
            $adminGroup = EmployeeGroup::where('customer_id', $employee->customer_id)
                ->where('name', 'Administratoren')
                ->first();

            if ($adminGroup) {
                $employee->groups()->attach($adminGroup->id);
            }
        }
    }

    public function down(): void
    {
        // Not reversible - groups and assignments would need manual cleanup
    }
};
