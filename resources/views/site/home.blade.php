@php
    $faq = [
        ['ImmoBooking est-il vraiment gratuit ?', "Oui. Le logiciel est open source (licence MIT) : vous pouvez l'utiliser, le modifier et l'installer pour votre agence sans abonnement ni limite de biens, d'agents ou de rendez-vous. Vos seuls coûts sont votre hébergement et, si vous les activez, les SMS."],
        ['Le visiteur doit-il créer un compte ?', "Non. Il ouvre le lien du bien sur son téléphone, choisit un créneau et laisse ses coordonnées. Il reçoit ensuite un lien privé pour ajouter la visite à son agenda, la déplacer ou l'annuler."],
        ['Comment le propriétaire valide-t-il une visite ?', "Il reçoit un e-mail (et un SMS si vous avez son numéro) avec un lien personnel. Une page récapitule la demande — date, heure, visiteur, situation professionnelle — avec deux boutons : accepter ou refuser. Aucun compte ni mot de passe."],
        ['Peut-on se passer de la validation du propriétaire ?', "Oui, chaque bien a son mode : validation par le propriétaire, validation par l'agent depuis l'administration, ou confirmation immédiate. Vous choisissez au cas par cas."],
        ['Est-ce compatible avec Google Agenda, Apple Calendrier et Outlook ?', "Oui. Chaque confirmation, déplacement ou annulation est envoyé avec une invitation d'agenda (.ics) qui met l'événement à jour automatiquement. Chaque agent dispose aussi d'un lien d'abonnement pour voir toutes ses visites dans son agenda habituel."],
        ['Comment éviter les doubles réservations ?', "Un créneau demandé est bloqué jusqu'à la décision, deux créneaux d'un même bien ne peuvent pas se chevaucher, et un même visiteur ne peut pas réserver deux visites au même moment."],
        ['Peut-on l\'installer sur un hébergement mutualisé ?', "Oui. ImmoBooking fonctionne avec PHP 8.2 à 8.5 et MySQL ou SQLite, sans serveur dédié, sans tâche planifiée obligatoire et sans file d'attente à surveiller. Les e-mails peuvent partir par Brevo ou par le SMTP de votre hébergeur."],
        ['Où sont stockées les données des visiteurs ?', "Sur votre propre hébergement. Vous restez maître des données personnelles collectées et pouvez choisir un hébergeur en France ou en Europe."],
    ];

    $features = [
        ['Un lien par bien', 'Partagez-le sur vos annonces, par SMS ou sur votre site. Le visiteur voit uniquement les créneaux encore libres.', 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244'],
        ['Validation propriétaire', "Le propriétaire accepte ou refuse en un clic, depuis l'e-mail ou le SMS reçu. Sans compte. Le locataire en place est prévenu.", 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Créneaux en série', 'Saisissez « samedi 10 h – 12 h », ImmoBooking découpe en visites de 30 minutes, sur plusieurs semaines si besoin.', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['Synchro agendas', "Invitations Google Agenda, Apple Calendrier et Outlook, mises à jour à chaque changement. Flux d'abonnement pour chaque agent.", 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99'],
        ['E-mails et SMS automatiques', "Invitation à réserver, accusé de réception, confirmation, déplacement, annulation, demande d'informations : tout part tout seul.", 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
        ['Le visiteur gère seul', 'Changer de créneau, annuler, compléter son dossier : depuis son lien privé, sans vous appeler.', 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
        ['Zéro double réservation', 'Créneaux bloqués pendant la validation, chevauchements interdits, numéros et e-mails vérifiés.', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
        ['Affiche et QR code', 'Une affiche A4 prête à imprimer pour la vitrine ou le panneau : le passant scanne et réserve.', 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z'],
        ['Bon de visite signé', 'Le visiteur signe sur votre téléphone à la fin de la visite. Pré-rempli, horodaté, archivé, imprimable.', 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125'],
        ['Avis et suivi propriétaire', "Après la visite, le visiteur donne son avis en 30 secondes. Le propriétaire suit visites et retours sur sa page personnelle.", 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
        ['Liste d\'attente', 'Tout est complet ? Le visiteur laisse son e-mail et il est prévenu dès qu\'un créneau se libère.', 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
        ['Multi-agents', "Chaque agent ne voit que ses biens et ses visites. Idéal pour une agence ou un réseau de mandataires.", 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
    ];

    $schema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'ImmoBooking',
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'Prise de rendez-vous immobilier',
            'operatingSystem' => 'Web',
            'description' => 'Logiciel gratuit et open source de prise de rendez-vous pour les visites immobilières, avec validation par le propriétaire et synchronisation des agendas.',
            'url' => route('home'),
            'image' => asset('images/site/og-image.png'),
            'inLanguage' => 'fr',
            'isAccessibleForFree' => true,
            'license' => 'https://opensource.org/licenses/MIT',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($qa) => [
                '@type' => 'Question',
                'name' => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
            ], $faq),
        ],
    ];
@endphp

<x-site.layout
    title="Prise de rendez-vous de visites immobilières gratuite | ImmoBooking"
    description="Un lien de réservation par bien : le visiteur choisit son créneau, le propriétaire valide en un clic, chacun reçoit l'invitation dans son agenda. Logiciel gratuit pour agences immobilières."
    :canonical="route('home')"
    :schema="$schema"
>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-amber-50 via-white to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-12 sm:px-6 lg:grid-cols-2 lg:pb-24 lg:pt-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 shadow-sm ring-1 ring-amber-200">
                    100 % gratuit · Open source · Hébergé chez vous
                </p>
                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl lg:text-[3.4rem] lg:leading-[1.05]">
                    La prise de rendez-vous de <span class="text-amber-600">visites immobilières</span>, sans les allers-retours
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-slate-600">
                    Un lien par bien. Le visiteur choisit son créneau, le propriétaire valide en un clic,
                    et l'agent, le visiteur et le propriétaire reçoivent l'invitation dans leur agenda.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @if (\App\Support\Demo::enabled())
                        <a href="{{ route('site.demo') }}" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 py-3.5 text-base font-semibold text-slate-950 shadow-sm hover:bg-amber-400">Essayer la démo gratuite</a>
                    @endif
                    <a href="{{ config('immobooking.github_url') }}" rel="noopener" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3.5 text-base font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Télécharger sur GitHub</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">Sans inscription. Sans carte bancaire. Sans limite de biens.</p>
            </div>
            <div class="relative mx-auto w-full max-w-lg">
                <img src="{{ asset('images/site/admin-demandes.webp') }}" width="1400" height="900" alt="Tableau des demandes de visite dans l'administration ImmoBooking"
                    class="w-full rounded-2xl shadow-2xl ring-1 ring-slate-200" fetchpriority="high">
                <img src="{{ asset('images/site/mobile-creneaux.webp') }}" width="390" height="844" alt="Page de réservation d'une visite sur mobile : choix du créneau"
                    class="absolute -bottom-10 -left-4 w-36 rounded-[1.6rem] border-[6px] border-slate-900 shadow-2xl sm:-left-10 sm:w-44" fetchpriority="high">
            </div>
        </div>
    </section>

    {{-- Problem --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-bold tracking-tight">Une visite, trois agendas : le casse-tête quotidien des agences</h2>
            <p class="mt-4 text-lg text-slate-600">Les outils de rendez-vous classiques sont pensés pour deux personnes. Une visite immobilière en implique trois : l'agent, le visiteur et le propriétaire, qui doit souvent donner son accord.</p>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Des dizaines d\'appels et de SMS', 'Pour caler une seule visite : proposer des dates au visiteur, vérifier avec le propriétaire, reconfirmer, relancer.'],
                ['Un propriétaire injoignable', 'La visite reste en suspens, le visiteur s\'impatiente et part voir un autre bien.'],
                ['Des créneaux en double', 'Deux visiteurs au même horaire, une visite oubliée dans un agenda, un rendez-vous annulé qui reste bloqué.'],
            ] as [$title, $text])
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h3 class="font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section id="fonctionnement" class="scroll-mt-20 bg-slate-950 py-20 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="max-w-2xl text-3xl font-bold tracking-tight">Comment ça marche : 3 étapes, zéro coup de fil</h2>
            <div class="mt-12 grid gap-10 lg:grid-cols-3">
                @foreach ([
                    ['1', 'Vous ouvrez des créneaux', 'Créez le bien, indiquez vos plages de visite (« samedi 10 h – 12 h ») et copiez son lien de réservation dans votre annonce.', 'admin-creneaux.webp', 920, 430, 'Génération de créneaux de visite dans ImmoBooking'],
                    ['2', 'Le visiteur réserve', 'Depuis son téléphone, il choisit un créneau libre et laisse ses coordonnées et sa situation professionnelle.', 'mobile-formulaire.webp', 390, 844, 'Formulaire de demande de visite sur mobile'],
                    ['3', 'Le propriétaire valide', 'Il reçoit un lien par e-mail ou SMS et accepte en un clic. Tout le monde reçoit la confirmation et l\'invitation d\'agenda.', 'mobile-proprietaire.webp', 390, 844, 'Page de validation de la visite par le propriétaire'],
                ] as [$n, $title, $text, $img, $w, $h, $alt])
                    <div>
                        <div class="flex h-56 items-start justify-center overflow-hidden rounded-2xl bg-slate-800 px-6 pt-6">
                            <img src="{{ asset('images/site/'.$img) }}" width="{{ $w }}" height="{{ $h }}" alt="{{ $alt }}" loading="lazy"
                                class="{{ $w > 800 ? 'mt-6 w-full rounded-lg' : 'w-40 rounded-t-2xl' }} shadow-xl">
                        </div>
                        <p class="mt-6 inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-500 font-bold text-slate-950">{{ $n }}</p>
                        <h3 class="mt-3 text-xl font-semibold">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-slate-300">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="fonctionnalites" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
        <h2 class="max-w-2xl text-3xl font-bold tracking-tight">Tout ce qu'il faut pour organiser vos visites, rien de superflu</h2>
        <div class="mt-12 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features as [$title, $text, $icon])
                <div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Comparison --}}
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <h2 class="text-3xl font-bold tracking-tight">Pourquoi pas un simple outil de rendez-vous ?</h2>
            <p class="mt-4 text-lg text-slate-600">Les outils génériques sont pensés pour un rendez-vous entre deux personnes. ImmoBooking est conçu pour la visite immobilière.</p>
            <div class="mt-8 overflow-x-auto rounded-2xl bg-white ring-1 ring-slate-200">
                <table class="w-full min-w-[34rem] text-left text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold"></th>
                            <th scope="col" class="px-4 py-3 font-semibold">Téléphone et SMS</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Outil de RDV générique</th>
                            <th scope="col" class="px-4 py-3 font-semibold text-amber-700">ImmoBooking</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ([
                            ['Un lien de réservation par bien', '—', 'Souvent un lien par agent', 'Oui'],
                            ['Accord du propriétaire', 'Par téléphone', 'Non prévu', 'En un clic, sans compte'],
                            ['Situation professionnelle du visiteur', 'À demander', 'Champ à configurer', 'Intégré'],
                            ['Invitation d\'agenda aux 3 parties', 'Non', 'Organisateur et invité', 'Agent, visiteur et propriétaire'],
                            ['Le visiteur déplace ou annule seul', 'Non', 'Oui', 'Oui, avec nouvelle validation'],
                            ['Données hébergées chez vous', 'Oui', 'Non', 'Oui'],
                            ['Prix', 'Gratuit, mais chronophage', 'Souvent payant par utilisateur', 'Gratuit'],
                        ] as [$label, $phone, $generic, $us])
                            <tr>
                                <th scope="row" class="px-4 py-3 font-medium text-slate-900">{{ $label }}</th>
                                <td class="px-4 py-3 text-slate-600">{{ $phone }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $generic }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $us }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Who / hosting --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-2">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Pour qui ?</h2>
                <ul class="mt-6 space-y-5">
                    @foreach ([
                        ['Agences de location et de transaction', 'Chaque agent gère ses biens et ses visites, sans voir ceux des collègues.'],
                        ['Mandataires immobiliers indépendants', 'Un outil professionnel sans abonnement, prêt en quelques minutes.'],
                        ['Gestion locative', 'Enchaînez les visites de relocation sans mobiliser votre standard.'],
                    ] as [$title, $text])
                        <li class="flex gap-3">
                            <svg class="mt-1 h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                            <div><h3 class="font-semibold">{{ $title }}</h3><p class="mt-1 text-slate-600">{{ $text }}</p></div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-2xl bg-amber-50 p-8 ring-1 ring-amber-200">
                <h2 class="text-2xl font-bold tracking-tight">Installez-le sur un simple hébergement mutualisé</h2>
                <p class="mt-4 text-slate-700">Pas de serveur dédié ni de compétences système : ImmoBooking tourne sur l'hébergement web que vous avez probablement déjà.</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li>• PHP 8.2 à 8.5 et MySQL (ou SQLite)</li>
                    <li>• Aucune tâche planifiée ni file d'attente obligatoire</li>
                    <li>• E-mails via Brevo ou le SMTP de l'hébergeur</li>
                    <li>• Vos données restent chez vous, en France si vous le souhaitez</li>
                </ul>
                <a href="{{ config('immobooking.github_url') }}#hébergement-mutualisé" rel="noopener" class="mt-6 inline-flex font-semibold text-amber-800 underline underline-offset-4">Lire le guide d'installation →</a>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="scroll-mt-20 bg-slate-50 py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-3xl font-bold tracking-tight">Questions fréquentes</h2>
            <div class="mt-8 divide-y divide-slate-200 rounded-2xl bg-white ring-1 ring-slate-200">
                @foreach ($faq as [$q, $a])
                    <details class="group px-6 py-5" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                            <h3>{{ $q }}</h3>
                            <span class="text-xl text-slate-400 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 leading-relaxed text-slate-600">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Free tools --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-3xl font-bold tracking-tight">Outils gratuits, sans inscription</h2>
                <a href="{{ route('site.tools') }}" class="hidden shrink-0 font-semibold text-amber-700 sm:inline">Tous les outils →</a>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (\App\Http\Controllers\Site\ToolsController::TOOLS as $slug => [$toolTitle, $toolDescription])
                    <a href="{{ route('site.tool.'.$slug) }}" class="group rounded-2xl bg-white p-6 ring-1 ring-slate-200 transition hover:shadow-md hover:ring-amber-300">
                        <h3 class="font-semibold group-hover:text-amber-700">{{ $toolTitle }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $toolDescription }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Guides --}}
    @if ($guides->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-3xl font-bold tracking-tight">Guides pour mieux organiser vos visites</h2>
                <a href="{{ route('site.guides') }}" class="hidden shrink-0 font-semibold text-amber-700 sm:inline">Tous les guides →</a>
            </div>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ($guides as $guide)
                    <a href="{{ route('site.guide', $guide['slug']) }}" class="group rounded-2xl p-6 ring-1 ring-slate-200 transition hover:shadow-md hover:ring-amber-300">
                        <p class="text-xs font-medium text-slate-500">{{ $guide['minutes'] }} min de lecture</p>
                        <h3 class="mt-2 font-semibold group-hover:text-amber-700">{{ $guide['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $guide['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="px-4 pb-20 sm:px-6">
        <div class="mx-auto max-w-4xl rounded-3xl bg-slate-950 px-6 py-14 text-center text-white sm:px-12">
            <h2 class="text-3xl font-bold tracking-tight">Prêt à arrêter les allers-retours ?</h2>
            <p class="mx-auto mt-4 max-w-xl text-slate-300">Testez la démo en deux minutes, côté visiteur, propriétaire et agent. Aucune inscription.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @if (\App\Support\Demo::enabled())
                    <a href="{{ route('site.demo') }}" class="rounded-xl bg-amber-500 px-6 py-3.5 font-semibold text-slate-950 hover:bg-amber-400">Essayer la démo</a>
                @endif
                <a href="{{ config('immobooking.github_url') }}" rel="noopener" class="rounded-xl px-6 py-3.5 font-semibold text-white ring-1 ring-white/30 hover:bg-white/10">Voir le code source</a>
            </div>
        </div>
    </section>
</x-site.layout>
