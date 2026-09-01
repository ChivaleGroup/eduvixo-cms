<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$packageRoot = $root . '/storage/marketplace/packages';

return [
    'package_root' => $packageRoot,
    'token_ttl' => 180,
    'license_cache_ttl' => 300,
    'license_key_max_length' => 128,
    'license_failure_limit' => 3,
    'license_failure_window' => 3600,
    'license_lock_ttl' => 3600,
    'license_endpoint' => 'https://www.chivale.com/license/',
    'license_product_name' => 'Eduvixo',
    'license_product_model' => 'Education Digital Experience & Communication Platform',
    'license_product_version' => '1.0',
    'packages' => [
        'b843df54f8988bad5b884f54dceb7250' => [
            'type' => 'system', 'slug' => 'eduvixo-cms', 'name' => 'Eduvixo CMS', 'version' => '1.0.1', 'size' => 10406984,
            'file' => $packageRoot . '/eduvixo-install-1.0.1.zip', 'checksum' => 'fbbd9b8df946979ded70f60106e00668b0c17fe6df0642d070d78504e60bd519',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'layers', 'copy_key' => 'marketplace.system_copy',
            'meta_keys' => ['marketplace.cms_price'],
        ],
        '56b33a4022d3ae4e11150c080f3e6189' => [
            'type' => 'theme', 'slug' => 'eduvixo', 'name' => 'Eduvixo', 'version' => '1.1.9', 'size' => 1570175,
            'file' => $packageRoot . '/eduvixo-theme-1.1.9.zip', 'checksum' => 'c4261ba1a8a9955539e4a7327b7e23abb983fa49cedcd1e0dcf9ca8d745603a4',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'layout', 'copy_key' => 'marketplace.eduvixo_copy',
        ],
        '60f0067c95f1e2719a97e66c7b78633c' => [
            'type' => 'theme', 'slug' => 'shoudu', 'name' => 'Shoudu', 'version' => '1.1.3', 'size' => 9454591,
            'file' => $packageRoot . '/shoudu-theme-1.1.3.zip', 'checksum' => '2d10a98eff9c0d938c7393192048bb57d8969700de1bfd53f64ad05c2c596387',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'graduation-cap', 'copy_key' => 'marketplace.shoudu_copy',
        ],
        '7f197682f4a6ab6bdde11578611ae511' => [
            'type' => 'plugin', 'slug' => 'ifirewall', 'name' => 'iFirewall', 'version' => '1.0.0', 'size' => 37193,
            'file' => $packageRoot . '/ifirewall-php-1.0.0.zip', 'checksum' => 'b70d4aee3303dbd55afa07112e650320fe5aac2bccda58d4daeb18385044c1a1',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'shield-check', 'copy_key' => 'marketplace.ifirewall_copy',
            'meta_keys' => ['marketplace.ifirewall_price', 'marketplace.php_85'], 'card_class' => 'is-premium-plugin',
            'license_product_name' => 'iFirewall', 'license_product_model' => 'PHP Security & IP Firewall', 'license_product_version' => '1.0.0',
        ],
        'd7a9559c60326f90ccf5fbda507c9b50' => [
            'type' => 'addon', 'slug' => 'calendar', 'name' => 'Eduvixo Calendar', 'version' => '1.1.1', 'release_channel' => 'stable', 'size' => 65934,
            'file' => $packageRoot . '/eduvixo-calendar-1.1.1.zip', 'checksum' => '45788257b17d16eacba7d4e2e3e1fd5b629fcdc9781a31d432fe9701d84b22f8',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-days', 'copy_key' => 'marketplace.calendar_copy',
            'meta_keys' => ['marketplace.calendar_price'], 'card_class' => 'is-premium-plugin',
            'license_product_name' => 'Eduvixo Calendar', 'license_product_model' => 'Education Scheduling & Notifications', 'license_product_version' => '1.1.1',
        ],
        '292dea9e61c2cc57d8f067d8b3f26a00' => [
            'type' => 'plugin', 'slug' => 'google-calendar', 'name' => 'Google Calendar', 'version' => '1.1.0', 'release_channel' => 'stable', 'size' => 3079,
            'file' => $packageRoot . '/google-calendar-1.1.0.zip', 'checksum' => '349ed7b3325b21844f4b2e5da66bec615bb3bdf65964fceea442d2e316309b08',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.google_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Google Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.1.0',
        ],
        '944aa50a0453b031017751abd243a77c' => [
            'type' => 'plugin', 'slug' => 'apple-calendar', 'name' => 'Apple Calendar', 'version' => '1.1.0', 'release_channel' => 'stable', 'size' => 3422,
            'file' => $packageRoot . '/apple-calendar-1.1.0.zip', 'checksum' => '13dd1f0a6bb0791e173786a0f72a4491a7e138a460ca492462ac81eb61331a0e',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.apple_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Apple Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.1.0',
        ],
        '20fab2cf5cc28954c5269d1435dd7160' => [
            'type' => 'plugin', 'slug' => 'microsoft-365-calendar', 'name' => 'Microsoft 365 Calendar', 'version' => '1.1.0', 'release_channel' => 'stable', 'size' => 3141,
            'file' => $packageRoot . '/microsoft-365-calendar-1.1.0.zip', 'checksum' => '6fd763fe5d3f12701153d57e44520f3eabb7985fd300f5ab1ebf957377f6cda5',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.microsoft_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Microsoft 365 Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.1.0',
        ],
        '40845422103c0ed50f9e7bdc6df97400' => [
            'type' => 'plugin', 'slug' => 'telegram-notifications', 'name' => 'Telegram Notifications', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 3044,
            'file' => $packageRoot . '/telegram-notifications-1.0.2-beta.1.zip', 'checksum' => '0817a5686b486dd1d3241c985ac55f491cdb579614922265264845e16ef852e4',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'send', 'copy_key' => 'marketplace.telegram_copy',
            'meta_keys' => ['marketplace.notification_price', 'marketplace.system_notifications'], 'license_product_name' => 'Telegram Notifications for Eduvixo', 'license_product_model' => 'Calendar Notification Integration', 'license_product_version' => '1.0.0',
        ],
        '854d28de19fe0025dc1f786786720529' => [
            'type' => 'plugin', 'slug' => 'whatsapp-notifications', 'name' => 'WhatsApp Notifications', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 3522,
            'file' => $packageRoot . '/whatsapp-notifications-1.0.2-beta.1.zip', 'checksum' => '13cecb339ab09c965db7b3eb784c11b03c9f6d50e9b72e089c33920515aba9ad',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'message-circle', 'copy_key' => 'marketplace.whatsapp_copy',
            'meta_keys' => ['marketplace.notification_price', 'marketplace.system_notifications'], 'license_product_name' => 'WhatsApp Notifications for Eduvixo', 'license_product_model' => 'Calendar Notification Integration', 'license_product_version' => '1.0.0',
        ],
        'c42137f830b6a10e8896a57eddfe6aee' => [
            'type' => 'plugin', 'slug' => 'google-analytics', 'name' => 'Google Analytics', 'version' => '1.0.0', 'size' => 8845,
            'file' => $packageRoot . '/google-analytics-1.0.0.zip', 'checksum' => '68ddbc291b03e87afbaeb4ac2fef1d966b1ca982edc40ff9d5d38e15b9ad4c1f',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'chart-line', 'copy_key' => 'marketplace.google_analytics_copy',
            'license_product_name' => 'Google Analytics for Eduvixo', 'license_product_model' => 'Web Analytics Integration', 'license_product_version' => '1.0.0',
        ],
        'ab80e3241f74ffa8f0d554f6ddf2b47a' => [
            'type' => 'plugin', 'slug' => 'ai-translation-assistant', 'name' => 'AI Translation Assistant', 'version' => '1.0.0-beta.1', 'release_channel' => 'beta', 'size' => 15628,
            'file' => $packageRoot . '/ai-translation-assistant-1.0.0-beta.1.zip', 'checksum' => 'd82f312c24037509814323371ce63dd52cd9037e4ed2a347d67f9a98c4ca7c72',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'languages', 'copy_key' => 'marketplace.ai_translation_copy',
            'license_product_name' => 'AI Translation Assistant', 'license_product_model' => 'AI-Assisted Multilingual Content', 'license_product_version' => '1.0.0',
        ],
        'e0250d15236f4c57ad2f32ff16e129e3' => [
            'type' => 'application', 'slug' => 'eduvixo-windows', 'name' => 'Eduvixo for Windows', 'version' => '0.2.3',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'devices',
            'copy_key' => 'marketplace.windows_copy', 'meta_keys' => ['marketplace.portable', 'marketplace.windows_compatibility'],
            'note_key' => 'marketplace.windows_notice',
            'variants' => [
                'x64' => [
                    'label_key' => 'marketplace.windows_x64', 'recommended' => true, 'size' => 157791936,
                    'file' => $packageRoot . '/eduvixo-windows-0.2.3-x64.exe', 'checksum' => '0633a2025d12c6fab266bb233bb258f6b7ba10dc3220c1644edd5dbf6738656d',
                    'download_name' => 'eduvixo-windows-0.2.3-x64.exe', 'content_type' => 'application/vnd.microsoft.portable-executable',
                ],
                'x86' => [
                    'label_key' => 'marketplace.windows_x86', 'recommended' => false, 'size' => 148772544,
                    'file' => $packageRoot . '/eduvixo-windows-0.2.3-x86.exe', 'checksum' => 'bef08fe7b71f3473e366fd33fba3cc6193781f17ae2ed71fcf9f2c47328986ef',
                    'download_name' => 'eduvixo-windows-0.2.3-x86.exe', 'content_type' => 'application/vnd.microsoft.portable-executable',
                ],
            ],
        ],
    ],
];
