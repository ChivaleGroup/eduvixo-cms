<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$project = dirname(__DIR__); $destination = $project . '/storage/marketplace';
$key = json_decode((string) file_get_contents($project . '/.cfg/marketplace-signing-key.json'), true, 16, JSON_THROW_ON_ERROR);
$secret = base64_decode((string) ($key['private_key'] ?? ''), true);
if (!is_string($secret) || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) throw new RuntimeException('Signing key is unavailable.');
$core = json_decode((string) file_get_contents($destination . '/core-release.json'), true, 16, JSON_THROW_ON_ERROR);
$catalog = (static fn(string $path): array => require $path)($project . '/config/marketplace.php');
$languages = [];
foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) $languages[$locale] = json_decode((string) file_get_contents($project . '/lang/' . $locale . '.json'), true, 64, JSON_THROW_ON_ERROR);
$lookup = static function (array $copy, string $path): string { foreach (explode('.', $path) as $part) $copy = $copy[$part] ?? []; return is_string($copy) ? $copy : ''; };
$products = [];
foreach ($catalog['packages'] as $id => $package) {
    $metaKeys = array_values((array) ($package['meta_keys'] ?? []));
    if (!array_filter($metaKeys, static fn(string $path): bool => str_ends_with($path, '_price'))) $metaKeys[] = 'marketplace.free';
    $copy = [];
    foreach ($languages as $locale => $language) $copy[$locale] = ['description' => $lookup($language, $package['copy_key']), 'meta' => array_map(static fn(string $path): string => $lookup($language, $path), $metaKeys)];
    $installable = !empty($package['system_installable']) && in_array($package['type'], ['theme', 'plugin', 'addon'], true);
    $paid = (bool) array_filter($metaKeys, static fn(string $path): bool => str_ends_with($path, '_price'));
    $products[] = ['id' => $id, 'type' => $package['type'], 'slug' => $package['slug'], 'name' => $package['name'], 'version' => $package['version'], 'channel' => $package['release_channel'] ?? 'stable', 'licensed' => !empty($package['license_download_enabled']), 'installable' => $installable, 'pricing' => $paid ? 'paid' : 'free', 'icon' => $package['icon'], 'package_url' => $installable ? 'https://www.eduvixo.com/api/marketplace/v1/package/?id=' . rawurlencode((string) $id) : null, 'package_checksum' => $installable ? $package['checksum'] : null, 'license' => ['product_name' => $package['license_product_name'] ?? $package['name'], 'product_model' => $package['license_product_model'] ?? 'Marketplace Extension', 'product_version' => $package['license_product_version'] ?? $package['version']], 'copy' => $copy];
}
$payload = json_encode(['schema' => 1, 'issued_at' => time(), 'expires_at' => time() + 31536000, 'core' => $core, 'products' => $products], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$document = json_encode(['signed_payload' => base64_encode($payload), 'signature' => base64_encode(sodium_crypto_sign_detached($payload, $secret))], JSON_THROW_ON_ERROR);
$temporary = $destination . '/official-catalog.json.tmp-' . bin2hex(random_bytes(6));
if (file_put_contents($temporary, $document, LOCK_EX) === false || !rename($temporary, $destination . '/official-catalog.json')) { @unlink($temporary); throw new RuntimeException('Official catalog could not be written.'); }
echo json_encode(['products' => count($products), 'languages' => count($languages)], JSON_THROW_ON_ERROR) . PHP_EOL;
