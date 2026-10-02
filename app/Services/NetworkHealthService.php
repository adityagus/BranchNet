<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Cache;

class NetworkHealthService
{
    public function summary(?int $branchId = null): array
    {
        $key = 'network-summary:'.($branchId ?: 'all');
        return Cache::remember($key, 15, function () use ($branchId) {
            $query = \App\Models\NetworkDevice::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
            $stats = (clone $query)->selectRaw("count(*) total, sum(case when status = 'online' then 1 else 0 end) online, sum(case when status = 'offline' then 1 else 0 end) offline, sum(case when status = 'warning' then 1 else 0 end) warning, sum(case when device_type = 'unknown' then 1 else 0 end) unknown, avg(latency_ms) latency, avg(packet_loss) packet_loss")->first();
            return ['total' => (int) ($stats->total ?? 0), 'online' => (int) ($stats->online ?? 0), 'offline' => (int) ($stats->offline ?? 0), 'warning' => (int) ($stats->warning ?? 0), 'unknown' => (int) ($stats->unknown ?? 0), 'latency' => round((float) ($stats->latency ?? 0), 2), 'packet_loss' => round((float) ($stats->packet_loss ?? 0), 2), 'active_alerts' => \App\Models\Alert::where('status', 'active')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count()];
        });
    }

    public function branches()
    {
        return Branch::query()->withCount(['devices', 'alerts as active_alerts' => fn ($q) => $q->where('status', 'active')])->latest()->get();
    }
}
