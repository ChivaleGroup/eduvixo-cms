<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$css = (string) file_get_contents($root . '/resources/site.css') . "\n" . (string) file_get_contents($root . '/resources/pages.css');
$css = preg_replace('~/\*.*?\*/~s', '', $css) ?? $css;
$css = preg_replace('/\s+/', ' ', $css) ?? $css;
$css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css) ?? $css;
$css = str_replace([';}', ' 0.', ':0px', ':0em', ':0rem'], ['}', ' .', ':0', ':0', ':0'], trim($css));
$js = trim((string) file_get_contents($root . '/resources/site.js'));
if (file_put_contents($root . '/public/assets/css/site.min.css', $css, LOCK_EX) === false) throw new RuntimeException('CSS build failed.');
if (file_put_contents($root . '/public/assets/js/site.min.js', $js, LOCK_EX) === false) throw new RuntimeException('JavaScript build failed.');
echo 'Assets built: ' . strlen($css) . ' CSS bytes, ' . strlen($js) . " JavaScript bytes.\n";
