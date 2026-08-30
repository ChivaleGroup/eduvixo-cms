# Marketplace dynamic search and filters - 2026-08-30

## Scope

- Replaced the Marketplace beta notice with a live product discovery panel.
- Added text search, category and pricing selects, synchronized category and pricing chips, live result count, shareable filter URL state, a clear action and a zero-results state.
- Category values: All, System, Theme, Add-on, Plugin and Application.
- Pricing values: All, Free and Paid.
- Added complete UI copy for English, German, Chinese, Vietnamese, Thai, Lao and Polish.
- Preserved product detail and licensed-download modal behavior.

## Implementation

- Marketplace items expose normalized type, pricing and localized search data as HTML data attributes.
- Filtering runs locally without a request or page reload. Search is case-insensitive and diacritic-insensitive.
- Selects and chip groups share one state. Active values are reflected in `q`, `type` and `price` query parameters using `history.replaceState`.
- Controls use native labels, grouped buttons, `aria-pressed`, `aria-live`, keyboard focus states and responsive layouts.

## Production deployment

- Target: `/var/www/clients/client9/web123/web` (`web123:client9`).
- Deployment: 13 files published atomically; PHP-FPM reloaded without a service stop.
- Backup: `/root/eduvixo-backups/marketplace-filter-pre-20260830-125542`.
- Backup archive size: 97,493 bytes.
- Rollback instructions are stored in the backup directory as `ROLLBACK.txt`.
- Database changes: none.

## Validation

- `php -l app/views/pages/marketplace.php`: passed.
- `node --check resources/site.js`: passed.
- Seven language JSON documents parsed and all required keys were present.
- Asset build completed: 47,640 CSS bytes and 9,652 JavaScript bytes.
- `git diff --check`: passed.
- Local interactive browser tests passed:
  - search `firewall` returned only iFirewall;
  - Plugin + Paid returned six paid plugins;
  - Theme + Paid rendered the localized zero-results state;
  - clear restored all 13 products;
  - URL state, select/chip synchronization and active states were correct;
  - 390 x 844 responsive test had no horizontal overflow;
  - no browser console warnings or errors.
- Production audit passed for all seven locale routes, 13 product classifications, the search icon, CSS, JavaScript, Apache and PHP-FPM.
- Final HTTPS browser test returned one Google Analytics result for `analytics` and no console errors.
- Two unrelated legacy test runners could not complete in the local Windows environment: the AI Translation test requires its loopback Ollama service after completing four security checks, and `system-update-tests.php` contains a Linux-only historical `/var/www/clients/client59/web119/web` path. Neither runner covers the Marketplace presentation changes; the targeted local browser and production audits above completed successfully.

## Risk and outstanding items

- Risk is limited to Marketplace presentation and client-side filtering. Download security, package files, catalog data and database state were not changed.
- No outstanding implementation items for this scope.
