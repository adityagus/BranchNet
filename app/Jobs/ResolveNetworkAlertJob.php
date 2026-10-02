<?php

namespace App\Jobs;

use App\Models\NetworkDevice;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveNetworkAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 30;
    public function __construct(public int $deviceId) {}
    public function handle(AlertService $alerts): void { if ($device = NetworkDevice::find($this->deviceId)) $alerts->resolveDevice($device); }
}
