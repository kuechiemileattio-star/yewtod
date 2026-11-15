<?php

// Cahier § 9.4 : contraste WCAG AA vérifié dans les deux modes, calculé
// directement depuis les jetons de resources/css/app.css.

function tokens(string $scope): array
{
    $css = file_get_contents(base_path('resources/css/app.css'));
    preg_match('/'.preg_quote($scope, '/').'\s*\{(.*?)\}/s', $css, $m);
    preg_match_all('/--([\w-]+):\s*(#[0-9A-Fa-f]{6})/', $m[1], $vars, PREG_SET_ORDER);

    return collect($vars)->mapWithKeys(fn ($v) => [$v[1] => $v[2]])->all() + ['white' => '#FFFFFF', 'navy' => '#172238'];
}

function luminance(string $hex): float
{
    $c = array_map(fn ($x) => hexdec($x) / 255, str_split(ltrim($hex, '#'), 2));
    $c = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);

    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function ratio(string $a, string $b): float
{
    [$l1, $l2] = [luminance($a), luminance($b)];

    return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
}

dataset('modes', [':root', '.dark']);

it('respecte 4,5:1 pour les textes', function (string $mode) {
    $t = tokens($mode);
    $pairs = [
        ['ink', 'page'], ['ink', 'surface'], ['ink', 'surface-2'], ['muted', 'page'], ['muted', 'surface'],
        ['accent-2-ink', 'page'], ['accent-2-ink', 'surface'], ['accent-ink', 'surface'], ['danger', 'surface'],
        ['success', 'surface'], ['warning-ink', 'surface'],
    ];
    foreach ($pairs as [$fg, $bg]) {
        expect(ratio($t[$fg], $t[$bg]))->toBeGreaterThanOrEqual(4.5, "$fg sur $bg ($mode)");
    }
})->with('modes');

it('respecte 4,5:1 pour le texte des boutons principaux', function () {
    expect(ratio('#FFFFFF', tokens(':root')['accent-ink']))->toBeGreaterThanOrEqual(4.5);
    expect(ratio(tokens('.dark')['on-accent'], tokens('.dark')['accent']))->toBeGreaterThanOrEqual(4.5);
    expect(ratio('#172238', tokens(':root')['accent']))->toBeGreaterThanOrEqual(4.5);
    expect(ratio('#172238', tokens('.dark')['accent']))->toBeGreaterThanOrEqual(4.5);
});

it('respecte 3:1 pour les bordures de champs et le focus', function (string $mode) {
    $t = tokens($mode);
    foreach (['page', 'surface'] as $bg) {
        expect(ratio($t['line-strong'], $t[$bg]))->toBeGreaterThanOrEqual(3.0, "bordure sur $bg ($mode)");
        expect(ratio($t['focus'], $t[$bg]))->toBeGreaterThanOrEqual(3.0, "focus sur $bg ($mode)");
    }
})->with('modes');
