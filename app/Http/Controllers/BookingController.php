<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
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
}
