<?php

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8892', '/');
$locales = ['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'];
if (isset($argv[2])) {
    if (!in_array($argv[2], $locales, true)) throw new InvalidArgumentException('Unsupported locale.');
    $locales = [$argv[2]];
}
$routes = ['', 'product/', 'services/', 'marketplace/', 'support/', 'support/docs/', 'support/faq/', 'support/knowledge-base/', 'updates/', 'contact/', 'privacy/', 'terms/'];
$errors = [];
$checked = 0;
$context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 20, 'user_agent' => 'Eduvixo language route audit']]);

foreach ($locales as $locale) {
    foreach ($routes as $route) {
        $url = $base . '/' . $locale . '/' . $route;
        $body = @file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) $status = (int) $match[1];
        }
        $prefix = $locale . '/' . $route;
        if ($status !== 200 || !is_string($body)) {
            $errors[] = $prefix . ': HTTP ' . $status;
            continue;
        }
        foreach ([
            '/<html\s+lang="' . preg_quote($locale, '/') . '"/u' => 'html language',
            '/<meta\s+name="description"\s+content="[^"]+"/u' => 'meta description',
            '/<meta\s+name="keywords"\s+content="[^"]+"/u' => 'meta keywords',
            '/<meta\s+property="og:title"\s+content="[^"]+"/u' => 'Open Graph title',
            '/<meta\s+property="og:description"\s+content="[^"]+"/u' => 'Open Graph description',
            '/<meta\s+property="og:image"\s+content="[^"]+"/u' => 'Open Graph image',
            '/<meta\s+name="twitter:card"\s+content="summary_large_image"/u' => 'Twitter card',
            '/<link\s+rel="canonical"\s+href="[^"]+\/' . preg_quote($locale, '/') . '\//u' => 'canonical URL',
            '/<script\s+type="application\/ld\+json"/u' => 'structured data',
            '/<\/html>/u' => 'complete document',
        ] as $pattern => $label) {
            if (preg_match($pattern, $body) !== 1) $errors[] = $prefix . ': missing ' . $label;
        }
        foreach ($locales as $alternate) {
            if (!str_contains($body, 'hreflang="' . $alternate . '"')) $errors[] = $prefix . ': missing hreflang ' . $alternate;
        }
        $checked++;
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo json_encode(['ok' => true, 'base' => $base, 'routes' => $checked, 'languages' => count($locales)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
