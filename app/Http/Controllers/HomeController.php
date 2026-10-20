<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();
        $cutoff = $now->copy()->addHours(48);
        $reminders = $request->user()->bookings()
            ->whereDate('date', '>=', $now->toDateString())
            ->whereDate('date', '<=', $cutoff->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(function (Booking $booking) use ($now, $cutoff): bool {
                $startsAt = $booking->date->copy()->setTime(
                    intdiv($booking->start_time, 100),
                    $booking->start_time % 100,
                );

                return $startsAt->greaterThan($now) && $startsAt->lessThan($cutoff);
            });

        return view('home', ['reminders' => $reminders]);
    }
}
