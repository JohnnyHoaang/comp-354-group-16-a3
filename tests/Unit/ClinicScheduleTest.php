<?php

namespace Tests\Unit;

use App\Models\ClinicSchedule;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ClinicScheduleTest extends TestCase
{
    public function test_slots_start_on_the_hour_between_opening_and_closing(): void
    {
        $schedule = new ClinicSchedule(['opens_at' => 900, 'closes_at' => 1200]);

        $this->assertSame([900, 1000, 1100], $schedule->slotStartTimes());
    }

    public function test_a_slot_that_would_run_past_closing_is_not_offered(): void
    {
        $schedule = new ClinicSchedule(['opens_at' => 930, 'closes_at' => 1200]);

        $this->assertSame([930, 1030], $schedule->slotStartTimes());
    }

    public function test_a_closed_day_offers_no_slots(): void
    {
        $schedule = new ClinicSchedule(['opens_at' => 900, 'closes_at' => 1700, 'is_closed' => true]);

        $this->assertFalse($schedule->isOpen());
        $this->assertSame([], $schedule->slotStartTimes());
    }

    public function test_closing_before_opening_offers_no_slots(): void
    {
        $schedule = new ClinicSchedule(['opens_at' => 1700, 'closes_at' => 900]);

        $this->assertFalse($schedule->isOpen());
        $this->assertSame([], $schedule->slotStartTimes());
    }

    #[TestWith([900, true])]
    #[TestWith([1100, true])]
    #[TestWith([800, false])]
    #[TestWith([1200, false])]
    #[TestWith([930, false])]
    public function test_only_generated_slot_starts_are_offered(int $startTime, bool $expected): void
    {
        $schedule = new ClinicSchedule(['opens_at' => 900, 'closes_at' => 1200]);

        $this->assertSame($expected, $schedule->offersSlot($startTime));
    }
}
