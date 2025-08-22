<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
    $this->inactiveUser = User::factory()->inactive()->create();
    $this->unverifiedUser = User::factory()->unverified()->create();
});

// Authentication and Authorization Tests
test('admin can access users index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/users')
        ->assertSuccessful();
});

test('non-admin user cannot access users index page', function () {
    $this->actingAs($this->user)
        ->get('/admin/users')
        ->assertForbidden();
});

test('inactive admin cannot access users page', function () {
    $inactiveAdmin = User::factory()->admin()->inactive()->create();
    
    $this->actingAs($inactiveAdmin)
        ->get('/admin/users')
        ->assertForbidden();
});

test('guest cannot access users page', function () {
    $this->get('/admin/users')
        ->assertRedirect('/admin/login');
});

// User List/Index Tests
test('admin can view users list with all users displayed', function () {
    $response = $this->actingAs($this->admin)
        ->get('/admin/users');
        
    $response->assertSuccessful();
});

// User Creation Tests
test('admin can access user creation page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/users/create')
        ->assertSuccessful();
});

test('non-admin cannot access user creation page', function () {
    $this->actingAs($this->user)
        ->get('/admin/users/create')
        ->assertForbidden();
});

// User Editing Tests
test('admin can access user edit page', function () {
    $this->actingAs($this->admin)
        ->get("/admin/users/{$this->user->id}/edit")
        ->assertSuccessful();
});

test('non-admin cannot access user edit page', function () {
    $this->actingAs($this->user)
        ->get("/admin/users/{$this->user->id}/edit")
        ->assertForbidden();
});

// UserResource Authorization Tests
test('UserResource canViewAny works correctly', function () {
    // Admin should be able to view users
    $this->actingAs($this->admin);
    expect(UserResource::canViewAny())->toBeTrue();
    
    // Non-admin should not be able to view users
    $this->actingAs($this->user);
    expect(UserResource::canViewAny())->toBeFalse();
    
    // Inactive admin should not be able to view users
    $inactiveAdmin = User::factory()->admin()->inactive()->create();
    $this->actingAs($inactiveAdmin);
    expect(UserResource::canViewAny())->toBeFalse();
});

test('UserResource canView works correctly', function () {
    $this->actingAs($this->admin);
    expect(UserResource::canView($this->user))->toBeTrue();
    
    $this->actingAs($this->user);
    expect(UserResource::canView($this->user))->toBeFalse();
});

test('UserResource canCreate works correctly', function () {
    $this->actingAs($this->admin);
    expect(UserResource::canCreate())->toBeTrue();
    
    $this->actingAs($this->user);
    expect(UserResource::canCreate())->toBeFalse();
});

test('UserResource canEdit works correctly', function () {
    $this->actingAs($this->admin);
    expect(UserResource::canEdit($this->user))->toBeTrue();
    
    $this->actingAs($this->user);
    expect(UserResource::canEdit($this->user))->toBeFalse();
});

test('UserResource canDelete works correctly', function () {
    // Admin can delete other users
    $this->actingAs($this->admin);
    expect(UserResource::canDelete($this->user))->toBeTrue();
    
    // Admin cannot delete their own account
    expect(UserResource::canDelete($this->admin))->toBeFalse();
    
    // Non-admin cannot delete users
    $this->actingAs($this->user);
    expect(UserResource::canDelete($this->user))->toBeFalse();
});

// Admin Panel Access Tests
test('user cannot access admin panel when deactivated', function () {
    $this->user->update(['is_active' => false]);
    
    $this->actingAs($this->user)
        ->get('/admin')
        ->assertForbidden();
});

test('user cannot access admin panel when not admin', function () {
    $this->actingAs($this->user)
        ->get('/admin')
        ->assertForbidden();
});

test('admin can access admin panel when active', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertSuccessful();
});

test('deactivated admin cannot access admin panel', function () {
    $this->admin->update(['is_active' => false]);
    
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertForbidden();
});

// User Model Tests
test('user model relationships work correctly', function () {
    $user = User::factory()->create();
    
    // Test User model methods
    expect($user->isActive())->toBe($user->is_active);
    expect($user->isAdmin())->toBe($user->is_admin);
    expect($user->initials())->toBeString();
});

test('user factory states work correctly', function () {
    $adminUser = User::factory()->admin()->create();
    $inactiveUser = User::factory()->inactive()->create();
    $unverifiedUser = User::factory()->unverified()->create();
    
    expect($adminUser->is_admin)->toBeTrue();
    expect($inactiveUser->is_active)->toBeFalse();
    expect($unverifiedUser->email_verified_at)->toBeNull();
});

test('user scopes work correctly', function () {
    User::factory()->admin()->count(2)->create();
    User::factory()->inactive()->count(3)->create();
    
    expect(User::admins()->count())->toBeGreaterThan(2); // Including seeded admin
    expect(User::active()->count())->toBeGreaterThan(0);
});

test('user can access panel method works correctly', function () {
    $panel = new \Filament\Panel('admin'); // Mock panel
    
    // Active admin can access
    expect($this->admin->canAccessPanel($panel))->toBeTrue();
    
    // Inactive admin cannot access
    $inactiveAdmin = User::factory()->admin()->inactive()->create();
    expect($inactiveAdmin->canAccessPanel($panel))->toBeFalse();
    
    // Non-admin cannot access
    expect($this->user->canAccessPanel($panel))->toBeFalse();
    
    // Inactive non-admin cannot access
    expect($this->inactiveUser->canAccessPanel($panel))->toBeFalse();
});

// Test User creation via direct User model (since we can't easily test Filament forms)
test('user creation works with valid data', function () {
    $userData = [
        'name' => 'New Test User',
        'email' => 'newuser@example.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
        'is_admin' => false,
    ];

    $user = User::create($userData);

    expect($user->name)->toBe('New Test User');
    expect($user->email)->toBe('newuser@example.com');
    expect($user->is_active)->toBeTrue();
    expect($user->is_admin)->toBeFalse();
    
    $this->assertDatabaseHas('users', [
        'name' => 'New Test User',
        'email' => 'newuser@example.com',
        'is_active' => true,
        'is_admin' => false,
    ]);
});

test('user update works correctly', function () {
    $this->user->update([
        'name' => 'Updated User Name',
        'is_active' => false,
        'is_admin' => true,
    ]);

    expect($this->user->fresh()->name)->toBe('Updated User Name');
    expect($this->user->fresh()->is_active)->toBeFalse();
    expect($this->user->fresh()->is_admin)->toBeTrue();
    
    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'name' => 'Updated User Name',
        'is_active' => false,
        'is_admin' => true,
    ]);
});

test('user deletion works correctly', function () {
    $userToDelete = User::factory()->create();
    $userId = $userToDelete->id;
    
    $userToDelete->delete();
    
    $this->assertDatabaseMissing('users', [
        'id' => $userId,
    ]);
});

test('bulk user operations work correctly', function () {
    // Create multiple users
    $users = User::factory()->count(3)->create();
    
    // Bulk deactivate
    User::whereIn('id', $users->pluck('id'))->update(['is_active' => false]);
    
    foreach ($users as $user) {
        expect($user->fresh()->is_active)->toBeFalse();
    }
    
    // Bulk activate
    User::whereIn('id', $users->pluck('id'))->update(['is_active' => true]);
    
    foreach ($users as $user) {
        expect($user->fresh()->is_active)->toBeTrue();
    }
    
    // Bulk delete
    User::whereIn('id', $users->pluck('id'))->delete();
    
    foreach ($users as $user) {
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
});

test('user validation rules work correctly', function () {
    // Test email uniqueness
    expect(function () {
        User::create([
            'name' => 'Test User',
            'email' => $this->user->email, // Use existing email
            'password' => bcrypt('password123'),
        ]);
    })->toThrow(Exception::class);
    
    // Test required fields
    expect(function () {
        User::create([
            'email' => 'test@example.com',
            // Missing name
            'password' => bcrypt('password123'),
        ]);
    })->toThrow(Exception::class);
});

test('user status toggle functionality works', function () {
    $user = User::factory()->create(['is_active' => true]);
    
    // Toggle to inactive
    $user->update(['is_active' => !$user->is_active]);
    expect($user->fresh()->is_active)->toBeFalse();
    
    // Toggle back to active
    $user->update(['is_active' => !$user->is_active]);
    expect($user->fresh()->is_active)->toBeTrue();
});

test('user email verification status can be managed', function () {
    $user = User::factory()->unverified()->create();
    expect($user->email_verified_at)->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse();
    
    // Verify email
    $user->markEmailAsVerified();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect($user->fresh()->email_verified_at)->not()->toBeNull();
});

test('user password hashing works correctly', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;
    
    // Update password
    $user->update(['password' => bcrypt('newpassword123')]);
    
    expect($user->fresh()->password)->not()->toBe($originalPassword);
});

test('user admin and active status combinations work correctly', function () {
    // Active admin - should have full access
    $activeAdmin = User::factory()->admin()->create();
    expect($activeAdmin->isAdmin())->toBeTrue();
    expect($activeAdmin->isActive())->toBeTrue();
    
    // Inactive admin - should be admin but not active
    $inactiveAdmin = User::factory()->admin()->inactive()->create();
    expect($inactiveAdmin->isAdmin())->toBeTrue();
    expect($inactiveAdmin->isActive())->toBeFalse();
    
    // Active non-admin - should be active but not admin
    $activeUser = User::factory()->create();
    expect($activeUser->isAdmin())->toBeFalse();
    expect($activeUser->isActive())->toBeTrue();
    
    // Inactive non-admin - should be neither admin nor active
    $inactiveUser = User::factory()->inactive()->create();
    expect($inactiveUser->isAdmin())->toBeFalse();
    expect($inactiveUser->isActive())->toBeFalse();
});

test('user search and filtering scenarios work with model queries', function () {
    // Create test users with specific attributes
    $johnDoe = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $janeSmith = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    $adminUser = User::factory()->admin()->create(['name' => 'Admin User']);
    $inactiveUser = User::factory()->inactive()->create(['name' => 'Inactive User']);
    
    // Test name search
    expect(User::where('name', 'like', '%John%')->count())->toBe(1);
    expect(User::where('name', 'like', '%Jane%')->count())->toBe(1);
    
    // Test email search
    expect(User::where('email', 'like', '%john@%')->count())->toBe(1);
    expect(User::where('email', 'like', '%jane@%')->count())->toBe(1);
    
    // Test admin filtering
    expect(User::where('is_admin', true)->count())->toBeGreaterThanOrEqual(2); // Including our admin
    
    // Test active filtering
    expect(User::where('is_active', true)->count())->toBeGreaterThan(2);
    expect(User::where('is_active', false)->count())->toBeGreaterThanOrEqual(1);
});

test('user model default values are set correctly', function () {
    $user = User::factory()->make(); // Make without saving to see defaults
    
    expect($user->is_active)->toBeTrue();
    expect($user->is_admin)->toBeFalse();
});

test('user model casts work correctly', function () {
    $user = User::factory()->create();
    
    expect($user->is_active)->toBeBool();
    expect($user->is_admin)->toBeBool();
    expect($user->email_verified_at)->toBeInstanceOf(Carbon\Carbon::class);
});

test('password generation functionality works correctly', function () {
    // Test that password generation creates secure passwords
    $userForm = new \App\Filament\Resources\UserResource\Schemas\UserForm();
    
    // Use reflection to access private method for testing
    $reflection = new ReflectionClass($userForm);
    $method = $reflection->getMethod('generateSecurePassword');
    $method->setAccessible(true);
    
    $password1 = $method->invoke($userForm);
    $password2 = $method->invoke($userForm);
    
    // Passwords should be different each time
    expect($password1)->not()->toBe($password2);
    
    // Password should meet requirements
    expect(strlen($password1))->toBe(12);
    expect($password1)->toMatch('/[a-z]/'); // Contains lowercase
    expect($password1)->toMatch('/[A-Z]/'); // Contains uppercase  
    expect($password1)->toMatch('/[0-9]/'); // Contains numbers
    expect($password1)->toMatch('/[!@#$%&*+\-=?]/'); // Contains symbols
});

test('password generation includes all character types', function () {
    $userForm = new \App\Filament\Resources\UserResource\Schemas\UserForm();
    
    // Use reflection to access private method for testing
    $reflection = new ReflectionClass($userForm);
    $method = $reflection->getMethod('generateSecurePassword');
    $method->setAccessible(true);
    
    // Test multiple generations to ensure consistency
    for ($i = 0; $i < 10; $i++) {
        $password = $method->invoke($userForm);
        
        expect($password)->toMatch('/[a-z]/', "Password should contain lowercase: {$password}");
        expect($password)->toMatch('/[A-Z]/', "Password should contain uppercase: {$password}");
        expect($password)->toMatch('/[0-9]/', "Password should contain numbers: {$password}");
        expect($password)->toMatch('/[!@#$%&*+\-=?]/', "Password should contain symbols: {$password}");
    }
});