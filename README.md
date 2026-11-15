# Yewtod SS — refonte Laravel (branche `v2`)

Média de recherche en sciences sociales : site public éditorial + back-office réservé aux membres invités.

- Stack : Laravel 13, PHP 8.3+, MariaDB, Blade + Livewire 4 + Alpine.js (build CSP) + Tailwind 4, Fortify (2FA TOTP obligatoire), Pest.
- Hébergement cible : mutualisé LWS (décision 12), sans worker ni Node en production.

Documentation :

| Fichier | Contenu |
| --- | --- |
| `docs/INSTALLATION.md` | Installer l'environnement local (WSL Debian), récupérer la branche `v2` |
| `docs/DEPLOIEMENT.md` | Mise en ligne sur LWS (préprod puis prod), retour arrière |
| `docs/SECURITE.md` | Chaque menace : protection, fichier, test qui la prouve |
| `docs/DECISIONS.md` | Choix techniques et écarts justifiés par rapport au cahier |
| `docs/AVANCEMENT.md` | Lots faits, en cours, prochaine action |
| `docs/lots/` | Un compte rendu pédagogique par lot |
