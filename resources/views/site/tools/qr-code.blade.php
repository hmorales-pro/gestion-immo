<x-site.tool slug="generateur-qr-code">
    <form method="GET" action="{{ route('site.tool.generateur-qr-code') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <label for="url" class="block text-sm font-medium text-slate-700">Adresse de l'annonce ou de la page de réservation</label>
        <div class="mt-1 flex flex-col gap-3 sm:flex-row">
            <input id="url" name="url" type="url" inputmode="url" value="{{ $url ?? request('url') }}" placeholder="https://www.exemple.fr/annonce/t3-centre-ville" maxlength="500"
                @class(['block w-full rounded-lg text-base shadow-sm', 'border-slate-300 focus:border-amber-500 focus:ring-amber-500' => ! $invalid, 'border-red-500 bg-red-50/40' => $invalid])>
            <button class="shrink-0 rounded-xl bg-amber-500 px-5 py-3 font-semibold text-slate-950 hover:bg-amber-400">Créer le QR code</button>
        </div>
        @if ($invalid)
            <p class="mt-2 text-sm text-red-600">Cette adresse ne semble pas valide. Exemple : https://www.exemple.fr/annonce</p>
        @endif
    </form>

    @if ($qr)
        <section class="mt-6 flex flex-col items-center gap-5 rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 sm:flex-row sm:text-left">
            <div class="w-52 shrink-0 [&_svg]:h-auto [&_svg]:w-full">{!! $qr !!}</div>
            <div>
                <p class="font-semibold">Votre QR code est prêt</p>
                <p class="mt-1 break-all text-sm text-slate-600">{{ $url }}</p>
                <p class="mt-2 text-sm text-slate-600">Format vectoriel SVG : il reste net à toutes les tailles, de la carte de visite à l'affiche.</p>
                <a href="{{ route('site.tool.generateur-qr-code.download', ['url' => $url]) }}" class="mt-4 inline-flex rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-800">Télécharger (SVG)</a>
            </div>
        </section>
    @endif

    <div class="prose-guide mt-10">
        <h2>Où utiliser un QR code en immobilier ?</h2>
        <ul>
            <li><strong>En vitrine</strong> : le passant scanne l'affiche et arrive directement sur l'annonce, même agence fermée.</li>
            <li><strong>Sur le panneau « À louer » ou « À vendre »</strong> : il réserve sa visite sans avoir à noter un numéro.</li>
            <li><strong>Sur les flyers et cartes de visite</strong> : un lien vers vos biens disponibles ou votre agenda.</li>
        </ul>
        <h2>Conseils pour un QR code qui fonctionne</h2>
        <ul>
            <li>Imprimez-le à <strong>au moins 3 cm de côté</strong>, davantage s'il doit être scanné de loin (vitrine, panneau).</li>
            <li>Gardez une <strong>marge blanche</strong> autour et un bon contraste (foncé sur fond clair).</li>
            <li>Testez-le avec deux téléphones différents avant d'imprimer en quantité.</li>
            <li>Pointez vers une page qui <strong>ne changera pas</strong> : si l'annonce est supprimée, le QR code mènera nulle part.</li>
        </ul>
        <p>Ce générateur ne trace rien et n'expire jamais : le QR code contient directement votre adresse.</p>
    </div>
</x-site.tool>
