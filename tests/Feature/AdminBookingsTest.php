<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminBookingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_view_or_delete_bookings(): void
    {
        $this->get('/admin/bookings')->assertRedirect('/login');
        $this->delete('/admin/users/1/bookings/1')->assertRedirect('/login');
    }

    public function test_normal_users_cannot_view_or_delete_bookings(): void
    {
        $booking = Booking::factory()->create();
        $this->actingAs($booking->user)->get('/admin/bookings')->assertForbidden();
        $this->delete('/admin/users/'.$booking->user_id.'/bookings/'.$booking->id)->assertForbidden();
        $this->assertModelExists($booking);
    }

    public function test_admin_can_search_and_only_sees_the_selected_users_bookings(): void
    {
        $booking = Booking::factory()->create(['doctor_full_name' => 'Dr. Selected']);
        Booking::factory()->create(['doctor_full_name' => 'Dr. Other']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.bookings', ['email' => $booking->user->email]))
            ->assertOk()->assertSeeText('Dr. Selected')->assertDontSeeText('Dr. Other')
            ->assertSee('<table>', false)->assertSeeText('Delete');
    }

    public function test_admin_starts_with_email_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/bookings')->assertOk()->assertSeeText('Find bookings')
            ->assertDontSee('<table>', false);
    }

    public function test_unknown_email_shows_not_found(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/bookings?email=missing@example.com')->assertOk()
            ->assertSeeText('No user found with that email.');
    }

    public function test_user_without_bookings_shows_empty_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.bookings', ['email' => $user->email]))->assertOk()
            ->assertSeeText('This user has no bookings.');
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from('/admin/bookings')->get('/admin/bookings?email=invalid')
            ->assertRedirect('/admin/bookings')->assertSessionHasErrors('email');
    }

    public function test_admin_deletes_one_booking_and_returns_to_the_selected_user(): void
    {
        $booking = Booking::factory()->create();
        $remaining = Booking::factory()->for($booking->user)->create();
        $other = Booking::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete('/admin/users/'.$booking->user_id.'/bookings/'.$booking->id)
            ->assertRedirect(route('admin.bookings', ['email' => $booking->user->email]));

        $this->assertModelMissing($booking);
        $this->assertModelExists($remaining);
        $this->assertModelExists($other);
    }

    public function test_booking_must_belong_to_the_selected_user(): void
    {
        $booking = Booking::factory()->create();
        $user = User::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete('/admin/users/'.$user->id.'/bookings/'.$booking->id)->assertNotFound();
        $this->assertModelExists($booking);
    }
}
