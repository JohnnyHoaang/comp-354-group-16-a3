<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingListTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/book/list')->assertRedirect('/login');
    }

    public function test_users_without_bookings_see_an_empty_message(): void
    {
        $this->actingAs(User::factory()->create())->get('/book/list')
            ->assertOk()->assertSeeText('You have no active bookings.');
    }

    public function test_only_the_users_current_and_future_bookings_are_listed_in_order(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->startOfDay());
        $user = User::factory()->create();
        Booking::factory()->for($user)->create([
            'doctor_full_name' => 'Dr. Later', 'date' => '2026-11-02', 'start_time' => 1234, 'confirmed' => true,
        ]);
        Booking::factory()->for($user)->create([
            'doctor_full_name' => 'Dr. Today', 'date' => '2026-11-01', 'start_time' => 905, 'confirmed' => false,
        ]);
        Booking::factory()->for($user)->create([
            'doctor_full_name' => 'Dr. Past', 'date' => '2026-10-31',
        ]);
        Booking::factory()->create([
            'doctor_full_name' => 'Dr. Private', 'date' => '2026-11-02',
        ]);

        $this->actingAs($user)->get('/book/list')->assertOk()
            ->assertSeeInOrder(['Dr. Today', '09:05', 'Unconfirmed', 'Dr. Later', '12:34', 'Confirmed'])
            ->assertDontSeeText('Dr. Past')->assertDontSeeText('Dr. Private');
    }
}
