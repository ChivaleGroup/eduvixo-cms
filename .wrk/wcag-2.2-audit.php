<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$base = rtrim((string) ($argv[1] ?? 'http://127.0.0.1:8899'), '/');
$locales = ['en', 'de', 'zh', 'vi', 'th', 'lo', 'pl'];
if (isset($argv[2])) {
    if (!in_array($argv[2], $locales, true)) throw new InvalidArgumentException('Unsupported locale.');
    $locales = [$argv[2]];
}
$routes = ['', 'product/', 'services/', 'marketplace/', 'support/', 'support/docs/', 'support/faq/', 'support/knowledge-base/', 'updates/', 'contact/', 'privacy/', 'terms/'];
$errors = [];
$checked = 0;

$assert = static function (bool $condition, string $message) use (&$errors): void {
    if (!$condition) $errors[] = $message;
};
$request = static function (string $url): string {
    $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true, 'user_agent' => 'Eduvixo WCAG audit']]);
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) $status = (int) $match[1];
    if ($status !== 200 || !is_string($body)) throw new RuntimeException($url . ': HTTP ' . $status);
    return $body;
};

foreach ($locales as $locale) {
    $copy = json_decode((string) file_get_contents($root . '/lang/' . $locale . '.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach (['accessibility', 'text_size', 'text_size_help', 'decrease_text', 'increase_text', 'reset', 'close_accessibility'] as $key) {
        $assert(is_string($copy['a11y'][$key] ?? null) && trim($copy['a11y'][$key]) !== '', $locale . ': missing a11y.' . $key);
    }
    foreach ($routes as $route) {
        $path = '/' . $locale . '/' . $route;
        $html = $request($base . $path);
        $assert(str_contains($html, 'class="skip-link" href="#content"'), $path . ': skip link missing');
        $assert(str_contains($html, '<main id="content">'), $path . ': main landmark target missing');
        $assert(str_contains($html, 'data-accessibility-toggle'), $path . ': accessibility trigger missing');
        $assert(str_contains($html, 'role="dialog" aria-labelledby="accessibility-title"'), $path . ': accessibility dialog semantics missing');
        $assert(str_contains($html, 'min="100" max="200" step="10"'), $path . ': text-scale range contract missing');
        $assert(substr_count($html, '<h1') === 1, $path . ': expected one h1');
        $assert(!str_contains($html, '—'), $path . ': em dash remains');
        $document = new DOMDocument();
        @$document->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
        foreach (['button', 'a'] as $elementName) {
            foreach ($document->getElementsByTagName($elementName) as $element) {
                $text = trim((string) preg_replace('/\s+/u', ' ', $element->textContent));
                $name = trim($element->getAttribute('aria-label'));
                $labelledBy = trim($element->getAttribute('aria-labelledby'));
                $title = trim($element->getAttribute('title'));
                $assert($text !== '' || $name !== '' || $labelledBy !== '' || $title !== '', $path . ': unnamed ' . $elementName . ' control');
            }
        }
        $checked++;
    }
}

$css = (string) file_get_contents($root . '/public/assets/css/site.min.css');
$js = (string) file_get_contents($root . '/public/assets/js/site.min.js');
$assert(str_contains($css, ':focus-visible') && str_contains($css, 'prefers-reduced-motion:reduce'), 'compiled focus or reduced-motion styles missing');
$assert(str_contains($js, 'eduvixo.accessibility.textScale') && str_contains($js, "event.key==='Escape'") && str_contains($js, "event.key==='Tab'"), 'compiled keyboard or persistence handling missing');

$backend = (string) file_get_contents($root . '/.cms/source/app/Views/console.php');
$shell = (string) file_get_contents($root . '/.cms/source/public/theme/eduvixo-shell.js');
$theme = (string) file_get_contents($root . '/.cms/source/themes/eduvixo/views/page.php');
$assert(str_contains($backend, 'data-text-range') && str_contains($backend, 'aria-modal="true"'), 'backend Settings accessibility controls missing');
$assert(str_contains($shell, 'syncTextScale') && str_contains($shell, 'workspace.inert = value'), 'backend scale or modal focus management missing');
$assert(str_contains($theme, 'data-school-accessibility') && str_contains($theme, 'id="school-main-content"'), 'school accessibility controls or main target missing');

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_unique($errors)) . PHP_EOL);
    exit(1);
}

echo json_encode([
    'ok' => true,
    'standard' => 'WCAG 2.2 AA',
    'routes' => $checked,
    'languages' => count($locales),
    'text_scale' => ['min' => 100, 'max' => 200, 'step' => 10],
    'static_checks' => ['skip_links', 'landmarks', 'headings', 'dialog_semantics', 'keyboard_contract', 'focus_visibility', 'reduced_motion', 'localized_labels'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
