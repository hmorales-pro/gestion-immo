<div class="space-y-6">
    {{-- Property --}}
    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Demande de visite</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $property->title }}</h1>
        <p class="mt-1 flex items-start gap-1.5 text-slate-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18s6-5.686 6-10A6 6 0 0 0 4 8c0 4.314 6 10 6 10Zm0-8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" clip-rule="evenodd"/></svg>
            <span>{{ $property->address }}</span>
        </p>
        @if ($property->description)
            <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $property->description }}</p>
        @endif
        <p class="mt-3 text-sm text-slate-500">Durée de la visite : {{ $property->slot_duration }} min</p>
    </section>

    @if ($this->booking)
        {{-- Success --}}
        <section class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200" role="status">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full {{ $confirmed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
            </div>
            <h2 class="mt-4 text-xl font-bold">{{ $confirmed ? 'Visite confirmée' : 'Demande envoyée' }}</h2>
            <p class="mt-2 text-slate-600">{{ ucfirst($this->booking->slot->label()) }}</p>
            <p class="mt-3 text-sm text-slate-600">
                @if ($confirmed)
                    Un e-mail de confirmation vous a été envoyé.
                @else
                    Votre demande doit être validée. Vous recevrez un e-mail dès qu'elle sera acceptée.
                @endif
            </p>
            @if ($isDemo)
                <div class="mt-5 rounded-xl bg-amber-50 p-4 text-left text-sm text-amber-950 ring-1 ring-amber-200">
                    <p class="font-semibold">Démo : aucun e-mail n'a été envoyé.</p>
                    <p class="mt-1">Voici les liens que chacun recevrait :</p>
                    <ul class="mt-2 space-y-1.5">
                        @if ($this->booking->isPending() && $property->booking_mode === \App\Enums\BookingMode::OwnerApproval)
                            <li>→ <a class="font-semibold underline underline-offset-2" href="{{ $this->booking->approvalUrl() }}">Le propriétaire : accepter ou refuser la visite</a></li>
                        @elseif ($this->booking->isPending())
                            <li>→ <a class="font-semibold underline underline-offset-2" href="{{ route('filament.admin.auth.login') }}">L'agent : valider depuis l'administration</a></li>
                        @endif
                        <li>→ <a class="font-semibold underline underline-offset-2" href="{{ $this->booking->manageUrl() }}">Le visiteur : gérer son rendez-vous</a></li>
                    </ul>
                </div>
            @endif
            <a href="{{ $this->booking->manageUrl() }}" class="mt-5 inline-flex rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                {{ $confirmed ? 'Ajouter à mon agenda / gérer' : 'Gérer mon rendez-vous' }}
            </a>
        </section>
    @else
        <form wire:submit="submit" class="space-y-6" novalidate>
            {{-- Slots --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold">1. Choisissez un créneau</h2>

                @error('form.slotId')
                    <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
                @enderror

                @forelse ($this->slotsByDay as $day => $slots)
                    <div class="mt-5" wire:key="day-{{ $day }}">
                        <h3 class="text-sm font-semibold text-slate-700">
                            {{ ucfirst(\Illuminate\Support\Carbon::parse($day)->locale(app()->getLocale())->isoFormat('dddd D MMMM')) }}
                        </h3>
                        <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4">
                            @foreach ($slots as $slot)
                                @php($selected = $form->slotId === $slot->id)
                                <button
                                    type="button"
                                    wire:key="slot-{{ $slot->id }}"
                                    wire:click="selectSlot({{ $slot->id }})"
                                    aria-pressed="{{ $selected ? 'true' : 'false' }}"
                                    @class([
                                        'rounded-lg px-2 py-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2',
                                        'bg-slate-900 text-white shadow' => $selected,
                                        'bg-slate-100 text-slate-800 hover:bg-amber-100' => ! $selected,
                                    ])
                                >{{ $slot->start_time->format('H:i') }}</button>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="mt-4 rounded-lg bg-slate-50 px-3 py-4 text-center text-sm text-slate-600">
                        Aucun créneau disponible pour le moment. Revenez bientôt ou contactez l'agence.
                    </p>
                @endforelse
            </section>

            @if ($this->slotsByDay->isNotEmpty())
                {{-- Prospect --}}
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold">2. Vos coordonnées</h2>

                    @if ($this->selectedSlot)
                        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            Créneau choisi : <strong>{{ $this->selectedSlot->label() }}</strong>
                        </p>
                    @endif

                    <div class="mt-4 space-y-4">
                        @foreach ([
                            ['name', 'Nom et prénom', 'text', 'name'],
                            ['email', 'E-mail', 'email', 'email'],
                            ['phone', 'Téléphone', 'tel', 'tel'],
                        ] as [$field, $label, $type, $autocomplete])
                            <div>
                                <label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                                <input id="{{ $field }}" type="{{ $type }}" wire:model.blur="form.{{ $field }}" autocomplete="{{ $autocomplete }}" required
                                    @if ($type === 'tel') inputmode="tel" placeholder="06 12 34 56 78" @endif
                                    @class([
                                        'mt-1 block w-full rounded-lg text-base shadow-sm',
                                        'border-slate-300 focus:border-amber-500 focus:ring-amber-500' => ! $errors->has('form.'.$field),
                                        'border-red-500 bg-red-50/40 focus:border-red-500 focus:ring-red-500' => $errors->has('form.'.$field),
                                    ])
                                    @error('form.'.$field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                                @error('form.'.$field) <p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach

                        <div>
                            <label for="employmentStatus" class="block text-sm font-medium text-slate-700">Situation professionnelle</label>
                            <select id="employmentStatus" wire:model="form.employmentStatus" required
                                @class([
                                    'mt-1 block w-full rounded-lg text-base shadow-sm',
                                    'border-slate-300 focus:border-amber-500 focus:ring-amber-500' => ! $errors->has('form.employmentStatus'),
                                    'border-red-500 bg-red-50/40 focus:border-red-500 focus:ring-red-500' => $errors->has('form.employmentStatus'),
                                ])
                                @error('form.employmentStatus') aria-invalid="true" @enderror>
                                <option value="">Sélectionnez…</option>
                                @foreach ($employmentStatuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->getLabel() }}</option>
                                @endforeach
                            </select>
                            @error('form.employmentStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-slate-700">Message <span class="font-normal text-slate-500">(facultatif)</span></label>
                            <textarea id="message" rows="3" wire:model="form.message" maxlength="1000"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                            @error('form.message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="hidden" aria-hidden="true">
                            <label for="website">Ne pas remplir</label>
                            <input id="website" type="text" wire:model="form.website" tabindex="-1" autocomplete="off">
                        </div>
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                        class="mt-6 flex w-full items-center justify-center rounded-xl bg-amber-500 px-4 py-3.5 text-base font-semibold text-slate-950 shadow-sm transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 focus-visible:ring-offset-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="submit">Demander la visite</span>
                        <span wire:loading wire:target="submit">Envoi…</span>
                    </button>
                    <p class="mt-3 text-center text-xs text-slate-500">
                        Vos coordonnées sont transmises uniquement à l'agence et au propriétaire du bien.
                    </p>
                </section>
            @endif
        </form>
    @endif
</div>
