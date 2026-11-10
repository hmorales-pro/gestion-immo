<x-site.layout
    title="Démo gratuite : testez la prise de rendez-vous de visites"
    description="Essayez ImmoBooking sans inscription : réservez une visite comme un visiteur, validez-la comme un propriétaire, puis gérez-la depuis l'espace agent."
    :canonical="route('site.demo')"
>
    <section class="bg-gradient-to-b from-amber-50 to-white">
        <div class="mx-auto max-w-4xl px-4 pb-10 pt-14 text-center sm:px-6">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">Testez ImmoBooking en 2 minutes</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">Mettez-vous tour à tour à la place du visiteur, du propriétaire et de l'agent. Aucune inscription, et <strong>aucun e-mail ni SMS n'est envoyé</strong> : les liens s'affichent directement à l'écran.</p>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 pb-16 sm:px-6">
        <ol class="grid gap-6 md:grid-cols-3">
            <li class="rounded-2xl p-6 ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-amber-700">1 · Vous êtes le visiteur</p>
                <p class="mt-2 text-slate-600">Ouvrez l'un des biens ci-dessous, choisissez un créneau et remplissez le formulaire (vous pouvez inventer les coordonnées).</p>
            </li>
            <li class="rounded-2xl p-6 ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-amber-700">2 · Vous êtes le propriétaire</p>
                <p class="mt-2 text-slate-600">Après la réservation, cliquez sur le lien « propriétaire » qui s'affiche pour accepter ou refuser la visite.</p>
            </li>
            <li class="rounded-2xl p-6 ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-amber-700">3 · Vous êtes l'agent</p>
                <p class="mt-2 text-slate-600">Connectez-vous à l'administration (identifiants pré-remplis) : biens, créneaux, demandes, déplacement, synchro agenda…</p>
            </li>
        </ol>

        <h2 class="mt-14 text-2xl font-bold tracking-tight">Les biens de démonstration</h2>
        <div class="mt-6 grid gap-6 md:grid-cols-3">
            @foreach ($properties as $property)
                <article class="flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <span class="self-start rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $property->booking_mode->getLabel() }}</span>
                    <h3 class="mt-3 font-semibold">{{ $property->title }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $property->address }}</p>
                    <p class="mt-3 flex-1 text-sm text-slate-500">{{ $property->booking_mode->getDescription() }}</p>
                    <p class="mt-3 text-sm text-slate-500">{{ $property->available_slots_count }} créneaux disponibles</p>
                    <a href="{{ $property->publicUrl() }}" class="mt-5 rounded-xl bg-amber-500 px-4 py-3 text-center font-semibold text-slate-950 hover:bg-amber-400">Réserver une visite</a>
                </article>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-6 rounded-2xl bg-slate-950 p-8 text-white md:flex-row">
            <div>
                <h2 class="text-xl font-bold">Espace agent</h2>
                <p class="mt-1 text-slate-300">Identifiant <code class="rounded bg-white/10 px-1.5 py-0.5">{{ config('immobooking.demo.email') }}</code> · mot de passe <code class="rounded bg-white/10 px-1.5 py-0.5">{{ config('immobooking.demo.password') }}</code></p>
                <p class="mt-1 text-sm text-slate-400">Les données de démonstration sont réinitialisées automatiquement toutes les quelques heures.</p>
            </div>
            <a href="{{ route('filament.admin.auth.login') }}" class="shrink-0 rounded-xl bg-white px-6 py-3 font-semibold text-slate-950 hover:bg-slate-100">Ouvrir l'espace agent</a>
        </div>
    </section>
</x-site.layout>
