<?php

namespace App\Http\Controllers;
use App\Services\BookingReservationService;
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
