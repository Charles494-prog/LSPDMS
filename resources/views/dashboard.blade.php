<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard - {{ config('app.name', 'LSPDMS') }}</title>
    </head>
    <body>
        <h1>Welcome, {{ $user['gmail'] ?? 'User' }}</h1>
        <p>Role: {{ $user['tungkulin'] ?? 'N/A' }}</p>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </body>
</html>