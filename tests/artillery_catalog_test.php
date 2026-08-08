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
require __DIR__ . '/../modules/artillery_lib.php';

$types = art_types();
if (count($types) !== 5) {
    fwrite(STDERR, "art_types failed: expected 5 types, got " . count($types) . "\n");
    exit(1);
}
foreach ($types as $type => $subs) {
    if (count($subs) !== 2) {
        fwrite(STDERR, "art_types failed: '{$type}' should have 2 subtypes\n");
        exit(1);
    }
}

$offense = art_offenseClasses();
$defense = art_defenseClasses();
if (count($offense) !== 9 || count($defense) !== 9) {
    fwrite(STDERR, "class lists failed: offense=" . count($offense) . " defense=" . count($defense) . "\n");
    exit(1);
}
foreach (array_merge($offense, $defense) as $class => $subs) {
    if (count($subs) !== 2) {
        fwrite(STDERR, "class '{$class}' should have 2 sub-classes\n");
        exit(1);
    }
}

$catalog = art_buildCatalog();
if (count($catalog) !== 180) {
    fwrite(STDERR, "catalog size failed: expected 180, got " . count($catalog) . "\n");
    exit(1);
}
$off = 0;
$def = 0;
$codes = [];
$ids = [];
foreach ($catalog as $art) {
    if ($art['major_class'] === 'offense') { $off++; } else { $def++; }
    $codes[$art['artillery_code']] = true;
    $ids[$art['artillery_id']] = true;

    if ($art['tier'] < 1 || $art['power_rating'] <= 0) {
        fwrite(STDERR, "bad tier/power for {$art['artillery_code']}\n");
        exit(1);
    }
    if ($art['attack_stat'] <= 0 || $art['attack_sub'] <= 0 ||
        $art['defense_stat'] <= 0 || $art['defense_sub'] <= 0 ||
        $art['shield_stat'] <= 0 || $art['shield_sub'] <= 0 ||
        $art['accuracy_stat'] <= 0 || $art['range_stat'] <= 0) {
        fwrite(STDERR, "bad stats/substats for {$art['artillery_code']}\n");
        exit(1);
    }
    $attrs = json_decode($art['attributes'], true);
    if (!is_array($attrs) || count($attrs) < 10) {
        fwrite(STDERR, "bad attributes for {$art['artillery_code']}\n");
        exit(1);
    }
    foreach ($attrs as $a) {
        if (empty($a['name']) || !isset($a['value'])) {
            fwrite(STDERR, "bad attribute entry for {$art['artillery_code']}\n");
            exit(1);
        }
    }
    if ($art['naq_cost'] <= 0 || $art['metal_cost'] <= 0 || $art['crystal_cost'] <= 0 || $art['deut_cost'] <= 0) {
        fwrite(STDERR, "bad costs for {$art['artillery_code']}\n");
        exit(1);
    }
    if ($art['major_class'] === 'offense' && $art['attack_convert'] <= 0) {
        fwrite(STDERR, "offense piece missing attack conversion: {$art['artillery_code']}\n");
        exit(1);
    }
    if ($art['major_class'] === 'defense' && $art['defense_convert'] <= 0) {
        fwrite(STDERR, "defense piece missing defense conversion: {$art['artillery_code']}\n");
        exit(1);
    }
}
if ($off !== 90 || $def !== 90) {
    fwrite(STDERR, "catalog split failed: offense=$off defense=$def\n");
    exit(1);
}
if (count($codes) !== 180 || count($ids) !== 180) {
    fwrite(STDERR, "catalog codes/ids are not unique\n");
    exit(1);
}

$attrs = art_buildAttributes('defense', 2, 0);
if (count($attrs) !== 10) {
    fwrite(STDERR, "art_buildAttributes defense count failed\n");
    exit(1);
}
$attrsOff = art_buildAttributes('offense', 2, 0);
if (count($attrsOff) !== 11) {
    fwrite(STDERR, "art_buildAttributes offense count failed\n");
    exit(1);
}

echo "artillery catalog checks passed (180 pieces: 90 offense / 90 defense)\n";
