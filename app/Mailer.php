<?php

declare(strict_types=1);

namespace Eduvixo\Website;

final class Mailer
{
    public function __construct(private readonly array $config) {}

    public function send(string $to, string $subject, string $body, string $replyTo): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Invalid recipient.');
        $from = trim((string) ($this->config['from_address'] ?? ''));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Mail sender is not configured.');
        $headers = ['From: ' . $this->mailbox((string) ($this->config['from_name'] ?? 'Eduvixo Website'), $from), 'Reply-To: ' . $replyTo, 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
        $host = trim((string) ($this->config['host'] ?? ''));
        if ($host === '') {
            if (!mail($to, $this->encode($subject), $body, implode("\r\n", $headers))) throw new \RuntimeException('Message delivery failed.');
            return;
        }
        $this->smtp($host, (int) ($this->config['port'] ?? 465), $from, $to, $subject, $body, $headers);
    }

    private function smtp(string $host, int $port, string $from, string $to, string $subject, string $body, array $headers): void
    {
        $secure = $port === 465;
        $addresses = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? [$host] : (@gethostbynamel($host) ?: []);
        $context = stream_context_create(['ssl' => ['peer_name' => $host, 'SNI_enabled' => true, 'verify_peer' => true, 'verify_peer_name' => true]]);
        $stream = false;
        foreach (array_unique($addresses) as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) continue;
            $stream = @stream_socket_client(($secure ? 'ssl://' : 'tcp://') . $address . ':' . $port, $code, $message, 10, STREAM_CLIENT_CONNECT, $context);
            if (is_resource($stream)) break;
        }
        if (!is_resource($stream)) throw new \RuntimeException('SMTP service is unavailable.');
        stream_set_timeout($stream, 10);
        try {
            $this->expect($stream, [220]); $this->command($stream, 'EHLO ' . (gethostname() ?: 'localhost'), [250]);
            if (!$secure && $port !== 25) { $this->command($stream, 'STARTTLS', [220]); if (!stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('SMTP encryption failed.'); $this->command($stream, 'EHLO ' . (gethostname() ?: 'localhost'), [250]); }
            $user = (string) ($this->config['username'] ?? '');
            if ($user !== '') { $this->command($stream, 'AUTH LOGIN', [334]); $this->command($stream, base64_encode($user), [334]); $this->command($stream, base64_encode((string) ($this->config['password'] ?? '')), [235]); }
            $this->command($stream, 'MAIL FROM:<' . $from . '>', [250]); $this->command($stream, 'RCPT TO:<' . $to . '>', [250, 251]); $this->command($stream, 'DATA', [354]);
            $message = implode("\r\n", array_merge(['Date: ' . date(DATE_RFC2822), 'To: ' . $to, 'Subject: ' . $this->encode($subject)], $headers)) . "\r\n\r\n" . str_replace("\n.", "\n..", str_replace(["\r\n", "\r"], "\n", $body)) . "\r\n.";
            $this->command($stream, $message, [250]); $this->command($stream, 'QUIT', [221]);
        } finally { fclose($stream); }
    }

    private function command($stream, string $command, array $codes): void { fwrite($stream, $command . "\r\n"); $this->expect($stream, $codes); }
    private function expect($stream, array $codes): void { $response = ''; do { $line = fgets($stream, 1024); if ($line === false) break; $response .= $line; } while (isset($line[3]) && $line[3] === '-'); if (!in_array((int) substr($response, 0, 3), $codes, true)) throw new \RuntimeException('SMTP rejected the request.'); }
    private function mailbox(string $name, string $email): string { return $this->encode(str_replace(["\r", "\n"], '', $name)) . ' <' . $email . '>'; }
    private function encode(string $value): string { return '=?UTF-8?B?' . base64_encode(str_replace(["\r", "\n"], '', $value)) . '?='; }
}
