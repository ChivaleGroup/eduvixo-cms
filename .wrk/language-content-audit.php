<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$locales = ['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'];
$errors = [];
$report = [];

$flatten = static function (array $data, string $prefix = '') use (&$flatten): array {
    $result = [];
    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) {
            $result += $flatten($value, $path);
            continue;
        }
        $result[$path] = $value;
    }
    return $result;
};

$placeholders = static function (string $value): array {
    preg_match_all('/(?:\{\{[^{}]+\}\}|%\d*\$?[a-z]|:[a-z][a-z0-9_]*)/iu', $value, $matches);
    $items = $matches[0];
    sort($items);
    return $items;
};

$translations = [];
foreach ($locales as $locale) {
    $file = $root . '/lang/' . $locale . '.json';
    try {
        $translations[$locale] = $flatten(json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR));
    } catch (Throwable $error) {
        $errors[] = $locale . ': invalid JSON: ' . $error->getMessage();
    }
}

if ($errors === []) {
    $reference = $translations['en'];
    foreach ($locales as $locale) {
        $values = $translations[$locale];
        $missing = array_diff_key($reference, $values);
        $extra = array_diff_key($values, $reference);
        if ($missing !== []) $errors[] = $locale . ': missing keys: ' . implode(', ', array_keys($missing));
        if ($extra !== []) $errors[] = $locale . ': extra keys: ' . implode(', ', array_keys($extra));

        $issues = ['empty' => 0, 'spacing' => 0, 'control' => 0, 'emdash' => 0, 'replacement' => 0, 'placeholder' => 0];
        foreach ($values as $path => $value) {
            if (!is_string($value)) {
                $errors[] = $locale . ':' . $path . ': expected string, got ' . get_debug_type($value);
                continue;
            }
            if (trim($value) === '') {
                $issues['empty']++;
                $errors[] = $locale . ':' . $path . ': empty value';
            }
            if ($value !== trim($value) || preg_match('/ {2,}/u', $value) === 1) {
                $issues['spacing']++;
                $errors[] = $locale . ':' . $path . ': invalid whitespace';
            }
            if (preg_match('/[\p{Cf}\p{Cc}]/u', $value) === 1) {
                $issues['control']++;
                $errors[] = $locale . ':' . $path . ': hidden control character';
            }
            if (str_contains($value, '—')) {
                $issues['emdash']++;
                $errors[] = $locale . ':' . $path . ': em dash';
            }
            if (str_contains($value, "\u{FFFD}")) {
                $issues['replacement']++;
                $errors[] = $locale . ':' . $path . ': Unicode replacement character';
            }
            if (isset($reference[$path]) && is_string($reference[$path]) && $placeholders($value) !== $placeholders($reference[$path])) {
                $issues['placeholder']++;
                $errors[] = $locale . ':' . $path . ': placeholder mismatch';
            }
        }

        foreach (['meta.home', 'meta.product', 'meta.services', 'meta.marketplace', 'meta.support', 'meta.docs', 'meta.faq', 'meta.knowledge-base', 'meta.updates', 'meta.contact', 'meta.privacy', 'meta.terms'] as $section) {
            foreach (['title', 'description'] as $field) {
                $path = $section . '.' . $field;
                if (!isset($values[$path]) || trim((string) $values[$path]) === '') $errors[] = $locale . ':' . $path . ': missing SEO content';
            }
        }
        foreach (['seo.keywords', 'seo.image_alt'] as $path) {
            if (!isset($values[$path]) || trim((string) $values[$path]) === '') $errors[] = $locale . ':' . $path . ': missing shared SEO content';
        }

        $report[$locale] = ['keys' => count($values), 'issues' => $issues];
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo json_encode(['ok' => true, 'languages' => count($locales), 'report' => $report], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
