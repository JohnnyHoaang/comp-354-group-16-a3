@extends('layout')

@section('title', 'Booking playground')

@section('content')
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif
    <form method="POST" action="{{ route('bookings.playground.add') }}">
        @csrf
        <p><wa-button type="submit" variant="brand">Add two mock bookings</wa-button></p>
    </form>
    <form method="POST" action="{{ route('bookings.playground.clear') }}">
        @csrf
        <p><wa-button type="submit" variant="danger">Clear all my bookings</wa-button></p>
    </form>
    <p><a href="{{ route('bookings.index') }}">My bookings</a></p>
@endsection
