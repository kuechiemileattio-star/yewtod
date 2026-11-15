{{--
    Champ mot de passe : afficher/masquer, collage autorisé (gestionnaires de
    mots de passe), et — si :strength est vrai — jauge + critères en direct.
--}}
@props(['name' => 'password', 'label' => 'Mot de passe', 'autocomplete' => 'current-password', 'strength' => false, 'personal' => ''])
@php($id = $attributes->get('id', $name))
<div x-data="passwordField" class="mb-4">
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    <div @if ($strength) x-data="passwordStrength" data-personal="{{ $personal }}" @endif>
        <div class="relative">
            <input id="{{ $id }}" name="{{ $name }}" x-bind:type="type" type="password" autocomplete="{{ $autocomplete }}"
                   @if ($strength) x-model="value" aria-describedby="{{ $id }}-criteres" @endif
                   {{ $attributes->except('id')->merge(['class' => 'field-input pr-12', 'required' => true, 'maxlength' => 128]) }}>
            <button type="button" x-on:click="toggle" class="absolute inset-y-0 right-0 flex min-w-11 items-center justify-center text-muted hover:text-ink"
                    x-bind:aria-label="toggleLabel" aria-label="Afficher le mot de passe" x-bind:aria-pressed="visible">
                <svg x-show="!visible" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg x-show="visible" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7c1.7 0 3.2-.4 4.6-1.1"/></svg>
            </button>
        </div>
        @if ($strength)
            <div class="mt-2" aria-live="polite">
                <div class="h-2 w-full overflow-hidden rounded-full bg-surface-2" aria-hidden="true">
                    <div class="h-full rounded-full transition-all" x-bind:class="barClass" x-bind:style="barStyle"></div>
                </div>
                <p class="mt-1 text-xs font-semibold text-muted" x-text="level"></p>
            </div>
            <ul id="{{ $id }}-criteres" class="mt-2 grid gap-1 text-xs text-muted sm:grid-cols-2">
                <li><span x-text="mark(okLength)">○</span> 12 à 128 caractères</li>
                <li><span x-text="mark(okLower)">○</span> une minuscule</li>
                <li><span x-text="mark(okUpper)">○</span> une majuscule</li>
                <li><span x-text="mark(okDigit)">○</span> un chiffre</li>
                <li><span x-text="mark(okSymbol)">○</span> un caractère spécial</li>
                <li><span x-text="mark(okRepeat)">○</span> pas 4 caractères identiques de suite</li>
                <li class="sm:col-span-2"><span x-text="mark(okPersonal)">○</span> ni votre nom, ni votre email, ni « yewtod »</li>
                <li class="sm:col-span-2">○ jamais apparu dans une fuite connue, différent des 5 derniers (vérifié à l'envoi)</li>
            </ul>
        @endif
    </div>
    @error($name)<p class="field-error">{{ $message }}</p>@enderror
</div>
