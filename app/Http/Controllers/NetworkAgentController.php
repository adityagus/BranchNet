<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\NetworkAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\AuditService;

class NetworkAgentController extends Controller
{
    public function __construct(private AuditService $audit) {}
    public function index(): View { return view('agents.index', ['agents' => NetworkAgent::with('branch')->latest()->paginate(20)]); }
    public function create(): View { return view('agents.form', ['agent' => new NetworkAgent(), 'branches' => Branch::orderBy('name')->get(), 'action' => route('agents.store'), 'method' => 'POST']); }

    public function store(Request $request): RedirectResponse
    {
        [$data, $plainToken] = $this->validated($request);
        $agent = NetworkAgent::create($data + ['token' => Hash::make($plainToken), 'status' => 'offline']);
        $this->audit->record('agent.created', $agent, null, ['id' => $agent->id, 'name' => $agent->name, 'branch_id' => $agent->branch_id]);
        return redirect()->route('agents.index')->with('success', "Agent {$agent->name} dibuat.")->with('agent_setup', ['agent_id' => $agent->id, 'branch_id' => $agent->branch_id, 'token' => $plainToken]);
    }

    public function edit(NetworkAgent $agent): View { return view('agents.form', ['agent' => $agent, 'branches' => Branch::orderBy('name')->get(), 'action' => route('agents.update', $agent), 'method' => 'PUT']); }

    public function update(Request $request, NetworkAgent $agent): RedirectResponse
    {
        $old = $agent->toArray(); [$data, $plainToken] = $this->validated($request, false);
        if ($plainToken) $data['token'] = Hash::make($plainToken);
        $agent->update($data);
        $this->audit->record('agent.updated', $agent, $old, $agent->fresh()->makeHidden('token')->toArray());
        $redirect = redirect()->route('agents.index')->with('success', "Agent {$agent->name} diperbarui.");
        return $plainToken
            ? $redirect->with('agent_setup', ['agent_id' => $agent->id, 'branch_id' => $agent->branch_id, 'token' => $plainToken])
            : $redirect;
    }

    public function config(Request $request, NetworkAgent $agent)
    {
        $setup = $request->session()->pull('agent_setup');
        abort_unless(is_array($setup) && (int) ($setup['agent_id'] ?? 0) === $agent->id, 403, 'Agent config is available only immediately after creation or token rotation.');

        $devices = $agent->branch->devices()->get(['ip_address', 'hostname', 'vendor'])->map(fn ($device) => [
            'ip_address' => $device->ip_address,
            'hostname' => $device->hostname,
            'vendor' => $device->vendor,
        ])->values();

        return Response::json([
            'central_url' => rtrim(config('app.url'), '/'),
            'token' => $setup['token'],
            'agent_id' => $agent->id,
            'branch_id' => $agent->branch_id,
            'hostname' => $agent->hostname,
            'ip_address' => $agent->ip_address,
            'version' => $agent->version ?: '1.0.0',
            'interval_seconds' => 60,
            'devices' => $devices,
        ], 200, ['Content-Disposition' => 'attachment; filename="branchnet-agent-'.$agent->id.'.json"']);
    }

    public function destroy(NetworkAgent $agent): RedirectResponse { $this->audit->record('agent.deleted', $agent, $agent->makeHidden('token')->toArray(), null); $agent->delete(); return back()->with('success', 'Agent berhasil dihapus.'); }

    private function validated(Request $request, bool $tokenRequired = true): array
    {
        $data = $request->validate(['branch_id' => ['required', 'exists:branches,id'], 'name' => ['required', 'string', 'max:120'], 'hostname' => ['nullable', 'string', 'max:255'], 'ip_address' => ['nullable', 'ip'], 'version' => ['nullable', 'string', 'max:50'], 'token' => [$tokenRequired ? 'nullable' : 'nullable', 'string', 'min:20', 'max:255']]);
        $plainToken = $data['token'] ?? null;
        unset($data['token']);
        if ($tokenRequired && ! $plainToken) $plainToken = Str::random(48);
        return [$data, $plainToken];
    }
}
