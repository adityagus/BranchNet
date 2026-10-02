<?php

declare(strict_types=1);

/**
 * BranchNet local monitoring agent.
 *
 * Sends a heartbeat and a ping report to the central Laravel application.
 * Works on Windows, Linux, and WSL with PHP CLI and the system ping command.
 */

const DEFAULT_INTERVAL_SECONDS = 60;

$configPath = $argv[1] ?? __DIR__ . DIRECTORY_SEPARATOR . 'config.json';

if (! is_file($configPath)) {
    fwrite(STDERR, "Config not found: {$configPath}\n");
    fwrite(STDERR, "Copy config.example.json to config.json and fill in the values.\n");
    exit(1);
}

$config = json_decode((string) file_get_contents($configPath), true);
if (! is_array($config)) {
    fwrite(STDERR, "Invalid JSON config: {$configPath}\n");
    exit(1);
}

foreach (['central_url', 'token', 'agent_id', 'branch_id'] as $required) {
    if (empty($config[$required]) || $config[$required] === 'PASTE_AGENT_TOKEN_HERE') {
        fwrite(STDERR, "Missing config value: {$required}\n");
        exit(1);
    }
}

$centralUrl = rtrim((string) $config['central_url'], '/');
$interval = max(15, (int) ($config['interval_seconds'] ?? DEFAULT_INTERVAL_SECONDS));
$devices = is_array($config['devices'] ?? null) ? $config['devices'] : [];

echo "BranchNet agent started for branch {$config['branch_id']}\n";
echo "Central API: {$centralUrl}\n";
echo "Interval: {$interval} seconds\n";

do {
    $startedAt = microtime(true);

    try {
        postJson($centralUrl . '/api/agent/heartbeat', $config, [
            'agent_id' => (int) $config['agent_id'],
            'branch_id' => (int) $config['branch_id'],
            'hostname' => $config['hostname'] ?? gethostname(),
            'ip_address' => $config['ip_address'] ?? null,
            'version' => $config['version'] ?? '1.0.0',
            'timestamp' => date(DATE_ATOM),
        ]);

        $reportDevices = [];
        foreach ($devices as $device) {
            $ip = trim((string) ($device['ip_address'] ?? ''));
            if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
                fwrite(STDERR, "Skipping invalid device IP\n");
                continue;
            }

            $result = ping($ip);
            $reportDevices[] = [
                'ip_address' => $ip,
                'online' => $result['online'],
                'latency_ms' => $result['latency_ms'],
                'packet_loss' => $result['online'] ? 0 : 100,
                'hostname' => $device['hostname'] ?? null,
                'vendor' => $device['vendor'] ?? null,
            ];

            printf(
                "[%s] %s %s%s\n",
                date('Y-m-d H:i:s'),
                $ip,
                $result['online'] ? 'ONLINE' : 'OFFLINE',
                $result['latency_ms'] !== null ? " {$result['latency_ms']} ms" : ''
            );
        }

        postJson($centralUrl . '/api/agent/report', $config, [
            'branch_id' => (int) $config['branch_id'],
            'devices' => $reportDevices,
        ]);

        echo '[' . date('Y-m-d H:i:s') . "] heartbeat/report accepted\n";
    } catch (Throwable $exception) {
        fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $exception->getMessage() . "\n");
    }

    $elapsed = microtime(true) - $startedAt;
    $sleepFor = max(1, $interval - (int) $elapsed);
    sleep($sleepFor);
} while (true);

function postJson(string $url, array $config, array $payload): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['token'],
                'Content-Length: ' . strlen(json_encode($payload, JSON_THROW_ON_ERROR)),
            ]),
            'content' => json_encode($payload, JSON_THROW_ON_ERROR),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }

    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException("API request failed ({$status}): {$url} {$body}");
    }

    return json_decode($body, true) ?: [];
}

function ping(string $ip): array
{
    $isWindows = PHP_OS_FAMILY === 'Windows';
    $command = $isWindows
        ? 'ping -n 1 -w 1000 ' . escapeshellarg($ip)
        : 'ping -c 1 -W 1 ' . escapeshellarg($ip);

    $output = [];
    $exitCode = 1;
    exec($command . ' 2>&1', $output, $exitCode);
    $text = implode("\n", $output);
    $latency = null;

    if (preg_match('/[=<]\s*(\d+(?:[\.,]\d+)?)\s*ms/i', $text, $matches)) {
        $latency = (float) str_replace(',', '.', $matches[1]);
    }

    return [
        'online' => $exitCode === 0,
        'latency_ms' => $latency,
    ];
}
