<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingPlaygroundController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->user()->bookings()->createMany([
            [
                'doctor_full_name' => 'Dr. Alex Smith',
                'start_time' => 930,
                'date' => today()->addDay(),
                'confirmed' => false,
            ],
            [
                'doctor_full_name' => 'Dr. Jordan Lee',
                'start_time' => 1430,
                'date' => today()->addDays(2),
                'confirmed' => true,
            ],
        ]);

        return redirect()->route('bookings.playground')->with('status', 'Added two mock bookings.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->bookings()->delete();

        return redirect()->route('bookings.playground')->with('status', 'Cleared your bookings.');
    }
}
