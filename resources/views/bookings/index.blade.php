@extends('layout')

@section('title', 'My bookings')

@section('content')
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif
    <p><a href="{{ route('bookings.create') }}">New booking</a></p>
    <a href="/book/playground">Booking playground (for testing)</a>

    @if ($bookings->isEmpty())
        <p>You have no active bookings.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th scope="col">Doctor</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time</th>
                    <th scope="col">Duration</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->doctor_full_name }}</td>
                        <td>{{ $booking->date->format('Y-m-d') }}</td>
                        <td>{{ sprintf('%02d:%02d', intdiv($booking->start_time, 100), $booking->start_time % 100) }}</td>
                        <td>{{ $booking->duration_minutes }} minutes</td>
                        <td>{{ $booking->confirmed ? 'Confirmed' : 'Unconfirmed' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <p><a href="{{ route('home') }}">Home</a></p>
@endsection
