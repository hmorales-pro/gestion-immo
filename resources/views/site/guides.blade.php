<x-site.layout
    title="Guides : organiser les visites immobilières"
    description="Méthodes et conseils pratiques pour organiser les visites de biens à louer ou à vendre : créneaux, accord du propriétaire, agendas, relances."
    :canonical="route('site.guides')"
>
    <section class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
        <h1 class="text-4xl font-extrabold tracking-tight">Guides pour organiser vos visites immobilières</h1>
        <p class="mt-4 text-lg text-slate-600">Des méthodes concrètes pour planifier plus de visites en passant moins de temps au téléphone.</p>
        <div class="mt-10 space-y-6">
            @foreach ($guides as $guide)
                <article class="rounded-2xl p-6 ring-1 ring-slate-200 transition hover:ring-amber-300">
                    <p class="text-xs text-slate-500"><time datetime="{{ $guide['date']->toDateString() }}">{{ $guide['date']->locale('fr')->isoFormat('D MMMM YYYY') }}</time> · {{ $guide['minutes'] }} min de lecture</p>
                    <h2 class="mt-2 text-xl font-semibold"><a href="{{ route('site.guide', $guide['slug']) }}" class="hover:text-amber-700">{{ $guide['title'] }}</a></h2>
                    <p class="mt-2 text-slate-600">{{ $guide['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>
</x-site.layout>
