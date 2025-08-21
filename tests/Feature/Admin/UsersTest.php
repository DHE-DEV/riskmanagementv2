<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
        
        $this->user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    public function test_non_admin_cannot_view_users_list(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
            'is_admin' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/users', $userData);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
            'is_admin' => false,
        ]);
    }

    public function test_admin_can_edit_user(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_active' => false,
            'is_admin' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->put("/admin/users/{$this->user->id}", $updateData);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_active' => false,
            'is_admin' => true,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/admin/users/{$this->admin->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/admin/users/{$this->user->id}");

        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        // Deaktivieren
        $response = $this->actingAs($this->admin)
            ->patch("/admin/users/{$this->user->id}/toggle-active");

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_active' => false,
        ]);

        // Reaktivieren
        $response = $this->actingAs($this->admin)
            ->patch("/admin/users/{$this->user->id}/toggle-active");

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_access_admin_panel_when_inactive(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->get('/admin');

        $response->assertStatus(403);
    }

    public function test_user_cannot_access_admin_panel_when_not_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin');

        $response->assertStatus(200);
    }
}
