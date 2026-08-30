# Multilingual content refinement - 2026-08-31

## Scope

- Audited all website copy in English, German, Chinese, Vietnamese, Thai, Lao and Polish.
- Preserved the existing `lang/xx.json` architecture and all 593 translation leaves per locale.
- Refined product, service, Marketplace, support, update, contact, privacy, terms, ecosystem and SEO copy where wording was incomplete, overly literal or technically inaccurate.
- Replaced remaining em dashes with normal hyphens and removed invisible Unicode formatting/control characters.
- Shortened the longest SEO titles and descriptions while retaining the page intent and localized search terms.

## Editorial results

The repeatable refinement script applies 325 reviewed copy corrections:

- English: 8
- German: 62
- Polish: 19
- Chinese: 69
- Vietnamese: 61
- Thai: 47
- Lao: 59

The largest improvements cover approved institutional AI knowledge, campus/location terminology, package verification and rollback, self-hosted delivery, implementation services, contact copy, privacy/rate-limiting explanations and the current Marketplace portfolio.

## Validation

- 7 locales with exactly 593 leaves each.
- Zero missing or extra keys and zero type mismatches.
- Zero empty strings, placeholder mismatches, hidden control characters, Unicode replacement characters or em dashes.
- Every locale contains titles and descriptions for all 12 public pages plus shared localized keywords and social-image text.
- Local HTTP audit: 84/84 routes returned complete localized HTML with description, keywords, Open Graph, Twitter Card, canonical, JSON-LD and all hreflang alternates.
- Responsive browser QA: German desktop plus German, Polish, Thai, Vietnamese, Lao and Chinese mobile views; no content overflow or clipped UI. The only off-screen nodes were the intentional contact-form honeypot.
- Production HTTP audit: 84/84 routes passed after deployment.
- Production sitemap: 84 URLs.
- Local and production SHA-256 values match for all deployed files.

## Deployment

- Target: `/var/www/clients/client9/web123/web`
- Files: seven `lang/*.json` files and `public/sitemap.xml`
- Owner/group: `web123:client9`
- Mode: `0640`
- Method: staged validation and atomic file replacement.
- No database, CMS application, service or infrastructure changes.

Backup:

- `/root/eduvixo-backups/language-content-refinement-pre-20260830-184326`
- `website-language-files.tar.gz`: SHA-256 `6e6dea993340add7d75ec2fa50783bbb646a76ce8204a7dda0995634d54da4a7`
- `ROLLBACK.txt`: SHA-256 `5f1b4019cf6f1b54345c11a1c2220a51046fc10081448144892803914e43e558`

Rollback: extract the backup archive over the website root, restore `web123:client9` ownership and mode `0640`, then rerun the 84-route and SEO audit. The first transport attempt used a timestamped archive name and stopped before backup or publication; no production files were changed during that attempt.
