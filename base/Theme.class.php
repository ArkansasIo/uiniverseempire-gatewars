<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Universe Civilization : Empire at wars
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
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
