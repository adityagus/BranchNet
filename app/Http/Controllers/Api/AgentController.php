<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAgentReportJob;
use App\Models\Branch;
use App\Models\NetworkAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate(['agent_id' => 'required|integer', 'branch_id' => 'required|integer', 'hostname' => 'nullable|string|max:255', 'ip_address' => 'nullable|ip', 'version' => 'nullable|string|max:50', 'timestamp' => 'nullable|date']);
        $agent = $this->agent($request, $data['branch_id'], $data['agent_id']);
        $agent->update(['hostname' => $data['hostname'] ?? $agent->hostname, 'ip_address' => $data['ip_address'] ?? $agent->ip_address, 'version' => $data['version'] ?? $agent->version, 'status' => 'active', 'last_seen' => now()]);
        $agent->branch->update(['agent_last_seen' => now(), 'status' => 'healthy']);
        return response()->json(['ok' => true, 'agent' => $agent->fresh()]);
    }

    public function report(Request $request): JsonResponse
    {
        $data = $request->validate(['branch_id' => 'required|integer', 'devices' => 'required|array', 'devices.*.ip_address' => 'required|ip', 'devices.*.online' => 'nullable|boolean', 'devices.*.status' => 'nullable|in:online,offline,warning,unknown', 'devices.*.latency_ms' => 'nullable|numeric|min:0', 'devices.*.packet_loss' => 'nullable|numeric|min:0|max:100', 'devices.*.mac_address' => 'nullable|string|max:50', 'devices.*.hostname' => 'nullable|string|max:255', 'devices.*.vendor' => 'nullable|string|max:120']);
        $agent = $this->agent($request, $data['branch_id']);
        $agent->update(['status' => 'active', 'last_seen' => now()]);
        $agent->branch->update(['agent_last_seen' => now(), 'status' => 'healthy']);
        ProcessAgentReportJob::dispatch($agent->branch_id, $data)->onQueue('network-monitoring');
        return response()->json(['accepted' => true], 202);
    }

    public function devices(Request $request): JsonResponse
    {
        $data = $request->validate(['agent_id' => 'required|integer', 'branch_id' => 'required|integer']);
        $agent = $this->agent($request, $data['branch_id'], $data['agent_id']);

        return response()->json(['devices' => $agent->branch->devices()->get(['ip_address', 'hostname', 'vendor'])]);
    }

    private function agent(Request $request, int $branchId, ?int $agentId = null): NetworkAgent
    {
        $token = $request->bearerToken();
        abort_unless($token, 401, 'Agent token is required.');
        $agent = NetworkAgent::where('branch_id', $branchId)->when($agentId, fn ($q) => $q->whereKey($agentId))->first();
        abort_unless($agent && Hash::check($token, $agent->token), 403, 'Invalid agent credentials.');
        return $agent;
    }
}
