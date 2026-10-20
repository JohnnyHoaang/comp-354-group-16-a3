<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingRelationshipsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_users_can_have_no_bookings(): void
    {
        $user = User::factory()->create();

        $this->assertCount(0, $user->bookings);
    }

    public function test_users_have_multiple_bookings_without_including_other_users_bookings(): void
    {
        $user = User::factory()->create();
        $bookings = Booking::factory()->for($user)->count(2)->create();
        Booking::factory()->create();

        $this->assertEqualsCanonicalizing($bookings->modelKeys(), $user->bookings->modelKeys());
        foreach ($bookings as $booking) {
            $this->assertTrue($booking->user->is($user));
        }
    }

    public function test_creating_a_booking_through_a_user_assigns_ownership(): void
    {
        $user = User::factory()->create();
        $booking = $user->bookings()->create([
            'doctor_full_name' => 'Dr. Alex Smith',
            'start_time' => 1234,
            'date' => '2026-11-01',
            'confirmed' => false,
        ]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'user_id' => $user->id]);
        $this->assertTrue($booking->fresh()->user->is($user));
    }
}
