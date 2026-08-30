<?php

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8892', '/');
$production = ($argv[2] ?? 'local') === 'production';
$errors = [];
$request = static function (string $url, string $method = 'GET', string $body = ''): array {
    $curl = curl_init($url);
    if ($curl === false) throw new RuntimeException('Cannot initialize security audit request.');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_POSTFIELDS => $body, CURLOPT_USERAGENT => 'Eduvixo-Security-Audit/1.0']);
    $response = curl_exec($curl);
    if (!is_string($response)) throw new RuntimeException('Security audit request failed: ' . curl_error($curl));
    $size = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $result = ['status' => (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'headers' => substr($response, 0, $size), 'body' => substr($response, $size)];
    curl_close($curl);
    return $result;
};
$assert = static function (bool $condition, string $message) use (&$errors): void { if (!$condition) $errors[] = $message; };
$header = static fn(array $response, string $name): bool => preg_match('/^' . preg_quote($name, '/') . ':\s*.+$/mi', $response['headers']) === 1;

foreach (['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'] as $locale) {
    $response = $request($base . '/' . $locale . '/');
    $assert($response['status'] === 200, $locale . ': home HTTP ' . $response['status']);
    foreach (['Strict-Transport-Security', 'Cross-Origin-Opener-Policy', 'X-Permitted-Cross-Domain-Policies', 'X-DNS-Prefetch-Control', 'X-Download-Options', 'X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Permissions-Policy'] as $name) $assert($header($response, $name), $locale . ': missing ' . $name);
    $assert(!$header($response, 'X-Powered-By'), $locale . ': X-Powered-By disclosed');
    $assert(preg_match("/^Content-Security-Policy:.*object-src 'none'.*form-action 'self'.*frame-ancestors 'self'.*upgrade-insecure-requests/mi", $response['headers']) === 1, $locale . ': CSP hardening');
    $assert(preg_match("/script-src 'self' 'nonce-[A-Za-z0-9_-]{24}' https:\/\/www\.googletagmanager\.com/", $response['headers']) === 1, $locale . ': CSP nonce');
}

foreach (['TRACE', 'OPTIONS', 'PUT', 'DELETE', 'PATCH'] as $method) {
    $response = $request($base . '/en/', $method);
    $assert($response['status'] === 405, $method . ': expected 405, got ' . $response['status']);
    if ($method !== 'TRACE') $assert(preg_match('/^Allow:\s*GET, HEAD\s*$/mi', $response['headers']) === 1 || $production, $method . ': Allow header');
}
$post = $request($base . '/en/', 'POST', 'value=1');
$assert($post['status'] === 405, 'POST on read-only page: HTTP ' . $post['status']);

$security = $request($base . '/.well-known/security.txt');
$assert($security['status'] === 200 && str_contains($security['body'], 'Canonical: https://www.eduvixo.com/.well-known/security.txt'), 'security.txt unavailable or non-canonical');
$assert(str_contains(strtolower($security['headers']), 'content-type: text/plain'), 'security.txt MIME type');

if ($production) {
    foreach (['/.env', '/.git/config', '/.cfg/SSH.txt', '/storage/marketplace/packages/test.zip', '/app/Site.php', '/public/index.php'] as $path) {
        $response = $request($base . $path);
        $assert($response['status'] === 404, $path . ': expected concealed 404, got ' . $response['status']);
    }
    $static = $request($base . '/assets/css/site.min.css');
    foreach (['Strict-Transport-Security', 'Cross-Origin-Opener-Policy', 'X-Permitted-Cross-Domain-Policies', 'X-DNS-Prefetch-Control', 'X-Download-Options'] as $name) $assert($header($static, $name), 'static asset: missing ' . $name);
    $large = $request($base . '/en/contact/', 'POST', str_repeat('x', 70000));
    $assert($large['status'] === 413, 'Request-body limit: expected 413, got ' . $large['status']);
}

if ($errors) { fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL); exit(1); }
echo json_encode(['ok' => true, 'base' => $base, 'languages' => 7, 'methods_blocked' => 6, 'protected_paths' => $production ? 6 : 0, 'request_body_limit' => $production ? 65536 : 'Apache-only'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
