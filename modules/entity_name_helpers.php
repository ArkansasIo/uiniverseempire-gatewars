<?php
function sanitizeEntityName(string $value): string {
    $trimmed = trim($value);
    $clean = preg_replace('/[^A-Za-z0-9 _:\/\-]/', '', $trimmed) ?? '';
    return trim($clean);
}

function buildDisplayName(string $currentName, string $fallback): string {
    $safe = sanitizeEntityName($currentName);
    if ($safe === '') {
        $safe = sanitizeEntityName($fallback);
    }
    return $safe !== '' ? $safe : 'Unnamed';
}

function dbSafeEntityName(string $value): string {
    return str_replace("'", "''", sanitizeEntityName($value));
}
