# Filament Resource Tests

This directory contains comprehensive CRUD tests for Filament resources.

## Test Files

### AirlineResourceTest.php
Comprehensive tests for the Airline resource covering:

**List Tests:**
- Rendering the airlines list page
- Listing all airlines
- Searching airlines by name
- Searching airlines by IATA code
- Filtering airlines by active status

**Create Tests:**
- Creating airlines with basic fields (name, codes, country, headquarters)
- Creating airlines with contact info (hotline, email, chat URL, help URL)
- Creating airlines with complex baggage rules (checked baggage, hand baggage, dimensions per cabin class)
- Creating airlines with cabin classes
- Creating airlines with pet policy (in-cabin, in-hold restrictions)
- Creating airlines with lounges (repeater field)
- Creating airlines with airport relationships (many-to-many)

**Validation Tests:**
- Name required validation
- Name max length validation
- IATA code uniqueness
- ICAO code uniqueness
- IATA code max length (2 characters)
- ICAO code max length (3 characters)
- Website URL format validation
- Booking URL format validation
- Contact email format validation

**Edit Tests:**
- Rendering edit page
- Retrieving airline data for editing
- Updating basic fields
- Updating contact info (JSON field)
- Updating baggage rules (nested JSON field)
- Updating cabin classes (array field)
- Updating lounges (repeater field)
- Updating airport relationships
- Updating IATA code to unique value
- Preventing IATA code update to duplicate value

**Delete Tests:**
- Soft deleting airlines
- Restoring soft-deleted airlines
- Force deleting airlines
- Bulk delete operations

**Authorization Tests:**
- Non-admin users cannot access resource
- Inactive admin users cannot access resource

### AirportResourceTest.php
Comprehensive tests for the Airport resource covering:

**List Tests:**
- Rendering the airports list page
- Listing all airports
- Searching airports by name
- Searching airports by IATA code
- Filtering airports by type
- Filtering airports by active status

**View Tests:**
- Rendering view airport page
- Viewing airport details

**Create Tests:**
- Creating airports with basic fields (name, codes, country, city, type)
- Creating airports with coordinates (lat, lng, altitude)
- Creating airports with website URLs (main website, security timeslot URL)
- Creating airports with lounges (repeater field with name, location, access, URL)
- Creating airports with nearby hotels (repeater field with name, distance, shuttle, booking URL, notes)
- Creating airports with mobility options:
  - Car rental (available flag, providers repeater, booking URL)
  - Public transport (available flag, types repeater, info URL)
  - Taxi (available flag, info text, approximate cost)
  - Parking (available flag, options repeater with name/distance/price, booking URL)
  - Airport shuttle (available flag, info text, URL)

**Validation Tests:**
- Name required
- IATA code required
- ICAO code required
- Country required
- City required
- Type required
- IATA code uniqueness
- ICAO code uniqueness
- IATA code max length (3 characters)
- ICAO code max length (4 characters)
- Website URL format validation
- Latitude range validation (-90 to 90)
- Longitude range validation (-180 to 180)

**Edit Tests:**
- Rendering edit page
- Retrieving airport data for editing
- Updating basic fields
- Updating coordinates
- Updating lounges (repeater field)
- Updating nearby hotels (repeater field)
- Updating mobility options (nested JSON structure)
- Updating IATA code to unique value
- Preventing IATA code update to duplicate value

**Delete Tests:**
- Soft deleting airports
- Restoring soft-deleted airports
- Force deleting airports
- Bulk delete operations

**Relationship Tests:**
- Viewing airlines relation on airport
- City options filtered by selected country

**Scope Tests:**
- Scoping airports by country
- Scoping airports by city
- Searching airports by name/IATA/ICAO

**Authorization Tests:**
- Non-admin users cannot access resource
- Inactive admin users cannot access resource

## Test Coverage

Both test suites provide comprehensive coverage of:
1. All CRUD operations (Create, Read, Update, Delete)
2. Form field validation (required, unique, format, length)
3. Complex JSON field handling (nested objects, arrays, repeaters)
4. Relationship handling (BelongsTo, BelongsToMany)
5. Search and filtering functionality
6. Authorization and access control
7. Soft delete operations
8. Bulk actions

## Known Issues

### Database Migration Issue
The tests currently fail due to a foreign key constraint issue in the migration file:
`database/migrations/2025_08_27_072849_add_missing_countries_to_countries_table.php`

This migration attempts to insert countries with hardcoded `continent_id = 1`, but the continent with ID 1 doesn't exist in the test database during migration.

**Possible Solutions:**
1. Update the migration to check if continents exist before inserting countries
2. Create a seeder for continents and run it before the migration
3. Skip data-seeding migrations in test environment
4. Use database transactions and seeders in test setup

**Temporary Workaround for Running Tests:**
You can modify your test setup to seed required data:

```php
// In tests/Feature/Filament/AirlineResourceTest.php or AirportResourceTest.php
beforeEach(function () {
    // Seed continents first
    \App\Models\Continent::factory()->create(['id' => 1]);

    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});
```

## Running the Tests

Once the database issue is resolved, run the tests with:

```bash
# Run all Filament tests
php artisan test --testsuite=Feature --filter=Filament

# Run specific resource tests
php artisan test --filter=AirlineResourceTest
php artisan test --filter=AirportResourceTest

# Run with coverage
php artisan test --testsuite=Feature --filter=Filament --coverage
```

## Test Structure

All tests follow Filament 4's testing approach using Livewire testing utilities:

- `Livewire::test(PageClass::class)` - Test Filament pages
- `->fillForm($data)` - Fill form fields
- `->call('create')` or `->call('save')` - Submit forms
- `->assertHasNoFormErrors()` - Assert no validation errors
- `->assertHasFormErrors(['field'])` - Assert specific validation errors
- `->searchTable($query)` - Search in table
- `->filterTable('field', $value)` - Filter table
- `->assertCanSeeTableRecords($records)` - Assert records visible
- `->callAction('delete')` - Call resource actions
- `->callTableBulkAction('delete', $records)` - Bulk actions

## Factory Files

The following factory files were created/updated to support the tests:

### AirlineFactory.php
Located at: `database/factories/AirlineFactory.php`

Features:
- Generates realistic airline data with IATA/ICAO codes
- Creates complete contact_info JSON structure
- Generates baggage_rules with nested dimensions
- Includes cabin classes array
- Generates pet_policy with nested structures
- Supports factory states:
  - `->inactive()` - Creates inactive airline
  - `->withLounges()` - Adds lounge data
  - `->withAllCabinClasses()` - All 4 cabin classes

### AirportFactory.php (Updated)
Located at: `database/factories/AirportFactory.php`

Updates:
- Fixed column names (lat/lng instead of latitude/longitude)
- Added altitude field
- Added operates_24h field
- Updated type options to match form schema

## JSON Field Testing

The tests extensively cover JSON field testing for Filament 4, including:

1. **Flat JSON Objects** (e.g., contact_info)
```php
'contact_info' => [
    'hotline' => '+49 123 456789',
    'email' => 'contact@airline.com',
]
```

2. **Nested JSON Objects** (e.g., baggage_rules)
```php
'baggage_rules' => [
    'checked_baggage' => [
        'economy' => '1x23kg',
    ],
    'hand_baggage_dimensions' => [
        'economy' => [
            'length' => 55,
            'width' => 40,
        ],
    ],
]
```

3. **Repeater Fields** (e.g., lounges, nearby_hotels)
```php
'lounges' => [
    [
        'name' => 'Business Lounge',
        'location' => 'Terminal 1',
    ],
    [
        'name' => 'VIP Lounge',
        'location' => 'Terminal 2',
    ],
]
```

4. **Complex Nested Structures** (e.g., mobility_options)
```php
'mobility_options' => [
    'car_rental' => [
        'available' => true,
        'providers' => [
            ['provider' => 'Sixt'],
            ['provider' => 'Hertz'],
        ],
        'booking_url' => 'https://...',
    ],
]
```

## Best Practices Demonstrated

1. **Test Organization**: Tests grouped by functionality (List, Create, Edit, Delete, Validation)
2. **Descriptive Test Names**: Clear, readable test names using Pest syntax
3. **Factory Usage**: Leveraging factories for test data generation
4. **Relationship Testing**: Testing both sides of relationships
5. **Authorization Testing**: Ensuring proper access control
6. **Edge Cases**: Testing validation rules, uniqueness constraints, format validation
7. **Bulk Operations**: Testing bulk actions on multiple records
8. **Soft Deletes**: Testing all soft delete operations (delete, restore, force delete)
9. **Complex Fields**: Comprehensive testing of JSON and nested field structures
10. **Search & Filter**: Testing all table interaction features

## Maintenance

When adding new fields to the Airline or Airport resources:

1. Add field to the model's `$fillable` array
2. Update the factory definition
3. Add create test for the field
4. Add edit/update test for the field
5. Add validation test if field has validation rules
6. Update this README with the new field coverage
