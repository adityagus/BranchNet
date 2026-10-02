<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchDeviceController extends Controller
{
    public function index(Request $request, Branch $branch)
    {
        $devices = $branch->devices()->when($request->search, function ($q, $search) { $q->where(fn ($q) => $q->where('ip_address', 'like', "%{$search}%")->orWhere('mac_address', 'like', "%{$search}%")->orWhere('hostname', 'like', "%{$search}%")->orWhere('device_name', 'like', "%{$search}%")); })->when($request->status, fn ($q, $v) => $q->where('status', $v))->when($request->device_type, fn ($q, $v) => $q->where('device_type', $v))->when($request->severity, fn ($q, $v) => $q->where('severity', $v))->when($request->registered !== null, fn ($q) => $q->where('is_registered', $request->boolean('registered')))->orderBy($request->get('sort', 'ip_address'), $request->get('direction', 'asc'))->paginate(20)->withQueryString();
        return view('branches.devices', compact('branch', 'devices'));
    }
}
