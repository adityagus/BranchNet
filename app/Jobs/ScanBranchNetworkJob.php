<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Services\NetworkScannerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanBranchNetworkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 120;
    public int $tries = 2;
    public bool $deleteWhenMissingModels = true;
    public int $branchId;

    public function __construct(int $branchId)
    {
        $this->branchId = $branchId;
    }

    public function middleware(): array { return [new WithoutOverlapping('branch-scan-'.$this->branchId)]; }
    public function handle(NetworkScannerService $scanner): void
    {
        // Resolve by ID at execution time. A deleted branch should be skipped,
        // not turn into a failed job while Laravel unserializes the payload.
        $branch = Branch::find($this->branchId);
        if (! $branch) return;
        foreach ($scanner->hostsFor($branch) as $ip) {
            CheckDeviceStatusJob::dispatch($branch->id, $ip)->onQueue('network-monitoring');
        }
    }
}
