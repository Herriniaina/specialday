#!/usr/bin/env php
<?php

declare(strict_types=1);

$file = $argv[1] ?? 'coverage.xml';

if (!is_readable($file)) {
    fwrite(STDERR, "Coverage file not readable: {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);

if (false === $xml) {
    fwrite(STDERR, "Unable to parse Clover XML: {$file}\n");
    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if (0 === $statements) {
    echo '0.00';
    exit(0);
}

printf('%.2f', $covered / $statements * 100);
