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

return [
    'root' => $root,
    'base_url' => rtrim($env('SITE_BASE_URL', 'https://www.eduvixo.com'), '/'),
    'demo_url' => rtrim($env('SITE_DEMO_URL', 'https://demo.eduvixo.com'), '/'),
    'contact_recipient' => $env('SITE_CONTACT_RECIPIENT', 'info@eduvixo.com'),
    'google_site_verification' => $env('SITE_GOOGLE_VERIFICATION'),
    'google_analytics_id' => $env('SITE_GOOGLE_ANALYTICS_ID', 'G-CCZKQZHM4S'),
    'rate_key' => $env('SITE_RATE_KEY', hash('sha256', $root)),
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
