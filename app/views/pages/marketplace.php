<?php

declare(strict_types=1);

$heroActions = [];
require dirname(__DIR__) . '/page-hero.php';
$releases = (array) ($state['marketplace_items'] ?? []);
$hasLicensedDownloads = (bool) array_filter($releases, static fn(array $release): bool => !empty($release['licensed']));
?>
<section class="section marketplace-page">
    <div class="shell">
        <?php if (!empty($state['download_error'])): ?>
            <div class="marketplace-alert" role="alert"><?= $icon('shield-check') ?><span><?= $e($t('marketplace.download_error')) ?></span></div>
        <?php endif; ?>
        <div class="marketplace-toolbar">
            <div><strong><?= count($releases) ?></strong><span><?= $e($t('marketplace.available')) ?></span></div>
            <p><?= $icon('shield-check') ?><?= $e($t('marketplace.verified')) ?></p>
        </div>
        <?php if (array_filter($releases, static fn(array $release): bool => ($release['release_channel'] ?? 'stable') === 'beta')): ?>
            <div class="marketplace-alert" role="note"><?= $icon('calendar-days') ?><span><?= $e($t('marketplace.calendar_beta_notice')) ?></span></div>
        <?php endif; ?>
        <div class="release-grid">
            <?php foreach ($releases as $release):
                $licensed = !$release['enabled'] && $release['licensed'];
                $variants = (array) ($release['variants'] ?? []);
            ?>
                <article id="package-<?= $e($release['id']) ?>" class="<?= $release['enabled'] ? 'is-downloadable' : ($licensed ? 'is-licensed' : 'is-listed') ?><?= $variants ? ' is-variant-product' : '' ?><?= $release['card_class'] !== '' ? ' ' . $e($release['card_class']) : '' ?>">
                    <div class="release-top">
                        <div class="feature-icon"><?= $icon($release['icon']) ?></div>
                        <span><?= $e($t('marketplace.types.' . $release['type'], ucfirst($release['type']))) ?></span>
                    </div>
                    <h2><?= $e($release['name']) ?></h2>
                    <p><?= $e($t($release['copy_key'])) ?></p>
                    <div class="release-meta">
                        <span>v<?= $e($release['version']) ?></span>
                        <?php if ($release['enabled'] || $licensed): ?>
                            <?php if ($release['size'] !== ''): ?><span><?= $e($release['size']) ?></span><?php endif; ?>
                            <?php foreach ((array) ($release['meta_keys'] ?? []) as $metaKey): ?><span<?= str_ends_with((string) $metaKey, '_price') ? ' class="is-price"' : '' ?>><?= $e($t((string) $metaKey)) ?></span><?php endforeach; ?>
                            <?php if (!$variants): ?><span><?= $e($t('marketplace.' . ($release['release_channel'] ?? 'stable'))) ?></span><?php endif; ?>
                        <?php else: ?>
                            <span><?= $e($t('marketplace.listed')) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($release['enabled'] && $variants): ?>
                        <div class="variant-panel">
                            <div class="download-variants">
                                <?php foreach ($variants as $variant): ?>
                                    <form method="post" action="/download/request/" class="download-form">
                                        <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                        <input type="hidden" name="package" value="<?= $e($release['id']) ?>">
                                        <input type="hidden" name="variant" value="<?= $e($variant['key']) ?>">
                                        <button class="button <?= $variant['recommended'] ? 'button-primary' : 'button-secondary' ?>" type="submit">
                                            <span><strong><?= $e($t($variant['label_key'])) ?></strong><small><?= $e($variant['size']) ?><?php if ($variant['recommended']): ?> · <?= $e($t('marketplace.recommended')) ?><?php endif; ?></small></span>
                                            <?= $icon('arrow-right') ?>
                                        </button>
                                    </form>
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
        <div class="marketplace-note"><?= $icon('shield-check') ?><div><h2><?= $e($t('marketplace.security_title')) ?></h2><p><?= $e($t('marketplace.security_copy')) ?></p></div></div>
    </div>
</section>
<?php if ($hasLicensedDownloads): ?>
    <dialog class="license-dialog" data-license-dialog aria-labelledby="license-dialog-title">
        <div class="license-dialog-head"><div class="license-dialog-icon"><?= $icon('lock') ?></div><button type="button" class="license-dialog-close" data-license-close aria-label="<?= $e($t('marketplace.license_cancel')) ?>">×</button></div>
        <div class="license-dialog-copy"><span>Eduvixo Marketplace</span><h2 id="license-dialog-title"><?= $e($t('marketplace.license_modal_title')) ?></h2><p><?= $e($t('marketplace.license_modal_copy')) ?></p><strong data-license-package-name></strong></div>
        <form data-license-form data-endpoint="/download/license/" data-locked-label="<?= $e($t('marketplace.download_unavailable')) ?>" data-network-error="<?= $e($t('marketplace.license_service_error')) ?>" novalidate>
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="package" value="">
            <label for="marketplace-license-key"><?= $e($t('marketplace.license_label')) ?></label>
            <div class="license-input"><span><?= $icon('lock') ?></span><input id="marketplace-license-key" name="license" type="text" maxlength="128" required autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="<?= $e($t('marketplace.license_placeholder')) ?>"></div>
            <p class="license-privacy"><?= $icon('shield-check') ?><?= $e($t('marketplace.license_privacy')) ?></p>
            <p class="license-status" data-license-status role="status" aria-live="polite" hidden></p>
            <div class="license-actions"><button class="button button-ghost" type="button" data-license-close><?= $e($t('marketplace.license_cancel')) ?></button><button class="button button-primary" type="submit" data-license-submit data-default-label="<?= $e($t('marketplace.license_submit')) ?>" data-checking-label="<?= $e($t('marketplace.license_checking')) ?>"><span data-license-submit-label><?= $e($t('marketplace.license_submit')) ?></span><?= $icon('arrow-right') ?></button></div>
        </form>
    </dialog>
<?php endif; ?>
