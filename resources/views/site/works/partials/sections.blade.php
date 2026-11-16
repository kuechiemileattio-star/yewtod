{{--
    Corps : sommaire interactif (liste des sections à gauche, section choisie à
    droite). Sans JavaScript, toutes les sections s'affichent à la suite et le
    sommaire devient une simple table des matières à ancres.
--}}
@php($sections = array_values(array_filter($content->sections, fn ($s) => trim(($s['title'] ?? '').strip_tags($s['content'] ?? '')) !== '')))
@if (count($sections) > 1)
    <div x-data="sectionToc" class="grid grid-cols-1 gap-6 md:grid-cols-[14rem_1fr]">
        <nav aria-label="Sommaire" class="min-w-0 md:sticky md:top-24 md:self-start">
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-muted">Sommaire</p>
            <ol class="flex gap-2 overflow-x-auto pb-2 md:flex-col md:gap-1 md:overflow-visible">
                @foreach ($sections as $i => $section)
                    <li class="shrink-0">
                        <a href="#section-{{ $i + 1 }}" data-index="{{ $i }}" x-on:click="select" @if ($i === 0) aria-current="true" @endif
                           class="toc-tab flex min-h-11 items-center gap-2 rounded-lg border-l-4 border-transparent px-3 text-sm font-semibold text-muted hover:bg-surface-2 aria-[current=true]:border-accent aria-[current=true]:bg-surface aria-[current=true]:text-ink">
                            <span class="text-xs">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="max-w-[14rem] truncate md:max-w-none md:whitespace-normal">{{ $section['title'] ?: 'Section '.($i + 1) }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>
        <div class="min-w-0">
            @foreach ($sections as $i => $section)
                <section id="section-{{ $i + 1 }}" class="toc-panel scroll-mt-24" data-index="{{ $i }}" tabindex="-1">
                    @if ($section['title'])<h2 class="text-2xl font-bold">{{ $section['title'] }}</h2>@endif
                    {{-- HTML nettoyé par HTML Purifier (à l'enregistrement ET ici) : seule sortie non échappée autorisée. --}}
                    <div class="prose-yewtod mt-2 max-w-none">{!! \App\Support\TextStructurer::toHtml($section['content'] ?? '') !!}</div>
                </section>
            @endforeach
        </div>
    </div>
@elseif (count($sections) === 1)
    <section>
        @if ($sections[0]['title'])<h2 class="text-2xl font-bold">{{ $sections[0]['title'] }}</h2>@endif
        <div class="prose-yewtod mt-2 max-w-none">{!! \App\Support\TextStructurer::toHtml($sections[0]['content'] ?? '') !!}</div>
    </section>
@endif
