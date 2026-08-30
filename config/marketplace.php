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
            'type' => 'theme', 'slug' => 'eduvixo', 'name' => 'Eduvixo', 'version' => '1.1.6', 'size' => 1565831,
            'file' => $packageRoot . '/eduvixo-theme-1.1.6.zip', 'checksum' => '67f85343d5c32650537e5c3576640312c639efd6ee05d6bc8a0469896f3d6769',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'icon' => 'layout', 'copy_key' => 'marketplace.eduvixo_copy',
        ],
        '60f0067c95f1e2719a97e66c7b78633c' => [
            'type' => 'theme', 'slug' => 'shoudu', 'name' => 'Shoudu', 'version' => '1.1.1', 'size' => 9450487,
            'file' => $packageRoot . '/shoudu-theme-1.1.1.zip', 'checksum' => '04e60ff5e3dd659cb41e914b3c5acbc008cdd2687a12f6b0355fe37c86aa9ee8',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'graduation-cap', 'copy_key' => 'marketplace.shoudu_copy',
        ],
        '7f197682f4a6ab6bdde11578611ae511' => [
            'type' => 'plugin', 'slug' => 'ifirewall', 'name' => 'iFirewall', 'version' => '1.0.0', 'size' => 37193,
            'file' => $packageRoot . '/ifirewall-php-1.0.0.zip', 'checksum' => 'b70d4aee3303dbd55afa07112e650320fe5aac2bccda58d4daeb18385044c1a1',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'shield-check', 'copy_key' => 'marketplace.ifirewall_copy',
            'meta_keys' => ['marketplace.ifirewall_price', 'marketplace.php_85'], 'card_class' => 'is-premium-plugin',
            'license_product_name' => 'iFirewall', 'license_product_model' => 'PHP Security & IP Firewall', 'license_product_version' => '1.0.0',
        ],
        'd7a9559c60326f90ccf5fbda507c9b5' => [
            'type' => 'addon', 'slug' => 'calendar', 'name' => 'Eduvixo Calendar', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 35418,
            'file' => $packageRoot . '/eduvixo-calendar-1.0.2-beta.1.zip', 'checksum' => 'b54d261083962a5bc57c38c45b5d24d76b5db59d28a700b966f3e59ebe4962cd',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'calendar-days', 'copy_key' => 'marketplace.calendar_copy',
            'meta_keys' => ['marketplace.calendar_price'], 'card_class' => 'is-premium-plugin',
            'license_product_name' => 'Eduvixo Calendar', 'license_product_model' => 'Education Scheduling & Notifications', 'license_product_version' => '1.0.0',
        ],
        '292dea9e61c2cc57d8f067d8b3f26a' => [
            'type' => 'plugin', 'slug' => 'google-calendar', 'name' => 'Google Calendar', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 2918,
            'file' => $packageRoot . '/google-calendar-1.0.2-beta.1.zip', 'checksum' => 'd506c91fe9a570433dbb1a5e53c0de4feae24d12e30ff066ede6ff00f4d5a338',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.google_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Google Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.0.0',
        ],
        '944aa50a0453b031017751abd243a77c' => [
            'type' => 'plugin', 'slug' => 'apple-calendar', 'name' => 'Apple Calendar', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 3252,
            'file' => $packageRoot . '/apple-calendar-1.0.2-beta.1.zip', 'checksum' => 'e978a7bbb455c4d4d54f098553771c78f3e925258be92d4d65f442ef9197d505',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.apple_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Apple Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.0.0',
        ],
        '20fab2cf5cc28954c5269d1435dd716' => [
            'type' => 'plugin', 'slug' => 'microsoft-365-calendar', 'name' => 'Microsoft 365 Calendar', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 2981,
            'file' => $packageRoot . '/microsoft-365-calendar-1.0.2-beta.1.zip', 'checksum' => '2077770f220822e78b42bf90383bf2254cce73887ccaf40e4077665620591599',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.microsoft_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Microsoft 365 Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.0.0',
        ],
        '40845422103c0ed50f9e7bdc6df974' => [
            'type' => 'plugin', 'slug' => 'telegram-notifications', 'name' => 'Telegram Notifications', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 3044,
            'file' => $packageRoot . '/telegram-notifications-1.0.2-beta.1.zip', 'checksum' => '0817a5686b486dd1d3241c985ac55f491cdb579614922265264845e16ef852e4',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'send', 'copy_key' => 'marketplace.telegram_copy',
            'meta_keys' => ['marketplace.notification_price', 'marketplace.system_notifications'], 'license_product_name' => 'Telegram Notifications for Eduvixo', 'license_product_model' => 'Calendar Notification Integration', 'license_product_version' => '1.0.0',
        ],
        '854d28de19fe0025dc1f786786720529' => [
            'type' => 'plugin', 'slug' => 'whatsapp-notifications', 'name' => 'WhatsApp Notifications', 'version' => '1.0.2-beta.1', 'release_channel' => 'beta', 'size' => 3522,
            'file' => $packageRoot . '/whatsapp-notifications-1.0.2-beta.1.zip', 'checksum' => '13cecb339ab09c965db7b3eb784c11b03c9f6d50e9b72e089c33920515aba9ad',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'message-circle', 'copy_key' => 'marketplace.whatsapp_copy',
            'meta_keys' => ['marketplace.notification_price', 'marketplace.system_notifications'], 'license_product_name' => 'WhatsApp Notifications for Eduvixo', 'license_product_model' => 'Calendar Notification Integration', 'license_product_version' => '1.0.0',
        ],
        'c42137f830b6a10e8896a57eddfe6aee' => [
            'type' => 'plugin', 'slug' => 'google-analytics', 'name' => 'Google Analytics', 'version' => '1.0.0', 'size' => 8845,
            'file' => $packageRoot . '/google-analytics-1.0.0.zip', 'checksum' => '68ddbc291b03e87afbaeb4ac2fef1d966b1ca982edc40ff9d5d38e15b9ad4c1f',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'chart-line', 'copy_key' => 'marketplace.google_analytics_copy',
            'license_product_name' => 'Google Analytics for Eduvixo', 'license_product_model' => 'Web Analytics Integration', 'license_product_version' => '1.0.0',
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
