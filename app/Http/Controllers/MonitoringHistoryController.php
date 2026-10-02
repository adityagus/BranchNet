<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\MonitoringLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $logs = MonitoringLog::with(['branch:id,name,code', 'device:id,device_name'])
            ->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, fn ($q, $search) => $q->where('ip_address', 'like', "%{$search}%"))
            ->latest('checked_at')->paginate(40)->withQueryString();

        return view('monitoring-history.index', ['logs' => $logs, 'branches' => Branch::orderBy('name')->get(['id', 'name'])]);
    }
}
