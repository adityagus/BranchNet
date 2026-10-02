<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Branch;
use App\Models\NetworkDevice;
use Illuminate\Http\Request;

class NetworkApiController extends Controller
{
    public function branches() { return Branch::withCount('devices')->paginate(50); }
    public function branch(Branch $branch) { return $branch->loadCount('devices')->load(['agents:id,branch_id,name,status,last_seen']); }
    public function devices(Request $request, Branch $branch) { return $branch->devices()->paginate($request->integer('per_page', 50)); }
    public function device(NetworkDevice $device) { return $device->load('branch'); }
    public function register(Request $request, NetworkDevice $device)
    {
        $data = $request->validate(['device_name' => 'required|string|max:255', 'device_type' => 'required|in:router,switch,access_point,server,cctv,printer,pc,laptop,pos,unknown', 'vendor' => 'nullable|string|max:255', 'description' => 'nullable|string']);
        $device->update($data + ['is_registered' => true]);
        return response()->json($device->fresh());
    }
    public function alerts(Request $request) { return Alert::with(['branch:id,name', 'device:id,device_name,ip_address'])->where('status', $request->get('status', 'active'))->latest()->paginate(50); }
}
