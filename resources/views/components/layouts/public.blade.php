<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-2xl items-center gap-2 px-4 py-3">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-sm font-bold text-white">IB</span>
            <span class="font-semibold tracking-tight">{{ config('app.name') }}</span>
        </div>
    </header>

    <main class="mx-auto max-w-2xl px-4 py-6 sm:py-10">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-2xl px-4 pb-8 text-center text-xs text-slate-500">
        Propulsé par {{ config('app.name') }}
    </footer>
</body>
</html>
