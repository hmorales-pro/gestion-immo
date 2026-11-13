<x-site.tool slug="modele-bon-de-visite" schema-type="WebPage">
    <button onclick="window.print()" class="mb-6 rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-800 print:hidden">Imprimer le modèle</button>

    <article class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200 print:p-0 print:shadow-none print:ring-0">
        <h2 class="text-2xl font-bold">Bon de visite</h2>
        <div class="mt-6 space-y-5 text-sm">
            @foreach (['Agence / intermédiaire', 'Bien visité (adresse)', 'Date et heure de la visite', 'Nom et prénom du visiteur', 'Téléphone et e-mail du visiteur'] as $field)
                <div>
                    <p class="text-slate-500">{{ $field }}</p>
                    <div class="mt-4 border-b border-slate-400"></div>
                </div>
            @endforeach
        </div>
        <p class="mt-8 text-sm leading-relaxed text-slate-700">
            Je soussigné(e) ______________________________ reconnais avoir visité le bien désigné ci-dessus,
            qui m'a été présenté par l'intermédiaire désigné ci-dessus. Je m'engage, si ce bien m'intéresse, à poursuivre
            toute démarche ou négociation le concernant par son intermédiaire, et à ne pas traiter directement avec le
            propriétaire ni par l'intermédiaire d'un tiers.
        </p>
        <div class="mt-10 grid grid-cols-2 gap-8 text-sm">
            <div><p class="text-slate-500">Fait à ______________ le ___ / ___ / ______</p></div>
            <div><p class="text-slate-500">Signature du visiteur</p><div class="mt-16 border-b border-slate-400"></div></div>
        </div>
        <p class="mt-8 text-xs text-slate-400">Modèle indicatif, à adapter à votre mandat et à votre activité.</p>
    </article>

    <div class="prose-guide mt-10 print:hidden">
        <h2>À quoi sert un bon de visite ?</h2>
        <p>Le bon de visite garde une trace écrite de la visite : qui a visité, quel bien, quand, et par l'intermédiaire de qui. En transaction, il permet à l'agence de prouver qu'elle a présenté le bien à l'acquéreur si celui-ci tentait ensuite de traiter directement avec le vendeur.</p>
        <h2>Le faire signer sur téléphone</h2>
        <p>Avec ImmoBooking, le visiteur signe directement sur l'écran du téléphone de l'agent à la fin de la visite. Le bon est rempli automatiquement (bien, date, visiteur), horodaté et archivé avec la visite, et peut être imprimé ou enregistré en PDF.</p>
    </div>
</x-site.tool>
