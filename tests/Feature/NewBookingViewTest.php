<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NewBookingViewTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/book/new')->assertRedirect('/login');
    }

    public function test_date_is_the_first_step(): void
    {
        $this->actingAs(User::factory()->create())->get('/book/new')->assertOk()
            ->assertSeeText('1. Choose a date')->assertDontSeeText('2. Choose a time');
    }

    public function test_selected_date_shows_all_hours_even_when_booked(): void
    {
        $user = User::factory()->create();
        Booking::factory()->for($user)->create(['date' => '2026-11-01', 'start_time' => 1200]);
        Booking::factory()->create(['date' => '2026-11-01', 'start_time' => 1300]);

        $response = $this->actingAs($user)->get('/book/new?date=2026-11-01')->assertOk()
            ->assertSeeText('2. Choose a time')->assertSeeText('Available all day.');

        for ($hour = 0; $hour < 24; $hour++) {
            $response->assertSee('data-time="'.sprintf('%02d:00', $hour).'"', false);
        }
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->from('/book/new')
            ->get('/book/new?date=2026-02-30')->assertRedirect('/book/new')
            ->assertSessionHasErrors('date');
    }
}
