<?php

namespace Database\Seeders;

use App\Models\ClinicSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClinicScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $weekendDays = [Carbon::SATURDAY, Carbon::SUNDAY];

        for ($weekday = Carbon::SUNDAY; $weekday <= Carbon::SATURDAY; $weekday++) {
            ClinicSchedule::updateOrCreate(['weekday' => $weekday], [
                'opens_at' => 900,
                'closes_at' => 1700,
                'is_closed' => in_array($weekday, $weekendDays, true),
            ]);
        }
    }
}
