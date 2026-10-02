<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\Branch;
use App\Models\NetworkAgent;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->call(function (): void {
            $cutoff = now()->subMinutes((int) env('AGENT_HEARTBEAT_TIMEOUT_MINUTES', 3));
            NetworkAgent::where(function ($query) use ($cutoff) {
                $query->whereNull('last_seen')->orWhere('last_seen', '<', $cutoff);
            })->update(['status' => 'offline']);
        })->name('network-agent-stale-check')->everyMinute()->withoutOverlapping();

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
