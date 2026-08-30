<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$web = '/var/www/clients/client9/web123/web'; $base = 'https://www.eduvixo.com';
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); echo 'OK ' . $message . PHP_EOL; };
$request = static function (string $url): array {
    $curl = curl_init($url); if ($curl === false) throw new RuntimeException('Cannot initialize HTTP request.');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'Eduvixo-AI-Translation-Audit/1.0']);
    $response = curl_exec($curl); $error = curl_error($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE); curl_close($curl);
    if (!is_string($response) || $error !== '') throw new RuntimeException('HTTP request failed: ' . $url);
    return [$status, substr($response, 0, $headerSize), substr($response, $headerSize)];
};
$packageName = 'ai-translation-assistant-1.0.0-beta.1.zip'; $package = $web . '/storage/marketplace/packages/' . $packageName;
$assert(is_file($package) && filesize($package) === 15628 && hash_equals('d82f312c24037509814323371ce63dd52cd9037e4ed2a347d67f9a98c4ca7c72', hash_file('sha256', $package)), 'signed AI Translation package integrity');
$assert((fileperms($package) & 0777) === 0640 && fileowner($package) === fileowner($web . '/public/index.php'), 'private package permissions and owner');
[$status] = $request($base . '/storage/marketplace/packages/' . $packageName); $assert($status === 403, 'direct package URL denied');
[$status, , $rawCatalog] = $request($base . '/api/marketplace/v1/official/');
$document = json_decode($rawCatalog, true, 8, JSON_THROW_ON_ERROR); $payload = base64_decode((string) ($document['signed_payload'] ?? ''), true); $signature = base64_decode((string) ($document['signature'] ?? ''), true); $public = base64_decode('q+WweIoNkskiUOzyLl80Bc9V2TkBdHXXrtOufSRIg54=', true);
$assert($status === 200 && is_string($payload) && is_string($signature) && is_string($public) && sodium_crypto_sign_verify_detached($signature, $payload, $public), 'official catalog endpoint and signature');
$catalog = json_decode($payload, true, 64, JSON_THROW_ON_ERROR); $products = (array) ($catalog['products'] ?? []);
$translation = array_values(array_filter($products, static fn(array $product): bool => ($product['name'] ?? '') === 'AI Translation Assistant'));
$assert(count($products) === 13 && count($translation) === 1 && !empty($translation[0]['licensed']), '13-product catalog with licensed AI Translation entry');
$assert(($translation[0]['copy']['en']['meta'][0] ?? '') === 'Free' && ($translation[0]['channel'] ?? '') === 'beta', 'Free Beta catalog metadata');
foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) {
    [$status, , $page] = $request($base . '/' . $locale . '/marketplace/');
    $assert($status === 200 && substr_count($page, 'id="package-') === 13, 'Marketplace product count: ' . $locale);
    $assert(substr_count($page, 'data-license-download') === 14, 'licensed download controls: ' . $locale);
    $assert(str_contains($page, 'data-package="ab80e3241f74ffa8f0d554f6ddf2b47a"') && str_contains($page, 'AI Translation Assistant'), 'AI Translation card and lock: ' . $locale);
    $assert(str_contains($page, htmlspecialchars((string) ($translation[0]['copy'][$locale]['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')), 'localized AI Translation description: ' . $locale);
}
[$status, , $icons] = $request($base . '/assets/icons.svg'); $assert($status === 200 && str_contains($icons, 'id="languages"'), 'Marketplace language icon asset');
foreach (['apache2', 'php8.4-fpm'] as $service) { exec('systemctl is-active ' . escapeshellarg($service), $output, $code); $assert($code === 0, $service . ' active'); }
echo json_encode(['ok' => true, 'products' => count($products), 'languages' => 7, 'licensed_controls_per_language' => 14], JSON_THROW_ON_ERROR) . PHP_EOL;
