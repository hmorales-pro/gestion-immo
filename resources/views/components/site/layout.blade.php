@props([
    'title',
    'description',
    'canonical' => url()->current(),
    'type' => 'website',
    'schema' => [],
    'noindex' => false,
])
@php
    $fullTitle = str_contains($title, 'ImmoBooking') ? $title : $title.' | ImmoBooking';
    $image = asset('images/site/og-image.png');
    $nav = [
        ['Fonctionnalités', route('home').'#fonctionnalites'],
        ['Comment ça marche', route('home').'#fonctionnement'],
        ['Guides', route('site.guides')],
        ['FAQ', route('home').'#faq'],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large">
    @endif
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:site_name" content="ImmoBooking">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#f59e0b">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="sitemap" type="application/xml" href="{{ route('site.sitemap') }}">
    @vite('resources/css/app.css')
    @foreach ((array) $schema as $block)
        <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="font-sans text-slate-900 antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Aller au contenu</a>

    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="ImmoBooking, accueil">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-sm font-bold text-white">IB</span>
                <span class="font-semibold tracking-tight">ImmoBooking</span>
            </a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex" aria-label="Navigation principale">
                @foreach ($nav as [$label, $href])
                    <a href="{{ $href }}" class="hover:text-slate-900">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-2">
                <a href="{{ config('immobooking.github_url') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 sm:inline-flex" rel="noopener">GitHub</a>
                @if (\App\Support\Demo::enabled())
                    <a href="{{ route('site.demo') }}" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-slate-800">Essayer la démo</a>
                @endif
            </div>
        </div>
    </header>

    <main id="contenu">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-4">
            <div class="md:col-span-2">
                <p class="flex items-center gap-2 font-semibold">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-500 text-xs font-bold text-white">IB</span>
                    ImmoBooking
                </p>
                <p class="mt-3 max-w-sm text-sm text-slate-600">Logiciel gratuit et open source de prise de rendez-vous pour les visites immobilières : agent, visiteur et propriétaire, enfin synchronisés.</p>
            </div>
            <div>
                <p class="text-sm font-semibold">Produit</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('home') }}#fonctionnalites" class="hover:text-slate-900">Fonctionnalités</a></li>
                    @if (\App\Support\Demo::enabled())<li><a href="{{ route('site.demo') }}" class="hover:text-slate-900">Démo en ligne</a></li>@endif
                    <li><a href="{{ config('immobooking.github_url') }}" class="hover:text-slate-900" rel="noopener">Code source (GitHub)</a></li>
                    <li><a href="{{ route('site.guides') }}" class="hover:text-slate-900">Guides</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold">Informations</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('site.legal') }}" class="hover:text-slate-900">Mentions légales</a></li>
                    <li><a href="{{ route('site.privacy') }}" class="hover:text-slate-900">Confidentialité</a></li>
                </ul>
            </div>
        </div>
        <p class="border-t border-slate-200 py-6 text-center text-xs text-slate-500">© {{ date('Y') }} ImmoBooking — logiciel libre sous licence MIT.</p>
    </footer>
</body>
</html>
