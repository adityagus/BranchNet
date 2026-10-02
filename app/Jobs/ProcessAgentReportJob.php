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

class ProcessAgentReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 60;
    public int $tries = 3;
    public function __construct(public int $branchId, public array $report) {}
    public function handle(AlertService $alerts, NetworkScannerService $scanner): void
    {
        $branch = Branch::find($this->branchId);
        if (! $branch) {
            Log::warning('network.agent_report_skipped_missing_branch', ['branch_id' => $this->branchId]);
            return;
        }
        foreach ($this->report['devices'] ?? [] as $item) {
            if (! $scanner->ipBelongsToBranch($branch, $item['ip_address'])) continue;
            $online = filter_var($item['online'] ?? null, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($online === null) {
                CheckDeviceStatusJob::dispatch($branch->id, $item['ip_address'])->onQueue('network-monitoring');
                continue;
            }

            $device = NetworkDevice::firstOrCreate(['branch_id' => $branch->id, 'ip_address' => $item['ip_address']], ['device_name' => 'Unknown Device', 'device_type' => 'unknown', 'is_registered' => false]);
            $status = $item['status'] ?? ($online ? 'online' : 'offline');
            $device->update(['mac_address' => $item['mac_address'] ?? $device->mac_address, 'hostname' => $item['hostname'] ?? $device->hostname, 'vendor' => $item['vendor'] ?? $device->vendor, 'status' => in_array($status, ['online', 'offline', 'warning', 'unknown'], true) ? $status : ($online ? 'online' : 'offline'), 'latency_ms' => $item['latency_ms'] ?? ($online ? null : null), 'packet_loss' => $item['packet_loss'] ?? ($online ? 0 : 100), 'last_seen' => $online ? now() : $device->last_seen]);
            $device->logs()->create(['branch_id' => $branch->id, 'ip_address' => $item['ip_address'], 'status' => $device->status, 'latency_ms' => $device->latency_ms, 'packet_loss' => $device->packet_loss, 'checked_at' => now()]);
            if ($online) $alerts->resolveDevice($device);
            elseif ($device->severity === 'critical' || $device->ip_address === $branch->gateway_ip) $alerts->openForDevice($device, 'device_offline', $device->severity === 'critical' ? 'critical' : 'high', $device->device_name.' Offline', "Device {$device->ip_address} tidak merespons dari agent cabang.");
        }
    }
}
