<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\BranchesRelationManager;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a user for all tests
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_customers()
    {
        $customers = Customer::factory()->count(5)->create();

        Livewire::test(ListCustomers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($customers);
    }

    /** @test */
    public function it_can_render_index_page()
    {
        $this->get(CustomerResource::getUrl('index'))
            ->assertSuccessful();
    }

    /** @test */
    public function it_cannot_create_customer()
    {
        // The resource has canCreate() returning false
        $this->assertFalse(CustomerResource::canCreate());
    }

    /** @test */
    public function it_does_not_have_create_page()
    {
        // Verify that there is no create route
        $pages = CustomerResource::getPages();
        $this->assertArrayNotHasKey('create', $pages);
    }

    /** @test */
    public function it_can_render_edit_page()
    {
        $customer = Customer::factory()->create();

        $this->get(CustomerResource::getUrl('edit', ['record' => $customer]))
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_retrieve_data_in_edit_form()
    {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'customer_type' => 'private',
        ]);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertFormSet([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'customer_type' => 'private',
            ]);
    }

    /** @test */
    public function it_can_update_customer_basic_info()
    {
        $customer = Customer::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updatedData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function it_validates_required_name_field()
    {
        $customer = Customer::factory()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    }

    /** @test */
    public function it_validates_required_email_field()
    {
        $customer = Customer::factory()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['email' => ''])
            ->call('save')
            ->assertHasFormErrors(['email' => 'required']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $customer = Customer::factory()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['email' => 'not-a-valid-email'])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    /** @test */
    public function it_validates_unique_email()
    {
        $customer1 = Customer::factory()->create(['email' => 'existing@example.com']);
        $customer2 = Customer::factory()->create(['email' => 'other@example.com']);

        Livewire::test(EditCustomer::class, ['record' => $customer2->getRouteKey()])
            ->fillForm(['email' => 'existing@example.com'])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    /** @test */
    public function it_allows_same_email_for_same_customer()
    {
        $customer = Customer::factory()->create(['email' => 'same@example.com']);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['email' => 'same@example.com'])
            ->call('save')
            ->assertHasNoFormErrors(['email']);
    }

    /** @test */
    public function it_can_update_company_information()
    {
        $customer = Customer::factory()->business()->create();

        $updatedData = [
            'company_name' => 'New Company Name GmbH',
            'company_additional' => 'Abteilung Reisen',
            'company_street' => 'Hauptstraße',
            'company_house_number' => '123',
            'company_postal_code' => '12345',
            'company_city' => 'Berlin',
            'company_country' => 'Deutschland',
        ];

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => 'New Company Name GmbH',
            'company_city' => 'Berlin',
        ]);
    }

    /** @test */
    public function it_can_update_billing_address()
    {
        $customer = Customer::factory()->business()->create();

        $updatedData = [
            'billing_company_name' => 'Billing Company Ltd',
            'billing_additional' => 'Finance Department',
            'billing_street' => 'Billing Street',
            'billing_house_number' => '456',
            'billing_postal_code' => '54321',
            'billing_city' => 'München',
            'billing_country' => 'Deutschland',
        ];

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'billing_company_name' => 'Billing Company Ltd',
            'billing_city' => 'München',
        ]);
    }

    /** @test */
    public function it_can_toggle_directory_listing_active()
    {
        $customer = Customer::factory()->create(['directory_listing_active' => false]);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['directory_listing_active' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();
        $this->assertTrue($customer->directory_listing_active);
    }

    /** @test */
    public function it_can_toggle_branch_management_active()
    {
        $customer = Customer::factory()->create(['branch_management_active' => false]);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['branch_management_active' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();
        $this->assertTrue($customer->branch_management_active);
    }

    /** @test */
    public function it_can_toggle_hide_profile_completion()
    {
        $customer = Customer::factory()->create(['hide_profile_completion' => false]);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['hide_profile_completion' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();
        $this->assertTrue($customer->hide_profile_completion);
    }

    /** @test */
    public function it_can_update_email_verified_at()
    {
        $customer = Customer::factory()->unverified()->create();

        $verifiedAt = now();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['email_verified_at' => $verifiedAt->format('Y-m-d H:i:s')])
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();
        $this->assertNotNull($customer->email_verified_at);
    }

    /** @test */
    public function it_can_filter_by_customer_type()
    {
        $privateCustomer = Customer::factory()->private()->create();
        $businessCustomer = Customer::factory()->business()->create();

        Livewire::test(ListCustomers::class)
            ->filterTable('customer_type', 'private')
            ->assertCanSeeTableRecords([$privateCustomer])
            ->assertCanNotSeeTableRecords([$businessCustomer]);

        Livewire::test(ListCustomers::class)
            ->filterTable('customer_type', 'business')
            ->assertCanSeeTableRecords([$businessCustomer])
            ->assertCanNotSeeTableRecords([$privateCustomer]);
    }

    /** @test */
    public function it_can_filter_by_email_verified_status()
    {
        $verified = Customer::factory()->create(['email_verified_at' => now()]);
        $unverified = Customer::factory()->unverified()->create();

        Livewire::test(ListCustomers::class)
            ->filterTable('email_verified', 'verified')
            ->assertCanSeeTableRecords([$verified])
            ->assertCanNotSeeTableRecords([$unverified]);

        Livewire::test(ListCustomers::class)
            ->filterTable('email_verified', 'unverified')
            ->assertCanSeeTableRecords([$unverified])
            ->assertCanNotSeeTableRecords([$verified]);
    }

    /** @test */
    public function it_can_search_by_name()
    {
        $customer1 = Customer::factory()->create(['name' => 'John Smith']);
        $customer2 = Customer::factory()->create(['name' => 'Jane Doe']);

        Livewire::test(ListCustomers::class)
            ->searchTable('John')
            ->assertCanSeeTableRecords([$customer1])
            ->assertCanNotSeeTableRecords([$customer2]);
    }

    /** @test */
    public function it_can_search_by_email()
    {
        $customer1 = Customer::factory()->create(['email' => 'john@example.com']);
        $customer2 = Customer::factory()->create(['email' => 'jane@example.com']);

        Livewire::test(ListCustomers::class)
            ->searchTable('john@example.com')
            ->assertCanSeeTableRecords([$customer1])
            ->assertCanNotSeeTableRecords([$customer2]);
    }

    /** @test */
    public function it_can_search_by_company_name()
    {
        $customer1 = Customer::factory()->business()->create(['company_name' => 'Acme Corp']);
        $customer2 = Customer::factory()->business()->create(['company_name' => 'XYZ Ltd']);

        Livewire::test(ListCustomers::class)
            ->searchTable('Acme')
            ->assertCanSeeTableRecords([$customer1])
            ->assertCanNotSeeTableRecords([$customer2]);
    }

    /** @test */
    public function it_can_sort_by_name()
    {
        $customerA = Customer::factory()->create(['name' => 'Alice']);
        $customerB = Customer::factory()->create(['name' => 'Bob']);

        Livewire::test(ListCustomers::class)
            ->sortTable('name', 'asc')
            ->assertCanSeeTableRecords([$customerA, $customerB], inOrder: true);
    }

    /** @test */
    public function it_can_sort_by_created_at()
    {
        $newer = Customer::factory()->create(['created_at' => now()]);
        $older = Customer::factory()->create(['created_at' => now()->subDays(5)]);

        Livewire::test(ListCustomers::class)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    }

    /** @test */
    public function it_can_soft_delete_customer()
    {
        $customer = Customer::factory()->create();

        Livewire::test(ListCustomers::class)
            ->callTableAction('delete', $customer);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    /** @test */
    public function it_can_force_delete_customer()
    {
        $customer = Customer::factory()->create();
        $customer->delete(); // Soft delete first

        Livewire::test(ListCustomers::class)
            ->callTableAction('forceDelete', $customer);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    /** @test */
    public function it_can_restore_soft_deleted_customer()
    {
        $customer = Customer::factory()->create();
        $customer->delete();

        Livewire::test(ListCustomers::class)
            ->callTableBulkAction('restore', [$customer]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_displays_trashed_customers_in_filter()
    {
        $active = Customer::factory()->create();
        $trashed = Customer::factory()->create();
        $trashed->delete();

        Livewire::test(ListCustomers::class)
            ->filterTable('trashed', 'with')
            ->assertCanSeeTableRecords([$active, $trashed]);

        Livewire::test(ListCustomers::class)
            ->filterTable('trashed', 'only')
            ->assertCanSeeTableRecords([$trashed])
            ->assertCanNotSeeTableRecords([$active]);
    }

    /** @test */
    public function it_handles_business_type_as_array()
    {
        $customer = Customer::factory()->business()->create([
            'business_type' => ['reisebuero', 'online', 'corporate'],
        ]);

        $customer->refresh();
        $this->assertIsArray($customer->business_type);
        $this->assertContains('reisebuero', $customer->business_type);
        $this->assertContains('online', $customer->business_type);
    }

    /** @test */
    public function it_handles_passolution_features_as_array()
    {
        $customer = Customer::factory()->withPassolution()->create([
            'passolution_features' => ['feature1', 'feature2', 'feature3'],
        ]);

        $customer->refresh();
        $this->assertIsArray($customer->passolution_features);
        $this->assertCount(3, $customer->passolution_features);
    }

    /** @test */
    public function it_handles_address_as_array()
    {
        $address = [
            'street' => 'Main Street',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'DE',
        ];

        $customer = Customer::factory()->withSSO()->create(['address' => $address]);

        $customer->refresh();
        $this->assertIsArray($customer->address);
        $this->assertEquals('Main Street', $customer->address['street']);
    }

    /** @test */
    public function it_displays_customer_with_social_login()
    {
        $customer = Customer::factory()->socialLogin('google')->create();

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$customer]);

        $this->assertEquals('google', $customer->provider);
        $this->assertNotNull($customer->provider_id);
    }

    /** @test */
    public function it_displays_customer_with_passolution_integration()
    {
        $customer = Customer::factory()->withPassolution('premium')->create();

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$customer]);

        $this->assertEquals('premium', $customer->passolution_subscription_type);
        $this->assertNotNull($customer->passolution_access_token);
    }

    /** @test */
    public function it_can_view_private_customer()
    {
        $customer = Customer::factory()->private()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertSuccessful();

        $this->assertEquals('private', $customer->customer_type);
        $this->assertNull($customer->company_name);
    }

    /** @test */
    public function it_can_view_business_customer_with_full_details()
    {
        $customer = Customer::factory()->business()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertSuccessful();

        $this->assertEquals('business', $customer->customer_type);
        $this->assertNotNull($customer->company_name);
        $this->assertNotNull($customer->billing_company_name);
    }

    /** @test */
    public function it_displays_navigation_badge_with_customer_count()
    {
        Customer::factory()->count(5)->create();

        $badge = CustomerResource::getNavigationBadge();

        $this->assertEquals('5', $badge);
    }

    /** @test */
    public function it_validates_max_length_for_text_fields()
    {
        $customer = Customer::factory()->create();

        $updatedData = [
            'name' => str_repeat('a', 256), // Over 255 limit
        ];

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasFormErrors(['name']);
    }

    /** @test */
    public function it_can_update_all_company_fields()
    {
        $customer = Customer::factory()->business()->create();

        $updatedData = [
            'company_name' => 'Updated Company',
            'company_additional' => 'Updated Additional',
            'company_street' => 'Updated Street',
            'company_house_number' => '999',
            'company_postal_code' => '99999',
            'company_city' => 'Updated City',
            'company_country' => 'Updated Country',
        ];

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();
        $this->assertEquals('Updated Company', $customer->company_name);
        $this->assertEquals('Updated Street', $customer->company_street);
        $this->assertEquals('99999', $customer->company_postal_code);
    }

    /** @test */
    public function it_can_update_all_billing_fields()
    {
        $customer = Customer::factory()->business()->create();

        $updatedData = [
            'billing_company_name' => 'Updated Billing Company',
            'billing_additional' => 'Updated Billing Additional',
            'billing_street' => 'Updated Billing Street',
            'billing_house_number' => '888',
            'billing_postal_code' => '88888',
            'billing_city' => 'Updated Billing City',
            'billing_country' => 'Updated Billing Country',
        ];

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();
        $this->assertEquals('Updated Billing Company', $customer->billing_company_name);
        $this->assertEquals('Updated Billing Street', $customer->billing_street);
        $this->assertEquals('88888', $customer->billing_postal_code);
    }

    /** @test */
    public function it_displays_disabled_fields_correctly()
    {
        $customer = Customer::factory()->create([
            'customer_type' => 'business',
            'provider' => 'google',
        ]);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertFormFieldIsDisabled('customer_type')
            ->assertFormFieldIsDisabled('provider')
            ->assertFormFieldIsDisabled('created_at');
    }

    /** @test */
    public function it_can_bulk_delete_customers()
    {
        $customers = Customer::factory()->count(3)->create();

        Livewire::test(ListCustomers::class)
            ->callTableBulkAction('delete', $customers);

        foreach ($customers as $customer) {
            $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        }
    }

    /** @test */
    public function it_can_bulk_force_delete_customers()
    {
        $customers = Customer::factory()->count(3)->create();
        foreach ($customers as $customer) {
            $customer->delete();
        }

        Livewire::test(ListCustomers::class)
            ->callTableBulkAction('forceDelete', $customers);

        foreach ($customers as $customer) {
            $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        }
    }

    // ========================================================================
    // Branches Relation Manager Operations
    // ========================================================================

    /** @test */
    public function it_can_render_branches_relation_manager()
    {
        $customer = Customer::factory()->create();

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_create_branch_through_relation_manager()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Filiale Berlin Mitte',
            'street' => 'Unter den Linden',
            'house_number' => '1',
            'postal_code' => '10117',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('branches', [
            'customer_id' => $customer->id,
            'name' => 'Filiale Berlin Mitte',
            'street' => 'Unter den Linden',
            'house_number' => '1',
            'postal_code' => '10117',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ]);
    }

    /** @test */
    public function it_can_create_branch_with_all_required_fields()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Hauptfiliale',
            'street' => 'Hauptstraße',
            'house_number' => '100',
            'postal_code' => '12345',
            'city' => 'München',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasNoTableActionErrors();

        $branch = Branch::where('customer_id', $customer->id)->first();
        $this->assertNotNull($branch);
        $this->assertEquals('Hauptfiliale', $branch->name);
        $this->assertEquals('Hauptstraße', $branch->street);
        $this->assertEquals('12345', $branch->postal_code);
    }

    /** @test */
    public function it_can_create_branch_with_optional_fields()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Filiale Hamburg',
            'additional' => 'Abteilung Reisen',
            'street' => 'Reeperbahn',
            'house_number' => '42',
            'postal_code' => '20359',
            'city' => 'Hamburg',
            'country' => 'Deutschland',
            'latitude' => '53.54969920',
            'longitude' => '9.96758340',
            'is_headquarters' => true,
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasNoTableActionErrors();

        $branch = Branch::where('customer_id', $customer->id)->first();
        $this->assertEquals('Abteilung Reisen', $branch->additional);
        $this->assertEquals('53.54969920', $branch->latitude);
        $this->assertEquals('9.96758340', $branch->longitude);
        $this->assertTrue($branch->is_headquarters);
    }

    /** @test */
    public function it_validates_required_name_field_for_branch()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => '',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['name' => 'required']);
    }

    /** @test */
    public function it_validates_required_street_field_for_branch()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => '',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['street' => 'required']);
    }

    /** @test */
    public function it_validates_required_house_number_field_for_branch()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => 'Teststraße',
            'house_number' => '',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['house_number' => 'required']);
    }

    /** @test */
    public function it_validates_required_postal_code_field_for_branch()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['postal_code' => 'required']);
    }

    /** @test */
    public function it_validates_required_city_field_for_branch()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => '',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['city' => 'required']);
    }

    /** @test */
    public function it_validates_required_country_field_for_branch()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => '',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['country' => 'required']);
    }

    /** @test */
    public function it_validates_max_length_for_branch_name()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => str_repeat('a', 256), // Over 255 limit
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['name']);
    }

    /** @test */
    public function it_validates_numeric_latitude_field()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'latitude' => 'not-a-number',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['latitude']);
    }

    /** @test */
    public function it_validates_numeric_longitude_field()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'longitude' => 'not-a-number',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasTableActionErrors(['longitude']);
    }

    /** @test */
    public function it_auto_generates_app_code_when_creating_branch()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Test Filiale',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasNoTableActionErrors();

        $branch = Branch::where('customer_id', $customer->id)->first();
        $this->assertNotNull($branch->app_code);
        $this->assertEquals(4, strlen($branch->app_code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}$/', $branch->app_code);
    }

    /** @test */
    public function it_associates_new_branch_with_correct_customer()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $branchData = [
            'name' => 'Filiale für Kunde 1',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer1,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: $branchData)
            ->assertHasNoTableActionErrors();

        $branch = Branch::where('name', 'Filiale für Kunde 1')->first();
        $this->assertEquals($customer1->id, $branch->customer_id);
        $this->assertNotEquals($customer2->id, $branch->customer_id);
    }

    /** @test */
    public function it_can_edit_existing_branch()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Original Name',
            'street' => 'Original Street',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Original City',
            'country' => 'Deutschland',
        ]);

        $updatedData = [
            'name' => 'Updated Name',
            'street' => 'Updated Street',
            'house_number' => '99',
            'postal_code' => '99999',
            'city' => 'Updated City',
            'country' => 'Updated Country',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('edit', $branch, data: $updatedData)
            ->assertHasNoTableActionErrors();

        $branch->refresh();
        $this->assertEquals('Updated Name', $branch->name);
        $this->assertEquals('Updated Street', $branch->street);
        $this->assertEquals('99999', $branch->postal_code);
    }

    /** @test */
    public function it_can_update_all_branch_fields()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Original',
            'street' => 'Original',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Original',
            'country' => 'Original',
        ]);

        $updatedData = [
            'name' => 'Updated Filiale',
            'additional' => 'Updated Additional',
            'street' => 'Updated Street',
            'house_number' => '42',
            'postal_code' => '54321',
            'city' => 'Updated City',
            'country' => 'Updated Country',
            'latitude' => '52.52000660',
            'longitude' => '13.40495400',
            'is_headquarters' => true,
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('edit', $branch, data: $updatedData)
            ->assertHasNoTableActionErrors();

        $branch->refresh();
        $this->assertEquals('Updated Filiale', $branch->name);
        $this->assertEquals('Updated Additional', $branch->additional);
        $this->assertEquals('52.52000660', $branch->latitude);
        $this->assertEquals('13.40495400', $branch->longitude);
        $this->assertTrue($branch->is_headquarters);
    }

    /** @test */
    public function it_validates_fields_on_update()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Original',
            'street' => 'Original',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Original',
            'country' => 'Original',
        ]);

        $invalidData = [
            'name' => '', // Required field
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('edit', $branch, data: $invalidData)
            ->assertHasTableActionErrors(['name' => 'required']);
    }

    /** @test */
    public function it_maintains_customer_relationship_after_update()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Original',
            'street' => 'Original',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Original',
            'country' => 'Original',
        ]);

        $originalCustomerId = $branch->customer_id;

        $updatedData = [
            'name' => 'Updated Name',
        ];

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('edit', $branch, data: $updatedData)
            ->assertHasNoTableActionErrors();

        $branch->refresh();
        $this->assertEquals($originalCustomerId, $branch->customer_id);
        $this->assertEquals($customer->id, $branch->customer_id);
    }

    /** @test */
    public function it_can_delete_branch()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'To Be Deleted',
            'street' => 'Delete Street',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Delete City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('delete', $branch);

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    /** @test */
    public function it_deletion_does_not_affect_customer()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch to Delete',
            'street' => 'Delete Street',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Delete City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('delete', $branch);

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $customer->refresh();
        $this->assertNotNull($customer);
    }

    /** @test */
    public function it_can_bulk_delete_branches()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City 1',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City 2',
            'country' => 'Deutschland',
        ]);
        $branch3 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 3',
            'street' => 'Street 3',
            'house_number' => '3',
            'postal_code' => '33333',
            'city' => 'City 3',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableBulkAction('delete', [$branch1, $branch2, $branch3]);

        $this->assertDatabaseMissing('branches', ['id' => $branch1->id]);
        $this->assertDatabaseMissing('branches', ['id' => $branch2->id]);
        $this->assertDatabaseMissing('branches', ['id' => $branch3->id]);
    }

    /** @test */
    public function it_can_view_all_customer_branches()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City 1',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City 2',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->assertCanSeeTableRecords([$branch1, $branch2]);
    }

    /** @test */
    public function it_only_displays_branches_for_current_customer()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $branch1 = Branch::create([
            'customer_id' => $customer1->id,
            'name' => 'Customer 1 Branch',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City 1',
            'country' => 'Deutschland',
        ]);

        $branch2 = Branch::create([
            'customer_id' => $customer2->id,
            'name' => 'Customer 2 Branch',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City 2',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer1,
            'pageClass' => EditCustomer::class,
        ])
            ->assertCanSeeTableRecords([$branch1])
            ->assertCanNotSeeTableRecords([$branch2]);
    }

    /** @test */
    public function it_can_search_branches_by_name()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Berlin Office',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'München Office',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'München',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->searchTableQuery('Berlin')
            ->assertCanSeeTableRecords([$branch1])
            ->assertCanNotSeeTableRecords([$branch2]);
    }

    /** @test */
    public function it_can_search_branches_by_app_code()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'app_code' => 'ABC1',
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City 1',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'app_code' => 'XYZ2',
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City 2',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->searchTableQuery('ABC1')
            ->assertCanSeeTableRecords([$branch1])
            ->assertCanNotSeeTableRecords([$branch2]);
    }

    /** @test */
    public function it_can_search_branches_by_street()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Hauptstraße',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City 1',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Nebenstraße',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City 2',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->searchTableQuery('Hauptstraße')
            ->assertCanSeeTableRecords([$branch1])
            ->assertCanNotSeeTableRecords([$branch2]);
    }

    /** @test */
    public function it_can_search_branches_by_postal_code()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->searchTableQuery('10115')
            ->assertCanSeeTableRecords([$branch1])
            ->assertCanNotSeeTableRecords([$branch2]);
    }

    /** @test */
    public function it_can_search_branches_by_city()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Hamburg',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'Köln',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->searchTableQuery('Hamburg')
            ->assertCanSeeTableRecords([$branch1])
            ->assertCanNotSeeTableRecords([$branch2]);
    }

    /** @test */
    public function it_can_filter_branches_by_headquarters_status()
    {
        $customer = Customer::factory()->create();
        $headquartersBranch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Headquarters',
            'street' => 'Main Street',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'is_headquarters' => true,
        ]);
        $regularBranch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Regular Branch',
            'street' => 'Side Street',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'München',
            'country' => 'Deutschland',
            'is_headquarters' => false,
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->filterTableQuery('is_headquarters', true)
            ->assertCanSeeTableRecords([$headquartersBranch])
            ->assertCanNotSeeTableRecords([$regularBranch]);
    }

    /** @test */
    public function it_can_filter_to_exclude_headquarters()
    {
        $customer = Customer::factory()->create();
        $headquartersBranch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Headquarters',
            'street' => 'Main Street',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'is_headquarters' => true,
        ]);
        $regularBranch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Regular Branch',
            'street' => 'Side Street',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'München',
            'country' => 'Deutschland',
            'is_headquarters' => false,
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->filterTableQuery('is_headquarters', false)
            ->assertCanSeeTableRecords([$regularBranch])
            ->assertCanNotSeeTableRecords([$headquartersBranch]);
    }

    /** @test */
    public function it_can_sort_branches_by_name()
    {
        $customer = Customer::factory()->create();
        $branchA = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Alpha Branch',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City',
            'country' => 'Deutschland',
        ]);
        $branchB = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Beta Branch',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->sortTable('name', 'asc')
            ->assertCanSeeTableRecords([$branchA, $branchB], inOrder: true);
    }

    /** @test */
    public function it_can_sort_branches_by_app_code()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'app_code' => 'AAA1',
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'app_code' => 'ZZZ9',
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->sortTable('app_code', 'asc')
            ->assertCanSeeTableRecords([$branch1, $branch2], inOrder: true);
    }

    /** @test */
    public function it_can_sort_branches_by_postal_code()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '10000',
            'city' => 'City',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '90000',
            'city' => 'City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->sortTable('postal_code', 'asc')
            ->assertCanSeeTableRecords([$branch1, $branch2], inOrder: true);
    }

    /** @test */
    public function it_can_sort_branches_by_city()
    {
        $customer = Customer::factory()->create();
        $branch1 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'Aachen',
            'country' => 'Deutschland',
        ]);
        $branch2 = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'Zwickau',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->sortTable('city', 'asc')
            ->assertCanSeeTableRecords([$branch1, $branch2], inOrder: true);
    }

    /** @test */
    public function it_displays_correct_branch_count()
    {
        $customer = Customer::factory()->create();
        Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 1',
            'street' => 'Street 1',
            'house_number' => '1',
            'postal_code' => '11111',
            'city' => 'City 1',
            'country' => 'Deutschland',
        ]);
        Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 2',
            'street' => 'Street 2',
            'house_number' => '2',
            'postal_code' => '22222',
            'city' => 'City 2',
            'country' => 'Deutschland',
        ]);
        Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Branch 3',
            'street' => 'Street 3',
            'house_number' => '3',
            'postal_code' => '33333',
            'city' => 'City 3',
            'country' => 'Deutschland',
        ]);

        $livewire = Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ]);

        $this->assertEquals(3, $customer->branches()->count());
    }

    /** @test */
    public function it_displays_app_code_as_badge()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'app_code' => 'TEST',
            'name' => 'Test Branch',
            'street' => 'Test Street',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Test City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->assertCanSeeTableRecords([$branch])
            ->assertTableColumnExists('app_code');
    }

    /** @test */
    public function it_displays_headquarters_icon()
    {
        $customer = Customer::factory()->create();
        $headquartersBranch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Headquarters',
            'street' => 'Main Street',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'is_headquarters' => true,
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->assertCanSeeTableRecords([$headquartersBranch])
            ->assertTableColumnExists('is_headquarters');
    }

    /** @test */
    public function it_has_create_action_in_header()
    {
        $customer = Customer::factory()->create();

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->assertTableActionExists('create');
    }

    /** @test */
    public function it_has_edit_action_for_branches()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Test Branch',
            'street' => 'Test Street',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Test City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->assertTableActionExists('edit');
    }

    /** @test */
    public function it_has_delete_action_for_branches()
    {
        $customer = Customer::factory()->create();
        $branch = Branch::create([
            'customer_id' => $customer->id,
            'name' => 'Test Branch',
            'street' => 'Test Street',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'Test City',
            'country' => 'Deutschland',
        ]);

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->assertTableActionExists('delete');
    }

    /** @test */
    public function it_app_code_field_is_disabled_in_form()
    {
        $customer = Customer::factory()->create();

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->mountTableAction('create')
            ->assertTableActionDataSet(['app_code' => null]);
    }

    /** @test */
    public function it_has_default_country_value()
    {
        $customer = Customer::factory()->create();

        Livewire::test(BranchesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->mountTableAction('create')
            ->assertTableActionDataSet(['country' => 'Deutschland']);
    }

    /** @test */
    public function it_generates_unique_app_codes_for_multiple_branches()
    {
        $customer = Customer::factory()->create();

        $branchData = [
            'name' => 'Branch',
            'street' => 'Street',
            'house_number' => '1',
            'postal_code' => '12345',
            'city' => 'City',
            'country' => 'Deutschland',
        ];

        // Create multiple branches
        for ($i = 1; $i <= 5; $i++) {
            Livewire::test(BranchesRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditCustomer::class,
            ])
                ->callTableAction('create', data: array_merge($branchData, ['name' => "Branch $i"]))
                ->assertHasNoTableActionErrors();
        }

        $branches = Branch::where('customer_id', $customer->id)->get();
        $appCodes = $branches->pluck('app_code')->toArray();

        // Check that all app codes are unique
        $this->assertEquals(5, count($appCodes));
        $this->assertEquals(5, count(array_unique($appCodes)));
    }
}
