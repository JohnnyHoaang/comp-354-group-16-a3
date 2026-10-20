@extends('layout')

@section('title', 'Home')

@section('content')
    <p>Email: {{ auth()->user()->email }}</p>
    <p>Role: {{ auth()->user()->role }}</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <wa-button type="submit" variant="neutral" appearance="outlined">Log out</wa-button>
    </form>
@endsection
