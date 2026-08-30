<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo 'PASS ' . $message . PHP_EOL;
};
$marketplace = require $root . '/config/marketplace.php';
$products = (array) ($marketplace['packages'] ?? []);
$matches = array_filter($products, static fn(array $item): bool => ($item['slug'] ?? '') === 'ai-translation-assistant');
$assert(count($products) === 13 && count($matches) === 1, '13-product Marketplace includes one AI Translation Assistant');
$product = reset($matches);
$assert(($product['version'] ?? '') === '1.0.0-beta.1' && ($product['release_channel'] ?? '') === 'beta', 'AI Translation Assistant Beta version');
$assert(!empty($product['license_download_enabled']) && empty($product['browser_enabled']) && empty($product['meta_keys']), 'free product uses protected licensed download');

$fileCount = 0;
foreach ($products as $item) {
    $files = isset($item['variants']) ? array_column($item['variants'], 'file') : [$item['file'] ?? null];
    foreach ($files as $file) {
        if (!is_string($file) || $file === '') continue;
        $fileCount++;
        $assert(is_file($file), 'package exists: ' . basename($file));
        $expected = isset($item['variants']) ? null : $item;
        if ($expected) $assert(filesize($file) === $expected['size'] && hash_file('sha256', $file) === $expected['checksum'], 'package integrity: ' . basename($file));
    }
}
$assert($fileCount === 14, '14 downloadable Marketplace files');

$packageFile = (string) $product['file'];
$zip = new ZipArchive();
$assert($zip->open($packageFile) === true, 'signed AI Translation package opens');
$raw = $zip->getFromName('eduvixo-package.json'); $signature = $zip->getFromName('signature.ed25519');
$manifest = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : [];
$key = json_decode((string) file_get_contents($root . '/.cfg/marketplace-signing-key.json'), true, 16, JSON_THROW_ON_ERROR);
$public = base64_decode((string) ($key['public_key'] ?? ''), true);
$assert(is_string($raw) && is_string($signature) && is_string($public) && sodium_crypto_sign_verify_detached(base64_decode($signature, true), $raw, $public), 'publisher signature verifies');
$assert(($manifest['slug'] ?? '') === 'ai-translation-assistant' && ($manifest['version'] ?? '') === '1.0.0-beta.1' && ($manifest['release_channel'] ?? '') === 'beta', 'signed manifest identity');
$assert(($manifest['license']['model'] ?? '') === 'free' && (float) ($manifest['license']['price'] ?? -1) === 0.0, 'signed manifest declares Free license');
$assert(count((array) ($manifest['files'] ?? [])) === 7, 'signed payload contains seven expected files');
foreach ((array) ($manifest['files'] ?? []) as $relative => $checksum) {
    $payload = $zip->getFromName('payload/' . $relative);
    $assert(is_string($payload) && hash('sha256', $payload) === $checksum, 'payload integrity: ' . $relative);
}
$zip->close();

foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) {
    $copy = json_decode((string) file_get_contents($root . '/lang/' . $locale . '.json'), true, 64, JSON_THROW_ON_ERROR);
    $assert(trim((string) ($copy['marketplace']['ai_translation_copy'] ?? '')) !== '' && trim((string) ($copy['marketplace']['free'] ?? '')) !== '', 'localized product copy and Free label: ' . $locale);
}

$catalogDocument = json_decode((string) file_get_contents($root . '/storage/marketplace/official-catalog.json'), true, 16, JSON_THROW_ON_ERROR);
$catalogPayload = base64_decode((string) ($catalogDocument['signed_payload'] ?? ''), true);
$catalogSignature = base64_decode((string) ($catalogDocument['signature'] ?? ''), true);
$assert(is_string($catalogPayload) && is_string($catalogSignature) && sodium_crypto_sign_verify_detached($catalogSignature, $catalogPayload, $public), 'official catalog signature verifies');
$catalog = json_decode($catalogPayload, true, 64, JSON_THROW_ON_ERROR);
$catalogProducts = (array) ($catalog['products'] ?? []);
$catalogMatch = array_values(array_filter($catalogProducts, static fn(array $item): bool => ($item['name'] ?? '') === 'AI Translation Assistant'));
$assert(count($catalogProducts) === 13 && count($catalogMatch) === 1, 'official catalog contains 13 products and AI Translation Assistant');
$assert(!empty($catalogMatch[0]['licensed']) && ($catalogMatch[0]['copy']['en']['meta'][0] ?? '') === 'Free', 'catalog product is Free and license-gated');

$source = '';
foreach (glob($root . '/.plugins/EduvixoAITranslationAssistant/source/{*.php,*.json,src/*.php,views/*.php,assets/*}', GLOB_BRACE) ?: [] as $path) if (is_file($path)) $source .= (string) file_get_contents($path);
$assert(!str_contains($source, 'G-CCZKQZHM4S') && !str_contains($source, 'api_key_encrypted":"'), 'plugin source contains no site identifier or embedded provider credential');
echo 'AI Translation Assistant Marketplace audit passed.' . PHP_EOL;
