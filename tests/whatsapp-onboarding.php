<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/WhatsAppOnboardingService.php';

use Eduvixo\Website\WhatsAppOnboardingService;

$root = sys_get_temp_dir() . '/eduvixo-whatsapp-' . bin2hex(random_bytes(6));
$calls = [];
$http = static function (string $method, string $url, array $headers, ?string $body) use (&$calls): array {
    $calls[] = [$method, preg_replace('/client_secret=[^&]+/', 'client_secret=REDACTED', $url), $headers];
    if ($url === 'https://license.example.test/') return [200, '{"data":{"valid":true}}'];
    if (str_contains($url, '/oauth/access_token?')) return [200, '{"access_token":"test-access-token-with-sufficient-length"}'];
    if (str_ends_with($url, '/12345678901/subscribed_apps')) return [200, '{"success":true}'];
    if (str_ends_with($url, '/10987654321?fields=is_on_biz_app,platform_type')) return [200, '{"is_on_biz_app":true,"platform_type":"CLOUD_API"}'];
    return [500, '{"error":{"message":"unexpected test request"}}'];
};
$cfg = ['app_id' => '12345678901', 'config_id' => '123456789012345', 'app_secret' => 'test-meta-secret-with-enough-length', 'webhook_token' => 'test_webhook_token_123456789', 'storage' => $root];
$service = new WhatsAppOnboardingService($cfg, ['endpoint' => 'https://license.example.test/'], 'https://www.eduvixo.com', str_repeat('a', 64), $http);
$server = ['HTTP_AUTHORIZATION' => 'Bearer ABCDEFGHIJKLMNOPQRSTUVWXYZ123456', 'HTTP_X_EDUVIXO_DOMAIN' => 'https://school.example.edu', 'HTTP_X_EDUVIXO_VERSION' => '1.0'];
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$state = static function (array $start): string { parse_str((string) parse_url((string) $start['url'], PHP_URL_QUERY), $query); return (string) ($query['state'] ?? ''); };

try {
    $assert($service->ready(), 'Configured service must be ready.');
    $start = $service->start($server); $session = $service->begin($state($start));
    $assert($session['domain'] === 'https://school.example.edu', 'Installation domain was not retained.');
    try { $service->begin($state($start)); throw new RuntimeException('Start token was reusable.'); } catch (RuntimeException $error) { $assert($error->getCode() === 410, 'Reused start token did not fail closed.'); }
    $done = $service->complete($session, ['code' => 'meta_exchange_code_abcdefghijklmnopqrstuvwxyz', 'event' => 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING', 'data' => ['waba_id' => '12345678901', 'phone_number_id' => '10987654321', 'business_id' => '11223344556']]);
    parse_str((string) parse_url($done['return_url'], PHP_URL_QUERY), $return); $claim = (string) ($return['claim'] ?? '');
    $assert($claim !== '' && !str_contains($done['return_url'], 'test-access-token'), 'Browser return URL exposed credentials.');
    $files = glob($root . '/claims/*.json') ?: []; $assert(count($files) === 1, 'Encrypted claim was not stored.'); $assert(!str_contains((string) file_get_contents($files[0]), 'test-access-token'), 'Credential was stored in plaintext.');
    $result = $service->claim($server, $claim); $assert($result['access_token'] === 'test-access-token-with-sufficient-length' && $result['api_version'] === 'v26.0', 'Claim did not return the expected credentials.');
    try { $service->claim($server, $claim); throw new RuntimeException('Claim token was reusable.'); } catch (RuntimeException $error) { $assert($error->getCode() === 410, 'Reused claim token did not fail closed.'); }
    $otherStart=$service->start($server);$otherSession=$service->begin($state($otherStart));$otherDone=$service->complete($otherSession,['code'=>'meta_exchange_code_zyxwvutsrqponmlkjihgfedcba','event'=>'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING','data'=>['waba_id'=>'12345678901','phone_number_id'=>'10987654321']]);parse_str((string)parse_url($otherDone['return_url'],PHP_URL_QUERY),$otherReturn);
    try { $service->claim(array_replace($server,['HTTP_X_EDUVIXO_DOMAIN'=>'https://other-school.example.edu']),(string)$otherReturn['claim']); throw new RuntimeException('Cross-installation claim was accepted.'); } catch (RuntimeException $error) { $assert($error->getCode()===403,'Cross-installation claim did not fail closed.'); }
    $assert($service->verifyWebhook('subscribe', $cfg['webhook_token'], 'challenge_123') === 'challenge_123', 'Webhook challenge failed.');
    $webhook = '{"object":"whatsapp_business_account","entry":[]}'; $service->acceptWebhook($webhook, 'sha256=' . hash_hmac('sha256', $webhook, $cfg['app_secret']));
    try { $service->acceptWebhook($webhook, 'sha256=' . str_repeat('0', 64)); throw new RuntimeException('Invalid webhook signature was accepted.'); } catch (RuntimeException $error) { $assert($error->getCode() === 403, 'Invalid webhook signature did not fail closed.'); }
    try { $service->start(array_replace($server, ['HTTP_X_EDUVIXO_DOMAIN' => 'https://user:pass@school.example.edu'])); throw new RuntimeException('Credential-bearing installation URL was accepted.'); } catch (RuntimeException $error) { $assert($error->getCode() === 401, 'Unsafe installation URL did not fail closed.'); }
    $assert(count(array_filter($calls, static fn(array $call): bool => str_contains($call[1], 'client_secret=REDACTED'))) === 2, 'Each OAuth code was exchanged exactly once.');
    echo "WhatsApp onboarding security tests passed.\n";
} finally {
    if (is_dir($root)) { $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($root); }
}
