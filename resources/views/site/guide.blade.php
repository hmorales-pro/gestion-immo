@php
    $url = route('site.guide', $guide['slug']);
    $schema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $guide['title'],
            'description' => $guide['description'],
            'datePublished' => $guide['date']->toIso8601String(),
            'dateModified' => $guide['updated']->toIso8601String(),
            'inLanguage' => 'fr',
            'mainEntityOfPage' => $url,
            'image' => asset('images/site/og-image.png'),
            'author' => ['@type' => 'Organization', 'name' => 'ImmoBooking', 'url' => route('home')],
            'publisher' => ['@type' => 'Organization', 'name' => 'ImmoBooking', 'url' => route('home')],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Guides', 'item' => route('site.guides')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $guide['title'], 'item' => $url],
            ],
        ],
    ];
@endphp
<x-site.layout :title="$guide['title']" :description="$guide['description']" :canonical="$url" type="article" :schema="$schema">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <nav aria-label="Fil d'Ariane" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-900">Accueil</a> /
            <a href="{{ route('site.guides') }}" class="hover:text-slate-900">Guides</a>
        </nav>
        <h1 class="mt-4 text-4xl font-extrabold tracking-tight">{{ $guide['title'] }}</h1>
        <p class="mt-3 text-sm text-slate-500">
            Mis à jour le <time datetime="{{ $guide['updated']->toDateString() }}">{{ $guide['updated']->locale('fr')->isoFormat('D MMMM YYYY') }}</time> · {{ $guide['minutes'] }} min de lecture
        </p>

        <div class="prose-guide mt-8">{!! $guide['html'] !!}</div>

        <aside class="mt-12 rounded-2xl bg-amber-50 p-6 ring-1 ring-amber-200">
            <p class="font-semibold">Automatisez tout ça gratuitement</p>
            <p class="mt-1 text-slate-700">ImmoBooking crée un lien de réservation par bien, fait valider le propriétaire en un clic et envoie les invitations d'agenda. Gratuit et open source.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @if (\App\Support\Demo::enabled())
                    <a href="{{ route('site.demo') }}" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-400">Essayer la démo</a>
                @endif
                <a href="{{ route('home') }}" class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold ring-1 ring-slate-200 hover:bg-slate-50">Découvrir ImmoBooking</a>
            </div>
        </aside>

        @if ($others->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-bold">À lire aussi</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($others as $other)
                        <li><a href="{{ route('site.guide', $other['slug']) }}" class="font-medium text-amber-700 hover:underline">{{ $other['title'] }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
</x-site.layout>
