<?php

declare(strict_types=1);

namespace Eduvixo\Website;

final class Site
{
    private const PAGE_ROUTES = [
        'home' => '', 'product' => 'product', 'services' => 'services', 'marketplace' => 'marketplace',
        'support' => 'support', 'docs' => 'support/docs', 'faq' => 'support/faq',
        'knowledge-base' => 'support/knowledge-base', 'updates' => 'updates', 'contact' => 'contact',
        'privacy' => 'privacy', 'terms' => 'terms',
    ];

    private array $copy;
    private array $state = [];
    private string $locale;
    private string $nonce;

    public function __construct(private readonly array $config)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('eduvixo_site');
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $this->secure(), 'path' => '/']);
            session_start();
        }
        $this->locale = $this->detectLocale();
        $this->copy = $this->loadCopy($this->locale);
        $this->nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    public function render(string $page): never
    {
        $allowed = ['home', 'product', 'services', 'marketplace', 'support', 'docs', 'faq', 'knowledge-base', 'updates', 'contact', 'privacy', 'terms'];
        if (!in_array($page, $allowed, true)) $page = 'home';
        $this->ensureCanonicalRoute($page);
        $view = $this->config['root'] . '/app/views/pages/' . $page . '.php';
        if (!is_file($view)) $view = $this->config['root'] . '/app/views/pages/home.php';
        $meta = $this->t('meta.' . $page, []);
        if (!is_array($meta)) $meta = $this->t('meta.home', []);
        $config = $this->config;
        $locale = $this->locale;
        $languages = $this->config['languages'];
        $nonce = $this->nonce;
        $t = fn(string $key, mixed $fallback = ''): mixed => $this->t($key, $fallback);
        $route = fn(string $target, ?string $code = null): string => $this->routePath($target, $code ?? $locale);
        $localize = fn(string $path): string => $this->localizePath($path, $locale);
        $asset = fn(string $path): string => $this->asset($path);
        $icon = fn(string $name, string $class = ''): string => $this->icon($name, $class);
        $currentPath = $this->routePath($page, $locale);
        $canonicalUrl = $this->config['base_url'] . $currentPath;
        $alternateUrl = fn(string $code): string => $this->config['base_url'] . $this->routePath($page, $code);
        $xDefaultUrl = $this->config['base_url'] . $this->routePath($page, 'en');
        $seo = (array) $this->t('seo', []);
        $keywords = trim(implode(', ', array_filter([(string) ($meta['keywords'] ?? ''), (string) ($seo['keywords'] ?? '')])));
        $ogLocales = ['zh' => 'zh_CN', 'en' => 'en_US', 'de' => 'de_DE', 'lo' => 'lo_LA', 'pl' => 'pl_PL', 'th' => 'th_TH', 'vi' => 'vi_VN'];
        $structuredData = $this->structuredData($page, $meta, $canonicalUrl);
        $demoUrl = $this->config['demo_url'];
        $csrf = $this->csrf();
        $state = $this->state;
        $needsSystemDetection = $this->pathLocale() === null && !isset($_COOKIE['eduvixo_language'], $_COOKIE['eduvixo_system_language']) && $this->acceptLanguage((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) === null;
        $this->headers();
        require $this->config['root'] . '/app/views/layout.php';
        exit;
    }

    public function locale(): string { return $this->locale; }
    public function config(): array { return $this->config; }
    public function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(24)); }
    public function validCsrf(?string $token): bool { return is_string($token) && hash_equals($this->csrf(), $token); }

    public function dispatch(): never
    {
        $segments = array_values(array_filter(explode('/', trim($this->requestPath(), '/')), static fn(string $segment): bool => $segment !== ''));
        if ($segments && isset($this->config['languages'][strtolower($segments[0])])) array_shift($segments);
        $slug = implode('/', $segments); $page = array_search($slug, self::PAGE_ROUTES, true);
        if (!is_string($page)) $this->plain('Not found.', 404);
        match ($page) { 'contact' => $this->contact(), 'marketplace' => $this->marketplace(), default => $this->render($page) };
    }

    public function contact(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            require_once __DIR__ . '/Mailer.php';
            require_once __DIR__ . '/ContactService.php';
            $service = new ContactService($this->config, new Mailer($this->config['mail']));
            $this->state = $service->submit($_POST, $this->validCsrf($_POST['csrf'] ?? null), $this->locale, fn(string $key, mixed $fallback = ''): mixed => $this->t($key, $fallback));
            if (!empty($this->state['success'])) {
                $_SESSION['contact_success'] = true;
                header('Location: ' . $this->routePath('contact', $this->locale) . '?sent=1', true, 303);
                exit;
            }
        } elseif (!empty($_SESSION['contact_success'])) {
            unset($_SESSION['contact_success']);
            $this->state = ['success' => true, 'values' => []];
        }
        $this->render('contact');
    }

    public function marketplace(): never
    {
        $this->state = ['marketplace_items' => $this->marketplaceService()->publicItems($this->clientIp())];
        if (!empty($_SESSION['marketplace_error'])) { unset($_SESSION['marketplace_error']); $this->state['download_error'] = true; }
        $this->render('marketplace');
    }

    public function requestDownload(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') $this->plain('Method not allowed.', 405);
        if (!$this->validCsrf($_POST['csrf'] ?? null)) { $_SESSION['marketplace_error'] = true; header('Location: ' . $this->routePath('marketplace', $this->locale), true, 303); exit; }
        try {
            $location = $this->marketplaceService()->issueBrowserToken((string) ($_POST['package'] ?? ''), (string) ($_POST['variant'] ?? ''), $this->clientIp(), $this->userAgent());
            header('Location: ' . $location, true, 303);
            exit;
        } catch (\RuntimeException) { $_SESSION['marketplace_error'] = true; header('Location: ' . $this->routePath('marketplace', $this->locale), true, 303); exit; }
    }

    public function downloadFile(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') $this->plain('Method not allowed.', 405);
        try { $this->marketplaceService()->streamBrowser((string) ($_GET['token'] ?? ''), $this->clientIp(), $this->userAgent()); }
        catch (\RuntimeException $error) { $this->plain($error->getCode() === 429 ? 'Too many download requests.' : 'Download unavailable.', in_array($error->getCode(), [404, 410, 429, 503], true) ? $error->getCode() : 404); }
    }

    public function requestLicensedDownload(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') $this->jsonError((string) $this->t('marketplace.license_method_error'), 405);
        if (!str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) $this->jsonError((string) $this->t('marketplace.license_request_error'), 415);
        try { $input = json_decode((string) file_get_contents('php://input'), true, 8, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { $this->jsonError((string) $this->t('marketplace.license_request_error'), 400); }
        if (!is_array($input) || !$this->validCsrf(isset($input['csrf']) && is_string($input['csrf']) ? $input['csrf'] : null)) $this->jsonError((string) $this->t('marketplace.license_request_error'), 403);
        try {
            $result = $this->marketplaceService()->issueLicensedBrowserToken((string) ($input['package'] ?? ''), (string) ($input['license'] ?? ''), $this->clientIp(), $this->userAgent());
            if (!empty($result['ok'])) $this->json(['ok' => true, 'download_url' => $result['download_url'], 'message' => $this->t('marketplace.license_success')]);
            if (!empty($result['locked'])) $this->jsonError((string) $this->t('marketplace.license_locked'), 429, ['code' => 'locked', 'locked' => true, 'retry_after' => (int) $result['retry_after']]);
            $message = str_replace('{count}', (string) $result['remaining'], (string) $this->t('marketplace.license_invalid'));
            $this->jsonError($message, 422, ['code' => 'invalid_license', 'locked' => false, 'attempts_remaining' => (int) $result['remaining']]);
        } catch (\RuntimeException $error) {
            $status = in_array($error->getCode(), [404, 429, 503], true) ? $error->getCode() : 503;
            $this->jsonError((string) $this->t($status === 503 ? 'marketplace.license_service_error' : 'marketplace.license_request_error'), $status);
        }
    }

    public function updateCatalog(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') $this->jsonError('Method not allowed.', 405);
        $type = strtolower(trim((string) ($_GET['type'] ?? ''))); $slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
        if (!in_array($type, ['theme', 'plugin', 'addon'], true) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) $this->jsonError('Package identity is invalid.', 422);
        try { $this->json($this->marketplaceService()->updaterCatalog($type, $slug)); }
        catch (\RuntimeException $error) { $this->jsonError($error->getCode() === 429 ? 'Too many update requests.' : 'Marketplace is temporarily unavailable.', in_array($error->getCode(), [429, 503], true) ? $error->getCode() : 503); }
    }

    public function updatePackage(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') $this->jsonError('Method not allowed.', 405);
        try { $this->marketplaceService()->streamUpdater((string) ($_GET['id'] ?? '')); }
        catch (\RuntimeException $error) { $this->jsonError($error->getCode() === 404 ? 'Package not found.' : 'Marketplace is temporarily unavailable.', in_array($error->getCode(), [404, 429, 503], true) ? $error->getCode() : 503); }
    }

    public function switchLanguage(): never
    {
        $locale = strtolower(trim((string) ($_GET['lang'] ?? '')));
        if (isset($this->config['languages'][$locale])) {
            setcookie('eduvixo_language', $locale, ['expires' => time() + 31536000, 'path' => '/', 'secure' => $this->secure(), 'httponly' => true, 'samesite' => 'Lax']);
        }
        $target = (string) ($_GET['return'] ?? '/');
        if (!str_starts_with($target, '/') || str_starts_with($target, '//') || str_contains($target, "\r") || str_contains($target, "\n")) $target = '/';
        header('Location: ' . $target, true, 303);
        exit;
    }

    public function rememberSystemLanguage(): never
    {
        $locale = $this->languageCode((string) ($_GET['locale'] ?? ''));
        $changed = false;
        if ($locale !== null && !isset($_COOKIE['eduvixo_language']) && (string) ($_COOKIE['eduvixo_system_language'] ?? '') !== $locale) {
            setcookie('eduvixo_system_language', $locale, ['expires' => time() + 2592000, 'path' => '/', 'secure' => $this->secure(), 'httponly' => true, 'samesite' => 'Lax']);
            $changed = true;
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['reload' => $changed && $locale !== $this->locale], JSON_THROW_ON_ERROR);
        exit;
    }

    private function detectLocale(): string
    {
        $explicit = $this->languageCode((string) ($_GET['lang'] ?? ''));
        if ($explicit !== null) {
            if ((string) ($_COOKIE['eduvixo_language'] ?? '') !== $explicit) setcookie('eduvixo_language', $explicit, ['expires' => time() + 31536000, 'path' => '/', 'secure' => $this->secure(), 'httponly' => true, 'samesite' => 'Lax']);
            return $explicit;
        }
        $path = $this->pathLocale();
        if ($path !== null) {
            if ((string) ($_COOKIE['eduvixo_language'] ?? '') !== $path) setcookie('eduvixo_language', $path, ['expires' => time() + 31536000, 'path' => '/', 'secure' => $this->secure(), 'httponly' => true, 'samesite' => 'Lax']);
            return $path;
        }
        $saved = $this->languageCode((string) ($_COOKIE['eduvixo_language'] ?? ''));
        if ($saved !== null) return $saved;
        $browser = $this->acceptLanguage((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        if ($browser !== null) return $browser;
        $system = $this->languageCode((string) ($_COOKIE['eduvixo_system_language'] ?? ''));
        if ($system !== null) return $system;
        $country = $this->countryLanguage();
        return $country ?? 'en';
    }

    private function acceptLanguage(string $header): ?string
    {
        $choices = [];
        foreach (explode(',', $header) as $position => $part) {
            [$tag, $params] = array_pad(explode(';', trim($part), 2), 2, '');
            $quality = preg_match('/q=([0-9.]+)/i', $params, $match) ? (float) $match[1] : 1.0;
            $code = $this->languageCode($tag);
            if ($code !== null) $choices[] = ['code' => $code, 'quality' => $quality, 'position' => $position];
        }
        usort($choices, static fn(array $a, array $b): int => $b['quality'] <=> $a['quality'] ?: $a['position'] <=> $b['position']);
        return $choices[0]['code'] ?? null;
    }

    private function countryLanguage(): ?string
    {
        $country = '';
        foreach (['HTTP_CF_IPCOUNTRY', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_APPENGINE_COUNTRY', 'GEOIP_COUNTRY_CODE'] as $key) {
            $candidate = strtoupper(trim((string) ($_SERVER[$key] ?? '')));
            if (preg_match('/^[A-Z]{2}$/', $candidate)) { $country = $candidate; break; }
        }
        if ($country === '' && function_exists('geoip_country_code_by_name')) {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            $country = strtoupper((string) @geoip_country_code_by_name($ip));
        }
        if ($country === '') {
            $host = strtolower(trim((string) ($_SERVER['REMOTE_HOST'] ?? '')));
            if (preg_match('/\.([a-z]{2})$/', $host, $match)) $country = strtoupper($match[1]);
        }
        foreach ($this->config['languages'] as $code => $language) if (in_array($country, $language['country'], true)) return $code;
        return null;
    }

    private function languageCode(string $value): ?string
    {
        $value = strtolower(str_replace('_', '-', trim($value)));
        $code = explode('-', $value)[0] ?? '';
        return isset($this->config['languages'][$code]) ? $code : null;
    }

    private function loadCopy(string $locale): array
    {
        $root = $this->config['root'] . '/lang/';
        $fallback = json_decode((string) file_get_contents($root . 'en.json'), true, 512, JSON_THROW_ON_ERROR);
        if ($locale === 'en') return $fallback;
        $localized = is_file($root . $locale . '.json') ? json_decode((string) file_get_contents($root . $locale . '.json'), true, 512, JSON_THROW_ON_ERROR) : [];
        return array_replace_recursive($fallback, is_array($localized) ? $localized : []);
    }

    private function t(string $key, mixed $fallback = ''): mixed
    {
        $value = $this->copy;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return $fallback;
            $value = $value[$segment];
        }
        return $value;
    }

    private function headers(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self' 'nonce-{$this->nonce}'; connect-src 'self'; font-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    }

    private function requestPath(): string
    {
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return '/' . trim($path, '/') . ($path !== '/' ? '/' : '');
    }

    private function ensureCanonicalRoute(string $page): void
    {
        $hasLanguageQuery = array_key_exists('lang', $_GET);
        $queryLocale = $this->languageCode((string) ($_GET['lang'] ?? ''));
        $targetLocale = $queryLocale ?? $this->pathLocale() ?? $this->locale;
        $target = $this->routePath($page, $targetLocale);
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if ($path === '') $path = '/';
        if ($path === $target && !$hasLanguageQuery) return;
        if ($page === 'contact' && (string) ($_GET['sent'] ?? '') === '1') $target .= '?sent=1';
        header('Location: ' . $target, true, $hasLanguageQuery || $this->pathLocale() !== null ? 301 : 302);
        exit;
    }

    private function routePath(string $page, string $locale): string
    {
        $slug = self::PAGE_ROUTES[$page] ?? '';
        return '/' . rawurlencode($locale) . '/' . ($slug !== '' ? $slug . '/' : '');
    }

    private function localizePath(string $path, string $locale): string
    {
        $slug = trim((string) parse_url($path, PHP_URL_PATH), '/');
        $page = array_search($slug, self::PAGE_ROUTES, true);
        return is_string($page) ? $this->routePath($page, $locale) : $path;
    }

    private function pathLocale(): ?string
    {
        $segment = strtolower(explode('/', trim($this->requestPath(), '/'))[0] ?? '');
        return isset($this->config['languages'][$segment]) ? $segment : null;
    }

    private function asset(string $path): string
    {
        $clean = ltrim($path, '/');
        $file = $this->config['root'] . '/public/' . $clean;
        return '/' . $clean . (is_file($file) ? '?v=' . substr(hash_file('sha256', $file), 0, 10) : '');
    }

    private function structuredData(string $page, array $meta, string $canonicalUrl): array
    {
        $base = (string) $this->config['base_url'];
        $graph = [
            ['@type' => 'Organization', '@id' => $base . '/#organization', 'name' => 'Eduvixo', 'legalName' => 'Chivale Group', 'url' => $base . '/', 'logo' => ['@type' => 'ImageObject', 'url' => $base . '/assets/eduvixo-logo.svg'], 'image' => $base . '/assets/images/og-default.jpg', 'email' => 'info@eduvixo.com', 'description' => (string) $this->t('footer.summary')],
            ['@type' => 'WebSite', '@id' => $base . '/#website', 'url' => $base . '/', 'name' => 'Eduvixo', 'description' => (string) $this->t('footer.summary'), 'publisher' => ['@id' => $base . '/#organization'], 'inLanguage' => array_keys($this->config['languages'])],
            ['@type' => match ($page) { 'contact' => 'ContactPage', 'marketplace', 'support', 'knowledge-base' => 'CollectionPage', default => 'WebPage' }, '@id' => $canonicalUrl . '#webpage', 'url' => $canonicalUrl, 'name' => (string) ($meta['title'] ?? 'Eduvixo'), 'description' => (string) ($meta['description'] ?? ''), 'inLanguage' => $this->locale, 'isPartOf' => ['@id' => $base . '/#website'], 'about' => ['@id' => $base . '/#software']],
        ];
        if (in_array($page, ['home', 'product'], true)) {
            $features = [];
            foreach ((array) $this->t('product.modules', []) as $module) if (is_array($module) && isset($module['title'])) $features[] = (string) $module['title'];
            $graph[] = ['@type' => 'SoftwareApplication', '@id' => $base . '/#software', 'name' => 'Eduvixo', 'url' => $base . $this->routePath('product', $this->locale), 'applicationCategory' => 'EducationalApplication', 'applicationSubCategory' => 'Education Content Management System', 'operatingSystem' => 'Web, Windows, macOS, Android, iOS', 'description' => (string) ($meta['description'] ?? ''), 'image' => $base . '/assets/images/eduvixo-cms-1920.webp', 'featureList' => $features, 'publisher' => ['@id' => $base . '/#organization'], 'inLanguage' => $this->locale];
        }
        if ($page === 'faq') {
            $entities = [];
            foreach ((array) $this->t('faq.items', []) as $item) if (is_array($item) && isset($item['question'], $item['answer'])) $entities[] = ['@type' => 'Question', 'name' => (string) $item['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) $item['answer']]];
            if ($entities) $graph[] = ['@type' => 'FAQPage', '@id' => $canonicalUrl . '#faq', 'url' => $canonicalUrl, 'inLanguage' => $this->locale, 'mainEntity' => $entities];
        }
        if ($page !== 'home') $graph[] = ['@type' => 'BreadcrumbList', '@id' => $canonicalUrl . '#breadcrumb', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Eduvixo', 'item' => $base . $this->routePath('home', $this->locale)], ['@type' => 'ListItem', 'position' => 2, 'name' => (string) ($meta['title'] ?? 'Eduvixo'), 'item' => $canonicalUrl]]];
        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    private function icon(string $name, string $class = ''): string
    {
        $safe = preg_replace('/[^a-z0-9-]/', '', strtolower($name)) ?: 'arrow-right';
        $css = preg_replace('/[^a-zA-Z0-9 _-]/', '', $class);
        return '<svg class="icon ' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"><use href="/assets/icons.svg#' . $safe . '"></use></svg>';
    }

    private function marketplaceService(): MarketplaceService
    {
        require_once __DIR__ . '/MarketplaceService.php';
        return new MarketplaceService((array) $this->config['marketplace'], (string) $this->config['base_url'], (string) $this->config['rate_key']);
    }

    private function json(array $data): never { header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow, noarchive'); echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES); exit; }
    private function jsonError(string $message, int $status, array $details = []): never { http_response_code($status); $this->json(['error' => true, 'message' => $message] + $details); }
    private function plain(string $message, int $status): never { http_response_code($status); header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow, noarchive'); echo $message; exit; }
    private function clientIp(): string { return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'); }
    private function userAgent(): string { return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500); }

    private function secure(): bool { return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; }
}
