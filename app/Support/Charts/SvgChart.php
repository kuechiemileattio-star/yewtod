<?php

namespace App\Support\Charts;

/**
 * Graphiques SVG rendus CÔTÉ SERVEUR (cahier § 3 « Graphiques », § 10 P1) :
 * barres, lignes, aires, barres empilées, nuage de points, camembert.
 *
 * Pourquoi côté serveur : aucune bibliothèque JavaScript, rendu visible par
 * les moteurs et sans JavaScript, CSP intacte. Toutes les chaînes issues des
 * données sont échappées (htmlspecialchars) ; les nombres sont convertis en
 * float. Le survol affiche les valeurs via <title> (infobulle native,
 * accessible). Couleurs = jetons sémantiques (classes fill-/stroke-*).
 */
class SvgChart
{
    private const W = 640;

    private const H = 360;

    private const PAD = ['top' => 20, 'right' => 16, 'bottom' => 56, 'left' => 56];

    /** Palette ordonnée : accent, accent-2, avertissement, succès, encre atténuée. */
    private const FILLS = ['fill-accent', 'fill-accent-2', 'fill-warning', 'fill-success', 'fill-muted', 'fill-danger'];

    private const STROKES = ['stroke-accent', 'stroke-accent-2', 'stroke-warning', 'stroke-success', 'stroke-muted', 'stroke-danger'];

    /** @var list<string> */
    private array $labels;

    /** @var list<array{name: string, values: list<float>}> */
    private array $series;

    /**
     * @param  array<string, mixed>  $data  {labels: list, series: list<{name, values}>}
     */
    public function __construct(private readonly string $type, array $data, private readonly string $title = '')
    {
        $this->labels = array_values(array_map(fn ($l) => (string) $l, array_slice((array) ($data['labels'] ?? []), 0, 500)));
        $this->series = [];
        foreach (array_slice((array) ($data['series'] ?? []), 0, 20) as $i => $s) {
            if (! is_array($s)) {
                continue;
            }
            $this->series[] = [
                'name' => (string) ($s['name'] ?? 'Série '.($i + 1)),
                'values' => array_values(array_map(fn ($v) => is_numeric($v) ? (float) $v : 0.0, array_slice((array) ($s['values'] ?? []), 0, 500))),
            ];
        }
    }

    public function isEmpty(): bool
    {
        return $this->labels === [] || $this->series === [];
    }

    public function render(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $body = match ($this->type) {
            'line' => $this->lines(false),
            'area' => $this->lines(true),
            'stacked' => $this->bars(true),
            'scatter' => $this->scatter(),
            'pie' => $this->pie(),
            default => $this->bars(false),
        };

        $label = $this->e($this->title !== '' ? $this->title : 'Graphique');

        return '<svg class="chart-svg h-auto w-full" viewBox="0 0 '.self::W.' '.self::H.'" role="img" aria-label="'.$label.'" xmlns="http://www.w3.org/2000/svg">'
            .'<title>'.$label.'</title>'.$body.'</svg>';
    }

    /** Légende HTML (affichée si plusieurs séries, ou pour le camembert). */
    public function legend(): string
    {
        $items = $this->type === 'pie'
            ? array_map(fn ($l, $i) => [$l, $i], $this->labels, array_keys($this->labels))
            : (count($this->series) > 1 ? array_map(fn ($s, $i) => [$s['name'], $i], $this->series, array_keys($this->series)) : []);
        if ($items === []) {
            return '';
        }
        $html = '<ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">';
        foreach ($items as [$name, $i]) {
            $html .= '<li class="inline-flex items-center gap-2"><svg class="size-3" viewBox="0 0 10 10" aria-hidden="true"><rect width="10" height="10" rx="2" class="'.self::FILLS[$i % 6].'"/></svg>'.$this->e($name).'</li>';
        }

        return $html.'</ul>';
    }

    /** Tableau de données accessible (lecteurs d'écran, impression). */
    public function table(): string
    {
        $html = '<table class="w-full text-left text-sm"><thead><tr><th scope="col" class="py-1 pr-3">Catégorie</th>';
        foreach ($this->series as $s) {
            $html .= '<th scope="col" class="py-1 pr-3">'.$this->e($s['name']).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($this->labels as $i => $label) {
            $html .= '<tr class="border-t border-line"><th scope="row" class="py-1 pr-3 font-normal">'.$this->e($label).'</th>';
            foreach ($this->series as $s) {
                $html .= '<td class="py-1 pr-3">'.$this->num($s['values'][$i] ?? 0).'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    private function bars(bool $stacked): string
    {
        $n = count($this->labels);
        $max = $stacked
            ? max(array_map(fn ($i) => array_sum(array_map(fn ($s) => max(0, $s['values'][$i] ?? 0), $this->series)), array_keys($this->labels)) ?: [0])
            : $this->maxValue();
        [$min, $max] = [self::nice(min(0.0, $stacked ? 0.0 : $this->minValue())), self::nice(max($max, 0.0))];
        $out = $this->axes($min, $max);
        $plotW = self::W - self::PAD['left'] - self::PAD['right'];
        $band = $plotW / max($n, 1);
        $groups = $stacked ? 1 : count($this->series);
        $barW = max(1.0, ($band * 0.75) / $groups);

        foreach ($this->labels as $i => $label) {
            $x0 = self::PAD['left'] + $i * $band + $band * 0.125;
            $acc = 0.0;
            foreach ($this->series as $si => $s) {
                $v = $s['values'][$i] ?? 0.0;
                if ($stacked) {
                    $v = max(0.0, $v);
                    $y1 = $this->y($acc + $v, $min, $max);
                    $y2 = $this->y($acc, $min, $max);
                    $acc += $v;
                    $x = $x0;
                } else {
                    $y1 = $this->y(max($v, 0.0), $min, $max);
                    $y2 = $this->y(min($v, 0.0), $min, $max);
                    $x = $x0 + $si * $barW;
                }
                $out .= sprintf('<rect class="chart-mark %s" x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="2"><title>%s</title></rect>',
                    self::FILLS[$si % 6], $x, $y1, $barW, max(0.5, $y2 - $y1), $this->tip($label, $s['name'], $v));
            }
        }

        return $out.$this->xLabels($band);
    }

    private function lines(bool $area): string
    {
        $n = count($this->labels);
        [$min, $max] = [self::nice(min(0.0, $this->minValue())), self::nice(max(0.0, $this->maxValue()))];
        $out = $this->axes($min, $max);
        $plotW = self::W - self::PAD['left'] - self::PAD['right'];
        $step = $n > 1 ? $plotW / ($n - 1) : 0;
        $base = $this->y(0.0, $min, $max);

        foreach ($this->series as $si => $s) {
            $points = [];
            foreach ($this->labels as $i => $label) {
                $points[] = [self::PAD['left'] + $i * $step, $this->y($s['values'][$i] ?? 0.0, $min, $max), $label, $s['values'][$i] ?? 0.0];
            }
            $path = implode(' ', array_map(fn ($p) => sprintf('%.1f,%.1f', $p[0], $p[1]), $points));
            if ($area) {
                $first = $points[0][0];
                $last = end($points)[0];
                $out .= sprintf('<polygon class="chart-mark %s opacity-30" points="%.1f,%.1f %s %.1f,%.1f"/>', self::FILLS[$si % 6], $first, $base, $path, $last, $base);
            }
            $out .= sprintf('<polyline class="chart-mark %s" fill="none" stroke-width="2.5" stroke-linejoin="round" points="%s"/>', self::STROKES[$si % 6], $path);
            foreach ($points as [$x, $y, $label, $v]) {
                $out .= sprintf('<circle class="%s" cx="%.1f" cy="%.1f" r="4"><title>%s</title></circle>', self::FILLS[$si % 6], $x, $y, $this->tip($label, $s['name'], $v));
            }
        }

        return $out.$this->xLabels($n > 1 ? $step : $plotW, $n > 1);
    }

    /** Nuage de points : 1re série = abscisses, séries suivantes = ordonnées (ou index si une seule série). */
    private function scatter(): string
    {
        $xs = count($this->series) > 1 ? $this->series[0]['values'] : array_keys($this->labels);
        $ySeries = count($this->series) > 1 ? array_slice($this->series, 1) : $this->series;
        $all = array_merge(...array_map(fn ($s) => $s['values'], $ySeries));
        [$min, $max] = [min(0.0, min($all ?: [0])), max(0.0, max($all ?: [0]))];
        [$xMin, $xMax] = [min($xs ?: [0]), max($xs ?: [1])];
        $out = $this->axes($min, $max);
        $plotW = self::W - self::PAD['left'] - self::PAD['right'];
        foreach ($ySeries as $si => $s) {
            foreach ($s['values'] as $i => $v) {
                $xv = (float) ($xs[$i] ?? $i);
                $x = self::PAD['left'] + ($xMax == $xMin ? $plotW / 2 : ($xv - $xMin) / ($xMax - $xMin) * $plotW);
                $out .= sprintf('<circle class="chart-mark %s opacity-80" cx="%.1f" cy="%.1f" r="5"><title>%s</title></circle>',
                    self::FILLS[(count($this->series) > 1 ? $si + 1 : $si) % 6], $x, $this->y($v, $min, $max), $this->tip($this->labels[$i] ?? '', $s['name'], $v));
            }
        }

        return $out;
    }

    private function pie(): string
    {
        $values = array_map(fn ($v) => max(0.0, $v), $this->series[0]['values']);
        $total = array_sum($values);
        if ($total <= 0) {
            return '';
        }
        [$cx, $cy, $r] = [self::W / 2, self::H / 2, self::H / 2 - 20];
        $angle = -M_PI / 2;
        $out = '';
        foreach ($values as $i => $v) {
            if ($v <= 0) {
                continue;
            }
            $slice = $v / $total * 2 * M_PI;
            $label = $this->tip($this->labels[$i] ?? '', $this->series[0]['name'], $v).' ('.$this->num($v / $total * 100).' %)';
            if ($slice >= 2 * M_PI - 1e-9) {
                $out .= sprintf('<circle class="chart-mark %s" cx="%.1f" cy="%.1f" r="%.1f"><title>%s</title></circle>', self::FILLS[$i % 6], $cx, $cy, $r, $label);
                break;
            }
            $x1 = $cx + $r * cos($angle);
            $y1 = $cy + $r * sin($angle);
            $angle += $slice;
            $x2 = $cx + $r * cos($angle);
            $y2 = $cy + $r * sin($angle);
            $out .= sprintf('<path class="chart-mark %s stroke-surface" stroke-width="2" d="M%.1f,%.1f L%.1f,%.1f A%.1f,%.1f 0 %d 1 %.1f,%.1f Z"><title>%s</title></path>',
                self::FILLS[$i % 6], $cx, $cy, $x1, $y1, $r, $r, $slice > M_PI ? 1 : 0, $x2, $y2, $label);
        }

        return $out;
    }

    /** Borne « ronde » (1, 2, 5 × 10^n) pour des graduations lisibles. */
    private static function nice(float $v): float
    {
        if ($v == 0.0) {
            return 0.0;
        }
        $sign = $v < 0 ? -1 : 1;
        $exp = 10 ** floor(log10(abs($v)));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($m * $exp >= abs($v)) {
                return $sign * $m * $exp;
            }
        }

        return $v;
    }

    private function axes(float $min, float $max): string
    {
        $out = '';
        $ticks = 4;
        for ($t = 0; $t <= $ticks; $t++) {
            $v = $min + ($max - $min) * $t / $ticks;
            $y = $this->y($v, $min, $max);
            $out .= sprintf('<line class="stroke-line" x1="%d" x2="%d" y1="%.1f" y2="%.1f"/>', self::PAD['left'], self::W - self::PAD['right'], $y, $y);
            $out .= sprintf('<text class="fill-muted" font-size="11" x="%d" y="%.1f" text-anchor="end">%s</text>', self::PAD['left'] - 6, $y + 4, $this->num($v));
        }

        return $out;
    }

    private function xLabels(float $band, bool $points = false): string
    {
        $n = count($this->labels);
        $every = max(1, (int) ceil($n / 12));
        $out = '';
        foreach ($this->labels as $i => $label) {
            if ($i % $every !== 0) {
                continue;
            }
            $x = self::PAD['left'] + ($points ? $i * $band : $i * $band + $band / 2);
            $text = mb_strlen($label) > 12 ? mb_substr($label, 0, 11).'…' : $label;
            $out .= sprintf('<text class="fill-muted" font-size="11" x="%.1f" y="%d" text-anchor="middle">%s</text>', $x, self::H - self::PAD['bottom'] + 18, $this->e($text));
        }

        return $out;
    }

    private function y(float $v, float $min, float $max): float
    {
        $plotH = self::H - self::PAD['top'] - self::PAD['bottom'];
        $range = $max - $min ?: 1.0;

        return self::PAD['top'] + $plotH - ($v - $min) / $range * $plotH;
    }

    private function maxValue(): float
    {
        return (float) max(array_merge(...array_map(fn ($s) => $s['values'] ?: [0], $this->series)));
    }

    private function minValue(): float
    {
        return (float) min(array_merge(...array_map(fn ($s) => $s['values'] ?: [0], $this->series)));
    }

    private function tip(string $label, string $series, float $v): string
    {
        return $this->e(trim($label.' — '.$series.' : '.$this->num($v)));
    }

    private function num(float $v): string
    {
        return number_format($v, fmod($v, 1.0) === 0.0 ? 0 : 2, ',', "\u{202F}");
    }

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }
}
