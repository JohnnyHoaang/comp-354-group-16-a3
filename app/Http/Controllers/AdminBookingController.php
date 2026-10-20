<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = null;
        $bookings = collect();
        $email = '';

        if ($request->has('email')) {
            $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
            $email = $validated['email'];
            $user = User::where('email', $email)->first();

            if ($user) {
                $bookings = $user->bookings()->orderBy('date')->orderBy('start_time')->get();
            }
        }

        return view('admin-bookings', compact('user', 'bookings', 'email'));
    }

    public function destroy(User $user, int $booking): RedirectResponse
    {
        $user->bookings()->findOrFail($booking)->delete();

        return redirect()->route('admin.bookings', ['email' => $user->email])
            ->with('status', 'Booking deleted.');
    }
}
