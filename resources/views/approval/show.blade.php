<x-layouts.public :title="'Demande de visite — '.$booking->property->title">
    <div class="space-y-6">
        @if (session('success'))
            <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-200" role="alert">{{ session('error') }}</p>
        @endif

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Demande de visite</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $booking->property->title }}</h1>
            <p class="mt-1 text-slate-600">{{ $booking->property->address }}</p>

            <dl class="mt-5 divide-y divide-slate-100 text-sm">
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Date</dt>
                    <dd class="text-right font-medium">{{ ucfirst($booking->slot->start_time->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY')) }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Heure</dt>
                    <dd class="text-right font-medium">{{ $booking->slot->start_time->format('H:i') }} – {{ $booking->slot->end_time->format('H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Visiteur</dt>
                    <dd class="text-right font-medium">{{ $booking->prospect_name }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Situation professionnelle</dt>
                    <dd class="text-right font-medium">{{ $booking->employment_status->getLabel() }}</dd>
                </div>
                @if ($booking->message)
                    <div class="py-2.5">
                        <dt class="text-slate-500">Message</dt>
                        <dd class="mt-1 whitespace-pre-line">{{ $booking->message }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Statut</dt>
                    <dd class="text-right">
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $booking->status === \App\Enums\BookingStatus::Pending,
                            'bg-emerald-100 text-emerald-800' => $booking->status === \App\Enums\BookingStatus::Confirmed,
                            'bg-red-100 text-red-800' => $booking->status === \App\Enums\BookingStatus::Rejected,
                        ])>{{ $booking->status->getLabel() }}</span>
                    </dd>
                </div>
            </dl>
        </section>

        @if ($booking->isPending() && $booking->slot->start_time->isFuture())
            <form method="POST" action="{{ route('owner.approve.decide', $booking->token) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                <button type="submit" name="decision" value="approve"
                    class="rounded-xl bg-emerald-600 px-4 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">
                    Accepter la visite
                </button>
                <button type="submit" name="decision" value="reject"
                    class="rounded-xl bg-white px-4 py-3.5 text-base font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-200 transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">
                    Refuser
                </button>
            </form>
        @elseif ($booking->isPending())
            <p class="text-center text-sm text-slate-600">Ce créneau est passé : la demande ne peut plus être traitée.</p>
        @else
            <p class="text-center text-sm text-slate-600">Cette demande a été traitée{{ $booking->decided_at ? ' le '.$booking->decided_at->format('d/m/Y à H:i') : '' }}.</p>
        @endif
    </div>
</x-layouts.public>
