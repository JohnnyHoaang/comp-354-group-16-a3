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
        <p>Availability for {{ $date }} — 00:00–24:00</p>
        <p>Available all day. Select an hour below or enter a time.</p>
        {{-- Intentional playground bug: existing bookings do not affect availability. --}}
        <div style="overflow-x: auto;" tabindex="0" role="region" aria-label="Daily availability chart">
            <table>
                <caption>Hourly availability</caption>
                <thead>
                    <tr>
                        @for ($hour = 0; $hour < 24; $hour++)
                            <th scope="col">{{ sprintf('%02d:00', $hour) }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @for ($hour = 0; $hour < 24; $hour++)
                            <td>
                                <wa-button type="button" variant="success" size="s"
                                    data-time="{{ sprintf('%02d:00', $hour) }}"
                                    aria-label="Select {{ sprintf('%02d:00', $hour) }} — available">Available</wa-button>
                            </td>
                        @endfor
                    </tr>
                </tbody>
            </table>
        </div>
        <p>
            <label for="time">Time (24-hour)</label>
            <input id="time" type="time" name="start_time" step="60" required>
        </p>
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
    <p><a href="{{ route('bookings.index') }}">My bookings</a></p>
@endsection
