#!/usr/bin/env php
<?php

/**
 * Inverts the brightness of all colours in an IntelliJ colour scheme XML file.
 * Each colour's brightness is mirrored around the midpoint: v = 1 - v
 * Hue and saturation are preserved, contrast relationships are maintained.
 *
 * Usage: ./scripts/invert-scheme-brightness.php <input> <output>
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: ./scripts/invert-scheme-brightness.php <input> <output>\n");
    exit(1);
}

$input  = $argv[1];
$output = $argv[2];

function hexToHsv(string $hex): array {
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $delta = $max - $min;

    $v = $max;
    $s = $max == 0 ? 0 : $delta / $max;

    if ($delta == 0) {
        $h = 0;
    } elseif ($max == $r) {
        $h = 60 * fmod(($g - $b) / $delta, 6);
    } elseif ($max == $g) {
        $h = 60 * (($b - $r) / $delta + 2);
    } else {
        $h = 60 * (($r - $g) / $delta + 4);
    }

    if ($h < 0) $h += 360;

    return [$h, $s, $v];
}

function hsvToHex(float $h, float $s, float $v): string {
    $v = max(0, min(1, $v));
    $s = max(0, min(1, $s));

    $c = $v * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $v - $c;

    if ($h < 60)       [$r, $g, $b] = [$c, $x, 0];
    elseif ($h < 120)  [$r, $g, $b] = [$x, $c, 0];
    elseif ($h < 180)  [$r, $g, $b] = [0, $c, $x];
    elseif ($h < 240)  [$r, $g, $b] = [0, $x, $c];
    elseif ($h < 300)  [$r, $g, $b] = [$x, 0, $c];
    else               [$r, $g, $b] = [$c, 0, $x];

    return sprintf('%02X%02X%02X',
        round(($r + $m) * 255),
        round(($g + $m) * 255),
        round(($b + $m) * 255)
    );
}

function invertHex(string $hex): string {
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return $hex;
    [$h, $s, $v] = hexToHsv($hex);
    return hsvToHex($h, $s, 1 - $v);
}

// Replace all value="RRGGBB" (6 hex chars, no #) attributes
$content = file_get_contents($input);
$content = preg_replace_callback(
    '/\bvalue="([0-9a-fA-F]{6})"/i',
    fn($m) => 'value="' . invertHex($m[1]) . '"',
    $content
);

file_put_contents($output, $content);
echo "Written to $output\n";
