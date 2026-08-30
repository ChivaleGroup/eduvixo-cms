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
            'type' => 'system', 'slug' => 'eduvixo-cms', 'name' => 'Eduvixo CMS', 'version' => '1.0.0', 'size' => 10334744,
            'file' => $packageRoot . '/eduvixo-install-1.0.0.zip', 'checksum' => '48ae0869807ae5556edf4b45b55c5683560e6025ed01b3bec936c451c80b332d',
            'browser_enabled' => false, 'license_download_enabled' => true, 'update_enabled' => false, 'icon' => 'layers', 'copy_key' => 'marketplace.system_copy',
        ],
        '56b33a4022d3ae4e11150c080f3e6189' => [
            'type' => 'theme', 'slug' => 'eduvixo', 'name' => 'Eduvixo', 'version' => '1.1.6', 'size' => 1565831,
            'file' => $packageRoot . '/eduvixo-theme-1.1.6.zip', 'checksum' => '67f85343d5c32650537e5c3576640312c639efd6ee05d6bc8a0469896f3d6769',
            'browser_enabled' => true, 'license_download_enabled' => false, 'update_enabled' => true, 'icon' => 'layout', 'copy_key' => 'marketplace.eduvixo_copy',
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
        'e0250d15236f4c57ad2f32ff16e129e3' => [
            'type' => 'application', 'slug' => 'eduvixo-windows', 'name' => 'Eduvixo for Windows', 'version' => '0.2.3',
            'browser_enabled' => true, 'license_download_enabled' => false, 'update_enabled' => false, 'icon' => 'devices',
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
