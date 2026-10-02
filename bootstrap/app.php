<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\CheckDeviceStatusJob;
use App\Models\Branch;
use App\Models\NetworkDevice;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->call(fn() => NetworkDevice::whereIn('severity', ['critical', 'high'])->each(fn($device) => CheckDeviceStatusJob::dispatch($device->branch_id, $device->ip_address)->onQueue('network-critical')))->name('network-critical-checks')->everyMinute()->withoutOverlapping();
        $schedule->call(fn() => NetworkDevice::whereNotIn('severity', ['critical', 'high'])->each(fn($device) => CheckDeviceStatusJob::dispatch($device->branch_id, $device->ip_address)->onQueue('network-monitoring')))->name('network-normal-checks')->everyMinute()->withoutOverlapping();
        $schedule->call(fn() => Branch::where(function ($q) {
            $q->whereNull('agent_last_seen')->orWhere('agent_last_seen', '<', now()->subMinutes(3)); })->update(['status' => 'unreachable']))->name('network-agent-heartbeats')->everyMinute()->withoutOverlapping();
        $schedule->command('sopiga:import-branches')->name('sopiga-branch-sync')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('network:cleanup-logs')->daily()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
