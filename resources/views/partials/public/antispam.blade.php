{{-- Champ piège (invisible pour les humains, rempli par les robots) + Turnstile si configuré. --}}
<div class="honeypot" aria-hidden="true">
    <label for="website-{{ $id ?? 'f' }}">Ne pas remplir ce champ</label>
    <input id="website-{{ $id ?? 'f' }}" type="text" name="{{ \App\Services\SpamGuard::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
</div>
@if (\App\Services\Turnstile::enabled())
    <div class="cf-turnstile mt-3" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="fr" data-theme="auto"></div>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}"></script>
    @endonce
@endif
