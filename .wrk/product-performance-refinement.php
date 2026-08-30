<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$locales = ['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'];
$reference = null;

$flatten = static function (array $data, string $prefix = '') use (&$flatten): array {
    $result = [];
    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) $result += $flatten($value, $path);
        else $result[$path] = $value;
    }
    return $result;
};

foreach ($locales as $locale) {
    $file = $root . '/lang/' . $locale . '.json';
    $copy = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (!isset($copy['product']['metrics']) || !is_array($copy['product']['metrics'])) throw new RuntimeException($locale . ': product metrics are missing.');
    unset($copy['product']['metrics']);
    $flat = $flatten($copy);
    if (count($flat) !== 585) throw new RuntimeException($locale . ': unexpected translation-key count after removing metrics.');
    if ($reference === null) $reference = array_keys($flat);
    elseif (array_keys($flat) !== $reference) throw new RuntimeException($locale . ': translation-key parity failed.');
    file_put_contents($file, json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL, LOCK_EX);
    echo $locale . ": removed 8 obsolete metric values\n";
}
