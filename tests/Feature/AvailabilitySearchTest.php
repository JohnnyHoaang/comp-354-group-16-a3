<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ClinicSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AvailabilitySearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const DATE = '2026-11-02';

    private function bookingAt(int $startTime, ?User $user = null): Booking
    {
        return Booking::factory()->for($user ?? User::factory())->create([
            'date' => self::DATE,
            'start_time' => $startTime,
            'doctor_full_name' => Booking::DOCTOR_FULL_NAME,
        ]);
    }

    public function test_search_offers_only_slots_inside_the_clinics_opening_hours(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1200]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/book/new?date='.self::DATE)->assertOk();

        foreach (['09:00', '10:00', '11:00'] as $offered) {
            $response->assertSee('data-time="'.$offered.'"', false);
        }
        foreach (['08:00', '12:00', '00:00', '23:00'] as $notOffered) {
            $response->assertDontSee('data-time="'.$notOffered.'"', false);
        }
    }

    public function test_search_marks_a_booked_slot_as_unavailable(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1200]);
        $this->bookingAt(1000);

        $this->actingAs(User::factory()->create())->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertSee('data-time="09:00"', false)
            ->assertDontSee('data-time="10:00"', false)
            ->assertSeeText('Booked');
    }

    public function test_search_marks_a_slot_booked_by_another_patient_as_unavailable(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1200]);
        $this->bookingAt(1100, User::factory()->create());

        $this->actingAs(User::factory()->create())->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertDontSee('data-time="11:00"', false);
    }

    public function test_search_marks_every_slot_a_booking_overlaps_as_unavailable(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1200]);
        $this->bookingAt(930);

        $this->actingAs(User::factory()->create())->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertDontSee('data-time="09:00"', false)
            ->assertDontSee('data-time="10:00"', false)
            ->assertSee('data-time="11:00"', false);
    }

    public function test_a_new_search_frees_a_slot_after_the_booking_is_cancelled(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1200]);
        $booking = $this->bookingAt(1000);
        $patient = User::factory()->create();

        $this->actingAs($patient)->get('/book/new?date='.self::DATE)
            ->assertDontSee('data-time="10:00"', false);

        $booking->delete();

        $this->actingAs($patient)->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertSee('data-time="10:00"', false);
    }

    public function test_a_new_search_reflects_an_updated_clinic_schedule(): void
    {
        $schedule = ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1200]);
        $patient = User::factory()->create();

        $this->actingAs($patient)->get('/book/new?date='.self::DATE)
            ->assertSee('data-time="09:00"', false)
            ->assertDontSee('data-time="14:00"', false);

        $schedule->update(['opens_at' => 1300, 'closes_at' => 1500]);

        $this->actingAs($patient)->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertSee('data-time="13:00"', false)
            ->assertSee('data-time="14:00"', false)
            ->assertDontSee('data-time="09:00"', false);
    }

    public function test_a_new_search_reflects_the_clinic_becoming_closed_that_day(): void
    {
        $schedule = ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1200]);
        $patient = User::factory()->create();

        $this->actingAs($patient)->get('/book/new?date='.self::DATE)
            ->assertSee('data-time="09:00"', false);

        $schedule->update(['is_closed' => true]);

        $this->actingAs($patient)->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertSeeText('The clinic is closed on '.self::DATE.'.')
            ->assertDontSee('data-time=', false);
    }

    public function test_search_reports_a_date_with_no_clinic_schedule_as_closed(): void
    {
        $this->actingAs(User::factory()->create())->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertSeeText('The clinic is closed on '.self::DATE.'.')
            ->assertDontSee('data-time=', false);
    }

    public function test_search_reports_a_fully_booked_day(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1100]);
        $this->bookingAt(900);
        $this->bookingAt(1000);

        $this->actingAs(User::factory()->create())->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertSeeText('No times are still available on '.self::DATE.'.')
            ->assertDontSee('data-time=', false);
    }

    public function test_search_ignores_bookings_on_other_dates(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->create(['opens_at' => 900, 'closes_at' => 1100]);
        Booking::factory()->create([
            'date' => '2026-11-09', 'start_time' => 900,
            'doctor_full_name' => Booking::DOCTOR_FULL_NAME,
        ]);

        $this->actingAs(User::factory()->create())->get('/book/new?date='.self::DATE)
            ->assertOk()
            ->assertSee('data-time="09:00"', false);
    }
}
