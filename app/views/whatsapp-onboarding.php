<?php declare(strict_types=1); $e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow,noarchive"><meta name="theme-color" content="#061b4f">
    <title>Connect WhatsApp - Eduvixo</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg"><link rel="stylesheet" href="/assets/css/site.min.css">
    <script src="/assets/js/whatsapp-onboarding.js" defer></script><script src="https://connect.facebook.net/en_US/sdk.js" defer crossorigin="anonymous"></script>
</head>
<body class="wa-onboarding" data-app-id="<?= $e($app['app_id']) ?>" data-config-id="<?= $e($app['config_id']) ?>" data-graph-version="<?= $e($app['graph_version']) ?>" data-csrf="<?= $e($csrf) ?>">
<main class="wa-onboarding-shell">
    <a class="wa-brand" href="/" aria-label="Eduvixo"><img src="/assets/eduvixo-logo.svg" width="154" height="42" alt="Eduvixo"></a>
    <section class="wa-card" aria-labelledby="wa-title">
        <span class="wa-icon" aria-hidden="true"><svg class="icon"><use href="/assets/icons.svg#message-circle"></use></svg></span>
        <p class="section-label">Secure connection</p><h1 id="wa-title">Connect WhatsApp Business</h1>
        <p>Connect this Eduvixo installation through Meta's official WhatsApp Business App Coexistence flow. Your existing WhatsApp Business mobile application and its message history remain available.</p>
        <ul><li>One central, verified Eduvixo integration</li><li>No Meta application required for your school</li><li>Credentials returned only to the authorized CMS</li></ul>
        <div class="wa-status" role="status" aria-live="polite" data-status>Ready to continue securely with Meta.</div>
        <button class="button button-primary" type="button" data-connect>Continue with Meta</button>
        <noscript><p class="wa-error">JavaScript is required to complete Meta onboarding.</p></noscript>
    </section>
    <p class="wa-foot">Eduvixo never places WhatsApp access tokens in browser addresses or page content.</p>
</main>
</body></html>
