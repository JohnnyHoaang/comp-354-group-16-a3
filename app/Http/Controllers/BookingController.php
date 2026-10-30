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
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'clinic' => ['nullable', 'string', 'max:100'],
            'appointment_type' => ['nullable', 'string', 'max:100'],
        ]);

        $date = $filters['date'] ?? null;
        $clinic = $filters['clinic'] ?? null;
        $appointmentType = $filters['appointment_type'] ?? null;
        $clinics = ['current' => 'Current clinic'];
        $appointmentTypes = ['one-hour' => 'One-hour appointment'];
        $days = [];

        if (($clinic === null || isset($clinics[$clinic]))
            && ($appointmentType === null || isset($appointmentTypes[$appointmentType]))) {
            $dates = $date ? [$date] : array_map(
                fn (int $offset): string => today()->addDays($offset)->toDateString(),
                range(0, 6)
            );

            foreach ($dates as $dayDate) {
                $schedule = ClinicSchedule::forDate($dayDate);
                $openingHours = $schedule?->isOpen() ? $schedule->openingHours() : null;
                $slots = $openingHours === null ? [] : $schedule->availabilityOn($dayDate);

                if ($date !== null || $openingHours !== null) {
                    $days[] = [
                        'date' => $dayDate,
                        'openingHours' => $openingHours,
                        'slots' => $slots,
                        'hasAvailableSlot' => collect($slots)->contains('is_available', true),
                    ];
                }
            }
        }

        return view('bookings.create', [
            'date' => $date,
            'clinic' => $clinic,
            'appointmentType' => $appointmentType,
            'clinics' => $clinics,
            'appointmentTypes' => $appointmentTypes,
            'days' => $days,
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
