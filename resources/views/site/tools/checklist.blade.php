@php
    $sections = [
        'Avant la visite' => [
            'Préparer la liste de vos questions (ci-dessous) et vos critères indispensables',
            'Prévoir de quoi noter, un mètre et votre téléphone chargé (photos, lampe)',
            'Venir de jour si possible, pour juger la lumière',
        ],
        'Immeuble et quartier' => [
            'État de la façade, de la toiture visible et des parties communes',
            'Présence d\'un ascenseur, d\'un local vélos, d\'une cave, d\'un parking',
            'Bruit de la rue et du voisinage, à différentes heures si possible',
            'Commerces, transports, écoles et stationnement à proximité',
        ],
        'Pièces de vie' => [
            'Luminosité et orientation de chaque pièce',
            'Traces d\'humidité : taches au plafond, moisissures, odeur de renfermé',
            'État des sols, des murs et des plafonds (fissures, dégâts des eaux)',
            'Ouverture et fermeture des fenêtres, simple ou double vitrage',
            'Nombre et emplacement des prises électriques',
            'Rangements et placards',
        ],
        'Cuisine' => [
            'Équipements fournis et leur état (plaques, four, hotte, réfrigérateur)',
            'Pression et évacuation de l\'eau à l\'évier',
            'Place pour le lave-linge ou le lave-vaisselle et les arrivées d\'eau',
        ],
        'Salle de bains et WC' => [
            'Pression et température de l\'eau chaude',
            'Joints de la douche ou de la baignoire, traces de moisissures',
            'Présence d\'une aération ou d\'une fenêtre',
            'Chasse d\'eau et évacuations',
        ],
        'Chauffage, électricité, isolation' => [
            'Type de chauffage (individuel ou collectif, électrique, gaz…) et de production d\'eau chaude',
            'État du tableau électrique',
            'Diagnostic de performance énergétique (DPE) et montant estimé des factures',
            'Isolation des fenêtres et des murs : sensation de courant d\'air ?',
        ],
        'Questions à poser' => [
            'Montant du loyer ou du prix, et des charges : que comprennent-elles ?',
            'Date de disponibilité',
            'Pourquoi l\'occupant actuel part-il ?',
            'Travaux prévus dans le logement ou dans l\'immeuble',
            'Montant de la taxe foncière ou des charges de copropriété (achat)',
            'Documents à fournir pour le dossier et délai de réponse',
        ],
    ];
    $schema = [[
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => 'Visiter un appartement : la checklist',
        'step' => collect($sections)->keys()->map(fn ($name, $i) => ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $name, 'text' => implode(' ; ', $sections[$name])])->all(),
    ]];
@endphp
<x-site.tool slug="checklist-visite-appartement" schema-type="WebPage">
    @foreach ($schema as $block)
        <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach

    <button onclick="window.print()" class="mb-6 rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-800 print:hidden">Imprimer la checklist</button>

    <div class="space-y-6">
        @foreach ($sections as $section => $items)
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 print:break-inside-avoid print:shadow-none">
                <h2 class="text-lg font-semibold">{{ $section }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($items as $item)
                        <li>
                            <label class="flex cursor-pointer items-start gap-3 text-slate-700">
                                <input type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                <span>{{ $item }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 print:break-inside-avoid">
            <h2 class="text-lg font-semibold">Mes notes</h2>
            <div class="mt-3 h-40 rounded-lg border border-dashed border-slate-300"></div>
        </section>
    </div>
</x-site.tool>
