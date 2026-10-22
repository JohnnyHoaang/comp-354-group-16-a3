<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ClinicSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ConfirmBookingTest extends TestCase
{
    use DatabaseMigrations;

    private const DATE = '2026-11-02';

    private const SLOT_TAKEN = 'That time slot is no longer available. Please choose another time.';

    private const NOT_OFFERED = 'The clinic does not offer that time slot on the selected date.';

    private function openClinic(int $opensAt = 900, int $closesAt = 1200): ClinicSchedule
    {
        return ClinicSchedule::factory()->forDate(self::DATE)->create([
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
        ]);
    }

    public function test_guests_cannot_confirm_bookings(): void
    {
        $this->post('/book/new')->assertRedirect('/login');
    }

    public function test_confirmation_saves_a_one_hour_booking_for_the_signed_in_user(): void
    {
        $this->openClinic();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $existingBooking = Booking::factory()->create([
            'doctor_full_name' => 'Dr. Alex Smith',
            'date' => '2026-11-01',
            'start_time' => 1100,
            'duration_minutes' => 60,
        ]);
        $this->actingAs($user)->post('/book/new', [
            'date' => self::DATE, 'start_time' => '09:00',
            'user_id' => $otherUser->id, 'duration_minutes' => 120, 'confirmed' => false,
        ])->assertRedirect(route('bookings.index'))
            ->assertSessionHas('status', 'Your one-hour booking is confirmed.');

        $booking = $user->bookings()->sole();
        $this->assertSame(900, $booking->start_time);
        $this->assertSame(60, $booking->duration_minutes);
        $this->assertTrue($booking->confirmed);
        $this->assertSame(self::DATE, $booking->date->format('Y-m-d'));
        $this->assertSame(Booking::DOCTOR_FULL_NAME, $booking->doctor_full_name);
        $this->assertSame(0, $otherUser->bookings()->count());
        $this->assertModelExists($existingBooking);
    }

    public function test_a_slot_taken_since_the_search_is_rejected(): void
    {
        $this->openClinic();
        $taken = Booking::factory()->create([
            'date' => self::DATE, 'start_time' => 900,
            'doctor_full_name' => Booking::DOCTOR_FULL_NAME,
        ]);

        $this->actingAs(User::factory()->create())->from('/book/new?date='.self::DATE)
            ->post('/book/new', ['date' => self::DATE, 'start_time' => '09:00'])
            ->assertRedirect('/book/new?date='.self::DATE)
            ->assertSessionHasErrors(['start_time' => self::SLOT_TAKEN]);

        $this->assertDatabaseCount('bookings', 1);
        $this->assertModelExists($taken);
    }

    public function test_a_slot_overlapped_by_an_earlier_booking_is_rejected(): void
    {
        $this->openClinic();
        Booking::factory()->create([
            'date' => self::DATE, 'start_time' => 930,
            'doctor_full_name' => Booking::DOCTOR_FULL_NAME,
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/book/new', ['date' => self::DATE, 'start_time' => '10:00'])
            ->assertSessionHasErrors(['start_time' => self::SLOT_TAKEN]);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_the_same_patient_cannot_double_book_a_slot(): void
    {
        $this->openClinic();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/book/new', ['date' => self::DATE, 'start_time' => '09:00'])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)->post('/book/new', ['date' => self::DATE, 'start_time' => '09:00'])
            ->assertSessionHasErrors(['start_time' => self::SLOT_TAKEN]);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_a_time_outside_opening_hours_is_rejected(): void
    {
        $this->openClinic(900, 1200);

        $this->actingAs(User::factory()->create())
            ->post('/book/new', ['date' => self::DATE, 'start_time' => '14:00'])
            ->assertSessionHasErrors(['start_time' => self::NOT_OFFERED]);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_time_that_is_not_a_slot_start_is_rejected(): void
    {
        $this->openClinic(900, 1200);

        $this->actingAs(User::factory()->create())
            ->post('/book/new', ['date' => self::DATE, 'start_time' => '09:30'])
            ->assertSessionHasErrors(['start_time' => self::NOT_OFFERED]);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_booking_on_a_closed_day_is_rejected(): void
    {
        ClinicSchedule::factory()->forDate(self::DATE)->closed()->create();

        $this->actingAs(User::factory()->create())
            ->post('/book/new', ['date' => self::DATE, 'start_time' => '09:00'])
            ->assertSessionHasErrors(['start_time' => self::NOT_OFFERED]);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_booking_on_a_date_with_no_schedule_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/book/new', ['date' => self::DATE, 'start_time' => '09:00'])
            ->assertSessionHasErrors(['start_time' => self::NOT_OFFERED]);

        $this->assertDatabaseCount('bookings', 0);
    }

    #[TestWith(['24:00'])]
    #[TestWith(['12:60'])]
    #[TestWith([''])]
    public function test_invalid_times_do_not_create_bookings(string $time): void
    {
        $this->openClinic();

        $this->actingAs(User::factory()->create())->post('/book/new', [
            'date' => self::DATE, 'start_time' => $time,
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
