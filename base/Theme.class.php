<?php
class ThemeSupport {
    public static function normalizeTheme($themeName, $fallback = 'og') {
        $normalized = strtolower(trim((string)($themeName ?? '')));
        $allowed = ['white', 'og', 'blue', 'stargate'];
        if ($normalized === '') {
            $normalized = strtolower(trim((string)$fallback));
        }
        if (!in_array($normalized, $allowed, true)) {
            $normalized = strtolower(trim((string)$fallback));
        }
        return $normalized;
    }

    public static function themeClass($themeName) {
        return 'theme-' . self::normalizeTheme($themeName);
    }

    public static function themeOptions() {
        return [
            'white' => 'White',
            'og' => 'OG',
            'blue' => 'Blue',
            'stargate' => 'Stargate',
        ];
    }

    public static function brandTitle($value = null) {
        $title = trim((string)($value ?? 'Universe Civilization: Empire at Wars'));
        return $title !== '' ? $title : 'Universe Civilization: Empire at Wars';
    }

    public static function brandSubtitle($value = null) {
        $subtitle = trim((string)($value ?? 'Strategic command and empire operations across the universe'));
        return $subtitle !== '' ? $subtitle : 'Strategic command and empire operations across the universe';
    }
}
