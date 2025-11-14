<?php

declare(strict_types=1);

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

// =====================================================
// AUTHORIZATION TESTS
// =====================================================

describe('Authorization', function () {
    test('admin can access user list page', function () {
        actingAs($this->admin)
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    test('non-admin cannot access user list page', function () {
        actingAs($this->user)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    });

    test('inactive admin cannot access user list page', function () {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        actingAs($inactiveAdmin)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    });

    test('guest is redirected to login', function () {
        $this->get(UserResource::getUrl('index'))
            ->assertRedirect('/admin/login');
    });

    test('admin can access create page', function () {
        actingAs($this->admin)
            ->get(UserResource::getUrl('create'))
            ->assertSuccessful();
    });

    test('admin can access edit page', function () {
        actingAs($this->admin)
            ->get(UserResource::getUrl('edit', ['record' => $this->user]))
            ->assertSuccessful();
    });

    test('non-admin cannot access create page', function () {
        actingAs($this->user)
            ->get(UserResource::getUrl('create'))
            ->assertForbidden();
    });

    test('non-admin cannot access edit page', function () {
        $otherUser = User::factory()->create();

        actingAs($this->user)
            ->get(UserResource::getUrl('edit', ['record' => $otherUser]))
            ->assertForbidden();
    });
});

// =====================================================
// CREATE TESTS
// =====================================================

describe('Create User', function () {
    test('can render create page', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->assertSuccessful();
    });

    test('can create user with all required fields', function () {
        $userData = [
            'name' => 'New Test User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
            'is_admin' => false,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'name' => 'New Test User',
            'email' => 'newuser@example.com',
            'is_active' => true,
            'is_admin' => false,
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->password)->not->toBeNull();
    });

    test('can create admin user', function () {
        $userData = [
            'name' => 'New Admin User',
            'email' => 'newadmin@example.com',
            'password' => 'AdminPass123!',
            'password_confirmation' => 'AdminPass123!',
            'is_active' => true,
            'is_admin' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'name' => 'New Admin User',
            'email' => 'newadmin@example.com',
            'is_admin' => true,
        ]);
    });

    test('can create inactive user', function () {
        $userData = [
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => false,
            'is_admin' => false,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);
    });

    test('can create user with email verification timestamp', function () {
        $verificationDate = now()->subDays(5);

        $userData = [
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
            'is_admin' => false,
            'email_verified_at' => $verificationDate->toDateTimeString(),
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'email' => 'verified@example.com',
        ]);

        $user = User::where('email', 'verified@example.com')->first();
        expect($user->email_verified_at)->not->toBeNull();
    });

    test('requires name field', function () {
        $userData = [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    });

    test('requires email field', function () {
        $userData = [
            'name' => 'Test User',
            'email' => '',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['email' => 'required']);
    });

    test('requires password field on create', function () {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['password' => 'required']);
    });

    test('validates email format', function () {
        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['email']);
    });

    test('validates email uniqueness', function () {
        $userData = [
            'name' => 'Duplicate Email User',
            'email' => $this->user->email, // Use existing email
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['email']);
    });

    test('validates minimum password length', function () {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['password']);
    });

    test('validates password confirmation match', function () {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['password_confirmation']);
    });

    test('validates name max length', function () {
        $userData = [
            'name' => str_repeat('a', 256), // Exceeds 255 character limit
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['name']);
    });

    test('validates email max length', function () {
        $userData = [
            'name' => 'Test User',
            'email' => str_repeat('a', 240) . '@example.com', // Exceeds 255 character limit
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasFormErrors(['email']);
    });
});

// =====================================================
// READ/LIST TESTS
// =====================================================

describe('List Users', function () {
    test('can render list page', function () {
        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertSuccessful();
    });

    test('can list all users', function () {
        $users = User::factory()->count(5)->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    });

    test('displays user details correctly', function () {
        $testUser = User::factory()->create([
            'name' => 'John Doe Display Test',
            'email' => 'johndoe@test.com',
            'is_active' => true,
            'is_admin' => false,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ListUsers::class);

        $component->assertCanSeeTableRecords([$testUser]);
    });

    test('can search users by name', function () {
        $searchableUser = User::factory()->create(['name' => 'Searchable John Smith']);
        $otherUser = User::factory()->create(['name' => 'Other User']);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->searchTable('Searchable John')
            ->assertCanSeeTableRecords([$searchableUser])
            ->assertCanNotSeeTableRecords([$otherUser]);
    });

    test('can search users by email', function () {
        $searchableUser = User::factory()->create(['email' => 'findme@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->searchTable('findme')
            ->assertCanSeeTableRecords([$searchableUser])
            ->assertCanNotSeeTableRecords([$otherUser]);
    });
});

// =====================================================
// UPDATE TESTS
// =====================================================

describe('Update User', function () {
    test('can render edit page', function () {
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->assertSuccessful();
    });

    test('can retrieve existing user data', function () {
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->assertFormSet([
                'name' => $this->user->name,
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
                'is_admin' => $this->user->is_admin,
            ]);
    });

    test('can update user name', function () {
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['name' => 'Updated Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    });

    test('can update user email', function () {
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['email' => 'newemail@example.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'newemail@example.com',
        ]);
    });

    test('can toggle is_active status', function () {
        $originalStatus = $this->user->is_active;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['is_active' => !$originalStatus])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_active' => !$originalStatus,
        ]);
    });

    test('can toggle is_admin status', function () {
        $originalStatus = $this->user->is_admin;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['is_admin' => !$originalStatus])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_admin' => !$originalStatus,
        ]);
    });

    test('can update email_verified_at', function () {
        $verificationDate = now();

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['email_verified_at' => $verificationDate->toDateTimeString()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->user->refresh();
        expect($this->user->email_verified_at)->not->toBeNull();
    });

    test('can clear email_verified_at', function () {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $verifiedUser->id])
            ->fillForm(['email_verified_at' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $verifiedUser->refresh();
        expect($verifiedUser->email_verified_at)->toBeNull();
    });

    test('can update user without changing password', function () {
        $originalPassword = $this->user->password;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm([
                'name' => 'Updated Without Password',
                'password' => '', // Leave password empty
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->user->refresh();
        expect($this->user->name)->toBe('Updated Without Password');
        expect($this->user->password)->toBe($originalPassword);
    });

    test('can update user password', function () {
        $originalPassword = $this->user->password;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm([
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->user->refresh();
        expect($this->user->password)->not->toBe($originalPassword);
    });

    test('can update all fields at once', function () {
        $updateData = [
            'name' => 'Completely Updated User',
            'email' => 'completelyupdated@example.com',
            'is_active' => false,
            'is_admin' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm($updateData)
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Completely Updated User',
            'email' => 'completelyupdated@example.com',
            'is_active' => false,
            'is_admin' => true,
        ]);
    });

    test('validates email uniqueness on update', function () {
        $anotherUser = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['email' => $anotherUser->email])
            ->call('save')
            ->assertHasFormErrors(['email']);
    });

    test('allows keeping same email on update', function () {
        $originalEmail = $this->user->email;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => $originalEmail, // Keep same email
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => $originalEmail,
        ]);
    });

    test('validates required fields on update', function () {
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    });

    test('validates email format on update', function () {
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->user->id])
            ->fillForm(['email' => 'invalid-email-format'])
            ->call('save')
            ->assertHasFormErrors(['email']);
    });
});

// =====================================================
// DELETE TESTS
// =====================================================

describe('Delete User', function () {
    test('admin can delete other users', function () {
        $userToDelete = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $userToDelete->id])
            ->callAction('delete');

        assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    test('admin cannot delete their own account', function () {
        expect(UserResource::canDelete($this->admin))->toBeFalse();
    });

    test('non-admin cannot delete users', function () {
        $userToDelete = User::factory()->create();

        actingAs($this->user);
        expect(UserResource::canDelete($userToDelete))->toBeFalse();
    });

    test('inactive admin cannot delete users', function () {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();
        $userToDelete = User::factory()->create();

        actingAs($inactiveAdmin);
        expect(UserResource::canDelete($userToDelete))->toBeFalse();
    });
});

// =====================================================
// PERMISSION TESTS
// =====================================================

describe('Permissions', function () {
    test('UserResource canViewAny returns correct values', function () {
        actingAs($this->admin);
        expect(UserResource::canViewAny())->toBeTrue();

        actingAs($this->user);
        expect(UserResource::canViewAny())->toBeFalse();
    });

    test('UserResource canView returns correct values', function () {
        actingAs($this->admin);
        expect(UserResource::canView($this->user))->toBeTrue();

        actingAs($this->user);
        expect(UserResource::canView($this->user))->toBeFalse();
    });

    test('UserResource canCreate returns correct values', function () {
        actingAs($this->admin);
        expect(UserResource::canCreate())->toBeTrue();

        actingAs($this->user);
        expect(UserResource::canCreate())->toBeFalse();
    });

    test('UserResource canEdit returns correct values', function () {
        actingAs($this->admin);
        expect(UserResource::canEdit($this->user))->toBeTrue();

        actingAs($this->user);
        expect(UserResource::canEdit($this->user))->toBeFalse();
    });
});
