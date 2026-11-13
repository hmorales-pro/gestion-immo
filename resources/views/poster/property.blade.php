<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Affiche — {{ $property->title }}</title>
    @vite('resources/css/app.css')
    <style>
        @page { size: A4; margin: 0; }
        @media print { .no-print { display: none; } body { background: #fff; } }
        .sheet { width: 210mm; min-height: 297mm; }
        .qr svg { width: 100%; height: auto; }
    </style>
</head>
<body class="bg-slate-200 font-sans text-slate-900 antialiased">
    <div class="no-print mx-auto flex max-w-[210mm] items-center justify-between gap-3 px-4 py-4">
        <a href="{{ url()->previous() }}" class="text-sm font-medium text-slate-700">← Retour</a>
        <div class="flex gap-2">
            <a href="{{ route('property.qr', $property) }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold ring-1 ring-slate-300">Télécharger le QR code (SVG)</a>
            <button onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Imprimer l'affiche</button>
        </div>
    </div>

    <main class="sheet mx-auto flex flex-col items-center bg-white px-[18mm] py-[20mm] text-center shadow-xl print:shadow-none">
        <p class="text-xl font-semibold uppercase tracking-[0.2em] text-amber-600">Visite sur rendez-vous</p>
        <h1 class="mt-6 text-5xl font-extrabold leading-tight tracking-tight">{{ $property->title }}</h1>
        <p class="mt-4 text-2xl text-slate-600">{{ $property->address }}</p>

        <div class="qr mt-12 w-[95mm] rounded-3xl p-4 ring-4 ring-slate-900">{!! $qr !!}</div>

        <p class="mt-10 text-4xl font-bold">Scannez pour réserver votre visite</p>
        <p class="mt-3 text-xl text-slate-600">Choisissez votre créneau en 1 minute, 24 h/24.</p>
        <p class="mt-8 break-all text-base text-slate-500">{{ $property->publicUrl() }}</p>

        <p class="mt-auto pt-10 text-lg text-slate-500">{{ $property->agent->name }} · {{ $property->agent->email }}</p>
    </main>
</body>
</html>
