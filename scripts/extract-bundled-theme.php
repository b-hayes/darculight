#!/usr/bin/env php
<?php

/**
 * Extracts a bundled theme.json from the IntelliJ Platform jar.
 *
 * Usage: ./scripts/extract-bundled-theme.php <theme-name> <output>
 */

$gradleHome = getenv('GRADLE_USER_HOME') ?: (getenv('HOME') . '/.gradle');
$jars = glob("$gradleHome/caches/*/transforms/*/transformed/ideaIC-*/lib/app-client.jar");
if (empty($jars)) {
    fwrite(STDERR, "Could not find app-client.jar in Gradle cache. Run ./gradlew first.\n");
    exit(1);
}
define('JAR', end($jars));

if ($argc < 2) {
    fwrite(STDERR, "Usage: ./scripts/extract-bundled-theme.php <theme-name> <output>\n");
    exit(1);
}

$themeName = strtolower($argv[1]);
$output    = $argv[2] ?? null;

// Get all theme.json paths from the jar
$lines = [];
exec('jar tf ' . escapeshellarg(JAR) . ' 2>/dev/null', $lines);
$themes = array_filter($lines, fn($l) => str_ends_with($l, '.theme.json'));

// Find a match
$match = null;
foreach ($themes as $path) {
    $basename = strtolower(basename($path, '.theme.json'));
    if ($basename === $themeName || str_contains($basename, $themeName)) {
        $match = $path;
        break;
    }
}

if (!$match) {
    fwrite(STDERR, "No theme matching \"$themeName\" found.\n\nAvailable themes:\n");
    foreach ($themes as $path) {
        fwrite(STDERR, "  " . basename($path, '.theme.json') . "\n");
    }
    exit(1);
}

if (!$output) {
    fwrite(STDERR, "Usage: ./scripts/extract-bundled-theme.php <theme-name> <output>\n");
    exit(1);
}

$content = shell_exec('unzip -p ' . escapeshellarg(JAR) . ' ' . escapeshellarg($match));
file_put_contents($output, $content);
echo "Extracted \"$match\" to $output\n";
