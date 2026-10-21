<?php

namespace Tests\Feature;

use App\Models\ClinicSchedule;
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

    public function test_selected_date_shows_the_clinics_opening_hours(): void
    {
        ClinicSchedule::factory()->forDate('2026-11-02')->create(['opens_at' => 900, 'closes_at' => 1700]);

        $this->actingAs(User::factory()->create())->get('/book/new?date=2026-11-02')->assertOk()
            ->assertSeeText('2. Choose a time')
            ->assertSeeText('Availability for 2026-11-02 — 09:00–17:00');
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->from('/book/new')
            ->get('/book/new?date=2026-02-30')->assertRedirect('/book/new')
            ->assertSessionHasErrors('date');
    }
}
