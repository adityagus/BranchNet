<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('settings.index', ['settings' => ['Application' => config('app.name'), 'Database' => config('database.default'), 'Queue' => config('queue.default'), 'Cache' => config('cache.default'), 'Log retention' => env('MONITORING_LOG_RETENTION_DAYS', 30).' days', 'Pulse' => config('pulse.enabled') ? 'Enabled' : 'Disabled']]);
    }
}
