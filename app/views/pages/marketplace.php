<?php

declare(strict_types=1);

$heroActions = [];
require dirname(__DIR__) . '/page-hero.php';
$releases = (array) ($state['marketplace_items'] ?? []);
$hasLicensedDownloads = (bool) array_filter($releases, static fn(array $release): bool => !empty($release['licensed']));
$filterTypes = ['all', 'system', 'theme', 'addon', 'plugin', 'application'];
$filterPrices = ['all', 'free', 'paid'];
?>
<section class="section marketplace-page">
    <div class="shell">
        <?php if (!empty($state['download_error'])): ?>
            <div class="marketplace-alert" role="alert"><?= $icon('shield-check') ?><span><?= $e($t('marketplace.download_error')) ?></span></div>
        <?php endif; ?>
        <div class="marketplace-toolbar">
            <div><strong data-marketplace-visible-count><?= count($releases) ?></strong><span><?= $e($t('marketplace.available')) ?></span></div>
            <p><?= $icon('shield-check') ?><?= $e($t('marketplace.verified')) ?></p>
        </div>
        <section class="marketplace-discovery" data-marketplace-filter data-count-template="<?= $e($t('marketplace.results_count')) ?>">
            <form class="marketplace-search" role="search">
                <label class="marketplace-query">
                    <span><?= $e($t('marketplace.search_label')) ?></span>
                    <span class="marketplace-query-field"><?= $icon('search') ?><input type="search" name="q" autocomplete="off" placeholder="<?= $e($t('marketplace.search_placeholder')) ?>" data-marketplace-query></span>
                </label>
                <label class="marketplace-select"><span><?= $e($t('marketplace.filter_type')) ?></span><select name="type" data-marketplace-type>
                    <?php foreach ($filterTypes as $type): ?><option value="<?= $e($type) ?>"><?= $e($type === 'all' ? $t('marketplace.filter_all') : $t('marketplace.types.' . $type, ucfirst($type))) ?></option><?php endforeach; ?>
                </select></label>
                <label class="marketplace-select"><span><?= $e($t('marketplace.filter_price')) ?></span><select name="price" data-marketplace-price>
                    <?php foreach ($filterPrices as $priceFilter): ?><option value="<?= $e($priceFilter) ?>"><?= $e($t('marketplace.filter_' . $priceFilter)) ?></option><?php endforeach; ?>
                </select></label>
            </form>
            <div class="marketplace-filter-groups">
                <div class="marketplace-filter-group"><span><?= $e($t('marketplace.filter_types_legend')) ?></span><div class="marketplace-chips" role="group" aria-label="<?= $e($t('marketplace.filter_types_legend')) ?>">
                    <?php foreach ($filterTypes as $type): ?><button type="button" class="<?= $type === 'all' ? 'is-active' : '' ?>" data-marketplace-type-chip="<?= $e($type) ?>" aria-pressed="<?= $type === 'all' ? 'true' : 'false' ?>"><?= $e($type === 'all' ? $t('marketplace.filter_all') : $t('marketplace.types.' . $type, ucfirst($type))) ?></button><?php endforeach; ?>
                </div></div>
                <div class="marketplace-filter-group"><span><?= $e($t('marketplace.filter_prices_legend')) ?></span><div class="marketplace-chips marketplace-price-chips" role="group" aria-label="<?= $e($t('marketplace.filter_prices_legend')) ?>">
                    <?php foreach ($filterPrices as $priceFilter): ?><button type="button" class="<?= $priceFilter === 'all' ? 'is-active' : '' ?>" data-marketplace-price-chip="<?= $e($priceFilter) ?>" aria-pressed="<?= $priceFilter === 'all' ? 'true' : 'false' ?>"><?= $e($t('marketplace.filter_' . $priceFilter)) ?></button><?php endforeach; ?>
                </div></div>
            </div>
            <div class="marketplace-filter-summary"><span data-marketplace-results role="status" aria-live="polite"><?= $e(str_replace('{count}', (string) count($releases), $t('marketplace.results_count'))) ?></span><button type="button" data-marketplace-clear hidden><?= $e($t('marketplace.clear_filters')) ?></button></div>
        </section>
        <div class="release-grid">
            <?php foreach ($releases as $release):
                $licensed = !$release['enabled'] && $release['licensed'];
                $variants = (array) ($release['variants'] ?? []);
                $metaKeys = (array) ($release['meta_keys'] ?? []);
                $priceKey = null;
                $detailMeta = [];
                foreach ($metaKeys as $metaKey) {
                    $metaKey = (string) $metaKey;
                    if (str_ends_with($metaKey, '_price')) $priceKey = $metaKey;
                    else $detailMeta[] = $t($metaKey);
                }
                $price = $t($priceKey ?? 'marketplace.free');
                $detailLabel = str_replace('{name}', (string) $release['name'], (string) $t('marketplace.details_open'));
            ?>
                <article id="package-<?= $e($release['id']) ?>" data-marketplace-item data-filter-type="<?= $e($release['type']) ?>" data-filter-price="<?= $priceKey === null ? 'free' : 'paid' ?>" data-filter-search="<?= $e($release['name'] . ' ' . $t('marketplace.types.' . $release['type'], ucfirst($release['type'])) . ' ' . $t($release['copy_key'])) ?>" class="<?= $release['enabled'] ? 'is-downloadable' : ($licensed ? 'is-licensed' : 'is-listed') ?><?= $variants ? ' is-variant-product' : '' ?><?= $release['card_class'] !== '' ? ' ' . $e($release['card_class']) : '' ?>">
                    <div class="release-top">
                        <button class="feature-icon" type="button" aria-label="<?= $e($detailLabel) ?>" data-marketplace-detail data-detail-name="<?= $e($release['name']) ?>" data-detail-type="<?= $e($t('marketplace.types.' . $release['type'], ucfirst($release['type']))) ?>" data-detail-copy="<?= $e($t($release['copy_key'])) ?>" data-detail-version="<?= $e($release['version']) ?>" data-detail-channel="<?= $e($t('marketplace.' . ($release['release_channel'] ?? 'stable'))) ?>" data-detail-price="<?= $e($price) ?>" data-detail-meta="<?= $e(json_encode($detailMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"><?= $icon($release['icon']) ?></button>
                        <span><?= $e($t('marketplace.types.' . $release['type'], ucfirst($release['type']))) ?></span>
                    </div>
                    <h2><?= $e($release['name']) ?></h2>
                    <p><?= $e($t($release['copy_key'])) ?></p>
                    <div class="release-meta">
                        <span>v<?= $e($release['version']) ?></span>
                        <span class="is-price<?= $priceKey === null ? ' is-free' : '' ?>"><?= $e($price) ?></span>
                        <?php if ($release['enabled'] || $licensed): ?>
                            <?php if ($release['size'] !== ''): ?><span><?= $e($release['size']) ?></span><?php endif; ?>
                            <?php foreach ($metaKeys as $metaKey): ?><?php if (!str_ends_with((string) $metaKey, '_price')): ?><span><?= $e($t((string) $metaKey)) ?></span><?php endif; ?><?php endforeach; ?>
                            <?php if (!$variants): ?><span><?= $e($t('marketplace.' . ($release['release_channel'] ?? 'stable'))) ?></span><?php endif; ?>
                        <?php else: ?>
                            <span><?= $e($t('marketplace.listed')) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (($release['enabled'] || $licensed) && $variants): ?>
                        <div class="variant-panel">
                            <div class="download-variants">
                                <?php foreach ($variants as $variant): ?>
                                    <?php if ($release['enabled']): ?>
                                    <form method="post" action="/download/request/" class="download-form">
                                        <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                        <input type="hidden" name="package" value="<?= $e($release['id']) ?>">
                                        <input type="hidden" name="variant" value="<?= $e($variant['key']) ?>">
                                        <button class="button <?= $variant['recommended'] ? 'button-primary' : 'button-secondary' ?>" type="submit">
                                            <span><strong><?= $e($t($variant['label_key'])) ?></strong><small><?= $e($variant['size']) ?><?php if ($variant['recommended']): ?> · <?= $e($t('marketplace.recommended')) ?><?php endif; ?></small></span>
                                            <?= $icon('arrow-right') ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <button class="button button-license<?= $release['locked'] ? ' is-locked' : '' ?>" type="button" data-license-download data-package="<?= $e($release['id']) ?>" data-variant="<?= $e($variant['key']) ?>" data-package-name="<?= $e($release['name'] . ' - ' . $t($variant['label_key'])) ?>" <?= $release['locked'] ? 'disabled aria-disabled="true"' : '' ?>>
                                            <span><strong><?= $e($t($variant['label_key'])) ?></strong><small data-download-label><?= $e($release['locked'] ? $t('marketplace.download_unavailable') : $variant['size'] . ($variant['recommended'] ? ' · ' . $t('marketplace.recommended') : '')) ?></small></span>
                                            <?= $icon('lock') ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($release['note_key'] !== ''): ?><p class="release-note"><?= $icon('shield-check') ?><span><?= $e($t($release['note_key'])) ?></span></p><?php endif; ?>
                        </div>
                    <?php elseif ($release['enabled']): ?>
                        <form method="post" action="/download/request/" class="download-form">
                            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                            <input type="hidden" name="package" value="<?= $e($release['id']) ?>">
                            <button class="button button-primary" type="submit"><?= $e($t('marketplace.download')) ?><?= $icon('arrow-right') ?></button>
                        </form>
                    <?php elseif ($licensed): ?>
                        <button class="button button-license<?= $release['locked'] ? ' is-locked' : '' ?>" type="button" data-license-download data-package="<?= $e($release['id']) ?>" data-package-name="<?= $e($release['name']) ?>" <?= $release['locked'] ? 'disabled aria-disabled="true"' : '' ?>>
                            <span data-download-label><?= $e($t($release['locked'] ? 'marketplace.download_unavailable' : 'marketplace.licensed_download')) ?></span><?= $icon('lock') ?>
                        </button>
                    <?php else: ?>
                        <button class="button button-disabled" type="button" disabled aria-disabled="true"><?= $e($t('marketplace.download_unavailable')) ?><?= $icon('lock') ?></button>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="marketplace-empty" data-marketplace-empty hidden><div><?= $icon('search') ?></div><h2><?= $e($t('marketplace.no_results_title')) ?></h2><p><?= $e($t('marketplace.no_results_copy')) ?></p><button class="button button-secondary" type="button" data-marketplace-empty-clear><?= $e($t('marketplace.clear_filters')) ?></button></div>
        <div class="marketplace-note"><?= $icon('shield-check') ?><div><h2><?= $e($t('marketplace.security_title')) ?></h2><p><?= $e($t('marketplace.security_copy')) ?></p></div></div>
    </div>
</section>
<dialog class="product-dialog" data-product-dialog aria-labelledby="product-dialog-title">
    <div class="product-dialog-head"><div class="product-dialog-icon" data-product-dialog-icon></div><button type="button" class="license-dialog-close" data-product-dialog-close aria-label="<?= $e($t('marketplace.details_close')) ?>">×</button></div>
    <div class="product-dialog-copy"><span data-product-dialog-type></span><h2 id="product-dialog-title" data-product-dialog-name></h2><p data-product-dialog-copy></p></div>
    <dl class="product-dialog-facts">
        <div><dt><?= $e($t('marketplace.details_version')) ?></dt><dd data-product-dialog-version></dd></div>
        <div><dt><?= $e($t('marketplace.details_channel')) ?></dt><dd data-product-dialog-channel></dd></div>
        <div><dt><?= $e($t('marketplace.details_price')) ?></dt><dd class="product-dialog-price" data-product-dialog-price></dd></div>
        <div data-product-dialog-meta-wrap><dt><?= $e($t('marketplace.details_requirements')) ?></dt><dd><ul data-product-dialog-meta></ul></dd></div>
    </dl>
    <div class="product-dialog-actions"><button class="button button-primary" type="button" data-product-dialog-close><?= $e($t('marketplace.details_close')) ?></button></div>
</dialog>
<?php if ($hasLicensedDownloads): ?>
    <dialog class="license-dialog" data-license-dialog aria-labelledby="license-dialog-title">
        <div class="license-dialog-head"><div class="license-dialog-icon"><?= $icon('lock') ?></div><button type="button" class="license-dialog-close" data-license-close aria-label="<?= $e($t('marketplace.license_cancel')) ?>">×</button></div>
        <div class="license-dialog-copy"><span>Eduvixo Marketplace</span><h2 id="license-dialog-title"><?= $e($t('marketplace.license_modal_title')) ?></h2><p><?= $e($t('marketplace.license_modal_copy')) ?></p><strong data-license-package-name></strong></div>
        <form data-license-form data-endpoint="/download/license/" data-locked-label="<?= $e($t('marketplace.download_unavailable')) ?>" data-network-error="<?= $e($t('marketplace.license_service_error')) ?>" novalidate>
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="package" value=""><input type="hidden" name="variant" value="">
            <label for="marketplace-license-key"><?= $e($t('marketplace.license_label')) ?></label>
            <div class="license-input"><span><?= $icon('lock') ?></span><input id="marketplace-license-key" name="license" type="text" maxlength="128" required autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="<?= $e($t('marketplace.license_placeholder')) ?>"></div>
            <p class="license-privacy"><?= $icon('shield-check') ?><?= $e($t('marketplace.license_privacy')) ?></p>
            <p class="license-status" data-license-status role="status" aria-live="polite" hidden></p>
            <div class="license-actions"><button class="button button-ghost" type="button" data-license-close><?= $e($t('marketplace.license_cancel')) ?></button><button class="button button-primary" type="submit" data-license-submit data-default-label="<?= $e($t('marketplace.license_submit')) ?>" data-checking-label="<?= $e($t('marketplace.license_checking')) ?>"><span data-license-submit-label><?= $e($t('marketplace.license_submit')) ?></span><?= $icon('arrow-right') ?></button></div>
        </form>
    </dialog>
<?php endif; ?>
