<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(ltrim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key); $value = trim($value);
        if (strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) $value = $decoded;
        }
        if ($key !== '' && getenv($key) === false) putenv($key . '=' . $value);
    }
}
$env = static fn(string $key, string $default = ''): string => (string) (getenv($key) !== false ? getenv($key) : $default);
$rateKey = trim($env('SITE_RATE_KEY'));
if ($rateKey === '') {
    $secretPath = $root . '/storage/.site-rate-key';
    if (!is_file($secretPath)) {
        $directory = dirname($secretPath);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) throw new RuntimeException('Secure runtime storage is unavailable.');
        $candidate = bin2hex(random_bytes(32)); $handle = @fopen($secretPath, 'x');
        if (is_resource($handle)) { try { if (fwrite($handle, $candidate) !== strlen($candidate) || !fflush($handle)) throw new RuntimeException('Security key could not be persisted.'); } finally { fclose($handle); } @chmod($secretPath, 0640); }
    }
    $rateKey = is_file($secretPath) ? trim((string) file_get_contents($secretPath)) : '';
}
if (!preg_match('/^[A-Fa-f0-9]{64}$/D', $rateKey)) throw new RuntimeException('SITE_RATE_KEY must contain 64 hexadecimal characters.');

return [
    'root' => $root,
    'base_url' => rtrim($env('SITE_BASE_URL', 'https://www.eduvixo.com'), '/'),
    'demo_url' => rtrim($env('SITE_DEMO_URL', 'https://demo.eduvixo.com'), '/'),
    'contact_recipient' => $env('SITE_CONTACT_RECIPIENT', 'info@eduvixo.com'),
    'google_site_verification' => $env('SITE_GOOGLE_VERIFICATION'),
    'google_analytics_id' => $env('SITE_GOOGLE_ANALYTICS_ID', 'G-CCZKQZHM4S'),
    'rate_key' => strtolower($rateKey),
    'marketplace' => require __DIR__ . '/marketplace.php',
    'languages' => [
        'zh' => ['english' => 'Chinese', 'native' => '中文', 'country' => ['CN', 'HK', 'MO', 'TW']],
        'en' => ['english' => 'English', 'native' => 'English', 'country' => []],
        'de' => ['english' => 'German', 'native' => 'Deutsch', 'country' => ['AT', 'DE', 'LI']],
        'lo' => ['english' => 'Lao', 'native' => 'ລາວ', 'country' => ['LA']],
        'pl' => ['english' => 'Polish', 'native' => 'Polski', 'country' => ['PL']],
        'th' => ['english' => 'Thai', 'native' => 'ไทย', 'country' => ['TH']],
        'vi' => ['english' => 'Vietnamese', 'native' => 'Tiếng Việt', 'country' => ['VN']],
    ],
    'mail' => [
        'host' => $env('SITE_MAIL_HOST'),
        'port' => (int) $env('SITE_MAIL_PORT', '465'),
        'username' => $env('SITE_MAIL_USERNAME'),
        'password' => $env('SITE_MAIL_PASSWORD'),
        'from_address' => $env('SITE_MAIL_FROM_ADDRESS', 'www@eduvixo.com'),
        'from_name' => $env('SITE_MAIL_FROM_NAME', 'Eduvixo Website'),
    ],
];
