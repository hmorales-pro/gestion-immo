@props(['slug', 'schemaType' => 'WebApplication'])
@php
    [$title, $description] = \App\Http\Controllers\Site\ToolsController::TOOLS[$slug];
    $url = route('site.tool.'.$slug);
    $schema = [
        [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'inLanguage' => 'fr',
            'isAccessibleForFree' => true,
        ] + ($schemaType === 'WebApplication' ? ['applicationCategory' => 'UtilitiesApplication', 'operatingSystem' => 'Web', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR']] : []),
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Outils gratuits', 'item' => route('site.tools')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $url],
            ],
        ],
    ];
@endphp
<x-site.layout :title="$title" :description="$description" :canonical="$url" :schema="$schema">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <nav aria-label="Fil d'Ariane" class="text-sm text-slate-500 print:hidden">
            <a href="{{ route('home') }}" class="hover:text-slate-900">Accueil</a> /
            <a href="{{ route('site.tools') }}" class="hover:text-slate-900">Outils gratuits</a>
        </nav>
        <h1 class="mt-4 text-4xl font-extrabold tracking-tight">{{ $title }}</h1>
        <p class="mt-3 text-lg text-slate-600">{{ $description }}</p>

        <div class="mt-8">{{ $slot }}</div>

        <aside class="mt-14 rounded-2xl bg-amber-50 p-6 ring-1 ring-amber-200 print:hidden">
            <p class="font-semibold">Vous organisez des visites ?</p>
            <p class="mt-1 text-slate-700">ImmoBooking crée un lien de réservation par bien, fait valider le propriétaire en un clic et envoie les invitations d'agenda. Gratuit et open source.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @if (\App\Support\Demo::enabled())
                    <a href="{{ route('site.demo') }}" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-400">Essayer la démo</a>
                @endif
                <a href="{{ route('site.tools') }}" class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold ring-1 ring-slate-200 hover:bg-slate-50">Tous les outils gratuits</a>
            </div>
        </aside>
    </div>
</x-site.layout>
