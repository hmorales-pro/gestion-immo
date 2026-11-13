{{-- Text of the visit voucher, shared by the signing page and the printable version. --}}
@php
    $property = $booking->property;
    $agent = $property->agent;
@endphp
<h1 class="text-2xl font-bold tracking-tight">Bon de visite</h1>
<dl class="mt-4 grid grid-cols-[auto,1fr] gap-x-4 gap-y-1.5 text-sm">
    <dt class="text-slate-500">Bien visité</dt><dd class="font-medium">{{ $property->title }}</dd>
    <dt class="text-slate-500">Adresse</dt><dd class="font-medium">{{ $property->address }}</dd>
    <dt class="text-slate-500">Date de visite</dt><dd class="font-medium">{{ ucfirst($booking->slot->label()) }}</dd>
    <dt class="text-slate-500">Présenté par</dt><dd class="font-medium">{{ $agent->name }} ({{ $agent->email }})</dd>
    <dt class="text-slate-500">Visiteur</dt><dd class="font-medium">{{ $booking->voucher_signer_name ?? $booking->prospect_name }}</dd>
</dl>
<p class="mt-5 text-sm leading-relaxed text-slate-700">
    Je soussigné(e) <strong>{{ $booking->voucher_signer_name ?? $booking->prospect_name }}</strong> reconnais avoir visité
    le bien désigné ci-dessus, qui m'a été présenté par <strong>{{ $agent->name }}</strong>.
    Je m'engage, si ce bien m'intéresse, à poursuivre toute démarche ou négociation le concernant par l'intermédiaire de
    {{ $agent->name }}, et à ne pas traiter directement avec le propriétaire ni par l'intermédiaire d'un tiers.
</p>
