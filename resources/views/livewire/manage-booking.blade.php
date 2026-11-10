@php
    $input = fn (bool $error) => $error
        ? 'mt-1 block w-full rounded-lg text-base shadow-sm border-red-500 bg-red-50/40 focus:border-red-500 focus:ring-red-500'
        : 'mt-1 block w-full rounded-lg text-base shadow-sm border-slate-300 focus:border-amber-500 focus:ring-amber-500';
    $status = $this->booking->status;
@endphp

<div class="space-y-6">
    @if ($flash)
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" role="status">{{ $flash }}</p>
    @endif

    {{-- Summary --}}
    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Mon rendez-vous</p>
            <span @class([
                'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                'bg-amber-100 text-amber-800' => $status === \App\Enums\BookingStatus::Pending,
                'bg-emerald-100 text-emerald-800' => $status === \App\Enums\BookingStatus::Confirmed,
                'bg-red-100 text-red-800' => $status === \App\Enums\BookingStatus::Rejected,
                'bg-slate-100 text-slate-700' => $status === \App\Enums\BookingStatus::Cancelled,
            ])>{{ $status->getLabel() }}</span>
        </div>
        <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $this->booking->property->title }}</h1>
        <p class="mt-1 text-slate-600">{{ $this->booking->property->address }}</p>
        <p class="mt-4 text-lg font-semibold">{{ ucfirst($this->booking->slot->label()) }}</p>
        <p class="mt-1 text-sm text-slate-600">
            Votre contact : {{ $this->booking->property->agent->name }} ·
            <a href="mailto:{{ $this->booking->property->agent->email }}" class="font-medium text-amber-700 underline underline-offset-2">{{ $this->booking->property->agent->email }}</a>
        </p>

        @if ($status === \App\Enums\BookingStatus::Pending)
            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">Votre demande attend une validation. Vous recevrez un e-mail de confirmation.</p>
        @endif

        @if ($googleUrl)
            <div class="mt-5">
                <p class="text-sm font-medium text-slate-700">Ajouter à mon agenda</p>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    <a href="{{ $googleUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-slate-100 px-2 py-2.5 text-center text-sm font-semibold text-slate-800 hover:bg-amber-100">Google</a>
                    <a href="{{ $this->booking->icsUrl() }}" class="rounded-lg bg-slate-100 px-2 py-2.5 text-center text-sm font-semibold text-slate-800 hover:bg-amber-100">Apple / .ics</a>
                    <a href="{{ $outlookUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-slate-100 px-2 py-2.5 text-center text-sm font-semibold text-slate-800 hover:bg-amber-100">Outlook</a>
                </div>
            </div>
        @endif
    </section>

    @if ($canChange)
        @if ($this->booking->information_requested_at && $panel !== 'info')
            <button type="button" wire:click="open('info')" class="w-full rounded-xl bg-amber-50 px-4 py-3 text-left text-sm font-medium text-amber-900 ring-1 ring-amber-200">
                L'agence vous demande des informations complémentaires → Compléter
            </button>
        @endif

        {{-- Actions --}}
        @if ($panel === '')
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <button type="button" wire:click="open('reschedule')" class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Changer de créneau</button>
                <button type="button" wire:click="open('info')" class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Mes informations</button>
                <button type="button" wire:click="open('cancel')" class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-red-200 hover:bg-red-50">Annuler</button>
            </div>
        @endif

        @if ($panel === 'reschedule')
            <form wire:submit="reschedule" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold">Choisir un nouveau créneau</h2>
                @if ($this->booking->property->booking_mode !== \App\Enums\BookingMode::Instant)
                    <p class="mt-1 text-sm text-slate-600">Le nouveau créneau devra être validé.</p>
                @endif
                @error('newSlotId') <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-700" role="alert">{{ $message }}</p> @enderror

                @forelse ($this->slotsByDay as $day => $slots)
                    <div class="mt-5" wire:key="day-{{ $day }}">
                        <h3 class="text-sm font-semibold text-slate-700">{{ ucfirst(\Illuminate\Support\Carbon::parse($day)->locale(app()->getLocale())->isoFormat('dddd D MMMM')) }}</h3>
                        <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4">
                            @foreach ($slots as $slot)
                                <button type="button" wire:key="slot-{{ $slot->id }}" wire:click="$set('newSlotId', {{ $slot->id }})"
                                    aria-pressed="{{ $newSlotId === $slot->id ? 'true' : 'false' }}"
                                    @class([
                                        'rounded-lg px-2 py-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500',
                                        'bg-slate-900 text-white shadow' => $newSlotId === $slot->id,
                                        'bg-slate-100 text-slate-800 hover:bg-amber-100' => $newSlotId !== $slot->id,
                                    ])>{{ $slot->start_time->format('H:i') }}</button>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="mt-4 rounded-lg bg-slate-50 px-3 py-4 text-center text-sm text-slate-600">Aucun autre créneau disponible pour le moment.</p>
                @endforelse

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="flex-1 rounded-xl bg-amber-500 px-4 py-3 font-semibold text-slate-950 hover:bg-amber-400">Confirmer le changement</button>
                    <button type="button" wire:click="open('')" class="rounded-xl px-4 py-3 font-semibold text-slate-600 ring-1 ring-slate-200">Retour</button>
                </div>
            </form>
        @endif

        @if ($panel === 'info')
            <form wire:submit="saveInformation" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200" novalidate>
                <h2 class="text-lg font-semibold">Mes informations</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Nom et prénom</label>
                        <input id="name" type="text" wire:model="name" autocomplete="name" class="{{ $input($errors->has('name')) }}">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700">Téléphone</label>
                        <input id="phone" type="tel" inputmode="tel" wire:model="phone" autocomplete="tel" class="{{ $input($errors->has('phone')) }}">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="employmentStatus" class="block text-sm font-medium text-slate-700">Situation professionnelle</label>
                        <select id="employmentStatus" wire:model="employmentStatus" class="{{ $input($errors->has('employmentStatus')) }}">
                            @foreach ($employmentStatuses as $option)
                                <option value="{{ $option->value }}">{{ $option->getLabel() }}</option>
                            @endforeach
                        </select>
                        @error('employmentStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700">Précisions pour l'agence</label>
                        <textarea id="message" rows="4" wire:model="message" maxlength="1000" class="{{ $input($errors->has('message')) }}"></textarea>
                        @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="submit" class="flex-1 rounded-xl bg-amber-500 px-4 py-3 font-semibold text-slate-950 hover:bg-amber-400">Envoyer à l'agence</button>
                    <button type="button" wire:click="open('')" class="rounded-xl px-4 py-3 font-semibold text-slate-600 ring-1 ring-slate-200">Retour</button>
                </div>
            </form>
        @endif

        @if ($panel === 'cancel')
            <form wire:submit="cancel" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-200">
                <h2 class="text-lg font-semibold">Annuler le rendez-vous ?</h2>
                <p class="mt-1 text-sm text-slate-600">L'agence et le propriétaire seront prévenus, et l'événement sera retiré de votre agenda.</p>
                <label for="cancelReason" class="mt-4 block text-sm font-medium text-slate-700">Motif <span class="font-normal text-slate-500">(facultatif)</span></label>
                <textarea id="cancelReason" rows="3" wire:model="cancelReason" maxlength="500" class="{{ $input($errors->has('cancelReason')) }}"></textarea>
                @error('cancelReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="mt-6 flex gap-3">
                    <button type="submit" class="flex-1 rounded-xl bg-red-600 px-4 py-3 font-semibold text-white hover:bg-red-500">Annuler le rendez-vous</button>
                    <button type="button" wire:click="open('')" class="rounded-xl px-4 py-3 font-semibold text-slate-600 ring-1 ring-slate-200">Retour</button>
                </div>
            </form>
        @endif
    @elseif (! $this->booking->isActive())
        <a href="{{ $this->booking->property->publicUrl() }}" class="block rounded-xl bg-amber-500 px-4 py-3.5 text-center font-semibold text-slate-950 hover:bg-amber-400">Choisir un nouveau créneau</a>
    @endif
</div>
