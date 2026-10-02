<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\NetworkDevice;
use Illuminate\Support\Carbon;

class AlertService
{
    public function openForDevice(NetworkDevice $device, string $type, string $severity, string $title, string $message): Alert
    {
        return Alert::firstOrCreate(
            ['branch_id' => $device->branch_id, 'device_id' => $device->id, 'type' => $type, 'status' => 'active'],
            compact('severity', 'title', 'message') + ['started_at' => now()]
        );
    }

    public function resolveDevice(NetworkDevice $device): int
    {
        return Alert::where('device_id', $device->id)->where('status', 'active')->update(['status' => 'resolved', 'resolved_at' => Carbon::now()]);
    }
}
