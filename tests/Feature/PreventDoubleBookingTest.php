<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PreventDoubleBookingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_duplicate_booking_is_rejected(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Booking::factory()->create([
            'user_id' => $user1->id,
            'doctor_full_name' => 'Dr. Alex Smith',
            'date' => '2026-11-01',
            'start_time' => 930,
            'duration_minutes' => 60,
        ]);

        $response = $this->actingAs($user2)->post('/book/new', [
            'date' => '2026-11-01',
            'start_time' => '09:30',
        ]);
         $this->assertDatabaseCount('bookings', 1);
         $response->assertSessionHasErrors('start_time');
    }
       
}

