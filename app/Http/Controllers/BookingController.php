<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ClinicSchedule;
use App\Services\BookingReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        app(BookingReservationService::class)->reserve(
            $request->user(),
            $validated['date'],
            $validated['start_time']
        );

        return redirect()
            ->route('bookings.index')
            ->with('status', 'Your one-hour booking is confirmed.');
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
}
