<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$base = 'https://www.eduvixo.com';
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); echo 'OK ' . $message . PHP_EOL; };
$request = static function (string $url): array {
    $curl = curl_init($url); if ($curl === false) throw new RuntimeException('Cannot initialize HTTP request.');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'Eduvixo-Marketplace-Filter-Audit/1.0']);
    $body = curl_exec($curl); $error = curl_error($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
    if (!is_string($body) || $error !== '') throw new RuntimeException('HTTP request failed: ' . $url . ' ' . $error);
    return [$status, $body];
};
foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) {
    [$status, $page] = $request($base . '/' . $locale . '/marketplace/');
    $assert($status === 200, 'Marketplace HTTP 200: ' . $locale);
    $assert(substr_count($page, 'data-marketplace-filter') === 1 && substr_count($page, 'data-marketplace-item') === 13, 'filter root and 13 products: ' . $locale);
    $assert(substr_count($page, 'data-marketplace-type-chip=') === 6 && substr_count($page, 'data-marketplace-price-chip=') === 3, 'category and price chips: ' . $locale);
    $assert(substr_count($page, 'data-filter-price="free"') + substr_count($page, 'data-filter-price="paid"') === 13, 'complete price classification: ' . $locale);
    $assert(str_contains($page, 'data-marketplace-query') && str_contains($page, 'data-marketplace-type') && str_contains($page, 'data-marketplace-price'), 'search and select controls: ' . $locale);
    $assert(!str_contains($page, 'Beta release. External providers require separate credentials and configuration.'), 'legacy beta notice removed: ' . $locale);
}
[$status, $js] = $request($base . '/assets/js/site.min.js');
$assert($status === 200 && str_contains($js, 'data-marketplace-filter') && str_contains($js, 'history.replaceState'), 'production dynamic filter JavaScript');
[$status, $css] = $request($base . '/assets/css/site.min.css');
$assert($status === 200 && str_contains($css, '.marketplace-discovery') && str_contains($css, '.marketplace-empty'), 'production responsive filter CSS');
[$status, $icons] = $request($base . '/assets/icons.svg');
$assert($status === 200 && str_contains($icons, 'id="search"'), 'production search icon');
foreach (['apache2', 'php8.4-fpm'] as $service) { exec('systemctl is-active ' . escapeshellarg($service), $output, $code); $assert($code === 0, $service . ' active'); }
echo json_encode(['ok' => true, 'languages' => 7, 'products' => 13], JSON_THROW_ON_ERROR) . PHP_EOL;
