<?php

namespace App\Http\Controllers;

use App\Models\NetworkDevice;
use App\Models\Branch;
use App\Services\NetworkScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\AuditService;
use App\Jobs\CheckDeviceStatusJob;

class DeviceController extends Controller
{
    public function __construct(private NetworkScannerService $scanner, private AuditService $audit) {}

    public function index(Request $request): View
    {
        $devices = NetworkDevice::with('branch:id,name,code')->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))->when($request->status, fn ($q, $status) => $q->where('status', $status))->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q->where('ip_address', 'like', "%{$search}%")->orWhere('device_name', 'like', "%{$search}%")->orWhere('hostname', 'like', "%{$search}%")))->latest('last_seen')->paginate(25)->withQueryString();
        return view('devices.index', ['devices' => $devices, 'branches' => Branch::orderBy('name')->get(['id', 'name'])]);
    }

    public function create(Branch $branch): View { return view('devices.form', ['device' => new NetworkDevice(['branch_id' => $branch->id]), 'branch' => $branch, 'action' => route('branches.devices.store', $branch), 'method' => 'POST']); }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $device = $branch->devices()->create($this->validated($request));
        $this->audit->record('device.created', $device, null, $device->toArray());
        return redirect()->route('devices.show', $device)->with('success', 'Device berhasil dibuat.');
    }

    public function edit(NetworkDevice $device): View { return view('devices.form', ['device' => $device, 'branch' => $device->branch, 'action' => route('devices.update', $device), 'method' => 'PUT']); }

    public function update(Request $request, NetworkDevice $device): RedirectResponse
    {
        $old = $device->toArray(); $device->update($this->validated($request, $device));
        $this->audit->record('device.updated', $device, $old, $device->fresh()->toArray());
        return redirect()->route('devices.show', $device)->with('success', 'Device berhasil diperbarui.');
    }

    public function destroy(NetworkDevice $device): RedirectResponse
    {
        $branch = $device->branch;
        $this->audit->record('device.deleted', $device, $device->toArray(), null);
        $device->delete();
        return redirect()->route('branches.devices', $branch)->with('success', 'Device berhasil dihapus.');
    }

    public function show(NetworkDevice $device)
    {
        $history = $device->logs()->where('checked_at', '>=', now()->subDay())->orderBy('checked_at')->get(['checked_at', 'latency_ms', 'packet_loss', 'status']);
        return view('devices.show', compact('device', 'history'));
    }

    public function history(NetworkDevice $device): JsonResponse { return response()->json($device->logs()->latest('checked_at')->paginate(100)); }

    public function check(NetworkDevice $device): RedirectResponse
    {
        CheckDeviceStatusJob::dispatch($device->branch_id, $device->ip_address, true)->onQueue($device->severity === 'critical' ? 'network-critical' : 'network-monitoring');
        return back()->with('success', "Ping {$device->ip_address} dimasukkan ke queue. Refresh beberapa detik lagi untuk melihat hasilnya.");
    }

    private function validated(Request $request, ?NetworkDevice $device = null): array
    {
        $data = $request->validate(['ip_address' => ['required', 'ip'], 'mac_address' => ['nullable', 'string', 'max:50'], 'hostname' => ['nullable', 'string', 'max:255'], 'device_name' => ['required', 'string', 'max:120'], 'device_type' => ['required', 'in:router,switch,access_point,server,cctv,printer,pc,laptop,pos,unknown'], 'vendor' => ['nullable', 'string', 'max:120'], 'description' => ['nullable', 'string'], 'severity' => ['required', 'in:low,medium,high,critical'], 'is_registered' => ['nullable', 'boolean'], 'status' => ['required', 'in:online,offline,warning,unknown'], 'latency_ms' => ['nullable', 'numeric', 'min:0'], 'packet_loss' => ['nullable', 'numeric', 'min:0', 'max:100']]);
        $branch = $device?->branch ?? Branch::findOrFail($request->route('branch')->id);
        if (! $this->scanner->ipBelongsToBranch($branch, $data['ip_address'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ip_address' => 'IP address harus berada di subnet cabang yang dipilih ('.$branch->network_cidr.').']);
        }
        return $data;
    }
}
