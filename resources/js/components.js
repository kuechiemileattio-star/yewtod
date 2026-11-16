/* Composants Alpine partagés par le site public et le dashboard. */

const THEMES = ['system', 'light', 'dark'];
const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

export function applyTheme(theme) {
    const dark = theme === 'dark' || (theme === 'system' && prefersDark());
    document.documentElement.classList.toggle('dark', dark);
}

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function registerComponents(Alpine) {
    /*
     * Bascule Clair / Sombre / Système (cahier § 9).
     * - Site public : cookie de préférence (aucun suivi), lu par le serveur.
     * - Dashboard : enregistrement sur le compte (data-save-url) + copie locale.
     */
    Alpine.data('themeSwitcher', () => ({
        theme: 'system',
        init() {
            this.theme = this.$el.dataset.theme || 'system';
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'system') applyTheme('system');
            });
        },
        get label() {
            return { system: 'Thème : système', light: 'Thème : clair', dark: 'Thème : sombre' }[this.theme];
        },
        get isLight() { return this.theme === 'light'; },
        get isDark() { return this.theme === 'dark'; },
        get isSystem() { return this.theme === 'system'; },
        cycle() {
            this.set(THEMES[(THEMES.indexOf(this.theme) + 1) % THEMES.length]);
        },
        choose(event) {
            this.set(event.currentTarget.dataset.value);
        },
        set(theme) {
            if (!THEMES.includes(theme)) return;
            this.theme = theme;
            applyTheme(theme);
            const maxAge = 60 * 60 * 24 * 365;
            document.cookie = `theme=${theme}; path=/; max-age=${maxAge}; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
            const url = this.$el.dataset.saveUrl;
            if (url) {
                fetch(url, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    body: JSON.stringify({ theme }),
                    credentials: 'same-origin',
                });
            }
        },
    }));

    /* Champ mot de passe avec bouton afficher / masquer. */
    Alpine.data('passwordField', () => ({
        visible: false,
        toggle() { this.visible = !this.visible; },
        get type() { return this.visible ? 'text' : 'password'; },
        get toggleLabel() { return this.visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'; },
    }));

    /*
     * Jauge de robustesse et critères cochés en direct (cahier § 8.2).
     * Indicatif seulement : la vérification qui compte est faite par le serveur.
     */
    Alpine.data('passwordStrength', () => ({
        value: '',
        personal: [],
        init() {
            this.personal = (this.$el.dataset.personal || '').toLowerCase().split(/[\s@._-]+/).filter((t) => t.length >= 3);
            this.personal.push('yewtod');
        },
        get okLength() { return this.value.length >= 12 && this.value.length <= 128; },
        get okLower() { return /[a-z]/.test(this.value); },
        get okUpper() { return /[A-Z]/.test(this.value); },
        get okDigit() { return /\d/.test(this.value); },
        get okSymbol() { return /[^A-Za-z0-9]/.test(this.value); },
        get okRepeat() { return this.value.length > 0 && !/(.)\1{3,}/.test(this.value); },
        get okPersonal() {
            const v = this.value.toLowerCase();
            return this.value.length > 0 && !this.personal.some((t) => v.includes(t));
        },
        get score() {
            return [this.okLength, this.okLower, this.okUpper, this.okDigit, this.okSymbol, this.okRepeat, this.okPersonal]
                .filter(Boolean).length + (this.value.length >= 16 ? 1 : 0);
        },
        get barStyle() { return { width: Math.round((this.score / 8) * 100) + '%' }; },
        get level() {
            if (this.score >= 8) return 'Très robuste';
            if (this.score >= 7) return 'Robuste';
            if (this.score >= 5) return 'Moyen';
            return this.value.length ? 'Faible' : '';
        },
        get barClass() {
            if (this.score >= 7) return 'bg-success';
            if (this.score >= 5) return 'bg-warning';
            return 'bg-danger';
        },
        mark(ok) { return ok ? '✓' : '○'; },
    }));

    /* Téléchargement des codes de secours (fichier texte généré localement). */
    Alpine.data('recoveryCodes', () => ({
        download() {
            const codes = JSON.parse(this.$el.dataset.codes || '[]');
            const text = `Codes de secours ${document.title}\nChaque code ne fonctionne qu'une fois.\n\n${codes.join('\n')}\n`;
            const url = URL.createObjectURL(new Blob([text], { type: 'text/plain;charset=utf-8' }));
            const a = document.createElement('a');
            a.href = url;
            a.download = 'codes-de-secours-yewtod.txt';
            a.click();
            URL.revokeObjectURL(url);
        },
    }));

    /* Tiroir / menu mobile : bloque le défilement de la page quand il est ouvert. */
    Alpine.data('drawer', () => ({
        open: false,
        toggle() { this.open ? this.close() : this.show(); },
        show() { this.open = true; document.documentElement.classList.add('overflow-hidden'); },
        close() { this.open = false; document.documentElement.classList.remove('overflow-hidden'); },
    }));

    /* Interrupteur générique (x-data="toggle"). */
    Alpine.data('toggle', () => ({
        on: false,
        flip() { this.on = !this.on; },
    }));

    /* Menu déroulant simple. */
    Alpine.data('dropdown', () => ({
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; },
    }));

    /* Notifications « toast » (cahier § 10) : événement navigateur `toast`. */
    Alpine.data('toasts', () => ({
        items: [],
        init() {
            const initial = JSON.parse(this.$el.dataset.initial || '[]');
            initial.forEach((t) => this.push(t));
            window.addEventListener('toast', (e) => this.push(Array.isArray(e.detail) ? e.detail[0] : e.detail));
        },
        push(t) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type: t.type || 'success', message: t.message || '' });
            setTimeout(() => this.remove(id), 6000);
        },
        remove(id) { this.items = this.items.filter((i) => i.id !== id); },
        dismiss(event) { this.remove(Number(event.currentTarget.dataset.id)); },
    }));

    /*
     * Appel d'une méthode Livewire depuis un champ (build CSP : pas
     * d'expression « $event.target.value » dans les gabarits).
     * <select x-data="wireCall" data-method="changeRole" data-arg="12" x-on:change="withValue">
     */
    Alpine.data('wireCall', () => ({
        withValue(event) {
            const arg = this.$el.dataset.arg;
            const args = arg !== undefined ? [Number(arg), event.target.value] : [event.target.value];
            this.$wire[this.$el.dataset.method](...args);
        },
    }));

    /* Confirmation avant une action Livewire destructrice (data-method, data-arg). */
    Alpine.data('confirmAction', () => ({
        open: false,
        ask() { this.open = true; this.$nextTick(() => this.$refs.cancel?.focus()); },
        cancel() { this.open = false; },
        accept() {
            this.open = false;
            const arg = this.$root.dataset.arg;
            const args = arg === undefined ? [] : [/^\d+$/.test(arg) ? Number(arg) : arg];
            this.$wire[this.$root.dataset.method](...args);
        },
    }));

    /* Navigation publique : ombre au défilement, barre de progression, menu mobile. */
    Alpine.data('siteNav', () => ({
        scrolled: false,
        progress: 0,
        menuOpen: false,
        init() {
            const update = () => {
                const max = document.documentElement.scrollHeight - window.innerHeight;
                this.scrolled = window.scrollY > 8;
                this.progress = max > 0 ? Math.min(1, window.scrollY / max) : 0;
            };
            window.addEventListener('scroll', update, { passive: true });
            update();
        },
        // Objet (et non chaîne) : Alpine utilise style.setProperty, autorisé par une CSP sans 'unsafe-inline'.
        get progressStyle() { return { transform: 'scaleX(' + this.progress + ')' }; },
        toggleMenu() { this.menuOpen ? this.closeMenu() : this.openMenu(); },
        openMenu() { this.menuOpen = true; document.documentElement.classList.add('overflow-hidden'); },
        closeMenu() { this.menuOpen = false; document.documentElement.classList.remove('overflow-hidden'); },
    }));

    /* Apparition au défilement (Reveal), désactivée si l'utilisateur réduit les animations. */
    Alpine.data('reveal', () => ({
        shown: false,
        init() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
                this.shown = true;
                return;
            }
            const io = new IntersectionObserver((entries) => {
                if (entries.some((e) => e.isIntersecting)) { this.shown = true; io.disconnect(); }
            }, { rootMargin: '0px 0px -10% 0px' });
            io.observe(this.$el);
        },
        get revealClass() { return this.shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'; },
    }));

    Alpine.data('backToTop', () => ({
        up() {
            window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            document.getElementById('contenu')?.focus({ preventScroll: true });
        },
    }));

    /*
     * Éditeur de texte riche minimal (contenteditable). Le HTML produit est
     * de toute façon NETTOYÉ côté serveur par HTML Purifier avant stockage.
     */
    Alpine.data('richText', () => ({
        timer: null,
        sync() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.$wire.$set(this.$el.dataset.model, this.$refs.area.innerHTML, false), 250);
        },
        exec(event) {
            const cmd = event.currentTarget.dataset.cmd;
            this.$refs.area.focus();
            const map = { bold: ['bold'], italic: ['italic'], ul: ['insertUnorderedList'], ol: ['insertOrderedList'], h3: ['formatBlock', 'h3'], quote: ['formatBlock', 'blockquote'], clear: ['removeFormat'] };
            if (cmd === 'link') {
                const url = window.prompt('Adresse du lien (https://…)');
                if (url && /^https?:\/\//i.test(url)) document.execCommand('createLink', false, url);
            } else if (map[cmd]) {
                document.execCommand(map[cmd][0], false, map[cmd][1] ?? null);
            }
            this.sync();
        },
    }));

    /* Analyse du PDF dans le navigateur avec pdf.js (chargé à la demande, cahier § 4.3). */
    Alpine.data('pdfInspector', () => ({
        progress: '',
        async inspect(event) {
            const file = event.target.files?.[0];
            if (!file || file.type !== 'application/pdf') return;
            this.progress = 'Analyse du PDF…';
            try {
                const { analysePdf } = await import('./pdf-analyse.js');
                const info = await analysePdf(file, (page, total) => { this.progress = `Analyse : page ${page} / ${total}`; });
                this.progress = `${info.pages} page(s), ${info.outline.length} signet(s) détecté(s).`;
                this.$wire.applyPdfInfo(info.pages, info.outline);
            } catch (e) {
                this.progress = 'Analyse locale impossible (le fichier sera tout de même envoyé).';
            }
        },
    }));

    /* Avertissement « modifications non enregistrées » (cahier § 10 P1). */
    Alpine.data('unsavedGuard', () => ({
        dirty: false,
        init() {
            window.addEventListener('content-saved', () => { this.dirty = false; });
            window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });
        },
        markDirty() { this.dirty = true; },
    }));

    /* Copie dans le presse-papiers (citation, lien). */
    Alpine.data('copyText', () => ({
        copied: false,
        async copy() {
            const text = this.$el.dataset.text || '';
            try { await navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => (this.copied = false), 2000); } catch (e) { /* navigateur sans presse-papiers */ }
        },
    }));

    /* Boîte de confirmation accessible (remplace window.confirm, cahier § 10). */
    Alpine.data('confirmDialog', () => ({
        open: false,
        ask() { this.open = true; this.$nextTick(() => this.$refs.cancel?.focus()); },
        cancel() { this.open = false; },
        accept() { this.open = false; this.$refs.form?.requestSubmit(); },
    }));
    /*
     * Sommaire interactif d'une publication (cahier § 2.4) : sans JavaScript,
     * toutes les sections s'affichent ; avec, seule la section choisie reste.
     */
    Alpine.data('sectionToc', () => ({
        init() { this.show(0); },
        select(event) {
            event.preventDefault();
            this.show(Number(event.currentTarget.dataset.index));
            const panel = this.$el.querySelector(`.toc-panel[data-index="${event.currentTarget.dataset.index}"]`);
            panel?.focus({ preventScroll: true });
            if (window.innerWidth < 768) panel?.scrollIntoView({ block: 'start' });
        },
        show(index) {
            this.$el.querySelectorAll('.toc-panel').forEach((p) => { p.hidden = Number(p.dataset.index) !== index; });
            // Onglets ARIA (role=tab) : aria-selected ; liste de liens (sommaire) : aria-current.
            this.$el.querySelectorAll('.toc-tab').forEach((t) => {
                const on = Number(t.dataset.index) === index;
                if (t.getAttribute('role') === 'tab') t.setAttribute('aria-selected', String(on));
                else if (on) t.setAttribute('aria-current', 'true');
                else t.removeAttribute('aria-current');
            });
        },
    }));

    /* Partage natif (mobile) ou copie du lien en repli. */
    Alpine.data('shareNative', () => ({
        label: 'Partager',
        async share() {
            const { title, url } = this.$el.dataset;
            if (navigator.share) {
                try { await navigator.share({ title, url }); } catch (e) { /* partage annulé */ }
                return;
            }
            try { await navigator.clipboard.writeText(url); this.label = 'Lien copié'; setTimeout(() => (this.label = 'Partager'), 2000); } catch (e) { /* sans presse-papiers */ }
        },
    }));

    /* Formulaire de filtres : soumission à chaque changement (le bouton « Appliquer » reste pour le mode sans JS). */
    Alpine.data('autoSubmit', () => ({
        submit(event) {
            if (event.target.type === 'search' || event.target.type === 'date') return;
            this.$el.requestSubmit();
        },
    }));

    /*
     * Recherche globale (cahier § 10 P2) : Ctrl K / ⌘ K ouvre la boîte,
     * résultats JSON du serveur (publications et livres visibles uniquement).
     * Les résultats sont insérés par x-text : jamais interprétés comme du HTML.
     */
    Alpine.data('globalSearch', () => ({
        open: false,
        q: '',
        results: [],
        loading: false,
        timer: null,
        init() {
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.show(); }
            });
        },
        show() { this.open = true; document.documentElement.classList.add('overflow-hidden'); this.$nextTick(() => this.$refs.input?.focus()); },
        close() { this.open = false; document.documentElement.classList.remove('overflow-hidden'); },
        get empty() { return this.q.trim().length >= 2 && !this.loading && this.results.length === 0; },
        get hint() { return this.q.trim().length < 2; },
        search() {
            clearTimeout(this.timer);
            this.timer = setTimeout(async () => {
                const q = this.q.trim();
                if (q.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const res = await fetch(this.$root.dataset.url + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                    this.results = res.ok ? (await res.json()).results : [];
                } catch (e) { this.results = []; }
                this.loading = false;
            }, 200);
        },
        submitAll() { window.location.href = this.$root.dataset.works + '?q=' + encodeURIComponent(this.q.trim()); },
    }));
}
