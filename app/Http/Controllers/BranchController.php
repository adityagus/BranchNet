<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Services\AuditService;
use Illuminate\Support\Facades\Artisan;

class BranchController extends Controller
{
    public function __construct(private AuditService $audit) {}
    public function index(Request $request): View
    {
        $branches = Branch::withCount('devices')
            ->withCount(['alerts as active_alerts' => fn ($q) => $q->where('status', 'active')])
            ->when($request->search, fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('branches.index', compact('branches'));
    }

    public function syncFromSopiga(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('sopiga:import-branches');
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                return back()->withErrors($output ?: 'Sinkronisasi Sopiga Pro gagal.');
            }

            return back()->with('success', $output ?: 'Branch berhasil disinkronkan dari Sopiga Pro.');
        } catch (\Throwable $exception) {
            return back()->withErrors('Sinkronisasi Sopiga Pro gagal: '.$exception->getMessage());
        }
    }

    public function create(): View { return view('branches.form', ['branch' => new Branch(), 'action' => route('branches.store'), 'method' => 'POST']); }

    public function store(Request $request): RedirectResponse
    {
        $branch = Branch::create($this->validated($request));
        $this->audit->record('branch.created', $branch, null, $branch->toArray());
        return redirect()->route('branches.index')->with('success', "Cabang {$branch->name} berhasil dibuat.");
    }

    public function edit(Branch $branch): View { return view('branches.form', ['branch' => $branch, 'action' => route('branches.update', $branch), 'method' => 'PUT']); }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $old = $branch->toArray(); $branch->update($this->validated($request, $branch));
        $this->audit->record('branch.updated', $branch, $old, $branch->fresh()->toArray());
        return redirect()->route('branches.index')->with('success', "Cabang {$branch->name} berhasil diperbarui.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $name = $branch->name;
        $this->audit->record('branch.deleted', $branch, $branch->toArray(), null);
        $branch->delete();
        return redirect()->route('branches.index')->with('success', "Cabang {$name} berhasil dihapus.");
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'network_cidr' => ['nullable', 'regex:/^(?:\d{1,3}\.){3}\d{1,3}\/(?:1[6-9]|2[0-9]|3[0-0])$/'],
            'gateway_ip' => ['nullable', 'ip'],
            'status' => ['required', 'in:healthy,warning,critical,unreachable'],
        ]);
    }
}
