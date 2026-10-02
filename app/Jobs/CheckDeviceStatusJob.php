<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\NetworkDevice;
use App\Services\AlertService;
use App\Services\NetworkScannerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDeviceStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 20;
    public int $tries = 2;
    // Keep these as explicitly initialized properties so queued payloads created
    // before a code reload still have a value for $force when unserialized.
    public int $branchId;
    public string $ip;
    public bool $force = false;

    public function __construct(int $branchId, string $ip, bool $force = false)
    {
        $this->branchId = $branchId;
        $this->ip = $ip;
        $this->force = $force;
    }
    public function handle(NetworkScannerService $scanner, AlertService $alerts): void
    {
        $branch = Branch::find($this->branchId);
        if (! $branch) {
            Log::warning('network.device_check_skipped_missing_branch', ['branch_id' => $this->branchId, 'ip_address' => $this->ip]);
            return;
        }
        $device = NetworkDevice::firstOrCreate(['branch_id' => $branch->id, 'ip_address' => $this->ip], ['device_name' => 'Unknown Device', 'device_type' => 'unknown', 'is_registered' => false]);
        $result = $scanner->check($this->ip, $device->device_type);
        Log::info('network.device_checked', ['branch_id' => $branch->id, 'device_id' => $device->id, 'ip_address' => $this->ip, 'device_type' => $device->device_type, 'online' => $result['online'], 'method' => $result['method'] ?? 'icmp', 'port' => $result['port'] ?? null, 'latency_ms' => $result['latency_ms'], 'packet_loss' => $result['packet_loss']]);
        $device->update(['status' => $result['online'] ? 'online' : 'offline', 'latency_ms' => $result['latency_ms'], 'packet_loss' => $result['packet_loss'], 'last_seen' => $result['online'] ? now() : $device->last_seen]);
        $device->logs()->create(['branch_id' => $branch->id, 'ip_address' => $this->ip, 'status' => $device->status, 'latency_ms' => $result['latency_ms'], 'packet_loss' => $result['packet_loss'], 'checked_at' => now()]);
        if ($result['online']) {
            $alerts->resolveDevice($device);
        } elseif ($device->severity === 'critical' || $device->ip_address === $branch->gateway_ip) {
            $alerts->openForDevice($device, 'device_offline', $device->severity === 'critical' ? 'critical' : 'high', $device->device_name.' Offline', "Device {$device->ip_address} tidak merespons ping.");
        }
    }
}
