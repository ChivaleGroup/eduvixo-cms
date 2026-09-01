<?php

declare(strict_types=1);

$ecosystemVariant = in_array($ecosystemVariant ?? '', ['preview', 'full'], true) ? $ecosystemVariant : 'full';
$ecosystemLimit = $ecosystemVariant === 'preview' ? 3 : PHP_INT_MAX;
$coreItems = array_map(static fn(array $item): array => ['name' => (string) ($item['title'] ?? ''), 'detail' => (string) ($item['copy'] ?? '')], (array) $t('product.modules', []));
$freeItems = $ecosystemVariant === 'preview' ? [
    ['name'=>'AI Translation Assistant','detail'=>$t('marketplace.ai_translation_copy')],
    ['name'=>'Google Analytics','detail'=>$t('marketplace.google_analytics_copy')],
    ['name'=>'Desktop Client for Windows','detail'=>$t('marketplace.windows_copy')],
] : [
    ['name'=>'Eduvixo','detail'=>$t('marketplace.eduvixo_copy')],['name'=>'Shoudu Custom Theme','detail'=>$t('marketplace.shoudu_copy')],['name'=>'Desktop Client for Windows','detail'=>$t('marketplace.windows_copy')],['name'=>'Google Analytics','detail'=>$t('marketplace.google_analytics_copy')],['name'=>'AI Translation Assistant','detail'=>$t('marketplace.ai_translation_copy')],
];
$tiers = [
    ['key'=>'core','icon'=>'layers','eyebrow'=>$t('ecosystem.core_eyebrow'),'title'=>'Base CMS','price'=>$t('marketplace.cms_price'),'copy'=>$t('ecosystem.core_copy'),'items'=>$coreItems],
    ['key'=>'free','icon'=>'shield-check','eyebrow'=>$t('ecosystem.free_eyebrow'),'title'=>$t('ecosystem.free_title'),'price'=>$t('marketplace.free'),'copy'=>$t('ecosystem.free_copy'),'items'=>$freeItems],
    ['key'=>'premium','icon'=>'sparkles','eyebrow'=>$t('ecosystem.premium_eyebrow'),'title'=>$t('ecosystem.premium_title'),'price'=>$t('ecosystem.premium_price'),'copy'=>$t('ecosystem.premium_copy'),'items'=>[
        ['name'=>'My Calendar - '.$t('marketplace.calendar_price'),'detail'=>$t('marketplace.calendar_copy')],['name'=>'Google Calendar - '.$t('marketplace.integration_price'),'detail'=>$t('marketplace.google_calendar_copy')],['name'=>'Apple Calendar - '.$t('marketplace.integration_price'),'detail'=>$t('marketplace.apple_calendar_copy')],['name'=>'Microsoft 365 Calendar - '.$t('marketplace.integration_price'),'detail'=>$t('marketplace.microsoft_calendar_copy')],['name'=>'Telegram Notifications - '.$t('marketplace.notification_price'),'detail'=>$t('marketplace.telegram_copy')],['name'=>'WhatsApp Notifications - '.$t('marketplace.notification_price'),'detail'=>$t('marketplace.whatsapp_copy')],['name'=>'iFirewall - '.$t('marketplace.ifirewall_price'),'detail'=>$t('marketplace.ifirewall_copy')],
    ]],
];
?>
<section class="section ecosystem-section ecosystem-<?= $e($ecosystemVariant) ?>">
    <div class="shell">
        <div class="section-head ecosystem-head"><div><span class="section-label"><?= $e($t('ecosystem.label')) ?></span><h2><?= $e($t('ecosystem.title')) ?></h2></div><p><?= $e($t('ecosystem.copy')) ?></p></div>
        <div class="ecosystem-grid">
            <?php foreach ($tiers as $tier): ?>
                <article class="ecosystem-tier ecosystem-tier-<?= $e($tier['key'] ?? 'core') ?>">
                    <header><div class="ecosystem-icon"><?= $icon((string) ($tier['icon'] ?? 'layers')) ?></div><span><?= $e($tier['eyebrow'] ?? '') ?></span></header>
                    <div class="ecosystem-tier-title"><h3><?= $e($tier['title'] ?? '') ?></h3><strong><?= $e($tier['price'] ?? '') ?></strong></div>
                    <p><?= $e($tier['copy'] ?? '') ?></p>
                    <ul><?php foreach (array_slice((array) ($tier['items'] ?? []), 0, $ecosystemLimit) as $item): ?><li><?= $icon('check') ?><span><b><?= $e($item['name'] ?? '') ?></b><small><?= $e($item['detail'] ?? '') ?></small></span></li><?php endforeach; ?></ul>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($ecosystemVariant === 'full'): ?>
            <aside class="ecosystem-custom">
                <div class="ecosystem-custom-icon"><?= $icon('sparkles') ?></div>
                <div class="ecosystem-custom-copy"><span><?= $e($t('ecosystem.custom.label')) ?></span><h3><?= $e($t('ecosystem.custom.title')) ?></h3><p><?= $e($t('ecosystem.custom.copy')) ?></p><ul><?php foreach ((array) $t('ecosystem.custom.items', []) as $item): ?><li><?= $icon('check') ?><?= $e($item) ?></li><?php endforeach; ?></ul></div>
                <a class="button button-white" href="<?= $e($route('contact')) ?>"><?= $e($t('ecosystem.custom.action')) ?><?= $icon('arrow-right') ?></a>
            </aside>
        <?php endif; ?>
        <div class="ecosystem-action"><a class="button button-secondary" href="<?= $e($route('marketplace')) ?>"><?= $e($t('ecosystem.action')) ?><?= $icon('arrow-right') ?></a><small><?= $e($t('ecosystem.note')) ?></small></div>
    </div>
</section>
