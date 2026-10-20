@extends('layout')

@section('title', 'Admin')

@section('content')
    <p>Welcome to the MediLink admin area. Schedule management will be available here.</p>
    <p><a href="{{ route('home') }}">Home</a></p>
@endsection
