<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ConfirmBookingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_confirm_bookings(): void
    {
        $this->post('/book/new')->assertRedirect('/login');
    }

    public function test_confirmation_saves_a_one_hour_booking_for_the_signed_in_user_even_with_an_overlap(): void
    {
        $user = User::factory()->create();
        $other = Booking::factory()->create(['date' => '2026-11-01', 'start_time' => 905]);

        $this->actingAs($user)->post('/book/new', [
            'date' => '2026-11-01', 'start_time' => '09:05',
            'user_id' => $other->user_id, 'duration_minutes' => 120, 'confirmed' => false,
        ])->assertRedirect(route('bookings.index'))
            ->assertSessionHas('status', 'Your one-hour booking is confirmed.');

        $booking = $user->bookings()->sole();
        $this->assertSame(905, $booking->start_time);
        $this->assertSame(60, $booking->duration_minutes);
        $this->assertTrue($booking->confirmed);
        $this->assertSame('2026-11-01', $booking->date->format('Y-m-d'));
        $this->assertSame('Dr. Alex Smith', $booking->doctor_full_name);
        $this->assertModelExists($other);
    }

    #[TestWith(['24:00'])]
    #[TestWith(['12:60'])]
    #[TestWith([''])]
    public function test_invalid_times_do_not_create_bookings(string $time): void
    {
        $this->actingAs(User::factory()->create())->post('/book/new', [
            'date' => '2026-11-01', 'start_time' => $time,
        ])->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_invalid_dates_do_not_create_bookings(): void
    {
        $this->actingAs(User::factory()->create())->post('/book/new', [
            'date' => '2026-02-30', 'start_time' => '12:00',
        ])->assertSessionHasErrors('date');

        $this->assertDatabaseCount('bookings', 0);
    }
}
