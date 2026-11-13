<div class="space-y-6">
    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        @include('voucher._content', ['booking' => $booking])

        @if ($booking->hasSignedVoucher())
            <div class="mt-6 rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
                <p class="text-sm font-semibold text-emerald-800">Signé le {{ $booking->voucher_signed_at->format('d/m/Y à H:i') }}</p>
                <img src="{{ $booking->voucher_signature }}" alt="Signature de {{ $booking->voucher_signer_name }}" class="mt-2 h-28 rounded-lg bg-white">
            </div>
            <a href="{{ route('voucher.print', $booking) }}" target="_blank" class="mt-5 block rounded-xl bg-slate-900 px-4 py-3 text-center font-semibold text-white hover:bg-slate-800">Imprimer / enregistrer en PDF</a>
        @elseif (! $booking->isConfirmed())
            <p class="mt-6 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">La visite doit être confirmée pour faire signer le bon de visite.</p>
        @else
            <form class="mt-6 space-y-4"
                x-data="{
                    drawing: false,
                    drawn: false,
                    ctx: null,
                    init() {
                        const canvas = this.$refs.canvas;
                        const ratio = window.devicePixelRatio || 1;
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        this.ctx = canvas.getContext('2d');
                        this.ctx.scale(ratio, ratio);
                        this.ctx.lineWidth = 2.2;
                        this.ctx.lineCap = 'round';
                        this.ctx.lineJoin = 'round';
                        this.ctx.strokeStyle = '#0f172a';
                    },
                    point(e) {
                        const r = this.$refs.canvas.getBoundingClientRect();
                        return [e.clientX - r.left, e.clientY - r.top];
                    },
                    start(e) {
                        this.drawing = true;
                        this.ctx.beginPath();
                        this.ctx.moveTo(...this.point(e));
                        e.target.setPointerCapture(e.pointerId);
                    },
                    move(e) {
                        if (! this.drawing) return;
                        this.ctx.lineTo(...this.point(e));
                        this.ctx.stroke();
                        this.drawn = true;
                    },
                    clear() {
                        const c = this.$refs.canvas;
                        this.ctx.clearRect(0, 0, c.width, c.height);
                        this.drawn = false;
                    },
                }"
                x-on:submit.prevent="$wire.signature = drawn ? $refs.canvas.toDataURL('image/png') : ''; $wire.sign()">
                <div>
                    <label for="signerName" class="block text-sm font-medium text-slate-700">Nom du visiteur</label>
                    <input id="signerName" type="text" wire:model="signerName" class="mt-1 block w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('signerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-700">Signature du visiteur</p>
                        <button type="button" x-on:click="clear()" class="text-sm font-semibold text-slate-500 hover:text-slate-800">Effacer</button>
                    </div>
                    <canvas x-ref="canvas" wire:ignore
                        x-on:pointerdown="start($event)" x-on:pointermove="move($event)"
                        x-on:pointerup="drawing = false" x-on:pointercancel="drawing = false" x-on:pointerleave="drawing = false"
                        @class(['mt-1 h-48 w-full touch-none rounded-xl border-2 border-dashed bg-white', 'border-slate-300' => ! $errors->has('signature'), 'border-red-500' => $errors->has('signature')])
                        aria-label="Zone de signature"></canvas>
                    @error('signature') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-3.5 font-semibold text-slate-950 hover:bg-amber-400">Valider la signature</button>
            </form>
        @endif
    </section>

    <a href="{{ \App\Filament\Resources\BookingResource::getUrl('view', ['record' => $booking]) }}" class="block text-center text-sm font-medium text-slate-600 hover:text-slate-900">← Retour à la visite</a>
</div>
