<x-site.layout
    title="Outils gratuits pour l'immobilier"
    description="Calculateur de salaire pour louer, générateur de QR code, checklist de visite et modèle de bon de visite : des outils gratuits, sans inscription."
    :canonical="route('site.tools')"
>
    <section class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
        <h1 class="text-4xl font-extrabold tracking-tight">Outils gratuits pour l'immobilier</h1>
        <p class="mt-4 text-lg text-slate-600">Sans inscription, sans publicité. Pour les agents, les propriétaires et les candidats à la location.</p>
        <div class="mt-10 grid gap-6 sm:grid-cols-2">
            @foreach ($tools as $slug => [$title, $description])
                <a href="{{ route('site.tool.'.$slug) }}" class="group rounded-2xl p-6 ring-1 ring-slate-200 transition hover:shadow-md hover:ring-amber-300">
                    <h2 class="text-lg font-semibold group-hover:text-amber-700">{{ $title }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $description }}</p>
                </a>
            @endforeach
        </div>
    </section>
</x-site.layout>
