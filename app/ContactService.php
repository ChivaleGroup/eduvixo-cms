<?php

declare(strict_types=1);

namespace Eduvixo\Website;

final class ContactService
{
    public function __construct(private readonly array $config, private readonly Mailer $mailer) {}

    public function submit(array $input, bool $csrfValid, string $locale, callable $t): array
    {
        $values = [
            'name' => $this->text($input['name'] ?? '', 120), 'email' => strtolower($this->text($input['email'] ?? '', 190)),
            'organization' => $this->text($input['organization'] ?? '', 180), 'role' => $this->text($input['role'] ?? '', 120),
            'topic' => $this->text($input['topic'] ?? '', 60), 'message' => $this->text($input['message'] ?? '', 5000),
        ];
        $errors = [];
        if (!$csrfValid) $errors['form'] = $t('contact.errors.session');
        if (trim((string) ($input['website'] ?? '')) !== '') return ['success' => true, 'values' => []];
        if ((int) ($input['started_at'] ?? 0) > time() - 2) $errors['form'] = $t('contact.errors.fast');
        if (mb_strlen($values['name']) < 2) $errors['name'] = $t('contact.errors.name');
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = $t('contact.errors.email');
        if (mb_strlen($values['organization']) < 2) $errors['organization'] = $t('contact.errors.organization');
        if (!in_array($values['topic'], ['demo', 'implementation', 'self-hosted', 'marketplace', 'support', 'partnership', 'other'], true)) $errors['topic'] = $t('contact.errors.topic');
        if (mb_strlen($values['message']) < 20) $errors['message'] = $t('contact.errors.message');
        if (empty($input['privacy'])) $errors['privacy'] = $t('contact.errors.privacy');
        if (!$this->rateAllowed()) $errors['form'] = $t('contact.errors.rate');
        if ($errors) return ['success' => false, 'errors' => $errors, 'values' => $values];
        $subject = '[Eduvixo.com] ' . ucfirst($values['topic']) . ' - ' . $values['organization'];
        $body = implode("\n", ['Eduvixo website enquiry', '', 'Name: ' . $values['name'], 'Email: ' . $values['email'], 'Organization: ' . $values['organization'], 'Role: ' . ($values['role'] ?: '-'), 'Topic: ' . $values['topic'], 'Language: ' . $locale, '', 'Message:', $values['message']]);
        try { $this->mailer->send((string) $this->config['contact_recipient'], $subject, $body, $values['email']); }
        catch (\Throwable) { return ['success' => false, 'errors' => ['form' => $t('contact.errors.delivery')], 'values' => $values]; }
        $this->recordRate();
        return ['success' => true, 'values' => []];
    }

    private function rateAllowed(): bool
    {
        $entries = $this->rateEntries(); $now = time();
        return count(array_filter($entries, static fn(int $time): bool => $time > $now - 3600)) < 5 && (!$entries || max($entries) < $now - 20);
    }
    private function recordRate(): void { $path = $this->ratePath(); $entries = array_filter($this->rateEntries(), static fn(int $time): bool => $time > time() - 3600); $entries[] = time(); @file_put_contents($path, json_encode(array_values($entries)), LOCK_EX); @chmod($path, 0640); }
    private function rateEntries(): array { $path = $this->ratePath(); $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : []; return array_values(array_filter(array_map('intval', is_array($data) ? $data : []))); }
    private function ratePath(): string { $dir = $this->config['root'] . '/storage/rate-limits'; if (!is_dir($dir)) @mkdir($dir, 0750, true); $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'); return $dir . '/' . hash_hmac('sha256', $ip, (string) $this->config['rate_key']) . '.json'; }
    private function text(mixed $value, int $max): string { return mb_substr(trim(str_replace("\0", '', (string) $value)), 0, $max); }
}
