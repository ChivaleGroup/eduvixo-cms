<?php

declare(strict_types=1);

$payload = json_decode((string) file_get_contents('php://input'), true);
$text = (string) ($payload['messages'][1]['content'] ?? '');
$content = match ($text) {
    'School' => 'Schule',
    'Hello {{name}}' => 'Hallo {{name}}',
    '  Hello {{name}}  ' => 'Hallo {{name}}',
    '<p>Hello <strong>school</strong></p>' => '<p>Hallo <strong>Schule</strong></p>',
    'Break {{name}}' => 'Defekt {{person}}',
    'Contact info@example.com at https://example.com/order/42 for ${person}, amount 120.50' => 'Kontakt info@example.com unter https://example.com/order/42 für ${person}, Betrag 120.50',
    'Change https://example.com/order/42' => 'Ändere https://example.com/order/43',
    default => 'Übersetzung',
};
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => ['role' => 'assistant', 'content' => $content]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
