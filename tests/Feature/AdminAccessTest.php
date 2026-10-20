<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_normal_users_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/admin')->assertForbidden();
    }

    public function test_admins_can_access_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin')->assertOk()->assertSeeText('Welcome to the MediLink admin area.');
    }
}
