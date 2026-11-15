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
        get barStyle() { return 'width: ' + Math.round((this.score / 8) * 100) + '%'; },
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
            this.$wire[this.$root.dataset.method](...(arg !== undefined ? [Number(arg)] : []));
        },
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
}
