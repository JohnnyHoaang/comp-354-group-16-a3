<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    @if ($errors->any())
        <p role="alert">{{ $errors->first() }}</p>
    @endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <p>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
        </p>
        <p>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </p>
        <button type="submit">Log in</button>
    </form>
</body>
</html>
