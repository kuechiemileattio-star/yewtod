{{-- Formulaire de collaboration (cahier § 2.7, § 5.5). --}}
<x-layouts.public title="Collaborations" description="Proposer une recherche, un article, un livre, une intervention ou un partenariat à Yewtod SS.">
    <div class="mx-auto max-w-[1120px] px-6 pb-[110px] pt-12 sm:pt-[72px]">
        <div class="grid gap-[30px] pb-12 md:grid-cols-[1.2fr_.8fr] md:items-end md:gap-[60px] md:pb-[62px] page-enter">
            <div>
                <p class="kicker mb-5 text-xs">Collaborations</p>
                <h1 class="mb-4 text-[clamp(2.4rem,5vw,3.9rem)] leading-[1.02]">Travaillons ensemble</h1>
                <p class="max-w-[620px] text-base leading-[1.7] text-muted">Une recherche à mener, un article ou un livre à proposer, une intervention, un partenariat, une interview ? Décrivez votre projet : chaque demande est lue.</p>
            </div>
            <p class="collab-note"><x-icon name="arrow-up-right" class="size-5 shrink-0" /><span>Chaque message est lu, même quand l'idée n'est pas encore très claire.</span></p>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-[.72fr_1.28fr]">
            <aside class="order-2 rounded-[14px] bg-navy p-[26px] text-white lg:order-1" aria-labelledby="ensemble">
                <h2 id="ensemble" class="mb-[22px] font-mono text-[10px] font-normal uppercase tracking-[0.1em] text-amber">Ce qu'on peut faire ensemble</h2>
                <ul class="stagger">
                    @foreach ([['flask', 'De la recherche', 'Un terrain, des données ou une intuition à tester ? On regarde ensemble si ça tient.'], ['pen', 'De l\'écriture', 'Un article co-signé, une note, un chapitre de livre, au format qui vous convient.'], ['mic', 'Une prise de parole', 'Un entretien, une intervention, une conversation enregistrée ou non.']] as [$icon, $label, $text])
                        <li class="collab-type"><x-icon :name="$icon" class="size-[18px] shrink-0 text-amber" /><div><strong>{{ $label }}</strong><span>{{ $text }}</span></div></li>
                    @endforeach
                </ul>
                <ul class="mt-2 space-y-2.5 border-t border-white/15 pt-5 text-xs text-on-navy-muted">
                    <li class="flex gap-2.5"><x-icon name="shield" class="size-4 shrink-0 text-teal-on-navy" /> Votre description est chiffrée et vos pièces jointes restent privées.</li>
                    <li class="flex gap-2.5"><x-icon name="trash" class="size-4 shrink-0 text-teal-on-navy" /> Les demandes sont supprimées automatiquement après {{ config('yewtod.retention.collaborations_months') }} mois.</li>
                    <li class="flex gap-2.5"><x-icon name="eye" class="size-4 shrink-0 text-teal-on-navy" /> <span>Voir la <a class="text-white underline underline-offset-2 hover:text-amber" href="{{ route('privacy') }}">politique de confidentialité</a>.</span></li>
                </ul>
            </aside>

        <div class="order-1 rounded-[14px] border border-line bg-page p-5 shadow-[0_18px_32px_-28px_var(--navy)] sm:p-7 lg:order-2">
            @if (session('collaboration_sent'))
                <div class="rounded-xl border border-line bg-surface-2 p-8 text-center" role="status">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-success/15 text-success"><x-icon name="check" class="size-7" /></span>
                    <h2 class="mt-4 text-2xl">Demande envoyée</h2>
                    <p class="mt-2 text-muted">Merci ! Nous reviendrons vers vous à l'adresse indiquée.</p>
                    <a href="{{ url('/works') }}" class="btn-secondary mt-6">Découvrir les travaux</a>
                </div>
            @else
                <form method="POST" action="{{ route('collaborations.store') }}" enctype="multipart/form-data" class="collab-form relative">
                    @csrf
                    @if ($errors->any())
                        <p class="mb-4 rounded-lg border border-danger/40 bg-danger/10 p-3 text-sm text-danger" role="alert">{{ $errors->first('form') ?: 'Certains champs sont à corriger.' }}</p>
                    @endif
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        @foreach ([['name', 'Nom', 'text', 120, true, 'name'], ['organization', 'Organisation (facultatif)', 'text', 200, false, 'organization'], ['email', 'Email', 'email', 254, true, 'email'], ['subject', 'Sujet', 'text', 200, true, 'off']] as [$f, $label, $type, $max, $req, $ac])
                            <div class="mb-4">
                                <label for="c-{{ $f }}" class="field-label">{{ $label }}</label>
                                <input id="c-{{ $f }}" name="{{ $f }}" type="{{ $type }}" maxlength="{{ $max }}" @if ($req) required @endif autocomplete="{{ $ac }}" value="{{ old($f) }}" class="field-input" @error($f) aria-invalid="true" aria-describedby="e-{{ $f }}" @enderror>
                                @error($f)<p id="e-{{ $f }}" class="field-error">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-4">
                        <label for="c-kind" class="field-label">Type de demande</label>
                        <select id="c-kind" name="kind" required class="field-input">
                            <option value="">Choisir…</option>
                            @foreach ($kinds as $key => $label)<option value="{{ $key }}" @selected(old('kind') === $key)>{{ $label }}</option>@endforeach
                        </select>
                        @error('kind')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-4">
                        <label for="c-description" class="field-label">Description <span class="font-normal text-muted">(5 000 caractères maximum)</span></label>
                        <textarea id="c-description" name="description" required maxlength="5000" rows="7" class="field-input">{{ old('description') }}</textarea>
                        @error('description')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-4">
                        <label for="c-files" class="field-label">Pièces jointes (facultatif)</label>
                        <input id="c-files" name="attachments[]" type="file" multiple accept=".pdf,.doc,.docx,.odt,image/jpeg,image/png,image/webp,image/gif" class="field-input py-2" aria-describedby="c-files-help">
                        <p id="c-files-help" class="field-help">Jusqu'à 3 fichiers : PDF, Word ou image, 10 Mo chacun.</p>
                        @error('attachments')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    @include('partials.public.antispam', ['id' => 'collab'])
                    <button class="btn-primary mt-4 w-full sm:w-auto">Envoyer la demande <x-icon name="arrow-right" class="size-4" /></button>
                </form>
            @endif
        </div>
        </div>
    </div>
</x-layouts.public>
