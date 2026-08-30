<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$web = '/var/www/clients/client9/web123/web';
$base = 'https://www.eduvixo.com';
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); echo 'OK ' . $message . PHP_EOL; };
$request = static function (string $url): array {
    $curl = curl_init($url);
    if ($curl === false) throw new RuntimeException('Cannot initialize HTTP request.');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'Eduvixo-Production-Audit/1.0']);
    $response = curl_exec($curl); $error = curl_error($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE); curl_close($curl);
    if (!is_string($response) || $error !== '') throw new RuntimeException('HTTP request failed: ' . $url);
    return [$status, substr($response, 0, $headerSize), substr($response, $headerSize)];
};
$package = $web . '/storage/marketplace/packages/google-analytics-1.0.0.zip';
$assert(is_file($package) && filesize($package) === 8845 && hash_equals('68ddbc291b03e87afbaeb4ac2fef1d966b1ca982edc40ff9d5d38e15b9ad4c1f', hash_file('sha256', $package)), 'signed Google Analytics package integrity');
$assert((fileperms($package) & 0777) === 0640 && fileowner($package) === fileowner($web . '/public/index.php'), 'private package permissions and owner');
[$status] = $request($base . '/storage/marketplace/packages/google-analytics-1.0.0.zip');
$assert($status === 403, 'direct package URL denied');
[$status, , $rawCatalog] = $request($base . '/api/marketplace/v1/official/');
$document = json_decode($rawCatalog, true, 8, JSON_THROW_ON_ERROR); $payload = base64_decode((string) ($document['signed_payload'] ?? ''), true); $signature = base64_decode((string) ($document['signature'] ?? ''), true); $public = base64_decode('q+WweIoNkskiUOzyLl80Bc9V2TkBdHXXrtOufSRIg54=', true);
$assert($status === 200 && is_string($payload) && is_string($signature) && is_string($public) && sodium_crypto_sign_verify_detached($signature, $payload, $public), 'official catalog endpoint and signature');
$catalog = json_decode($payload, true, 64, JSON_THROW_ON_ERROR); $products = (array) ($catalog['products'] ?? []);
$googleAnalytics = array_values(array_filter($products, static fn(array $product): bool => ($product['name'] ?? '') === 'Google Analytics'));
$assert(count($products) === 12 && count($googleAnalytics) === 1 && !empty($googleAnalytics[0]['licensed']), '12-product catalog with licensed Google Analytics entry');
foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) {
    [$status, , $page] = $request($base . '/' . $locale . '/marketplace/');
    $assert($status === 200 && substr_count($page, 'id="package-') === 12, 'Marketplace product count: ' . $locale);
    $assert(substr_count($page, 'data-license-download') === 13, 'licensed download controls: ' . $locale);
    $assert(str_contains($page, 'data-package="56b33a4022d3ae4e11150c080f3e6189"') && str_contains($page, 'data-package="c42137f830b6a10e8896a57eddfe6aee"'), 'theme and Google Analytics locks: ' . $locale);
    $assert(str_contains($page, 'data-variant="x64"') && str_contains($page, 'data-variant="x86"'), 'licensed Windows variants: ' . $locale);
}
[$status, $headers, $home] = $request($base . '/en/');
$assert($status === 200 && str_contains($home, 'data-analytics-consent') && str_contains($home, 'data-analytics-id="G-CCZKQZHM4S"'), 'website Analytics consent and property configuration');
$assert(!str_contains($home, 'googletagmanager.com/gtag/js?id=G-CCZKQZHM4S'), 'no Google Analytics loader before consent');
$assert(str_contains(strtolower($headers), 'content-security-policy:') && str_contains($headers, 'https://www.googletagmanager.com'), 'Analytics-compatible Content Security Policy');
echo json_encode(['ok' => true, 'products' => count($products), 'languages' => 7, 'licensed_controls_per_language' => 13], JSON_THROW_ON_ERROR) . PHP_EOL;
