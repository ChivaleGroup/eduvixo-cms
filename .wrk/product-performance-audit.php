<?php

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8892', '/');
$supported = ['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'];
$locales = isset($argv[2]) ? [$argv[2]] : $supported;
if (array_diff($locales, $supported) !== []) throw new InvalidArgumentException('Unsupported locale.');
$routes = ['', 'product/', 'services/', 'support/', 'support/docs/', 'support/faq/', 'support/knowledge-base/', 'updates/', 'privacy/', 'terms/'];
$errors = [];
$checked = 0;

$request = static function (string $url): array {
    $handle = curl_init($url);
    if ($handle === false) throw new RuntimeException('Cannot initialize HTTP audit.');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'Eduvixo performance audit']);
    $response = curl_exec($handle);
    if (!is_string($response)) throw new RuntimeException('HTTP audit failed: ' . curl_error($handle));
    $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    $result = ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'headers' => substr($response, 0, $headerSize), 'body' => substr($response, $headerSize)];
    curl_close($handle);
    return $result;
};

$assert = static function (bool $condition, string $message) use (&$errors): void {
    if (!$condition) $errors[] = $message;
};

foreach ($locales as $locale) {
    foreach ($routes as $route) {
        $path = '/' . $locale . '/' . $route;
        $response = $request($base . $path);
        $assert($response['status'] === 200, $path . ': HTTP ' . $response['status']);
        $assert(preg_match('/^Cache-Control:\s*private,\s*max-age=300,\s*stale-while-revalidate=60\s*$/mi', $response['headers']) === 1, $path . ': public-page cache policy');
        $assert(stripos($response['headers'], 'Set-Cookie: eduvixo_site=') === false, $path . ': unexpected PHP session');
        $assert(!str_contains($response['body'], 'name="csrf"'), $path . ': unexpected CSRF state');
        $checked++;
    }

    foreach (['contact/', 'marketplace/'] as $route) {
        $path = '/' . $locale . '/' . $route;
        $response = $request($base . $path);
        $assert($response['status'] === 200, $path . ': HTTP ' . $response['status']);
        $assert(preg_match('/^Cache-Control:\s*private,\s*no-store,\s*no-cache,\s*must-revalidate,\s*max-age=0\s*$/mi', $response['headers']) === 1, $path . ': sensitive-page no-store policy');
        $assert(stripos($response['headers'], 'Set-Cookie: eduvixo_site=') !== false, $path . ': secure session not started');
        $assert(str_contains($response['body'], 'name="csrf"'), $path . ': CSRF token missing');
        $checked++;
    }

    $product = $request($base . '/' . $locale . '/product/');
    $assert(!str_contains($product['body'], 'metric-grid') && !str_contains($product['body'], 'product.metrics'), '/' . $locale . '/product/: obsolete counters are visible');
    $assert(str_contains($product['body'], 'product-modules'), '/' . $locale . '/product/: product modules section missing');
}

$css = $request($base . '/assets/css/site.min.css');
$js = $request($base . '/assets/js/site.min.js');
$assert($css['status'] === 200 && !str_contains($css['body'], '.metric-grid'), 'compiled CSS still contains metric styles');
$assert($js['status'] === 200 && str_contains($js['body'], "'PerformanceObserver'in window") && str_contains($js['body'], "'web_vital'"), 'consent-aware Web Vitals monitor missing');

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo json_encode(['ok' => true, 'base' => $base, 'languages' => count($locales), 'routes' => $checked, 'product_counters' => false, 'public_session' => false, 'sensitive_cache' => 'no-store', 'web_vitals' => ['LCP', 'INP', 'CLS']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
