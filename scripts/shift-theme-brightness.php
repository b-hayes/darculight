#!/usr/bin/env php
<?php

/**
 * Shifts Darcula theme colours for use in a light theme.
 * - Background/border/panel/separator keys: +$shift (dark → light)
 * - Foreground/text keys: -$shift (light → dark)
 *
 * Usage: ./scripts/shift-theme-brightness.php <shift> <input> <output>
 * Example: ./scripts/shift-theme-brightness.php 170
 */

if ($argc < 4) {
    fwrite(STDERR, "Usage: ./scripts/shift-theme-brightness.php <shift> <input> <output>\n");
    exit(1);
}
$shift  = (int)$argv[1];
$input  = $argv[2];
$output = $argv[3];

$BG_KEYS   = ['background', 'border', 'panel', 'separator'];
$FG_KEYS   = ['foreground', 'text'];

function hexToHsv(string $hex): array {
    $hex = ltrim($hex, '#');
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

    return sprintf('#%02X%02X%02X',
        round(($r + $m) * 255),
        round(($g + $m) * 255),
        round(($b + $m) * 255)
    );
}

function shiftHex(string $hex, int $delta): string {
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) return $hex;
    [$h, $s, $v] = hexToHsv($hex);

    // Clamp based on which side of the midpoint the original colour is on
    // to prevent darks and lights converging into indistinguishable mid-greys
    $v = $v + $delta / 255;
    if ($v >= 0.5) {
        $v = max(180 / 255, min(1.0, $v));
    } else {
        $v = max(0.0, min(75 / 255, $v));
    }

    return hsvToHex($h, $s, $v);
}

function classifyKey(string $key, array $bgKeys, array $fgKeys): string {
    $lower = strtolower($key);
    foreach ($bgKeys as $k) { if (str_contains($lower, $k)) return 'bg'; }
    foreach ($fgKeys as $k) { if (str_contains($lower, $k)) return 'fg'; }
    return 'none';
}

function processValue(mixed $value, string $key, array $bgKeys, array $fgKeys, int $shift): mixed {
    $type = classifyKey($key, $bgKeys, $fgKeys);
    if ($type === 'none') return $value;

    $delta = $type === 'bg' ? $shift : -$shift;

    if (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        return shiftHex($value, $delta);
    }

    if (is_array($value)) {
        foreach ($value as $k => $v) {
            if (is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                $value[$k] = shiftHex($v, $delta);
            }
        }
        return $value;
    }

    return $value;
}

function processNode(mixed $node, array $bgKeys, array $fgKeys, int $shift): mixed {
    if (!is_array($node)) return $node;

    foreach ($node as $key => $value) {
        if (is_array($value)) {
            $allStrings = array_reduce($value, fn($c, $v) => $c && is_string($v), true);
            if ($allStrings) {
                foreach ($value as $childKey => $childVal) {
                    $node[$key][$childKey] = processValue($childVal, $childKey, $bgKeys, $fgKeys, $shift);
                }
            } else {
                $node[$key] = processNode($value, $bgKeys, $fgKeys, $shift);
                $node[$key] = processValue($node[$key], $key, $bgKeys, $fgKeys, $shift);
            }
        } else {
            $node[$key] = processValue($value, $key, $bgKeys, $fgKeys, $shift);
        }
    }

    return $node;
}

// Load Darcula
$darcula = json_decode(file_get_contents($input), true);
$ui = $darcula['ui'] ?? [];

// Process
$shifted = processNode($ui, $BG_KEYS, $FG_KEYS, $shift);

// Build output theme
$theme = [
    'name'        => 'Darculight',
    'dark'        => false,
    'author'      => 'b-hayes',
    'parentTheme' => 'ExperimentalLight',
    'editorScheme' => 'Darculight',
    'ui'          => $shifted,
];

file_put_contents($output, json_encode($theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "Written to " . $output . "\n";
