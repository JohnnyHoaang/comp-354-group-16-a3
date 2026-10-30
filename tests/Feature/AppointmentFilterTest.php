<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ClinicSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AppointmentFilterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_patients_can_filter_appointments_by_date(): void
    {
        $user = User::factory()->create();

        ClinicSchedule::factory()
            ->forDate('2026-11-02')
            ->create([
                'opens_at' => 900,
                'closes_at' => 1200,
            ]);

        $response = $this->actingAs($user)
            ->get('/book/new?date=2026-11-02');

        $response->assertOk();

        $response->assertViewHas('date', '2026-11-02');

        $response->assertSee('09:00');
        $response->assertSee('10:00');
        $response->assertSee('11:00');
    }

    #[TestWith([])]
    #[TestWith(['clinic=current'])]
    #[TestWith(['appointment_type=one-hour'])]
    #[TestWith(['clinic=current&appointment_type=one-hour'])]
    public function test_filters_without_a_date_show_the_upcoming_week(string $query = ''): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
        ClinicSchedule::factory()->forDate('2026-11-02')
            ->create(['opens_at' => 900, 'closes_at' => 1000]);
        ClinicSchedule::factory()->forDate('2026-11-08')
            ->create(['opens_at' => 1400, 'closes_at' => 1500]);

        $this->actingAs(User::factory()->create())->get('/book/new?'.$query)
            ->assertOk()
            ->assertSeeText('Availability for the next seven days')
            ->assertSeeText('Availability for 2026-11-02')
            ->assertSeeText('Availability for 2026-11-08')
            ->assertDontSeeText('Availability for 2026-11-09')
            ->assertSee('data-time="09:00"', false)
            ->assertSee('data-time="14:00"', false)
            ->assertSee('data-target="time-2026-11-02"', false)
            ->assertSee('data-target="time-2026-11-08"', false);
    }

    #[TestWith([''])]
    #[TestWith(['clinic=current'])]
    #[TestWith(['appointment_type=one-hour'])]
    #[TestWith(['clinic=current&appointment_type=one-hour'])]
    public function test_date_filter_restricts_results_with_other_filters(string $query): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
        ClinicSchedule::factory()->forDate('2026-11-02')
            ->create(['opens_at' => 900, 'closes_at' => 1000]);
        ClinicSchedule::factory()->forDate('2026-11-03')
            ->create(['opens_at' => 1400, 'closes_at' => 1500]);

        $this->actingAs(User::factory()->create())->get('/book/new?date=2026-11-03&'.$query)
            ->assertOk()
            ->assertSeeText('Availability for 2026-11-03')
            ->assertSee('data-time="14:00"', false)
            ->assertDontSeeText('Availability for 2026-11-02')
            ->assertDontSee('data-time="09:00"', false);
    }

    #[TestWith(['clinic=unavailable'])]
    #[TestWith(['appointment_type=unavailable'])]
    #[TestWith(['date=2026-11-02&clinic=unavailable&appointment_type=one-hour'])]
    #[TestWith(['date=2026-11-02&clinic=current&appointment_type=unavailable'])]
    public function test_nonmatching_clinic_or_type_returns_no_slots(string $query): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
        ClinicSchedule::factory()->forDate('2026-11-02')->create();

        $this->actingAs(User::factory()->create())->get('/book/new?'.$query)
            ->assertOk()
            ->assertSeeText('No appointments match these filters.')
            ->assertDontSee('data-time=', false)
            ->assertDontSeeText('Confirm one-hour booking');
    }

    public function test_clear_filters_restores_unfiltered_availability_and_empty_controls(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
        ClinicSchedule::factory()->forDate('2026-11-02')
            ->create(['opens_at' => 900, 'closes_at' => 1000]);
        ClinicSchedule::factory()->forDate('2026-11-03')
            ->create(['opens_at' => 1400, 'closes_at' => 1500]);
        $this->actingAs(User::factory()->create());

        $filtered = $this->get('/book/new?date=2026-11-02&clinic=current&appointment_type=one-hour')
            ->assertOk()
            ->assertSee('value="current" selected', false)
            ->assertSee('value="one-hour" selected', false)
            ->assertSee('data-time="09:00"', false)
            ->assertDontSee('data-time="14:00"', false)
            ->assertSee('<a href="'.route('bookings.create').'">Clear Filters</a>', false);

        preg_match('/<a href="([^"]+)">Clear Filters<\/a>/', $filtered->getContent(), $link);

        $this->get($link[1])->assertOk()
            ->assertViewHas('date', null)
            ->assertViewHas('clinic', null)
            ->assertViewHas('appointmentType', null)
            ->assertSee('name="date" value=""', false)
            ->assertDontSee('value="current" selected', false)
            ->assertDontSee('value="one-hour" selected', false)
            ->assertSee('data-time="09:00"', false)
            ->assertSee('data-time="14:00"', false);
    }

    public function test_filtered_and_unfiltered_searches_keep_overlapping_bookings_unavailable(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
        ClinicSchedule::factory()->forDate('2026-11-02')
            ->create(['opens_at' => 900, 'closes_at' => 1200]);
        Booking::factory()->create([
            'doctor_full_name' => Booking::DOCTOR_FULL_NAME,
            'date' => '2026-11-02',
            'start_time' => 930,
            'duration_minutes' => 60,
        ]);
        $this->actingAs(User::factory()->create());

        foreach (['', '?date=2026-11-02&clinic=current&appointment_type=one-hour'] as $query) {
            $this->get('/book/new'.$query)->assertOk()
                ->assertDontSee('data-time="09:00"', false)
                ->assertDontSee('data-time="10:00"', false)
                ->assertSee('data-time="11:00"', false)
                ->assertSeeText('Booked');
        }
    }

    public function test_empty_filters_restore_the_default_results(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
        ClinicSchedule::factory()->forDate('2026-11-02')->create();

        $this->actingAs(User::factory()->create())->get('/book/new?date=&clinic=&appointment_type=')
            ->assertOk()
            ->assertSeeText('Availability for the next seven days')
            ->assertSee('data-time="09:00"', false);
    }

    #[TestWith(['clinic[]=current', 'clinic'])]
    #[TestWith(['appointment_type[]=one-hour', 'appointment_type'])]
    public function test_malformed_filters_are_rejected(string $query, string $field): void
    {
        $this->actingAs(User::factory()->create())->from('/book/new')
            ->get('/book/new?'.$query)
            ->assertRedirect('/book/new')
            ->assertSessionHasErrors($field);
    }

    #[TestWith(['clinic'])]
    #[TestWith(['appointment_type'])]
    public function test_oversized_filters_are_rejected(string $field): void
    {
        $this->actingAs(User::factory()->create())->from('/book/new')
            ->get('/book/new?'.$field.'='.str_repeat('a', 101))
            ->assertRedirect('/book/new')
            ->assertSessionHasErrors($field);
    }
}
