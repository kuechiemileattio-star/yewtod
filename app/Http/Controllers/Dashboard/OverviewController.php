<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Content;
use App\Models\ContentEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Vue d'ensemble (cahier § 4.1, bug n° 16) : tuiles avec « +N ce mois »,
 * publications par mois sur 12 mois, « les plus consultés » sur 7 jours,
 * 6 contenus récents. Les comptes « publiés » s'appuient sur le scope
 * published() : les programmés échus sont comptés, comme sur le site.
 */
class OverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $tz = (string) config('app.display_timezone');
        $monthStart = CarbonImmutable::now($tz)->startOfMonth()->utc();

        $tiles = [];
        foreach ([[ContentType::Report, 'Rapports publiés', 'report'], [ContentType::Article, 'Articles publiés', 'article'], [ContentType::DataVisualization, 'Visualisations publiées', 'chart']] as [$type, $label, $icon]) {
            $q = Content::query()->published()->ofType($type);
            $tiles[] = ['label' => $label, 'icon' => $icon, 'total' => (clone $q)->count(),
                'month' => (clone $q)->whereRaw('COALESCE(published_at, created_at) >= ?', [$monthStart])->count()];
        }
        $books = Book::query()->published();
        $tiles[] = ['label' => 'Livres publiés', 'icon' => 'book', 'total' => (clone $books)->count(),
            'month' => (clone $books)->whereRaw('COALESCE(published_at, created_at) >= ?', [$monthStart])->count()];

        return view('dashboard.home', [
            'tiles' => $tiles,
            'chart' => $this->monthly($tz),
            'top' => $this->mostViewed(),
            'recent' => Content::query()->with('author')->orderByDesc('updated_at')->limit(6)->get(),
            'canContents' => $user->hasPermission('manage_articles'),
        ]);
    }

    /** @return array{labels: list<string>, series: list<array{name: string, values: list<int>}>} */
    private function monthly(string $tz): array
    {
        $start = CarbonImmutable::now($tz)->startOfMonth()->subMonths(11);
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[$start->addMonths($i)->format('Y-m')] = 0;
        }
        // Regroupement en SQL sur la date affichée (publication, sinon création), sans entrée utilisateur.
        $count = function ($query) use (&$months, $start) {
            $rows = $query->whereRaw('COALESCE(published_at, created_at) >= ?', [$start->utc()])
                ->selectRaw("DATE_FORMAT(COALESCE(published_at, created_at), '%Y-%m') AS ym, COUNT(*) AS n")->groupBy('ym')->pluck('n', 'ym');
            foreach ($rows as $ym => $n) {
                if (isset($months[$ym])) {
                    $months[$ym] += (int) $n;
                }
            }
        };
        $count(Content::query()->published());
        $count(Book::query()->published());

        return [
            'labels' => array_map(fn ($ym) => CarbonImmutable::createFromFormat('Y-m', $ym)->translatedFormat('M y'), array_keys($months)),
            'series' => [['name' => 'Publications et livres', 'values' => array_values($months)]],
        ];
    }

    /** @return list<array{title: string, kind: string, url: string, views: int}> */
    private function mostViewed(): array
    {
        $rows = ContentEvent::query()->where('kind', 'view')->where('created_at', '>=', now()->subDays(7))
            ->select('eventable_type', 'eventable_id', DB::raw('COUNT(*) AS views'))
            ->groupBy('eventable_type', 'eventable_id')->orderByDesc('views')->limit(3)->get();

        $top = [];
        foreach ($rows as $row) {
            $item = match ($row->eventable_type) {
                Content::class => Content::query()->find($row->eventable_id),
                Book::class => Book::query()->find($row->eventable_id),
                default => null,
            };
            if ($item instanceof Content || $item instanceof Book) {
                $top[] = ['title' => $item->title, 'kind' => $item instanceof Book ? 'Livre' : $item->type->label(), 'url' => $item->publicUrl(), 'views' => (int) $row->getAttribute('views')];
            }
        }

        return $top;
    }
}
