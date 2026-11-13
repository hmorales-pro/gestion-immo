@php
    // Visitors are shown by first name and initial only.
    $who = fn ($b) => \Illuminate\Support\Str::of($b->prospect_name)->explode(' ')->pipe(fn ($p) => $p->first().($p->count() > 1 ? ' '.mb_substr($p->last(), 0, 1).'.' : ''));
@endphp
<x-layouts.public :title="'Suivi des visites — '.$property->title">
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Suivi des visites</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $property->title }}</h1>
            <p class="mt-1 text-slate-600">{{ $property->address }}</p>
            <p class="mt-3 text-sm text-slate-600">Votre contact : {{ $property->agent->name }} · <a href="mailto:{{ $property->agent->email }}" class="font-medium text-amber-700 underline underline-offset-2">{{ $property->agent->email }}</a></p>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['Visites faites', $stats['visits']],
                ['À venir', $stats['upcoming']],
                ['Avis reçus', $stats['feedback']],
                ['Intéressés', $stats['interested']],
            ] as [$label, $value])
                <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-2xl font-bold">{{ $value }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $label }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Visites à venir</h2>
            @forelse ($upcoming as $booking)
                <div class="flex items-center justify-between gap-3 border-t border-slate-100 py-3 first:mt-3 first:border-0 first:pt-0">
                    <div>
                        <p class="font-medium">{{ ucfirst($booking->slot->label()) }}</p>
                        <p class="text-sm text-slate-500">{{ $who($booking) }} · {{ $booking->employment_status->getLabel() }}</p>
                    </div>
                    <span @class([
                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                        'bg-amber-100 text-amber-800' => $booking->isPending(),
                        'bg-emerald-100 text-emerald-800' => $booking->isConfirmed(),
                    ])>{{ $booking->status->getLabel() }}</span>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Aucune visite prévue pour le moment.</p>
            @endforelse
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Avis des visiteurs</h2>
            @forelse ($feedback as $booking)
                <article class="border-t border-slate-100 py-4 first:mt-3 first:border-0 first:pt-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ $who($booking) }}</p>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-emerald-100 text-emerald-800' => $booking->feedback_interest === \App\Enums\FeedbackInterest::Yes,
                            'bg-amber-100 text-amber-800' => $booking->feedback_interest === \App\Enums\FeedbackInterest::Maybe,
                            'bg-slate-100 text-slate-700' => $booking->feedback_interest === \App\Enums\FeedbackInterest::No,
                        ])>{{ $booking->feedback_interest->getLabel() }}</span>
                        @if ($booking->feedback_price)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $booking->feedback_price->getLabel() }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Visite du {{ $booking->slot->start_time->locale('fr')->isoFormat('D MMMM YYYY') }}</p>
                    @if ($booking->feedback_comment)
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">« {{ $booking->feedback_comment }} »</p>
                    @endif
                </article>
            @empty
                <p class="mt-3 text-sm text-slate-500">Les avis apparaîtront ici après les visites.</p>
            @endforelse
        </section>

        <p class="text-center text-xs text-slate-500">Cette page est personnelle et se met à jour automatiquement. Ne la partagez pas.</p>
    </div>
</x-layouts.public>
