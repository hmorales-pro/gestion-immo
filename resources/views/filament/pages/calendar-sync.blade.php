<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Votre lien d'abonnement</x-slot>
        <x-slot name="description">
            Toutes vos visites (confirmées, en attente et annulées) apparaissent dans votre agenda et se mettent à jour automatiquement.
            Ce lien est personnel : ne le partagez pas.
        </x-slot>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <x-filament::input.wrapper class="flex-1">
                <x-filament::input type="text" readonly :value="$feedUrl" x-on:focus="$el.select()" />
            </x-filament::input.wrapper>
            <x-filament::button icon="heroicon-o-clipboard" color="gray" x-on:click="{{ $copyJs }}">Copier</x-filament::button>
        </div>
    </x-filament::section>

    <div class="grid gap-6 md:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Google Agenda</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-400">Ouvre Google Agenda avec l'abonnement pré-rempli. Google rafraîchit les abonnements toutes les quelques heures.</p>
            <x-filament::button tag="a" :href="$googleUrl" target="_blank" class="mt-4" icon="heroicon-o-plus">Ajouter à Google Agenda</x-filament::button>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Apple Calendrier</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-400">Sur Mac, iPhone ou iPad : ouvre l'app Calendrier et propose l'abonnement.</p>
            <x-filament::button tag="a" :href="$webcalUrl" class="mt-4" icon="heroicon-o-plus">S'abonner dans Calendrier</x-filament::button>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Outlook</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-400">Agenda → Ajouter un calendrier → « S'abonner à partir du web », puis collez le lien ci-dessus.</p>
            <x-filament::button tag="a" href="https://outlook.live.com/calendar/0/addfromweb" target="_blank" color="gray" class="mt-4" icon="heroicon-o-arrow-top-right-on-square">Ouvrir Outlook</x-filament::button>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Et pour vos visiteurs et propriétaires ?</x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Chaque confirmation, déplacement ou annulation est envoyé avec une invitation d'agenda (.ics) :
            Gmail, Apple Mail et Outlook proposent de l'ajouter en un clic, et l'événement est mis à jour ou supprimé automatiquement ensuite.
        </p>
    </x-filament::section>
</x-filament-panels::page>
