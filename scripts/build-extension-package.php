<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
if ($argc !== 4) exit("Usage: php scripts/build-extension-package.php <source> <output> <type>\n");
[$script, $source, $output, $type] = $argv;
$source = realpath($source);
if (!$source || !is_dir($source) || !in_array($type, ['addon', 'plugin', 'theme'], true)) throw new RuntimeException('Package source or type is invalid.');
$runtimeName = ['addon' => 'addon.json', 'plugin' => 'plugin.json', 'theme' => 'theme.json'][$type];
$runtime = json_decode((string) file_get_contents($source . DIRECTORY_SEPARATOR . $runtimeName), true, 64, JSON_THROW_ON_ERROR);
foreach (['name', 'slug', 'version'] as $field) if (!is_string($runtime[$field] ?? null) || trim($runtime[$field]) === '') throw new RuntimeException('Runtime manifest is missing ' . $field . '.');
$root = dirname(__DIR__); $keyFile = $root . '/.cfg/marketplace-signing-key.json';
if (!is_file($keyFile)) {
    $pair = sodium_crypto_sign_keypair();
    $key = ['key_id' => 'chivale-eduvixo-2026', 'publisher' => 'QUANT Software House', 'website' => 'https://www.ittsp.com/', 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'private_key' => base64_encode(sodium_crypto_sign_secretkey($pair)), 'created_at' => gmdate(DATE_ATOM)];
    if (file_put_contents($keyFile, json_encode($key, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Signing key could not be created.');
}
$key = json_decode((string) file_get_contents($keyFile), true, 16, JSON_THROW_ON_ERROR);
$secret = base64_decode((string) ($key['private_key'] ?? ''), true);
if (!is_string($secret) || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) throw new RuntimeException('Signing key is invalid.');
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->isLink()) continue;
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
    if ($relative === '' || str_contains($relative, '..') || preg_match('#(^|/)(?:\.htaccess|\.user\.ini|\.env|web\.config)$#i', $relative) || preg_match('/\.(?:phar|exe|dll|so|dylib|sh|bat|cmd|ps1)$/i', $relative)) throw new RuntimeException('Prohibited package file: ' . $relative);
    $files[$relative] = hash_file('sha256', $file->getPathname());
}
ksort($files);
$manifest = [
    'schema' => 1, 'type' => $type, 'slug' => $runtime['slug'], 'name' => $runtime['name'], 'version' => $runtime['version'],
    'description' => (string) ($runtime['description'] ?? ''), 'engine' => (string) ($runtime['compatible_engine_version'] ?? '^1.0'),
    'publisher' => ['name' => (string) $key['publisher'], 'key_id' => (string) $key['key_id'], 'website' => (string) $key['website']],
    'permissions' => array_values((array) ($runtime['permissions'] ?? [])), 'dependencies' => array_values((array) ($runtime['dependencies'] ?? [])),
    'migrations' => array_values((array) ($runtime['migrations'] ?? [])), 'release_channel' => (string) ($runtime['release_channel'] ?? 'stable'),
    'license' => (array) ($runtime['license'] ?? []), 'files' => $files,
    'navigation' => (array) ($runtime['navigation'] ?? []), 'config_url' => (string) ($runtime['config_url'] ?? ''),
];
$raw = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
$signature = base64_encode(sodium_crypto_sign_detached($raw, $secret));
$directory = dirname($output); if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Output directory could not be created.');
if (is_file($output) && !unlink($output)) throw new RuntimeException('Previous package could not be replaced.');
$zip = new ZipArchive(); if ($zip->open($output, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Package archive could not be created.');
try {
    $zip->addFromString('eduvixo-package.json', $raw); $zip->addFromString('signature.ed25519', $signature);
    foreach (array_keys($files) as $relative) if (!$zip->addFile($source . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative), 'payload/' . $relative)) throw new RuntimeException('Package file could not be added: ' . $relative);
} finally { $zip->close(); }
echo json_encode(['file' => realpath($output), 'size' => filesize($output), 'sha256' => hash_file('sha256', $output), 'key_id' => $key['key_id'], 'public_key' => $key['public_key']], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
