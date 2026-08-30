<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Mailer.php';
require dirname(__DIR__) . '/app/ContactService.php';
require dirname(__DIR__) . '/app/MarketplaceService.php';

use Eduvixo\Website\ContactService;
use Eduvixo\Website\Mailer;
use Eduvixo\Website\MarketplaceService;

$root = dirname(__DIR__);
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$config = require $root . '/config/site.php';
$assert(preg_match('/^[a-f0-9]{64}$/D', (string) $config['rate_key']) === 1, 'Runtime security key format is invalid.');
$assert(!hash_equals((string) $config['rate_key'], hash('sha256', (string) $config['root'])), 'Predictable fallback security key remains active.');

$temporary = sys_get_temp_dir() . '/eduvixo-security-' . bin2hex(random_bytes(6));
if (!mkdir($temporary . '/storage/marketplace/packages', 0750, true)) throw new RuntimeException('Cannot create isolated security test storage.');

try {
    $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
    $contact = new ContactService(['root' => $temporary, 'rate_key' => str_repeat('a', 64)], new Mailer([]));
    $contactRate = new ReflectionMethod($contact, 'consumeRate');
    $assert($contactRate->invoke($contact) === true, 'Contact limiter rejected its first request.');
    $assert($contactRate->invoke($contact) === false, 'Contact minimum interval is not enforced atomically.');

    $marketConfig = ['package_root' => $temporary . '/storage/marketplace/packages', 'license_failure_limit' => 3, 'packages' => []];
    $marketplace = new MarketplaceService($marketConfig, 'https://www.eduvixo.com', str_repeat('b', 64));
    $marketRate = new ReflectionMethod($marketplace, 'rate');
    $marketRate->invoke($marketplace, 'test', 'subject', 2, 3600, 0);
    $marketRate->invoke($marketplace, 'test', 'subject', 2, 3600, 0);
    try { $marketRate->invoke($marketplace, 'test', 'subject', 2, 3600, 0); $assert(false, 'Marketplace limiter did not reject the request limit.'); }
    catch (ReflectionException $error) { throw $error; }
    catch (Throwable $error) { $assert($error instanceof RuntimeException && $error->getCode() === 429, 'Marketplace limiter returned an unexpected failure.'); }

    $attemptDirectory = $temporary . '/storage/marketplace/license-attempts';
    if (!mkdir($attemptDirectory, 0750, true)) throw new RuntimeException('Cannot create isolated license state storage.');
    file_put_contents($attemptDirectory . '/' . hash_hmac('sha256', '192.0.2.10', str_repeat('b', 64)) . '.json', '{broken', LOCK_EX);
    $licenseState = (new ReflectionMethod($marketplace, 'browserLicenseState'))->invoke($marketplace, '192.0.2.10');
    $assert(!empty($licenseState['locked']), 'Corrupt license-attempt state does not fail closed.');
} finally {
    $remove = static function (string $path) use (&$remove): void {
        if (is_dir($path) && !is_link($path)) { foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $name) $remove($path . '/' . $name); @rmdir($path); }
        elseif (is_file($path) || is_link($path)) @unlink($path);
    };
    $remove($temporary);
}

$site = (string) file_get_contents($root . '/app/Site.php');
$access = (string) file_get_contents($root . '/public/.htaccess');
$rootAccess = (string) file_get_contents($root . '/.htaccess');
$assert(str_contains($site, "ini_set('session.use_strict_mode', '1')"), 'Strict session mode is absent.');
$assert(str_contains($site, "object-src 'none'") && str_contains($site, 'upgrade-insecure-requests'), 'CSP hardening is incomplete.');
$assert(str_contains($access, 'LimitRequestBody 65536') && str_contains($access, '[R=405,L]') && str_contains($access, '[R=413,L]'), 'Apache request limits are incomplete.');
$assert(str_contains($rootAccess, 'LimitRequestBody 65536') && str_contains($rootAccess, '[R=413,L]'), 'Fallback-document-root request-body limit is absent.');
$assert(str_contains($access, 'Strict-Transport-Security') && str_contains($access, 'Cross-Origin-Opener-Policy'), 'Apache security headers are incomplete.');
$assert(is_file($root . '/public/.well-known/security.txt'), 'security.txt is missing.');

echo json_encode(['ok' => true, 'rate_key' => 'random', 'contact_limiter' => 'atomic', 'marketplace_limiter' => 'atomic', 'corrupt_state' => 'fail-closed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
