<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

$sourceFilter = '/root/eduvixo-deploy/eduvixo-mariadb-auth.conf';
$sourceJail = '/root/eduvixo-deploy/eduvixo-mariadb-auth-jail.conf';
$filter = '/etc/fail2ban/filter.d/eduvixo-mariadb-auth.conf';
$jail = '/etc/fail2ban/jail.d/eduvixo-mariadb-auth.conf';
$backup = '/root/eduvixo-backups/security-infrastructure-pre-' . gmdate('Ymd-His');
$targets = [$filter, $jail];
$sources = [$sourceFilter, $sourceJail];

$run = static function (array $command): string {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start infrastructure command.');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException(trim($error . "\n" . $output));
    return trim($output);
};
$publish = static function (string $source, string $target): void {
    $temporary = $target . '.eduvixo-new-' . bin2hex(random_bytes(4));
    if (!copy($source, $temporary) || !chmod($temporary, 0644) || !chown($temporary, 'root') || !chgrp($temporary, 'root') || !rename($temporary, $target)) { @unlink($temporary); throw new RuntimeException('Cannot publish Fail2ban configuration: ' . $target); }
};

foreach ($sources as $source) if (!is_file($source)) throw new RuntimeException('Missing infrastructure release file: ' . $source);
if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create infrastructure backup.');
$previous = [];
foreach ($targets as $index => $target) {
    $previous[$target] = is_file($target);
    if ($previous[$target] && !copy($target, $backup . '/' . ($index === 0 ? 'filter.previous' : 'jail.previous'))) throw new RuntimeException('Cannot back up ' . $target);
}
file_put_contents($backup . '/state.json', json_encode($previous, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX);
file_put_contents($backup . '/ROLLBACK.txt', "Restore files marked true in state.json from their .previous copies, remove files marked false, run fail2ban-client -t and fail2ban-client reload, then confirm the previous jail list. This change does not modify UFW rules, MariaDB configuration, database users or data.\n", LOCK_EX);
foreach (glob($backup . '/*') ?: [] as $file) { chmod($file, 0600); echo 'BACKUP ' . basename($file) . ' ' . filesize($file) . ' ' . hash_file('sha256', $file) . PHP_EOL; }

try {
    foreach ($targets as $index => $target) $publish($sources[$index], $target);
    $test = $run(['fail2ban-client', '-t']);
    $run(['fail2ban-client', 'reload']);
    $status = $run(['fail2ban-client', 'status', 'eduvixo-mariadb-auth']);
    if (!str_contains($status, 'eduvixo-mariadb-auth') || (!str_contains($status, 'Journal matches') && !str_contains($status, '/var/log/journal'))) throw new RuntimeException('MariaDB jail did not become active.');
} catch (Throwable $error) {
    foreach ($targets as $index => $target) {
        $saved = $backup . '/' . ($index === 0 ? 'filter.previous' : 'jail.previous');
        if ($previous[$target] && is_file($saved)) $publish($saved, $target);
        elseif (!$previous[$target]) @unlink($target);
    }
    try { $run(['fail2ban-client', 'reload']); } catch (Throwable) {}
    throw $error;
}

echo $test . PHP_EOL . $status . PHP_EOL;
echo json_encode(['ok' => true, 'backup' => $backup, 'jail' => 'eduvixo-mariadb-auth', 'maxretry' => 3, 'findtime' => 600, 'bantime' => 3600, 'firewall_rules_changed' => false, 'database_changed' => false], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
