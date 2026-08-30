<?php

declare(strict_types=1);

$payload = json_decode((string) file_get_contents('php://input'), true);
$text = (string) ($payload['messages'][1]['content'] ?? '');
$content = match ($text) {
    'School' => 'Schule',
    'Hello {{name}}' => 'Hallo {{name}}',
    '<p>Hello <strong>school</strong></p>' => '<p>Hallo <strong>Schule</strong></p>',
    'Break {{name}}' => 'Defekt {{person}}',
    default => 'Übersetzung',
};
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => ['role' => 'assistant', 'content' => $content]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
