<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ClinicSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $startTime = (int) str_replace(':', '', $validated['start_time']);

        DB::transaction(function () use ($request, $validated, $startTime): void {
            $schedule = ClinicSchedule::forDate($validated['date']);

            if (! $schedule?->offersSlot($startTime)) {
                throw ValidationException::withMessages([
                    'start_time' => 'The clinic does not offer that time slot on the selected date.',
                ]);
            }

            if (! $schedule->isSlotFreeOn($validated['date'], $startTime)) {
                throw ValidationException::withMessages([
                    'start_time' => 'That time slot is no longer available. Please choose another time.',
                ]);
            }

            $request->user()->bookings()->create([
                'doctor_full_name' => Booking::DOCTOR_FULL_NAME,
                'date' => $validated['date'],
                'start_time' => $startTime,
                'confirmed' => true,
            ]);
        });

        return redirect()->route('bookings.index')->with('status', 'Your one-hour booking is confirmed.');
    }

    public function create(Request $request): View
    {
        $date = null;
        $openingHours = null;
        $slots = [];

        if ($request->has('date')) {
            $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];
            $schedule = ClinicSchedule::forDate($date);

            if ($schedule?->isOpen()) {
                $openingHours = $schedule->openingHours();
                $slots = $schedule->availabilityOn($date);
            }
        }

        return view('bookings.create', [
            'date' => $date,
            'openingHours' => $openingHours,
            'slots' => $slots,
            'hasAvailableSlot' => collect($slots)->contains('is_available', true),
            'doctorFullName' => Booking::DOCTOR_FULL_NAME,
        ]);
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
