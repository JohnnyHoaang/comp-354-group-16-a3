<?php

namespace Tests\Feature;

use App\Models\ClinicSchedule;
use Database\Seeders\ClinicScheduleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClinicScheduleSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeding_opens_the_clinic_on_weekdays_only(): void
    {
        $this->seed(ClinicScheduleSeeder::class);

        $this->assertDatabaseCount('clinic_schedules', 7);

        $monday = ClinicSchedule::where('weekday', Carbon::MONDAY)->sole();
        $this->assertTrue($monday->isOpen());
        $this->assertSame([900, 1000, 1100, 1200, 1300, 1400, 1500, 1600], $monday->slotStartTimes());

        foreach ([Carbon::SATURDAY, Carbon::SUNDAY] as $weekend) {
            $this->assertFalse(ClinicSchedule::where('weekday', $weekend)->sole()->isOpen());
        }
    }

    public function test_seeding_twice_does_not_duplicate_weekdays(): void
    {
        $this->seed(ClinicScheduleSeeder::class);
        $this->seed(ClinicScheduleSeeder::class);

        $this->assertDatabaseCount('clinic_schedules', 7);
    }
}
