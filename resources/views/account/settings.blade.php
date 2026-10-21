@extends('layout')

@section('title', 'Account settings')

@section('content')
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    <p>Update your contact information below.</p>

    <form method="POST" action="{{ route('account.settings.update') }}">
        @csrf
        @method('PUT')

        <wa-input
            type="email"
            name="email"
            label="Email"
            value="{{ old('email', $user->email) }}"
            autocomplete="email"
            required
        ></wa-input>
        @error('email')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror

        <wa-input
            type="tel"
            name="phone"
            label="Phone number"
            value="{{ old('phone', $user->phone) }}"
            autocomplete="tel"
            help-text="US or Canadian number, such as (514) 555-0123"
            required
        ></wa-input>
        @error('phone')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror

        <wa-button type="submit" variant="brand">Save contact information</wa-button>
    </form>

    <p><a href="{{ route('home') }}">Back to home</a></p>
@endsection
