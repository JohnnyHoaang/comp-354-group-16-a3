@extends('layout')

@section('title', 'Home')

@section('content')
    @if ($reminders->isNotEmpty())
        <wa-callout variant="warning">
            <strong>Booking reminder</strong>
            <p>You have bookings in less than 2 days:</p>
            <ul>
                @foreach ($reminders as $booking)
                    <li>
                        {{ $booking->doctor_full_name }} — {{ $booking->date->format('Y-m-d') }}
                        at {{ sprintf('%02d:%02d', intdiv($booking->start_time, 100), $booking->start_time % 100) }}
                        ({{ $booking->confirmed ? 'Confirmed' : 'Unconfirmed' }})
                    </li>
                @endforeach
            </ul>
        </wa-callout>
    @endif
    <p>Email: {{ auth()->user()->email }}</p>
    <p>Phone: {{ auth()->user()->phone ?? 'Not provided' }}</p>
    <p>Role: {{ auth()->user()->role }}</p>
    <p><a href="{{ route('account.settings.edit') }}">Account settings</a></p>
    <p><a href="{{ route('bookings.index') }}">My bookings</a></p>
    @if (auth()->user()->role === 'admin')
        <p><a href="{{ route('admin') }}">Admin</a></p>
    @endif
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <wa-button type="submit" variant="neutral" appearance="outlined">Log out</wa-button>
    </form>
@endsection
