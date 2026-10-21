@extends('layout')

@section('title', 'Clinic schedule')

@section('content')
    <p>Opening hours decide which one-hour slots patients can search for and book.</p>
    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif
    @if ($schedules->isEmpty())
        <p>No clinic schedule has been set up yet.</p>
    @else
        @foreach ($schedules as $schedule)
            <form method="POST" action="{{ route('admin.schedule.update', $schedule) }}">
                @csrf
                @method('PATCH')
                <h2>{{ $schedule->weekdayName() }}</h2>
                <p>
                    <label for="opens_at_{{ $schedule->id }}">Opens</label>
                    <input id="opens_at_{{ $schedule->id }}" type="time" name="opens_at" step="60" required
                        value="{{ App\Models\ClinicSchedule::formatClock($schedule->opens_at) }}">
                </p>
                <p>
                    <label for="closes_at_{{ $schedule->id }}">Closes</label>
                    <input id="closes_at_{{ $schedule->id }}" type="time" name="closes_at" step="60" required
                        value="{{ App\Models\ClinicSchedule::formatClock($schedule->closes_at) }}">
                </p>
                <p>
                    <label for="is_closed_{{ $schedule->id }}">
                        <input id="is_closed_{{ $schedule->id }}" type="checkbox" name="is_closed" value="1"
                            @checked($schedule->is_closed)>
                        Closed all day
                    </label>
                </p>
                <wa-button type="submit" variant="brand" size="s">Save {{ $schedule->weekdayName() }}</wa-button>
            </form>
        @endforeach
    @endif
    <p><a href="{{ route('admin') }}">Admin</a></p>
@endsection
