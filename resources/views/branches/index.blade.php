@extends('layouts.app', ['heading' => 'Branches'])
@section('content')
<div class="content">
    <div class="toolbar">
        <div><p class="muted">Daftar cabang otomatis disinkronkan dari Sopiga Pro.</p></div>
        <div class="toolbar-actions">
            <form class="filters" method="GET" action="{{ route('branches.index') }}">
                <input name="search" value="{{ request('search') }}" placeholder="Cari kode, nama, kota..."><button class="refresh" type="submit">Search</button>
            </form>
            <a class="refresh" href="{{ route('branches.create') }}">＋ Tambah manual</a>
        </div>
    </div>
    <div class="table-card"><table><thead><tr><th>Code</th><th>Branch</th><th>Network</th><th>Gateway</th><th>Status</th><th>Devices</th><th>Actions</th></tr></thead><tbody>
    @forelse($branches as $branch)
        <tr><td class="mono">{{ $branch->code }}</td><td><strong>{{ $branch->name }}</strong><small>{{ $branch->city }}</small></td><td class="mono">{{ $branch->network_cidr ?: 'Belum diisi' }}</td><td class="mono">{{ $branch->gateway_ip ?: 'Belum diisi' }}</td><td><span class="status-pill {{ $branch->status }}"><b></b>{{ strtoupper($branch->status) }}</span></td><td>{{ $branch->devices_count }} <small>{{ $branch->active_alerts }} active alerts</small></td><td class="actions"><a href="{{ route('branches.devices', $branch) }}">Devices</a><a href="{{ route('branches.edit', $branch) }}">Edit</a><form method="POST" action="{{ route('branches.destroy', $branch) }}" onsubmit="return confirm('Hapus cabang dan data terkait?')">@csrf @method('DELETE')<button>Delete</button></form></td></tr>
    @empty
        <tr><td colspan="7" class="empty">Belum ada cabang.</td></tr>
    @endforelse
    </tbody></table></div>
    <div class="pagination">{{ $branches->links() }}</div>
</div>
@endsection
