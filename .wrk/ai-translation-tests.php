<?php

declare(strict_types=1);

require dirname(__DIR__) . '/.plugins/EduvixoAITranslationAssistant/source/src/TranslationService.php';

use Eduvixo\AITranslation\TranslationService;

$service = new TranslationService();
$failures = [];
$check = static function (bool $result, string $name) use (&$failures): void {
    echo ($result ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$result) $failures[] = $name;
};
$rejects = static function (callable $callback): bool {
    try { $callback(); return false; } catch (RuntimeException) { return true; }
};

$settings = $service->normalizeSettings(['provider' => 'ollama', 'endpoint' => 'http://127.0.0.1:18947', 'model' => 'qwen2.5:7b'], false);
$check($settings['provider'] === 'ollama' && $settings['endpoint'] === 'http://127.0.0.1:18947', 'loopback Ollama configuration');
$check(array_keys(TranslationService::languages()) === ['zh','en','de','lo','pl','th','vi'], 'supported languages are alphabetically ordered');
$check($rejects(fn() => $service->normalizeSettings(['provider' => 'ollama', 'endpoint' => 'http://169.254.169.254', 'model' => 'qwen2.5:7b'], false)), 'metadata endpoint rejected');
$check($rejects(fn() => $service->normalizeSettings(['provider' => 'openai-compatible', 'endpoint' => 'http://example.com/v1', 'model' => 'model', 'api_key' => 'key'], false)), 'non-HTTPS remote endpoint rejected');
$check($rejects(fn() => $service->normalizeSettings(['provider' => 'openai-compatible', 'endpoint' => 'https://127.0.0.1/v1', 'model' => 'model', 'api_key' => 'key'], false)), 'private remote endpoint rejected');
$check($service->translate($settings, 'Hello {{name}}', 'en', 'de', 'plain') === 'Hallo {{name}}', 'placeholder-preserving translation');
$check($service->translate($settings, '  Hello {{name}}  ', 'en', 'de', 'plain') === '  Hallo {{name}}  ', 'boundary whitespace preserved');
$check($service->translate($settings, '<p>Hello <strong>school</strong></p>', 'en', 'de', 'html') === '<p>Hallo <strong>Schule</strong></p>', 'HTML-preserving translation');
$check($service->translate($settings, 'Contact info@example.com at https://example.com/order/42 for ${person}, amount 120.50', 'en', 'de', 'plain') === 'Kontakt info@example.com unter https://example.com/order/42 für ${person}, Betrag 120.50', 'contact, link, variable and number preservation');
$check($rejects(fn() => $service->translate($settings, 'Break {{name}}', 'en', 'de', 'plain')), 'changed placeholder rejected');
$check($rejects(fn() => $service->translate($settings, 'Change https://example.com/order/42', 'en', 'de', 'plain')), 'changed URL and number rejected');
$service->test($settings); $check(true, 'provider connection test');

if ($failures) exit(1);
echo 'AI Translation Assistant service checks passed.' . PHP_EOL;
