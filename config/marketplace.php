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
    // Legacy license contract names are intentionally retained below as non-public compatibility identifiers.
    'packages' => [
        'b843df54f8988bad5b884f54dceb7250' => [
            'type' => 'system', 'slug' => 'eduvixo-cms', 'name' => 'Base CMS', 'version' => '1.0.16', 'release_channel' => 'stable', 'size' => 10426263,
            'file' => $packageRoot . '/eduvixo-install-1.0.16.zip', 'checksum' => '0005cc400518596a775c067cae5844a51e01719f7a7f98562549b9a4c8987b2b',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'layers', 'copy_key' => 'marketplace.system_copy',
            'meta_keys' => ['marketplace.cms_price'],
        ],
        '56b33a4022d3ae4e11150c080f3e6189' => [
            'type' => 'theme', 'slug' => 'eduvixo', 'name' => 'Eduvixo', 'version' => '1.1.10', 'size' => 1570250,
            'file' => $packageRoot . '/eduvixo-theme-1.1.10.zip', 'checksum' => 'd8b4660f4d7dcc9ae1fc88736124a1e5b5e3524cd358e1e004f742731c459345',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'layout', 'copy_key' => 'marketplace.eduvixo_copy',
        ],
        '60f0067c95f1e2719a97e66c7b78633c' => [
            'type' => 'theme', 'slug' => 'shoudu', 'name' => 'Shoudu Custom Theme', 'version' => '1.1.4', 'size' => 9454662,
            'file' => $packageRoot . '/shoudu-theme-1.1.4.zip', 'checksum' => '89430c1fc40366bce232f70cba41f5ed6556576c83049b187454fcd7def50c39',
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
            'type' => 'addon', 'slug' => 'calendar', 'name' => 'My Calendar', 'version' => '1.1.5', 'release_channel' => 'stable', 'size' => 67091,
            'file' => $packageRoot . '/eduvixo-calendar-1.1.5.zip', 'checksum' => '283de002ea521f221fbfa8cb900601c4950029028a86a5a708cc958402ea024e',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-days', 'copy_key' => 'marketplace.calendar_copy',
            'meta_keys' => ['marketplace.calendar_price'], 'card_class' => 'is-premium-plugin',
            'license_product_name' => 'Eduvixo Calendar', 'license_product_model' => 'Education Scheduling & Notifications', 'license_product_version' => '1.1.5',
        ],
        '292dea9e61c2cc57d8f067d8b3f26a00' => [
            'type' => 'plugin', 'slug' => 'google-calendar', 'name' => 'Google Calendar', 'version' => '1.1.1', 'release_channel' => 'stable', 'size' => 3063,
            'file' => $packageRoot . '/google-calendar-1.1.1.zip', 'checksum' => 'b271edc186d055e4664d9ccde8d176a832ad806e07b78a0cba48584569a66e9a',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.google_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Google Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.1.0',
        ],
        '944aa50a0453b031017751abd243a77c' => [
            'type' => 'plugin', 'slug' => 'apple-calendar', 'name' => 'Apple Calendar', 'version' => '1.1.1', 'release_channel' => 'stable', 'size' => 3408,
            'file' => $packageRoot . '/apple-calendar-1.1.1.zip', 'checksum' => '08658d2e08829b9d327a9f6e9db14c04d6fd5ff50b45a198b9694456d3aa04cf',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.apple_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Apple Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.1.0',
        ],
        '20fab2cf5cc28954c5269d1435dd7160' => [
            'type' => 'plugin', 'slug' => 'microsoft-365-calendar', 'name' => 'Microsoft 365 Calendar', 'version' => '1.1.1', 'release_channel' => 'stable', 'size' => 3129,
            'file' => $packageRoot . '/microsoft-365-calendar-1.1.1.zip', 'checksum' => 'e286b9ca1b5ba31a072aa749dc3a5a2495016e948e5f7c3786bcab4fdfe109ea',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'calendar-sync', 'copy_key' => 'marketplace.microsoft_calendar_copy',
            'meta_keys' => ['marketplace.integration_price', 'marketplace.requires_calendar'], 'license_product_name' => 'Microsoft 365 Calendar for Eduvixo', 'license_product_model' => 'Calendar Integration', 'license_product_version' => '1.1.0',
        ],
        '40845422103c0ed50f9e7bdc6df97400' => [
            'type' => 'plugin', 'slug' => 'telegram-notifications', 'name' => 'Telegram Notifications', 'version' => '1.2.0-beta.1', 'release_channel' => 'beta', 'size' => 3904,
            'file' => $packageRoot . '/telegram-notifications-1.2.0-beta.1.zip', 'checksum' => '1bd6d4ee66473d3857e81791fb70a0fa9d710647c5cd77361b92138d43f2b94c',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'send', 'copy_key' => 'marketplace.telegram_copy',
            'meta_keys' => ['marketplace.notification_price', 'marketplace.system_notifications'], 'license_product_name' => 'Telegram Notifications', 'license_product_model' => 'System and Calendar Notification Integration', 'license_product_version' => '1.2.0-beta.1',
        ],
        '854d28de19fe0025dc1f786786720529' => [
            'type' => 'plugin', 'slug' => 'whatsapp-notifications', 'name' => 'WhatsApp Notifications', 'version' => '1.1.0-beta.2', 'release_channel' => 'beta', 'size' => 3505,
            'file' => $packageRoot . '/whatsapp-notifications-1.1.0-beta.2.zip', 'checksum' => 'c61e1acf15a35f4d899f7aac1f05d20db0f51ad7c8acddaf1ef98f1ebe59fb29',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'message-circle', 'copy_key' => 'marketplace.whatsapp_copy',
            'meta_keys' => ['marketplace.notification_price', 'marketplace.system_notifications'], 'license_product_name' => 'WhatsApp Notifications for Eduvixo', 'license_product_model' => 'System and Calendar Notification Integration', 'license_product_version' => '1.1.0-beta.1',
        ],
        'c42137f830b6a10e8896a57eddfe6aee' => [
            'type' => 'plugin', 'slug' => 'google-analytics', 'name' => 'Google Analytics', 'version' => '1.0.1', 'size' => 8827,
            'file' => $packageRoot . '/google-analytics-1.0.1.zip', 'checksum' => '885448f4192e244d3b6478c51bafaeceffbd8535e90d1878f130b1eb8c9ab2b8',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'chart-line', 'copy_key' => 'marketplace.google_analytics_copy',
            'license_product_name' => 'Google Analytics for Eduvixo', 'license_product_model' => 'Web Analytics Integration', 'license_product_version' => '1.0.0',
        ],
        'ab80e3241f74ffa8f0d554f6ddf2b47a' => [
            'type' => 'plugin', 'slug' => 'ai-translation-assistant', 'name' => 'AI Translation Assistant', 'version' => '1.0.0-beta.2', 'release_channel' => 'beta', 'size' => 15640,
            'file' => $packageRoot . '/ai-translation-assistant-1.0.0-beta.2.zip', 'checksum' => '882bc1b884a956f510e7fd14a8a3befca1c46334cb0c78521fb6782294f98b25',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => true, 'system_installable' => true, 'icon' => 'languages', 'copy_key' => 'marketplace.ai_translation_copy',
            'license_product_name' => 'AI Translation Assistant', 'license_product_model' => 'AI-Assisted Multilingual Content', 'license_product_version' => '1.0.0',
        ],
        'e0250d15236f4c57ad2f32ff16e129e3' => [
            'type' => 'application', 'slug' => 'eduvixo-windows', 'name' => 'Desktop Client for Windows', 'version' => '0.2.5',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'devices',
            'copy_key' => 'marketplace.windows_copy', 'meta_keys' => ['marketplace.portable', 'marketplace.windows_compatibility'],
            'note_key' => 'marketplace.windows_notice',
            'variants' => [
                'x64' => [
                    'label_key' => 'marketplace.windows_x64', 'recommended' => true, 'size' => 157791936,
                    'file' => $packageRoot . '/eduvixo-windows-0.2.5-x64.exe', 'checksum' => '50f6691c9145452aad34297f69edbc4c4c37b5bd73c4fd37af399298a3976965',
                    'download_name' => 'desktop-client-windows-0.2.5-x64.exe', 'content_type' => 'application/vnd.microsoft.portable-executable',
                ],
                'x86' => [
                    'label_key' => 'marketplace.windows_x86', 'recommended' => false, 'size' => 148772544,
                    'file' => $packageRoot . '/eduvixo-windows-0.2.5-x86.exe', 'checksum' => '0d5b406e2d940fb57ce22dcc97041ef173965d043265a17bd3742f103e534f4b',
                    'download_name' => 'desktop-client-windows-0.2.5-x86.exe', 'content_type' => 'application/vnd.microsoft.portable-executable',
                ],
            ],
        ],
    ],
];
