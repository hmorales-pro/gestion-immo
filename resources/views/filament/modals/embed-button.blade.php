@php
    $snippet = '<a href="'.$url.'" target="_blank" rel="noopener" style="display:inline-block;padding:12px 22px;border-radius:10px;background:#f59e0b;color:#0f172a;font:600 16px/1.2 system-ui,-apple-system,sans-serif;text-decoration:none">Réserver une visite</a>';
@endphp
<div class="space-y-4" x-data="{ copied: false }">
    <p class="text-sm text-gray-600 dark:text-gray-400">Collez ce code dans votre site (WordPress, Wix, page d'annonce…) : il affiche un bouton qui ouvre la page de réservation de ce bien.</p>
    <div class="rounded-xl bg-gray-50 p-4 text-center dark:bg-white/5">{!! $snippet !!}</div>
    <textarea readonly rows="4" x-ref="code" class="block w-full rounded-lg border-gray-300 font-mono text-xs dark:border-white/10 dark:bg-white/5" x-on:focus="$el.select()">{{ $snippet }}</textarea>
    <x-filament::button color="gray" icon="heroicon-o-clipboard"
        x-on:click="navigator.clipboard.writeText($refs.code.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
        <span x-text="copied ? 'Copié !' : 'Copier le code'">Copier le code</span>
    </x-filament::button>
</div>
