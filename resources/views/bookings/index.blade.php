@extends('layout')

@section('title', 'My bookings')

@section('content')
    @if ($bookings->isEmpty())
        <p>You have no active bookings.</p>
    @else
        <ul>
            @foreach ($bookings as $booking)
                <li>
                    {{ $booking->doctor_full_name }} — {{ $booking->date->format('Y-m-d') }}
                    at {{ sprintf('%02d:%02d', intdiv($booking->start_time, 100), $booking->start_time % 100) }}
                    ({{ $booking->confirmed ? 'Confirmed' : 'Unconfirmed' }})
                </li>
            @endforeach
        </ul>
    @endif
    <p><a href="{{ route('home') }}">Home</a></p>
@endsection
