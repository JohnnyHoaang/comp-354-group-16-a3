<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BookingReminderTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['2026-11-01', 959, false])]
    #[TestWith(['2026-11-01', 1000, false])]
    #[TestWith(['2026-11-01', 1001, true])]
    #[TestWith(['2026-11-02', 905, true])]
    #[TestWith(['2026-11-03', 959, true])]
    #[TestWith(['2026-11-03', 1000, false])]
    #[TestWith(['2026-11-03', 1001, false])]
    public function test_reminders_only_include_bookings_starting_in_less_than_48_hours(string $date, int $time, bool $visible): void
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(10, 0));
        $user = User::factory()->create();
        Booking::factory()->for($user)->create([
            'doctor_full_name' => 'Dr. Reminder', 'date' => $date, 'start_time' => $time,
        ]);

        $response = $this->actingAs($user)->get('/home')->assertOk();

        if ($visible) {
            $response->assertSeeText('Booking reminder')->assertSeeText('Dr. Reminder');
        } else {
            $response->assertDontSeeText('Booking reminder')->assertDontSeeText('Dr. Reminder');
        }
    }

    public function test_other_users_bookings_do_not_trigger_reminders(): void
    {
        $this->freezeTime();
        Booking::factory()->create(['date' => today()->addDay(), 'start_time' => 1200]);

        $this->actingAs(User::factory()->create())->get('/home')->assertOk()
            ->assertDontSeeText('Booking reminder');
    }

    public function test_no_callout_is_shown_without_bookings(): void
    {
        $this->actingAs(User::factory()->create())->get('/home')->assertOk()
            ->assertDontSeeText('Booking reminder');
    }
}
