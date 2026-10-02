<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class NetworkScannerService
{
    /** Return only hosts inside the administrator-configured branch CIDR. */
    public function hostsFor(Branch $branch): array
    {
        if (! $branch->network_cidr) {
            return [];
        }
        [$network, $prefix] = $this->parseCidr($branch->network_cidr);
        if ($prefix < 16 || $prefix > 30) {
            throw new InvalidArgumentException('Only IPv4 networks between /16 and /30 are supported.');
        }

        $start = ip2long($network) + 1;
        $end = ip2long($network) + (2 ** (32 - $prefix)) - 2;
        $hosts = [];
        for ($ip = $start; $ip <= $end; $ip++) {
            $hosts[] = long2ip($ip);
        }
        return $hosts;
    }

    public function ipBelongsToBranch(Branch $branch, string $ip): bool
    {
        if (! $branch->network_cidr) {
            return false;
        }
        [$network, $prefix] = $this->parseCidr($branch->network_cidr);
        $mask = -1 << (32 - $prefix);
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false && (ip2long($ip) & $mask) === ip2long($network);
    }

    public function ping(string $ip, int $timeoutSeconds = 1): array
    {
        $started = microtime(true);
        $command = PHP_OS_FAMILY === 'Windows'
            ? sprintf('ping -n 1 -w %d %s', $timeoutSeconds * 1000, escapeshellarg($ip))
            : sprintf('ping -c 1 -W %d %s', $timeoutSeconds, escapeshellarg($ip));
        exec($command, $output, $exitCode);
        $latency = round((microtime(true) - $started) * 1000, 2);

        return ['online' => $exitCode === 0, 'latency_ms' => $exitCode === 0 ? $latency : null, 'packet_loss' => $exitCode === 0 ? 0 : 100];
    }

    /** ICMP first, then a device-type TCP probe for devices that block ping. */
    public function check(string $ip, string $deviceType = 'unknown'): array
    {
        $icmp = $this->ping($ip);
        if ($icmp['online']) return $icmp + ['method' => 'icmp'];

        $ports = match ($deviceType) {
            'printer' => [9100, 631, 515],
            'cctv' => [80, 443, 554],
            'router', 'switch', 'access_point' => [80, 443, 22],
            'server', 'pc', 'laptop', 'pos' => [80, 443, 22, 3389],
            default => [],
        };

        foreach ($ports as $port) {
            $started = microtime(true);
            $socket = @fsockopen($ip, $port, $errorCode, $errorMessage, 0.75);
            $latency = round((microtime(true) - $started) * 1000, 2);
            if (is_resource($socket)) {
                fclose($socket);
                return ['online' => true, 'latency_ms' => $latency, 'packet_loss' => 0, 'method' => 'tcp', 'port' => $port];
            }
        }

        return $icmp + ['method' => 'icmp_tcp_failed', 'ports_checked' => $ports];
    }

    private function parseCidr(string $cidr): array
    {
        [$ip, $prefix] = array_pad(explode('/', trim($cidr), 2), 2, null);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !is_numeric($prefix)) {
            throw new InvalidArgumentException('Invalid branch network CIDR.');
        }
        $mask = -1 << (32 - (int) $prefix);
        return [long2ip(ip2long($ip) & $mask), (int) $prefix];
    }
}
