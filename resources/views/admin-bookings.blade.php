@extends('layout')

@section('title', 'Manage bookings')

@section('content')
    <form method="GET" action="{{ route('admin.bookings') }}">
        <wa-input type="email" name="email" label="User email" value="{{ old('email', $email) }}" required></wa-input>
        <wa-button type="submit" variant="brand">Find bookings</wa-button>
    </form>
    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif
    @if ($user)
        <h2>{{ $user->email }}</h2>
        @if ($bookings->isEmpty())
            <p>This user has no bookings.</p>
        @else
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Doctor</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td>{{ $booking->doctor_full_name }}</td>
                                <td>{{ $booking->date->format('Y-m-d') }}</td>
                                <td>{{ sprintf('%02d:%02d', intdiv($booking->start_time, 100), $booking->start_time % 100) }}</td>
                                <td>{{ $booking->confirmed ? 'Confirmed' : 'Unconfirmed' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.bookings.destroy', ['user' => $user, 'booking' => $booking->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <wa-button type="submit" variant="danger" size="s">Delete</wa-button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @elseif ($email !== '')
        <p>No user found with that email.</p>
    @endif
    <p><a href="{{ route('admin') }}">Admin</a></p>
@endsection
