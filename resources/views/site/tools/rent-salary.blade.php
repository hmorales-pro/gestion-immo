@php $fmt = fn ($n) => number_format($n, 0, ',', ' ').' €'; @endphp
<x-site.tool slug="salaire-pour-louer">
    <div class="grid gap-6 md:grid-cols-2">
        <form method="GET" action="{{ route('site.tool.salaire-pour-louer') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Quel salaire pour ce loyer ?</h2>
            <label for="loyer" class="mt-4 block text-sm font-medium text-slate-700">Loyer charges comprises (€ / mois)</label>
            <input id="loyer" name="loyer" type="number" min="1" step="1" inputmode="decimal" value="{{ $rent }}" placeholder="900" class="mt-1 block w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500">
            <button class="mt-4 w-full rounded-xl bg-amber-500 px-4 py-3 font-semibold text-slate-950 hover:bg-amber-400">Calculer</button>
            @if ($requiredIncome)
                <p class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200" role="status">
                    Pour un loyer de <strong>{{ $fmt($rent) }}</strong>, les bailleurs attendent en général des revenus nets d'au moins
                    <strong class="text-lg">{{ $fmt($requiredIncome) }}</strong> par mois.
                </p>
            @endif
        </form>

        <form method="GET" action="{{ route('site.tool.salaire-pour-louer') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Quel loyer pour mes revenus ?</h2>
            <label for="revenus" class="mt-4 block text-sm font-medium text-slate-700">Revenus nets mensuels du foyer (€)</label>
            <input id="revenus" name="revenus" type="number" min="1" step="1" inputmode="decimal" value="{{ $income }}" placeholder="2 700" class="mt-1 block w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500">
            <button class="mt-4 w-full rounded-xl bg-amber-500 px-4 py-3 font-semibold text-slate-950 hover:bg-amber-400">Calculer</button>
            @if ($maxRent)
                <p class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200" role="status">
                    Avec <strong>{{ $fmt($income) }}</strong> de revenus nets, visez un loyer charges comprises d'au plus
                    <strong class="text-lg">{{ $fmt($maxRent) }}</strong> par mois.
                </p>
            @endif
        </form>
    </div>

    <div class="prose-guide mt-10">
        <h2>La règle des 3 fois le loyer</h2>
        <p>La plupart des propriétaires et des agences demandent que les <strong>revenus nets mensuels</strong> du candidat représentent au moins <strong>trois fois le loyer charges comprises</strong>. Autrement dit, le loyer ne doit pas dépasser environ un tiers des revenus : c'est ce qu'on appelle un taux d'effort de 33 %.</p>
        <p>Ce n'est pas une obligation légale mais une règle de prudence : chaque bailleur reste libre d'apprécier le dossier dans son ensemble.</p>
        <h2>Questions fréquentes</h2>
        <h3>Les revenus d'un couple ou de colocataires s'additionnent-ils ?</h3>
        <p>Oui, en général le bailleur regarde les revenus cumulés des personnes qui signent le bail.</p>
        <h3>Et si mes revenus sont inférieurs à 3 fois le loyer ?</h3>
        <p>Un garant ou une garantie locative peut rassurer le bailleur. Un dossier complet et clair, avec un contrat de travail stable ou des revenus réguliers, compte aussi beaucoup.</p>
        <h3>Faut-il compter le loyer avec ou sans charges ?</h3>
        <p>Le calcul se fait le plus souvent sur le loyer <strong>charges comprises</strong>, c'est-à-dire ce que vous paierez réellement chaque mois.</p>
    </div>
</x-site.tool>
