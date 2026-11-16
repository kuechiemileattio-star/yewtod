<?php

use App\Models\Book;
use App\Models\Collaboration;
use App\Models\Content;
use App\Models\Review;
use App\Models\SocialLink;
use App\Models\User;
use App\Services\SiteSettings;

// Charges XSS écrites directement en base (validation contournée) pour vérifier l'échappement à l'affichage.
// Usage : php artisan tinker tests/securite/injecter-charges-xss.php   (base de démonstration uniquement)
abort_unless(app()->environment('local'), 403, 'Réservé à l\'environnement local.');
$x = '<img src=x onerror=alert(1)><script>alert(2)</script>"><svg onload=alert(3)>';
$c = Content::where('type', 'articles')->published()->first();
$c->forceFill(['title' => 'XSS '.$x, 'summary' => $x, 'tags' => [$x], 'meta' => ['subtitle' => $x, 'coauthors' => [$x]], 'sections' => [['title' => $x, 'content' => $x]], 'references' => [['title' => $x, 'url' => 'javascript:alert(4)']]])->save();
$v = Content::where('type', 'visualisations-de-donnees')->first();
$v->forceFill(['meta' => ['legend' => $x, 'data_source' => $x, 'blocks' => [['title' => $x, 'description' => $x, 'chart_type' => 'bar', 'data' => ['labels' => [$x, 'b'], 'series' => [['name' => $x, 'values' => [1, 2]]]]]]]])->save();
$e = Content::where('type', 'episodes-documentaires')->first();
$e->forceFill(['meta' => ['video_url' => 'javascript:alert(5)', 'chapters' => [['time' => '0:00', 'title' => $x]]]])->save();
$b = Book::published()->first();
$b->forceFill(['title' => 'XSS '.$x, 'author' => $x, 'review' => $x, 'quotes' => [$x], 'buy_print_url' => 'javascript:alert(6)', 'description' => $x, 'composition' => [['title' => $x, 'page' => 1]]])->save();
Review::where('reviewable_type', Content::class)->where('reviewable_id', $c->id)->update(['name' => $x, 'body' => $x, 'status' => 'approved']);
(new SocialLink(['platform' => '<svg onload=alert(7)>', 'url' => 'javascript:alert(7)']))->forceFill(['position' => 0])->save();
app(SiteSettings::class)->set('site_name', 'Y <svg onload=alert(8)>');
app(SiteSettings::class)->set('reflection', ['text' => $x, 'author' => $x, 'date' => null]);
Collaboration::first()?->forceFill(['name' => $x, 'subject' => $x, 'description' => $x])->save();
User::where('email', 'editeur@yewtod.test')->update(['name' => 'Chloé <svg onload=alert(9)>', 'bio' => $x]);
$base = 'http://127.0.0.1:8002';
$rel = fn (string $u) => $base.parse_url($u, PHP_URL_PATH);
file_put_contents('/tmp/yewtod-xss.json', json_encode(['article' => $rel($c->publicUrl()), 'viz' => $rel($v->publicUrl()), 'episode' => $rel($e->publicUrl()), 'book' => $rel($b->publicUrl())]));
echo "Charges injectées.\n";
