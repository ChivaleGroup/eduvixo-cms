<?php

declare(strict_types=1);

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . $path)) return false;

/** @var Eduvixo\Website\Site $site */
$site = require dirname(__DIR__) . '/app/bootstrap.php';
$site->dispatch();
