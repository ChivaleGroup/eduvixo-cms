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
            'type' => 'system', 'slug' => 'eduvixo-cms', 'name' => 'Eduvixo CMS', 'version' => '1.0.0', 'size' => 9392262,
            'file' => $packageRoot . '/eduvixo-install-1.0.0.zip', 'checksum' => '4cff5f05292346fd4def417a17c224487b053a84d4609f7befdf2dacd658d392',
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
        'e0250d15236f4c57ad2f32ff16e129e3' => [
            'type' => 'application', 'slug' => 'eduvixo-windows', 'name' => 'Eduvixo for Windows', 'version' => '0.2.1',
            'browser_enabled' => true, 'license_download_enabled' => false, 'update_enabled' => false, 'icon' => 'devices',
            'copy_key' => 'marketplace.windows_copy', 'meta_keys' => ['marketplace.portable', 'marketplace.windows_compatibility'],
            'note_key' => 'marketplace.windows_notice',
            'variants' => [
                'x64' => [
                    'label_key' => 'marketplace.windows_x64', 'recommended' => true, 'size' => 157796032,
                    'file' => $packageRoot . '/eduvixo-windows-0.2.1-x64.exe', 'checksum' => 'd3a0664af8294c82d690c49d60409f10dbd06b6f4dcbc6dc104bcb85cab448bf',
                    'download_name' => 'eduvixo-windows-0.2.1-x64.exe', 'content_type' => 'application/vnd.microsoft.portable-executable',
                ],
                'x86' => [
                    'label_key' => 'marketplace.windows_x86', 'recommended' => false, 'size' => 148780736,
                    'file' => $packageRoot . '/eduvixo-windows-0.2.1-x86.exe', 'checksum' => '65009bf2395659e1c2addbdbe5fd569c452888d7aeb80a24491dbd60f7c45a4b',
                    'download_name' => 'eduvixo-windows-0.2.1-x86.exe', 'content_type' => 'application/vnd.microsoft.portable-executable',
                ],
            ],
        ],
    ],
];
