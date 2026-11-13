<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Bon de visite — {{ $booking->property->title }}</title>
    @vite('resources/css/app.css')
    <style>@page { size: A4; margin: 18mm; } @media print { .no-print { display: none; } }</style>
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    <main class="mx-auto max-w-2xl px-6 py-10">
        @include('voucher._content', ['booking' => $booking])
        <div class="mt-8 grid grid-cols-2 gap-6 text-sm">
            <div>
                <p class="text-slate-500">Fait le</p>
                <p class="font-medium">{{ $booking->voucher_signed_at?->format('d/m/Y à H:i') ?? '____________________' }}</p>
            </div>
            <div>
                <p class="text-slate-500">Signature du visiteur</p>
                @if ($booking->voucher_signature)
                    <img src="{{ $booking->voucher_signature }}" alt="Signature" class="mt-1 h-24">
                @else
                    <div class="mt-1 h-24 border-b border-slate-400"></div>
                @endif
            </div>
        </div>
        <p class="mt-10 text-xs text-slate-400">Document généré par ImmoBooking. Modèle indicatif, à adapter à votre mandat.</p>
        <button onclick="window.print()" class="no-print mt-8 rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white">Imprimer / enregistrer en PDF</button>
    </main>
</body>
</html>
