<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'IceSum'))</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96x96.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @fonts

    @include('layouts.partials.theme-script')

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @stack('styles')
</head>

<body class="bg-slate-50 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
        @include('layouts.partials.navbar')

        <main class="mx-auto max-w-7xl px-4 pb-28 pt-8 sm:px-6 md:pb-8 lg:px-8">
            @yield('content')
        </main>

        @include('layouts.partials.bottom-nav')
    </div>

    @if (session('success') || $errors->any())
        <script>
            window.__flash = @json(['success' => session('success'), 'errors' => $errors->all()]);
        </script>
    @endif

    @stack('scripts')
</body>

</html>
