@extends('layout')

@section('title', 'New booking')

@section('content')
    <h2>1. Choose a date or filter appointments</h2>
    <p>The application currently offers one clinic schedule and one-hour appointments.</p>
    <form method="GET" action="{{ route('bookings.create') }}">
        <p>
            <label for="date">Date</label>
            <input id="date" type="date" name="date" value="{{ $date }}">
        </p>
        <p>
            <label for="clinic">Clinic</label>
            <select id="clinic" name="clinic">
                <option value="">All clinics</option>
                @if ($clinic !== null && ! isset($clinics[$clinic]))
                    <option value="{{ $clinic }}" selected>Unavailable clinic</option>
                @endif
                @foreach ($clinics as $value => $label)
                    <option value="{{ $value }}" @selected($clinic === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>
            <label for="appointment_type">Appointment type</label>
            <select id="appointment_type" name="appointment_type">
                <option value="">All appointment types</option>
                @if ($appointmentType !== null && ! isset($appointmentTypes[$appointmentType]))
                    <option value="{{ $appointmentType }}" selected>Unavailable appointment type</option>
                @endif
                @foreach ($appointmentTypes as $value => $label)
                    <option value="{{ $value }}" @selected($appointmentType === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <wa-button type="submit" variant="brand">Show availability</wa-button>
        <a href="{{ route('bookings.create') }}">Clear Filters</a>
    </form>
    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif

    @if ($date)
        <h2>2. Choose a time</h2>
    @else
        <h2>Availability for the next seven days</h2>
        <p>Choose a date to search beyond this range.</p>
    @endif
    @forelse ($days as $day)
        @if ($day['openingHours'] === null)
            <p>The clinic is closed on {{ $day['date'] }}.</p>
        @else
            <p>Availability for {{ $day['date'] }} — {{ $day['openingHours'] }}</p>
            @if ($day['hasAvailableSlot'])
                <p>Select an available hour below or enter a time.</p>
            @else
                <p>No times are still available on {{ $day['date'] }}.</p>
            @endif
            <div style="overflow-x: auto;" tabindex="0" role="region" aria-label="Daily availability chart">
                <table>
                    <caption>Hourly availability</caption>
                    <thead>
                        <tr>
                            @foreach ($day['slots'] as $slot)
                                <th scope="col" style="white-space: nowrap;">{{ $slot['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach ($day['slots'] as $slot)
                                <td>
                                    @if ($slot['is_available'])
                                        <wa-button type="button" variant="success" size="s"
                                            data-time="{{ $slot['label'] }}"
                                            data-target="time-{{ $day['date'] }}"
                                            aria-label="Select {{ $slot['label'] }} — available">Available</wa-button>
                                    @else
                                        <wa-button type="button" variant="neutral" size="s" disabled
                                            aria-label="{{ $slot['label'] }} — already booked">Booked</wa-button>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            @if ($day['hasAvailableSlot'])
                <form method="POST" action="{{ route('bookings.store') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $day['date'] }}">
                    <p>Doctor: {{ $doctorFullName }} (mock doctor)</p>
                    <p>Duration: 1 hour</p>
                    <p>
                        <label for="time-{{ $day['date'] }}">Start time (24-hour)</label>
                        <input id="time-{{ $day['date'] }}" type="time" name="start_time" value="{{ old('start_time') }}" step="60" required>
                    </p>
                    <wa-button type="submit" variant="brand">Confirm one-hour booking</wa-button>
                </form>
            @endif
        @endif
    @empty
        <p>No appointments match these filters.</p>
    @endforelse
    <script>
        document.querySelectorAll('[data-time]').forEach(button => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                input.value = button.dataset.time;
                input.focus();
            });
        });
    </script>
    <p><a href="{{ route('bookings.index') }}">My bookings</a></p>
@endsection
