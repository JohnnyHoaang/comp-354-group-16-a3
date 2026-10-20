@extends('layout')

@section('title', 'Login')

@section('content')
    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <wa-input type="email" name="email" label="Email" value="{{ old('email') }}" autocomplete="username" required></wa-input>
        <wa-input type="password" name="password" label="Password" autocomplete="current-password" required></wa-input>
        <wa-button type="submit" variant="brand">Log in</wa-button>
    </form>
@endsection
