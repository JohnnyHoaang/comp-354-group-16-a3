<?php
namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\ClinicSchedule;

class BookingReservationService
{
    public function reserve(User $user, string $date, string $time): Booking
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        $newStart = $hour * 60 + $minute;
        $newEnd = $newStart + 60;

        $pdo = DB::connection()->getPdo();

        // Allow another SQLite writer up to 5 seconds to finish.
        $pdo->exec('PRAGMA busy_timeout = 5000');

        // Acquire the write lock BEFORE checking availability.
        $pdo->exec('BEGIN IMMEDIATE');

        try {

                $startTime = $hour * 100 + $minute;

                $schedule = ClinicSchedule::forDate($date);

                if (! $schedule?->offersSlot($startTime)) {
                    throw ValidationException::withMessages([
                    'start_time' => 'The clinic does not offer that time slot on the selected date.',
                ]);
            }

            if (! $schedule->isSlotFreeOn($date, $startTime)) {
                throw ValidationException::withMessages([
        'start_time' => 'That time slot is no longer available. Please choose another time.',
            ]);
                }

            $existingBookings = Booking::query()
                ->where('doctor_full_name', 'Dr. Alex Smith')
                ->whereDate('date', $date)
                ->get();

            foreach ($existingBookings as $booking) {
                $existingStart =
                    intdiv($booking->start_time, 100) * 60
                    + ($booking->start_time % 100);

                $existingEnd = $existingStart + $booking->duration_minutes;

                if ($newStart < $existingEnd && $newEnd > $existingStart) {
                    throw ValidationException::withMessages([
                        'start_time' => 'This timeslot is already booked.',
                    ]);
                }
            }

            $booking = $user->bookings()->create([
                'doctor_full_name' => 'Dr. Alex Smith',
                'date' => $date,
                'start_time' => $hour * 100 + $minute,
                'duration_minutes' => 60,
                'confirmed' => true,
            ]);

            $pdo->exec('COMMIT');

            return $booking;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->exec('ROLLBACK');
            }

            throw $e;
        }
    }
}
