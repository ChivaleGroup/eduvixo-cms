<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$base = 'https://www.eduvixo.com';
$languages = ['zh', 'en', 'de', 'lo', 'pl', 'th', 'vi'];
$pages = [
    'home' => '/', 'product' => '/product/', 'services' => '/services/', 'marketplace' => '/marketplace/',
    'support' => '/support/', 'docs' => '/support/docs/', 'faq' => '/support/faq/',
    'knowledge-base' => '/support/knowledge-base/', 'updates' => '/updates/', 'contact' => '/contact/',
    'privacy' => '/privacy/', 'terms' => '/terms/',
];
$localizedPath = static function (string $path, string $language): string {
    $slug = trim($path, '/');
    return '/' . $language . '/' . ($slug !== '' ? $slug . '/' : '');
};
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$copy = [];
foreach ($languages as $language) $copy[$language] = json_decode((string) file_get_contents($root . '/lang/' . $language . '.json'), true, 512, JSON_THROW_ON_ERROR);
$xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'];
foreach ($pages as $page => $path) {
    foreach ($languages as $language) {
        $view = $root . '/app/views/pages/' . $page . '.php';
        $lastModified = date('Y-m-d', max(filemtime($root . '/lang/' . $language . '.json'), is_file($view) ? filemtime($view) : 0, filemtime($root . '/app/views/layout.php')));
        $xml[] = '  <url>';
        $xml[] = '    <loc>' . $escape($base . $localizedPath($path, $language)) . '</loc>';
        $xml[] = '    <lastmod>' . $lastModified . '</lastmod>';
        foreach ($languages as $alternate) $xml[] = '    <xhtml:link rel="alternate" hreflang="' . $alternate . '" href="' . $escape($base . $localizedPath($path, $alternate)) . '" />';
        $xml[] = '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $escape($base . $localizedPath($path, 'en')) . '" />';
        if ($page === 'home') {
            $xml[] = '    <image:image>';
            $xml[] = '      <image:loc>' . $base . '/assets/images/eduvixo-cms-1920.webp</image:loc>';
            $xml[] = '      <image:title>' . $escape((string) $copy[$language]['home']['hero']['image_alt']) . '</image:title>';
            $xml[] = '    </image:image>';
        }
        $xml[] = '  </url>';
    }
}
$xml[] = '</urlset>';
$target = $root . '/public/sitemap.xml';
if (file_put_contents($target, implode("\n", $xml) . "\n", LOCK_EX) === false) throw new RuntimeException('Sitemap could not be written.');
echo 'Sitemap built: ' . (count($pages) * count($languages)) . ' localized URLs.' . PHP_EOL;
