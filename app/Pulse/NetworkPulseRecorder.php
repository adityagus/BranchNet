<?php

namespace App\Pulse;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Laravel\Pulse\Pulse;

/** Feeds network-specific scan counters into Pulse's custom metric stream. */
class NetworkPulseRecorder
{
    public array $listen = [JobProcessing::class, JobProcessed::class, JobFailed::class];
    private array $started = [];

    public function __construct(private Pulse $pulse) {}

    public function record(JobProcessing|JobProcessed|JobFailed $event): void
    {
        $name = $event->job->resolveName();
        if (! str_contains($name, 'ScanBranchNetworkJob') && ! str_contains($name, 'CheckDeviceStatusJob') && ! str_contains($name, 'ProcessAgentReportJob')) return;
        $key = $event->job->getJobId();
        if ($event instanceof JobProcessing) { $this->started[$key] = microtime(true); return; }
        $duration = isset($this->started[$key]) ? round((microtime(true) - $this->started[$key]) * 1000, 2) : 0;
        unset($this->started[$key]);
        $failed = $event instanceof JobFailed;
        $this->pulse->record(type: 'network_scan', key: $failed ? 'failed_scans' : 'successful_scans', value: 1)->count();
        $this->pulse->record(type: 'network_scan', key: 'scan_duration_ms', value: $duration)->avg()->max();
    }
}
