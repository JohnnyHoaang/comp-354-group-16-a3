@extends('layout')

@section('title', 'Admin')

@section('content')
    <p>Welcome to the MediLink admin area.</p>
    <p><a href="{{ route('admin.bookings') }}">Manage user bookings</a></p>
    <p><a href="{{ route('admin.schedule') }}">Clinic schedule</a></p>
    <p><a href="{{ route('home') }}">Home</a></p>
@endsection
