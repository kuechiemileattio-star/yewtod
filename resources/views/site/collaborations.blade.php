{{-- Formulaire de collaboration (cahier § 2.7, § 5.5). --}}
<x-layouts.public title="Collaborations" description="Proposer une recherche, un article, un livre, une intervention ou un partenariat à Yewtod SS.">
    <section class="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_1.4fr] lg:py-16">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent-ink dark:text-accent">Collaborations</p>
            <h1 class="mt-3 text-4xl font-bold leading-tight sm:text-5xl">Travaillons ensemble</h1>
            <p class="mt-5 text-lg text-muted">Une recherche à mener, un article ou un livre à proposer, une intervention, un partenariat, une interview ? Décrivez votre projet : chaque demande est lue.</p>
            <ul class="mt-8 space-y-3 text-sm">
                <li class="flex gap-3"><x-icon name="shield" class="size-5 shrink-0 text-accent-2-ink" /> Votre description est chiffrée et vos pièces jointes restent privées.</li>
                <li class="flex gap-3"><x-icon name="trash" class="size-5 shrink-0 text-accent-2-ink" /> Les demandes sont supprimées automatiquement après {{ config('yewtod.retention.collaborations_months') }} mois.</li>
                <li class="flex gap-3"><x-icon name="eye" class="size-5 shrink-0 text-accent-2-ink" /> <span>Voir la <a class="link" href="{{ route('privacy') }}">politique de confidentialité</a>.</span></li>
            </ul>
        </div>

        <div>
            @if (session('collaboration_sent'))
                <div class="card p-8 text-center" role="status">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-success/15 text-success"><x-icon name="check" class="size-7" /></span>
                    <h2 class="mt-4 text-2xl font-bold">Demande envoyée</h2>
                    <p class="mt-2 text-muted">Merci ! Nous reviendrons vers vous à l'adresse indiquée.</p>
                    <a href="{{ url('/works') }}" class="btn-secondary mt-6">Découvrir les travaux</a>
                </div>
            @else
                <form method="POST" action="{{ route('collaborations.store') }}" enctype="multipart/form-data" class="card relative p-5 sm:p-8">
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
                    <button class="btn-primary mt-4 w-full sm:w-auto"><x-icon name="send" class="size-4" /> Envoyer la demande</button>
                </form>
            @endif
        </div>
    </section>
</x-layouts.public>
