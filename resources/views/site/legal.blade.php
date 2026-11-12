@php $legal = config('immobooking.legal'); @endphp
<x-site.layout title="Mentions légales" description="Mentions légales du site ImmoBooking : éditeur, directeur de la publication et hébergeur." :canonical="route('site.legal')">
    <article class="prose-guide mx-auto max-w-3xl px-4 py-14 sm:px-6">
        <h1>Mentions légales</h1>
        <h2>Éditeur du site</h2>
        <p>{{ $legal['editor'] }}<br>{{ $legal['address'] }}<br>Contact : {{ $legal['email'] }}@if ($legal['siret'])<br>SIRET : {{ $legal['siret'] }}@endif</p>
        <h2>Directeur de la publication</h2>
        <p>{{ $legal['publisher'] }}</p>
        <h2>Hébergement</h2>
        <p>{{ $legal['host'] }}</p>
        <h2>Propriété intellectuelle</h2>
        <p>Le logiciel ImmoBooking est distribué sous licence libre MIT. Son code source est disponible sur <a href="{{ config('immobooking.github_url') }}" rel="noopener">GitHub</a>. Les textes du site restent la propriété de leur éditeur.</p>
    </article>
</x-site.layout>
