<?php

declare(strict_types=1);

$heroActions = [['class'=>'button-white','href'=>$route('marketplace'),'label'=>$t('updates.marketplace_action')],['class'=>'button-outline-light','href'=>$route('docs'),'label'=>$t('updates.docs_action')]];
require dirname(__DIR__) . '/page-hero.php';
$releaseFile = $config['root'] . '/storage/marketplace/core-release.json';
$coreRelease = is_file($releaseFile) ? json_decode((string) file_get_contents($releaseFile), true) : [];
$packages = (array) ($config['marketplace']['packages'] ?? []);
$package = static function (array $items, string $type, string $slug): array { foreach ($items as $item) if (($item['type'] ?? '') === $type && ($item['slug'] ?? '') === $slug) return $item; return []; };
$coreVersion = (string) ($coreRelease['version'] ?? '1.0.5');
$coreChannel = (string) ($coreRelease['channel'] ?? 'beta');
$eduvixoTheme = $package($packages, 'theme', 'eduvixo');
$shouduTheme = $package($packages, 'theme', 'shoudu');
$releaseCards = [
    ['icon'=>'layers','eyebrow'=>$t('updates.cards.core.eyebrow'),'title'=>$t('updates.cards.core.title'),'copy'=>$t('updates.cards.core.copy'),'value'=>'v'.$coreVersion,'meta'=>$coreChannel === 'stable' ? $t('marketplace.stable') : $t('marketplace.beta')],
    ['icon'=>'layout','eyebrow'=>$t('updates.cards.themes.eyebrow'),'title'=>$t('updates.cards.themes.title'),'copy'=>$t('updates.cards.themes.copy'),'value'=>'v'.($eduvixoTheme['version'] ?? '1.1.7'),'meta'=>'Shoudu v'.($shouduTheme['version'] ?? '1.1.1')],
    ['icon'=>'shield-check','eyebrow'=>$t('updates.cards.catalog.eyebrow'),'title'=>$t('updates.cards.catalog.title'),'copy'=>$t('updates.cards.catalog.copy'),'value'=>(string) count($packages),'meta'=>$t('updates.cards.catalog.meta')],
];
?>
<section class="section updates-page"><div class="shell">
    <div class="release-current"><div><span><?= $e($t('updates.current')) ?></span><strong><?= $e($coreVersion) ?></strong><small><?= $e($coreChannel === 'stable' ? $t('marketplace.stable') : $t('marketplace.beta')) ?> · <?= $e($t('updates.release_date')) ?></small></div><div><h2><?= $e(str_replace('{version}', $coreVersion, (string) $t('updates.release_title'))) ?></h2><p><?= $e($t('updates.release_copy')) ?></p><ul><?php foreach ((array) $t('updates.highlights', []) as $item): ?><li><?= $icon('check') ?><?= $e($item) ?></li><?php endforeach; ?></ul></div></div>
    <div class="updates-section-head"><span class="section-label"><?= $e($t('updates.overview.label')) ?></span><h2><?= $e($t('updates.overview.title')) ?></h2><p><?= $e($t('updates.overview.copy')) ?></p></div>
    <div class="updates-release-grid"><?php foreach ($releaseCards as $card): ?><article><header><div class="updates-release-icon"><?= $icon($card['icon']) ?></div><span><?= $e($card['eyebrow']) ?></span></header><strong><?= $e($card['value']) ?></strong><small><?= $e($card['meta']) ?></small><h3><?= $e($card['title']) ?></h3><p><?= $e($card['copy']) ?></p></article><?php endforeach; ?></div>
    <div class="updates-section-head updates-process-head"><span class="section-label"><?= $e($t('updates.process.label')) ?></span><h2><?= $e($t('updates.process.title')) ?></h2><p><?= $e($t('updates.process.copy')) ?></p></div>
    <ol class="updates-process"><?php foreach ((array) $t('updates.process.steps', []) as $index => $step): ?><li><b><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></b><div class="updates-step-icon"><?= $icon((string) ($step['icon'] ?? 'check')) ?></div><h3><?= $e($step['title'] ?? '') ?></h3><p><?= $e($step['copy'] ?? '') ?></p></li><?php endforeach; ?></ol>
    <div class="updates-policy"><div class="feature-icon"><?= $icon('shield-check') ?></div><div><span><?= $e($t('updates.policy_label')) ?></span><h2><?= $e($t('updates.policy_title')) ?></h2><p><?= $e($t('updates.policy_copy')) ?></p></div><a class="button button-primary" href="<?= $e($route('docs')) ?>"><?= $e($t('updates.policy_action')) ?><?= $icon('arrow-right') ?></a></div>
</div></section>
