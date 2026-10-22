<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_from_account_settings(): void
    {
        $this->get(route('account.settings.edit'))->assertRedirect('/login');
        $this->post(route('account.settings.email-availability'))->assertRedirect('/login');
        $this->put(route('account.settings.update'))->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_their_contact_information(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+15145550123',
        ]);

        $this->actingAs($user)->get(route('account.settings.edit'))
            ->assertOk()
            ->assertSee('value="patient@example.net"', false)
            ->assertSee('value="+15145550123"', false)
            ->assertSee('id="phone-help" class="help-text" hidden', false)
            ->assertSee('wa-button type="submit" variant="brand" disabled', false)
            ->assertSeeText('Use a US or Canadian 10-digit number. The area code and exchange must each start with 2-9. You may include +1 and formatting, for example (514) 223-2345.');
    }

    public function test_email_availability_check_accepts_the_current_email(): void
    {
        $user = User::factory()->create(['email' => 'Patient@Example.net']);

        $this->actingAs($user)
            ->postJson(route('account.settings.email-availability'), ['email' => 'patient@example.NET'])
            ->assertOk()
            ->assertExactJson(['available' => true]);
    }

    public function test_email_availability_check_rejects_another_users_email_case_insensitively(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.org']);

        $this->actingAs($user)
            ->postJson(route('account.settings.email-availability'), ['email' => 'TAKEN@example.ORG'])
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_email_availability_check_rejects_invalid_email_format(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('account.settings.email-availability'), ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_successful_updates_normalize_contact_information_without_sending_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);

        $this->actingAs($user)->put(route('account.settings.update'), [
            'email' => 'New.Patient@Example.ORG',
            'phone' => '+1 (514) 555-0123',
        ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHas('status', 'Your contact information was updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new.patient@example.org',
            'phone' => '+15145550123',
        ]);

        $this->get(route('account.settings.edit'))
            ->assertOk()
            ->assertSee('value="new.patient@example.org"', false)
            ->assertSee('value="+15145550123"', false);

        $this->actingAs($user->fresh())->get(route('home'))
            ->assertOk()
            ->assertSeeText('Email: new.patient@example.org')
            ->assertSeeText('Phone: +15145550123')
            ->assertSee(route('account.settings.edit'));

        Mail::assertNothingSent();
    }

    public function test_phone_can_be_updated_while_unchanged_email_is_preserved(): void
    {
        $user = User::factory()->create([
            'email' => 'Patient@Example.net',
            'phone' => '+14165550123',
        ]);

        $this->actingAs($user)->put(route('account.settings.update'), [
            'email' => 'patient@example.NET',
            'phone' => '(514) 555-0123',
        ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHas('status', 'Your contact information was updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'Patient@Example.net',
            'phone' => '+15145550123',
        ]);
    }

    public function test_email_can_be_updated_while_unchanged_phone_is_preserved(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+15145550123',
        ]);

        $this->actingAs($user)->put(route('account.settings.update'), [
            'email' => 'updated@example.org',
            'phone' => '514 555 0123',
        ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHas('status', 'Your contact information was updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'updated@example.org',
            'phone' => '+15145550123',
        ]);
    }

    public function test_invalid_email_is_rejected_without_saving_contact_changes(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);

        $this->actingAs($user)->from(route('account.settings.edit'))
            ->put(route('account.settings.update'), [
                'email' => 'not-an-email',
                'phone' => '514-555-0123',
            ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);
    }

    public function test_update_is_rejected_when_both_contact_details_are_unchanged(): void
    {
        $user = User::factory()->create([
            'email' => 'Patient@Example.net',
            'phone' => '+15145550123',
        ]);

        $this->actingAs($user)->from(route('account.settings.edit'))
            ->put(route('account.settings.update'), [
                'email' => 'patient@example.NET',
                'phone' => '(514) 555-0123',
            ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHasErrors(['email' => 'No changes detected. Update your email address or phone number.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'Patient@Example.net',
            'phone' => '+15145550123',
        ]);
    }

    public function test_email_in_use_by_another_user_is_rejected_case_insensitively(): void
    {
        $user = User::factory()->create(['email' => 'patient@example.net']);
        User::factory()->create(['email' => 'other@example.org']);

        $this->actingAs($user)->from(route('account.settings.edit'))
            ->put(route('account.settings.update'), [
                'email' => 'OTHER@EXAMPLE.ORG',
                'phone' => '514-555-0123',
            ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHasErrors(['email' => 'This email address is already in use.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'patient@example.net',
            'phone' => null,
        ]);
    }

    #[TestWith(['114-555-0123'])]
    #[TestWith(['514-155-0123'])]
    #[TestWith(['514-555-012'])]
    public function test_invalid_north_american_phone_numbers_are_rejected(string $phone): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);

        $this->actingAs($user)->from(route('account.settings.edit'))
            ->put(route('account.settings.update'), [
                'email' => 'new@example.org',
                'phone' => $phone,
            ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHasErrors(['phone' => 'Enter a valid US or Canadian phone number.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);
    }

    public function test_different_users_can_use_the_same_phone_number(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'other@example.net',
            'phone' => null,
        ]);

        $this->actingAs($otherUser)->put(route('account.settings.update'), [
            'email' => 'other.updated@example.net',
            'phone' => '514-555-0123',
        ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+14165550123',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'phone' => '+15145550123',
        ]);
    }

    public function test_submitted_user_id_cannot_change_another_users_contact_information(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'other@example.net',
            'phone' => '+16135550123',
        ]);

        $this->actingAs($user)->put(route('account.settings.update'), [
            'user_id' => $otherUser->id,
            'email' => 'patient.updated@example.net',
            'phone' => '514-555-0123',
        ])->assertRedirect(route('account.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'patient.updated@example.net',
            'phone' => '+15145550123',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'email' => 'other@example.net',
            'phone' => '+16135550123',
        ]);
    }

    public function test_email_and_phone_are_required(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);

        $this->actingAs($user)->from(route('account.settings.edit'))
            ->put(route('account.settings.update'), [])
            ->assertRedirect(route('account.settings.edit'))
            ->assertSessionHasErrors(['email', 'phone']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'patient@example.net',
            'phone' => '+14165550123',
        ]);
    }
}
