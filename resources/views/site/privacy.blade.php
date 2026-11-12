@php $legal = config('immobooking.legal'); @endphp
<x-site.layout title="Politique de confidentialité" description="Données collectées par le site et la démo ImmoBooking, durée de conservation et exercice de vos droits." :canonical="route('site.privacy')">
    <article class="prose-guide mx-auto max-w-3xl px-4 py-14 sm:px-6">
        <h1>Politique de confidentialité</h1>
        <h2>Ce site</h2>
        <p>Le site ne dépose pas de cookie publicitaire ni de traceur de mesure d'audience. Un cookie de session technique est utilisé pour faire fonctionner les formulaires et l'espace de démonstration.</p>
        <h2>La démo</h2>
        <p>Les informations saisies dans la démo (nom, e-mail, téléphone, situation professionnelle) servent uniquement à faire fonctionner la démonstration. Elles ne sont transmises à personne : aucun e-mail ni SMS n'est envoyé. Elles sont supprimées automatiquement lors de la réinitialisation de la démo, au plus tard quelques heures après leur saisie. Merci de ne pas y saisir de données réelles.</p>
        <h2>Les installations d'ImmoBooking</h2>
        <p>Une agence qui installe ImmoBooking sur son propre hébergement est seule responsable des données de ses visiteurs et propriétaires. Elle doit informer les personnes concernées, limiter la durée de conservation et répondre à leurs demandes d'accès, de rectification et de suppression.</p>
        <h2>Vos droits</h2>
        <p>Pour toute question ou demande concernant vos données : {{ $legal['email'] }}. Vous pouvez aussi saisir la <a href="https://www.cnil.fr/fr/plaintes" rel="noopener">CNIL</a>.</p>
    </article>
</x-site.layout>
