#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * Minimal, dependency-free PHPUnit coverage gate.
 *
 * Reads a Clover report and fails (exit code 1) when the covered share of
 * coverable elements is below the required threshold.
 *
 * Usage: php bin/coverage-check.php <clover.xml> [min-percentage]
 */

$cloverFile = $argv[1] ?? null;
$threshold = (float) ($argv[2] ?? 100);

if (null === $cloverFile || !is_file($cloverFile)) {
    fwrite(\STDERR, sprintf("Coverage file not found: %s\n", (string) $cloverFile));
    exit(1);
}

$xml = simplexml_load_file($cloverFile);
if (false === $xml) {
    fwrite(\STDERR, "Could not parse the coverage file.\n");
    exit(1);
}

$elements = 0;
$covered = 0;
// Summing every <metrics> node double-counts (per-file plus the project total),
// but the ratio is preserved, so the resulting percentage is correct.
foreach ($xml->xpath('//metrics') ?: [] as $metrics) {
    $elements += (int) $metrics['elements'];
    $covered += (int) $metrics['coveredelements'];
}

$coverage = $elements > 0 ? ($covered / $elements) * 100 : 100.0;

printf("Coverage: %.2f%% (%d/%d elements), threshold %.2f%%\n", $coverage, $covered, $elements, $threshold);

if ($coverage + 1.0E-9 < $threshold) {
    fwrite(\STDERR, sprintf("FAIL: coverage %.2f%% is below the required %.2f%%.\n", $coverage, $threshold));
    exit(1);
}

echo "PASS\n";
exit(0);
