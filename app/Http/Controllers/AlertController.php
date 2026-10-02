<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Branch;
use App\Models\NetworkDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\AuditService;

class AlertController extends Controller
{
    public function __construct(private AuditService $audit) {}
    public function index(): View { return view('alerts.index', ['alerts' => Alert::with(['branch', 'device'])->latest()->paginate(20)]); }

    public function create(): View { return view('alerts.form', ['alert' => new Alert(), 'branches' => Branch::with('devices')->orderBy('name')->get(), 'action' => route('alerts.store'), 'method' => 'POST']); }

    public function store(Request $request): RedirectResponse
    {
        $alert = Alert::create($this->validated($request) + ['started_at' => now(), 'status' => 'active']);
        $this->audit->record('alert.created', $alert, null, $alert->toArray());
        return redirect()->route('alerts.index')->with('success', "Alert {$alert->title} dibuat.");
    }

    public function edit(Alert $alert): View { return view('alerts.form', ['alert' => $alert, 'branches' => Branch::with('devices')->orderBy('name')->get(), 'action' => route('alerts.update', $alert), 'method' => 'PUT']); }

    public function update(Request $request, Alert $alert): RedirectResponse
    {
        $old = $alert->toArray(); $data = $this->validated($request);
        if (($data['status'] ?? 'active') === 'resolved') $data['resolved_at'] = $alert->resolved_at ?: now();
        else $data['resolved_at'] = null;
        $alert->update($data);
        $this->audit->record('alert.updated', $alert, $old, $alert->fresh()->toArray());
        return redirect()->route('alerts.index')->with('success', 'Alert berhasil diperbarui.');
    }

    public function destroy(Alert $alert): RedirectResponse { $this->audit->record('alert.deleted', $alert, $alert->toArray(), null); $alert->delete(); return back()->with('success', 'Alert berhasil dihapus.'); }

    private function validated(Request $request): array
    {
        return $request->validate(['branch_id' => ['required', 'exists:branches,id'], 'device_id' => ['nullable', 'exists:network_devices,id'], 'severity' => ['required', 'in:low,medium,high,critical'], 'type' => ['required', 'string', 'max:80'], 'title' => ['required', 'string', 'max:160'], 'message' => ['required', 'string'], 'status' => ['required', 'in:active,resolved']]);
    }
}
