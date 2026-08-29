<?php declare(strict_types=1); $e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title><?= $e($meta['title'] ?? 'Eduvixo') ?></title>
    <meta name="description" content="<?= $e($meta['description'] ?? '') ?>">
    <?php if ($keywords !== ''): ?><meta name="keywords" content="<?= $e($keywords) ?>"><?php endif; ?>
    <meta name="author" content="Eduvixo · Chivale Group">
    <meta name="application-name" content="Eduvixo">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <meta name="googlebot" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <?php if ($config['google_site_verification'] !== ''): ?><meta name="google-site-verification" content="<?= $e($config['google_site_verification']) ?>"><?php endif; ?>
    <meta name="theme-color" content="#061b4f">
    <meta name="color-scheme" content="light">
    <link rel="canonical" href="<?= $e($canonicalUrl) ?>">
    <?php foreach ($languages as $code => $language): ?><link rel="alternate" hreflang="<?= $e($code) ?>" href="<?= $e($alternateUrl($code)) ?>"><?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= $e($xDefaultUrl) ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml">
    <link rel="manifest" href="/site.webmanifest">
    <meta property="og:type" content="website"><meta property="og:site_name" content="Eduvixo"><meta property="og:title" content="<?= $e($meta['title'] ?? '') ?>"><meta property="og:description" content="<?= $e($meta['description'] ?? '') ?>"><meta property="og:url" content="<?= $e($canonicalUrl) ?>"><meta property="og:locale" content="<?= $e($ogLocales[$locale]) ?>"><?php foreach ($ogLocales as $code => $ogLocale): ?><?php if ($code !== $locale): ?><meta property="og:locale:alternate" content="<?= $e($ogLocale) ?>"><?php endif; ?><?php endforeach; ?><meta property="og:image" content="<?= $e($config['base_url'] . '/assets/images/og-default.jpg') ?>"><meta property="og:image:secure_url" content="<?= $e($config['base_url'] . '/assets/images/og-default.jpg') ?>"><meta property="og:image:type" content="image/jpeg"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630"><meta property="og:image:alt" content="<?= $e($seo['image_alt'] ?? 'Eduvixo - Education Digital Experience & Communication Platform') ?>">
    <meta name="twitter:card" content="summary_large_image"><meta name="twitter:domain" content="www.eduvixo.com"><meta name="twitter:url" content="<?= $e($canonicalUrl) ?>"><meta name="twitter:title" content="<?= $e($meta['title'] ?? '') ?>"><meta name="twitter:description" content="<?= $e($meta['description'] ?? '') ?>"><meta name="twitter:image" content="<?= $e($config['base_url'] . '/assets/images/og-default.jpg') ?>"><meta name="twitter:image:alt" content="<?= $e($seo['image_alt'] ?? 'Eduvixo - Education Digital Experience & Communication Platform') ?>">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg"><link rel="alternate icon" href="/favicon.ico">
    <link rel="stylesheet" href="<?= $e($asset('assets/css/site.min.css')) ?>">
    <script type="application/ld+json" nonce="<?= $e($nonce) ?>"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</head>
<body data-locale="<?= $e($locale) ?>" data-system-detection="<?= $needsSystemDetection ? '1' : '0' ?>">
<a class="skip-link" href="#content"><?= $e($t('a11y.skip')) ?></a>
<header class="site-header" data-header>
    <div class="shell header-inner">
        <a class="brand" href="<?= $e($route('home')) ?>" aria-label="Eduvixo"><img src="/assets/eduvixo-logo.svg" width="183" height="50" alt="Eduvixo"></a>
        <nav class="nav" aria-label="<?= $e($t('a11y.primary_nav')) ?>" data-nav>
            <a class="<?= $page === 'product' ? 'is-active' : '' ?>" href="<?= $e($route('product')) ?>"><?= $e($t('nav.product')) ?></a>
            <a class="<?= $page === 'services' ? 'is-active' : '' ?>" href="<?= $e($route('services')) ?>"><?= $e($t('nav.services')) ?></a>
            <a href="<?= $e($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('nav.demo')) ?></a>
            <a class="<?= $page === 'marketplace' ? 'is-active' : '' ?>" href="<?= $e($route('marketplace')) ?>"><?= $e($t('nav.marketplace')) ?></a>
            <a class="<?= in_array($page, ['support', 'docs', 'faq', 'knowledge-base'], true) ? 'is-active' : '' ?>" href="<?= $e($route('support')) ?>"><?= $e($t('nav.support')) ?></a>
            <a class="<?= $page === 'updates' ? 'is-active' : '' ?>" href="<?= $e($route('updates')) ?>"><?= $e($t('nav.updates')) ?></a>
            <a class="<?= $page === 'contact' ? 'is-active' : '' ?>" href="<?= $e($route('contact')) ?>"><?= $e($t('nav.contact')) ?></a>
        </nav>
        <div class="header-actions">
            <details class="language-menu"><summary aria-label="<?= $e($t('a11y.language')) ?>"><?= $icon('globe') ?><span><?= $e(strtoupper($locale)) ?></span><?= $icon('chevron-down') ?></summary><div><?php foreach ($languages as $code => $language): ?><a class="<?= $locale === $code ? 'is-current' : '' ?>" href="<?= $e($route($page, $code)) ?>" hreflang="<?= $e($code) ?>" lang="<?= $e($code) ?>"><span><?= $e($language['native']) ?></span><small><?= $e($language['english']) ?></small></a><?php endforeach; ?></div></details>
            <a class="button button-small button-ghost demo-link" href="<?= $e($demoUrl . '/login') ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('actions.login')) ?><?= $icon('external-link') ?></a>
            <button class="nav-toggle" type="button" aria-label="<?= $e($t('a11y.menu')) ?>" aria-expanded="false" data-nav-toggle><?= $icon('menu') ?></button>
        </div>
    </div>
</header>
<main id="content"><?php require $view; ?></main>
<footer class="site-footer">
    <div class="shell footer-grid"><div class="footer-brand"><img src="/assets/eduvixo-logo-white.png" width="183" height="50" alt="Eduvixo"><p><?= $e($t('footer.summary')) ?></p><strong><?= $e($t('footer.tagline')) ?></strong></div><div><h2><?= $e($t('footer.platform')) ?></h2><a href="<?= $e($route('product')) ?>"><?= $e($t('nav.product')) ?></a><a href="<?= $e($route('services')) ?>"><?= $e($t('nav.services')) ?></a><a href="<?= $e($route('marketplace')) ?>"><?= $e($t('nav.marketplace')) ?></a><a href="<?= $e($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('actions.demo')) ?></a></div><div><h2><?= $e($t('footer.resources')) ?></h2><a href="<?= $e($route('docs')) ?>"><?= $e($t('nav.docs')) ?></a><a href="<?= $e($route('faq')) ?>"><?= $e($t('nav.faq')) ?></a><a href="<?= $e($route('knowledge-base')) ?>"><?= $e($t('nav.knowledge')) ?></a><a href="<?= $e($route('updates')) ?>"><?= $e($t('nav.updates')) ?></a></div><div><h2><?= $e($t('footer.company')) ?></h2><a href="<?= $e($route('contact')) ?>"><?= $e($t('nav.contact')) ?></a><a href="<?= $e($route('privacy')) ?>"><?= $e($t('nav.privacy')) ?></a><a href="<?= $e($route('terms')) ?>"><?= $e($t('nav.terms')) ?></a><a href="mailto:info@eduvixo.com">info@eduvixo.com</a></div></div>
    <div class="shell footer-bottom"><div class="footer-credits"><span>© Copyright by Eduvixo &amp; <a href="https://www.ittsp.com/?IdRef=eduvixo.com" target="_blank" rel="noopener noreferrer">QUANT Software House</a>. All rights reserved.</span><a class="chivale-credit" href="https://www.chivale.com/?IdRef=eduvixo.com" target="_blank" rel="noopener noreferrer"><img src="/assets/images/chivale-mark-white.svg" width="24" height="16" alt="" aria-hidden="true">Hosting provided by Chivale Group.</a></div></div>
</footer>
<script src="<?= $e($asset('assets/js/site.min.js')) ?>" defer></script>
</body></html>
