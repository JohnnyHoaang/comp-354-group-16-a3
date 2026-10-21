<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'date' => ['required', 'date_format:Y-m-d'],
        'start_time' => ['required', 'date_format:H:i'],
    ]);

    $doctor = 'Dr. Alex Smith';
    $date = $validated['date'];

    [$hour, $minute] = array_map(
        'intval',
        explode(':', $validated['start_time'])
    );

    $newStart = $hour * 60 + $minute;
    $newEnd = $newStart + 60;

    $startTime = $hour * 100 + $minute;

    // Reserve the SQLite write lock before checking availability.
    $database = DB::connection();
    $pdo = $database->getPdo();

    // SQLite-specific: use BEGIN IMMEDIATE rather than a deferred transaction.
    $pdo->exec('BEGIN IMMEDIATE');

    try {
        $bookings = Booking::query()
            ->where('doctor_full_name', $doctor)
            ->whereDate('date', $date)
            ->get();

        foreach ($bookings as $booking) {
            $existingHour = intdiv($booking->start_time, 100);
            $existingMinute = $booking->start_time % 100;

            $existingStart = $existingHour * 60 + $existingMinute;
            $existingEnd = $existingStart + $booking->duration_minutes;

            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                $pdo->exec('ROLLBACK');

                return back()->withErrors([
                    'start_time' => 'This timeslot is already booked.',
                ])->withInput();
            }
        }

        $request->user()->bookings()->create([
            'doctor_full_name' => $doctor,
            'date' => $date,
            'start_time' => $startTime,
            'duration_minutes' => 60,
            'confirmed' => true,
        ]);

        $pdo->exec('COMMIT');

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }

        throw $e;
    }

    return redirect()
        ->route('bookings.index')
        ->with('status', 'Your one-hour booking is confirmed.');
}

    public function create(Request $request): View
    {
        $date = null;

        if ($request->has('date')) {
            $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];
        }

        return view('bookings.create', ['date' => $date]);
    }

    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('bookings.index', ['bookings' => $bookings]);
    }

    /**
     * Intentional unused duplicate of create() for the project exercise.
     */
    private function prepareBooking(Request $request): View
    {
        $date = null;

        if ($request->has('date')) {
            $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];
        }

        return view('bookings.create', ['date' => $date]);
    }

    /**
     * Intentional unused duplicate of index() for the project exercise.
     */
    private function listBookings(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('bookings.index', ['bookings' => $bookings]);
    }
}
