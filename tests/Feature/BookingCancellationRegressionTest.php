<?php

namespace Tests\Feature;

use App\Models\ClinicSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BookingCancellationRegressionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_cancelled_booking_slot_can_be_booked_again(): void
    {
        $date = now()->addWeek()->toDateString();

        ClinicSchedule::factory()->forDate($date)->create([
            'opens_at' => 900,
            'closes_at' => 1000,
        ]);
        $patient = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($patient)->post(route('bookings.store'), [
            'date' => $date,
            'start_time' => '09:00',
        ])->assertRedirect(route('bookings.index'))
            ->assertSessionHas('status', 'Your one-hour booking is confirmed.');

        $booking = $patient->bookings()->sole();
        $this->assertSame(900, $booking->start_time);
        $this->assertSame(60, $booking->duration_minutes);
        $this->assertTrue($booking->confirmed);
        $this->assertDatabaseCount('bookings', 1);

        $this->actingAs($patient)->get(route('bookings.create', ['date' => $date]))
            ->assertOk()
            ->assertSeeText('Booked')
            ->assertDontSee('data-time="09:00"', false);

        $this->actingAs($admin)->delete(route('admin.bookings.destroy', [
            'user' => $patient->id,
            'booking' => $booking->id,
        ]))->assertRedirect(route('admin.bookings', ['email' => $patient->email]))
            ->assertSessionHas('status', 'Booking deleted.');

        $this->assertModelMissing($booking);
        $this->assertDatabaseCount('bookings', 0);

        $this->actingAs($patient)->get(route('bookings.create', ['date' => $date]))
            ->assertOk()
            ->assertSee('data-time="09:00"', false);

        $this->actingAs($patient)->post(route('bookings.store'), [
            'date' => $date,
            'start_time' => '09:00',
        ])->assertRedirect(route('bookings.index'))
            ->assertSessionHas('status', 'Your one-hour booking is confirmed.');

        $rebooked = $patient->bookings()->sole();
        $this->assertSame(900, $rebooked->start_time);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertModelExists($rebooked);
    }
}
