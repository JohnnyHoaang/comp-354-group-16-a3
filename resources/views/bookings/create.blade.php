@extends('layout')

@section('title', 'New booking')

@section('content')
    <h2>1. Choose a date</h2>
    <form method="GET" action="{{ route('bookings.create') }}">
        <p>
            <label for="date">Date</label>
            <input id="date" type="date" name="date" value="{{ old('date', $date) }}" required>
        </p>
        <wa-button type="submit" variant="brand">Show availability</wa-button>
    </form>
    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif

    @if ($date)
        <h2>2. Choose a time</h2>
        @if ($openingHours === null)
            <p>The clinic is closed on {{ $date }}.</p>
        @else
            <p>Availability for {{ $date }} — {{ $openingHours }}</p>
            @if ($hasAvailableSlot)
                <p>Select an available hour below or enter a time.</p>
            @else
                <p>No times are still available on {{ $date }}.</p>
            @endif
            <div style="overflow-x: auto;" tabindex="0" role="region" aria-label="Daily availability chart">
                <table>
                    <caption>Hourly availability</caption>
                    <thead>
                        <tr>
                            @foreach ($slots as $slot)
                                <th scope="col">{{ $slot['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach ($slots as $slot)
                                <td>
                                    @if ($slot['is_available'])
                                        <wa-button type="button" variant="success" size="s"
                                            data-time="{{ $slot['label'] }}"
                                            aria-label="Select {{ $slot['label'] }} — available">Available</wa-button>
                                    @else
                                        <span aria-label="{{ $slot['label'] }} — already booked">Booked</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            @if ($hasAvailableSlot)
                <form method="POST" action="{{ route('bookings.store') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <p>Doctor: {{ $doctorFullName }} (mock doctor)</p>
                    <p>Duration: 1 hour</p>
                    <p>
                        <label for="time">Start time (24-hour)</label>
                        <input id="time" type="time" name="start_time" value="{{ old('start_time') }}" step="60" required>
                    </p>
                    <wa-button type="submit" variant="brand">Confirm one-hour booking</wa-button>
                </form>
                <script>
                    document.querySelectorAll('[data-time]').forEach(button => {
                        button.addEventListener('click', () => {
                            const input = document.getElementById('time');
                            input.value = button.dataset.time;
                            input.focus();
                        });
                    });
                </script>
            @endif
        @endif
    @endif
    <p><a href="{{ route('bookings.index') }}">My bookings</a></p>
@endsection
