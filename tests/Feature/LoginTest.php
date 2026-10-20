<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_logout_ends_the_session_and_redirects_to_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['private_value' => 'example'])
            ->post('/logout')->assertRedirect('/login')
            ->assertSessionMissing('private_value');

        $this->assertGuest();
        $this->get('/home')->assertRedirect('/login');
    }

    public function test_guests_posting_logout_are_redirected_to_login(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_guests_can_view_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('Log in');
    }

    public function test_guests_are_redirected_from_home_to_login(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }

    #[TestWith(['user'])]
    #[TestWith(['admin'])]
    public function test_authenticated_users_see_their_email_and_role(string $role): void
    {
        $user = User::factory()->create(['email' => 'patient@example.com', 'role' => $role]);
        $this->actingAs($user)->get('/home')->assertOk()
            ->assertSeeText('Email: patient@example.com')->assertSeeText('Role: '.$role);
    }

    public function test_valid_credentials_log_in_and_redirect_home(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/home')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_incorrect_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'The email or password is incorrect.'])
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_missing_credentials_are_rejected(): void
    {
        $this->from('/login')->post('/login', [])->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_from_login_to_home(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/home');
    }
}
