<?php

namespace App\Http\Controllers;

use App\Models\ClinicSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminScheduleController extends Controller
{
    public function index(): View
    {
        $schedules = ClinicSchedule::query()->orderBy('weekday')->get();

        return view('admin-schedule', ['schedules' => $schedules]);
    }

    public function update(Request $request, ClinicSchedule $clinicSchedule): RedirectResponse
    {
        $validated = $request->validate([
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
            'is_closed' => ['nullable', 'boolean'],
        ]);

        $clinicSchedule->update([
            'opens_at' => (int) str_replace(':', '', $validated['opens_at']),
            'closes_at' => (int) str_replace(':', '', $validated['closes_at']),
            'is_closed' => $request->boolean('is_closed'),
        ]);

        return redirect()->route('admin.schedule')->with('status', 'Clinic schedule updated.');
    }
}
