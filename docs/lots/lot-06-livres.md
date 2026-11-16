# Lot 06 — Livres

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Tables `books` et `book_related` : catégories (9), difficulté, mois, prix EUR et statut contrôlés par CHECK ; slug unique ; similaires par identifiant | `database/migrations/2026_11_16_100200_create_books_tables.php` |
| Modèle `Book` (scope `published()`, prix en euros, JSON-LD `Book` avec offres en EUR) | `app/Models/Book.php` |
| Règles métier : validation, slug manuel ou automatique, programmation, liens http(s), suppression des fichiers remplacés | `app/Services/BookService.php` |
| Bascule automatique des livres programmés (même commande que les publications) | `routes/console.php` |
| Écran du dashboard : recherche titre/auteur, filtre catégorie/statut, cartes avec date programmée, aperçu modal, éditeur modal en 6 onglets (tous les champs § 3.1), couverture WebP, ebook et extraits privés, signets du PDF → composition, analyse IA (description, biographie, composition) | `app/Livewire/Dashboard/Books.php`, `resources/views/livewire/dashboard/books.blade.php` |
| Pages publiques : `/books` (recherche, catégorie, auteur, année, tri, mise en page magazine) et `/books/{slug}` 100 % en français (« Ce livre », onglets Description / Livre électronique / Auteur, avis Yewtod, citations, ressource externe, « À lire aussi ») | `app/Http/Controllers/Site/BookController.php`, `resources/views/site/books/*` |
| Extraits (`attachment`) et ebook (`inline`) servis depuis le disque privé, avec comptage | `BookController::excerpt()`, `::ebook()` |
| Livres de démonstration | `database/seeders/DemoBookSeeder.php` |

## Choix expliqués

- **Bug n° 13** : plus aucun texte anglais, prix en euros, et un bouton « Acheter » n'existe que si son lien est renseigné ET valide (http/https).
- **Bug n° 9** : `book_related` relie les livres par identifiant ; renommer un livre ne casse plus rien. Les identifiants envoyés sont filtrés (livres existants, jamais le livre lui-même).
- **Affectation de masse** : statut, dates, créateur, chemins de fichiers et compteurs sont hors `$fillable`. Les extraits conservés sont relus en base : un chemin envoyé par le navigateur n'est jamais enregistré.
- **Défense en profondeur** : même si un chemin d'extrait était altéré en base, le contrôleur refuse tout ce qui ne commence pas par `excerpts/` ou contient `..`.

## Vérifier

```bash
php artisan test tests/Feature/Books   # 30 tests
```
